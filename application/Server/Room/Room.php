<?php
/**
 * Room.php
 */

namespace Server\Room;

use Game\GameInterface;
use Server\Message\Message;
use Server\Server;
use User\User;

/**
 * Room
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-28 11:52
 * @author      ruud.seberechts
 */
class Room
{
    const MESSAGE_TYPE_NEW_USER  = 'roomNewUser';
    const MESSAGE_TYPE_USER_LEFT = 'roomUserLeft';
    const MESSAGE_TYPE_USER_LIST = 'roomUserList';

    const REQUEST_TYPE_USER_LIST = 'roomUserList';

    /** @var int */
    protected $id = 0;

    /** @var string */
    protected $name;

    /** @var  User[] */
    protected $users;

    /** @var Server */
    protected $server;

    /** @var GameInterface */
    protected $game;

    function __construct()
    {
        $this->users  = [];
        $this->server = Server::$instance;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param GameInterface $game
     */
    public function setGame($game)
    {
        $this->game = $game;
        $game->setRoom($this);
    }

    /**
     * @return GameInterface
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param \Server\Server $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return \User\User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function getClients()
    {
        $clients = [];
        foreach ($this->users as $user) {
            $clients[] = $user->getClient();
        }
        return $clients;
    }

    public function addUser(User $user)
    {
        $user->setRoom($this);
        $this->users[(string)$user->getClient()] = $user;
        $user->setGameUser($this->getGame()->createGameUser());
        $message = new Message(array(
            'type' => self::MESSAGE_TYPE_NEW_USER,
            'user' => $user->toArray(),
        ));
        $this->server->log("User '{$user->getUsername()}' connected to room '{$this->getName()}'");
        $this->server->send($message, $this->getClients());
        $this->sendGameInformation($user);
        $this->game->usersUpdate([$user], []);
    }

    public function removeUser(User $user)
    {
        foreach ($this->users as $u => $linkedUser) {
            if ($linkedUser->getId() == $user->getId()) {
                $message = new Message(array(
                    'type' => self::MESSAGE_TYPE_USER_LEFT,
                    'user' => $linkedUser->toArray(),
                ));
                unset($this->users[$u]);
                $this->server->log("User '{$linkedUser->getUsername()}' removed from room '{$this->getName()}'");
                $this->server->send($message, $this->getClients());
                $this->game->usersUpdate([], [$user]);
                break;
            }
        }
    }

    public function hasUser(User $user)
    {
        foreach ($this->users as $linkedUser) {
            if ($linkedUser->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getUserArray()
    {
        $users = [];
        foreach ($this->getUsers() as $user) {
            $users[] = $user->toArray();
        }
        return $users;
    }

    public function processRequest($request, User $user = null)
    {
        if ($this->getGame()->processRequest($request, $user)) {
            return true;
        }
        switch ($request->type) {
            case self::REQUEST_TYPE_USER_LIST:
                $message = new Message(array(
                    'type'  => self::MESSAGE_TYPE_USER_LIST,
                    'users' => $this->getUserArray(),
                ));
                $this->server->send($message, [$user->getClient()]);
                return true;
        }
        return false;
    }

    public function sendGameInformation(User $user)
    {
        $message = new Message(array(
            'type'  => self::MESSAGE_TYPE_USER_LIST,
            'users' => $this->getUserArray(),
            'game'  => $this->game->toArray(),
        ));
        $this->server->send($message, [$user->getClient()]);
    }
}