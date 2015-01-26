<?php
/**
 * Server.php
 */

namespace Server;

use Game\Picturnery\Picturnery;
use Server\Message\Message;
use Server\Message\MessageInterface;
use Server\Room\Room;
use Server\Timer\Timer;
use User\User;

/**
 * Server
 *
 * @copyright   2014 Qronicle.be
 * @since       2014-12-28 11:21
 * @author      ruud.seberechts
 */
class Server
{
    const LOG_FOR_WINDOWS = true;

    /** @var Server */
    public static $instance;

    protected $socket;
    protected $aSocket;

    /** @var Resource[] */
    protected $clients;
    /** @var User[] */
    protected $clientUsers;
    /** @var Room[] */
    protected $rooms;
    /** @var User[] */
    protected $users;

    protected $logFile;

    const SERVER_PORT = 1337;
    const SERVER_HOST = 'pictionary.dev';

    public function run()
    {
        $this->logFile = ROOT_DIR . '/log-'.date('Y-m-d-H-i-s').'.txt';
        //file_put_contents($this->logFile, '');
        @unlink(ROOT_DIR . "/php-error.log");
        $this->log('Server started');
        echo "
----------------------------------------------------------------
Running new server instance
----------------------------------------------------------------

";

        self::$instance = $this;
        $null           = null;

        // Initialize rooms
        $room = new Room();
        $room->setName('Test room');
        $room->setGame(new Picturnery());
        $this->rooms = array($room);

        // Initialize users
        global $users;
        $this->users = [];
        foreach ($users as $user) {
            $this->users[$user->getHash()] = $user;
        }
        $this->clientUsers = [];

        // Create TCP/IP stream socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // Reusable port
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        // Bind socket to specified host
        socket_bind($this->socket, 0, self::SERVER_PORT);
        // Listen to port
        socket_listen($this->socket);

        // Create & add listening socket to the list
        $this->clients = array();

        // Start server loop
        while (true) {
            // Check for new client sockets
            $this->checkForNewClients();
            // Check for client socket requests or disconnects
            $this->checkForClientRequestsOrDisconnects();
            // Timer!
            Timer::tick();
        }
        // Close the listening socket
        socket_close($this->socket);
    }

    protected function checkForClientRequestsOrDisconnects()
    {
        if (!$this->clients) return;
        // The changed clients will now only contain requests from client sockets
        $changedClients = $this->clients;
        socket_select($changedClients, $null, $null, 0, 10);

        // Loop through all changed client sockets
        foreach ($changedClients as $changedClient) {
            // Check for incoming requests
            $input = '';
            while (socket_recv($changedClient, $buf, 1024, MSG_DONTWAIT)) {
                if (is_null($buf) || !$buf) break;
                $input .= $buf;
            }
            if ($input) {
                if ($requestMessage = trim($this->unmask($input))) {
                    $this->log('Incoming message: ' . $requestMessage);
                    $request = json_decode($requestMessage);
                    if (is_object($request) && ($info = $this->getRequestInfo($request))) {
                        switch ($request->type) {
                            default:
                                $info->room->processRequest($request, $info->user);
                        }
                    } else {
                        file_put_contents(ROOT_DIR . '/' . microtime(true) . '.txt', $requestMessage);
                        $this->closeClientConnection($changedClient);
                    }
                }
                continue;
            }
            // Check for closed connection
            $buf = @socket_read($changedClient, 1024, PHP_NORMAL_READ);
            if ($buf === false) {
                $this->closeClientConnection($changedClient);
            }
        }
    }

    protected function getRequestInfo($request)
    {
        $info = false;
        if (isset($request->user) && isset($request->room)) {
            $userId = isset($request->user->id) ? $request->user->id : null;
            $userHash = isset($request->user->hash) ? $request->user->hash : null;
            $roomId = isset($request->room->id) ? $request->room->id : null;
            if (isset($this->users[$userHash])) {
                $user = $this->users[$userHash];
                if ($user->getId() == $userId && $user->getRoom() && $user->getRoom()->getId() == $roomId) {
                    $info = (object) array(
                        'user' => $user,
                        'room' => $user->getRoom(),
                    );
                }
            }
        }
        return $info;
    }

    protected function closeClientConnection($clientSocket)
    {
        $resourceName = (string)$clientSocket;
        if (isset($this->clientUsers[$resourceName])) {
            $this->clientUsers[$resourceName]->getRoom()->removeUser($this->clientUsers[$resourceName]);
            $this->log("Disconnecting user '" . $this->clientUsers[$resourceName]->getUsername() . "'");
        }
        $this->log('Disconnecting socket: ' . $resourceName);
        unset($this->clients[$resourceName], $this->clientUsers[$resourceName]);
        socket_close($clientSocket);
    }

    protected function checkForNewClients()
    {
        $null    = null;
        $aSocket = [$this->socket];
        socket_select($aSocket, $null, $null, 0, 10);
        if ($aSocket) {
            $this->log('New socket connection');
            // Create new client socket connection
            $newSocket = socket_accept($this->socket);
            // Check client header
            $header = socket_read($newSocket, 1024);
            $info   = $this->getAuthenticationInformationFromHeader($header);
            if ($info->success) {
                // Do that handshake magic
                $this->handshake($header, $newSocket, self::SERVER_HOST, self::SERVER_PORT);
                // Get user and room from info
                /** @var User $user */
                $user = $info->user;
                /** @var Room $room */
                $room = $info->room;
                // Prepare user and add to room
                $user->setClient($newSocket);
                socket_getpeername($newSocket, $ip);
                $user->setIp($ip);
                $this->log("User '{$user->getUsername()}' connected");
                $room->addUser($user);
                // Save the connection in the clients array
                $this->clients[(string)$newSocket] = $newSocket;
                $this->clientUsers[(string)$newSocket] = $user;
            } else {
                socket_close($newSocket);
            }
        }
    }

    protected function getAuthenticationInformationFromHeader($header)
    {
        $info    = array(
            'success' => false,
        );
        $request = explode('?', array_shift(explode("\r\n", $header)), 2);
        if (!count($request) == 2) {
            $this->log('New socket connection failed: ' . $request);
            return (object)$info;
        }
        $request  = array_shift(explode(' ', $request[1]));
        $get      = [];
        $getParts = explode('&', $request);
        foreach ($getParts as $part) {
            $parts = explode('=', $part);
            $key   = urldecode($parts[0]);
            $value = urldecode(isset($parts[1]) ? $parts[1] : '');
            if ($key) {
                $get[$key] = $value;
            }
        }
        if (isset($get['user']) && isset($get['hash']) && isset($get['room'])) {
            if (isset($this->users[$get['hash']])) {
                $user = $this->users[$get['hash']];
                if ($user->getId() == $get['user'] && isset($this->rooms[$get['room']])) {
                    $info = array(
                        'success' => true,
                        'user'    => $user,
                        'room'    => $this->rooms[$get['room']],
                    );
                }
            }
        }
        if ($info['success']) {
            $this->log('New socket connection success: ' . json_encode($get));
        } else {
            $this->log('New socket connection failed: ' . json_encode($get));
        }
        return (object)$info;
    }

    protected function handshake($receivedHeader, $clientConnection, $host, $port)
    {
        $headers = array();
        $lines   = preg_split("/\r\n/", $receivedHeader);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        $secKey    = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        //hand shaking header
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: ws://$host:$port/server.php\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        socket_write($clientConnection, $upgrade, strlen($upgrade));
    }

    public function send(MessageInterface $message, $clients)
    {
        $messageString = $this->mask(json_encode($message->toArray()));
        foreach ($clients as $client) {
            if ($client != $this->socket)
                @socket_write($client, $messageString, strlen($messageString));
        }
        return true;
    }

    /**
     * Unmask incoming framed message
     *
     * @param string $text
     *
     * @return string
     */
    protected function unmask($text)
    {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data  = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data  = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data  = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    /**
     * Encode message for transfer to client
     *
     * @param $text
     *
     * @return string
     */
    function mask($text)
    {
        $b1     = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        $header = null;
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $text;
    }

    public function log($message, $toScreen = true)
    {
        $log = date('Y-m-d H:i:s') . '  ' . ((string)$message) . "\n";
        if ($toScreen) echo $log;
        //exec('echo ' . escapeshellarg(self::LOG_FOR_WINDOWS ? str_replace(["\r\n", "\n", "\r"], "\r", $log) : $log) . ' >> ' . escapeshellarg($this->logFile));
        /*$f = fopen($this->logFile, 'a');
        fwrite($f, self::LOG_FOR_WINDOWS ? str_replace(["\r\n", "\n"], "\r", $log) : $log);
        fclose($f);*/
    }
}