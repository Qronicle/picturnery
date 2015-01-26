<?php
/**
 * Picturnery.php
 */

namespace Game\Picturnery;

use Game\GameInterface;
use Server\Message\Message;
use Server\Room\Room;
use Server\Server;
use Server\Timer\Timer;
use User\User;

/**
 * Picturnery
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-29 0:07
 * @author      ruud.seberechts
 */
class Picturnery implements GameInterface
{
    const MESSAGE_TYPE_GUESS          = 'picturneryGuess';
    const MESSAGE_TYPE_DRAWING_UPDATE = 'picturneryDrawingUpdate';
    const MESSAGE_TYPE_NEW_GAME       = 'picturneryNewGame';
    const MESSAGE_TYPE_END_GAME       = 'picturneryEndGame';
    const MESSAGE_TYPE_NEW_ROUND      = 'picturneryNewRound';
    const MESSAGE_TYPE_END_ROUND      = 'picturneryEndRound';

    const REQUEST_TYPE_GUESS          = 'picturneryGuess';
    const REQUEST_TYPE_DRAWING_UPDATE = 'picturneryDrawingUpdate';

    const STATUS_WAITING_FOR_PLAYERS = 'waitingForPlayers';
    const STATUS_GAME                = 'game';

    /** @var Room */
    protected $room;

    /** @var Server */
    protected $server;

    /** @var string */
    protected $status;

    /** @var User[] */
    protected $drawers;

    /** @var User */
    protected $currentDrawer;

    /** @var int */
    protected $roundNumber;

    /** @var string */
    protected $currentWord;

    /** @var int */
    protected $roundDuration = 30;

    /** @var User[] */
    protected $guessers;

    protected $nextActionTimeout = null;

    function __construct()
    {
        $this->status = self::STATUS_WAITING_FOR_PLAYERS;
    }

    /** @return PicturneryUser */
    public function createGameUser()
    {
        return new PicturneryUser();
    }

    public function setRoom(Room $room)
    {
        $this->room   = $room;
        $this->server = Server::$instance;
    }

    /**
     * @return \Server\Room\Room
     */
    public function getRoom()
    {
        return $this->room;
    }

    public function processRequest($request, User $user = null)
    {
        switch ($request->type) {
            case self::REQUEST_TYPE_GUESS:
                $this->guess($user, $request->guess);
                return true;
            case self::REQUEST_TYPE_DRAWING_UPDATE:
                $this->updateDrawing($user, $request->update);
                return true;
        }
        return false;
    }

    public function guess(User $user, $guess)
    {
        $guess = trim(strip_tags($guess));
        // Do not allow guesses when there is no current drawer or word
        if (!$guess || is_null($this->currentDrawer) || is_null($user)) return false;
        // Do not allow guesses from the drawer
        if ($user->getId() == $this->currentDrawer->getId()) return false;
        if (strpos($guess, $this->currentWord) !== false) {
            // Correct!
            if (!isset($this->guessers[$user->getId()])) {
                /** @var PicturneryUser $gameUser */
                $gameUser = $user->getGameUser();
                $gameUser->addPoints(10);
                $this->guessers[$user->getId()] = $user->toArray();
                /** @var PicturneryUser $drawGameUser */
                $drawGameUser = $this->currentDrawer->getGameUser();
                $drawGameUser->addPoints(count($this->guessers) == 1 ? 10 : 1);
                $message = new Message(array(
                    'type'  => self::MESSAGE_TYPE_GUESS,
                    'user'  => $user->toArray(),
                    'guess' => '<i>Guessed it!</i>',
                ));
                $this->server->send($message, $this->room->getClients());
            }

        } else {
            $message = new Message(array(
                'type'  => self::MESSAGE_TYPE_GUESS,
                'user'  => $user->toArray(),
                'guess' => $guess,
            ));
            $this->server->send($message, $this->room->getClients());
        }
    }

    public function updateDrawing(User $user, $update)
    {
        // Do not allow drawing when there is no current drawer or word
        if (is_null($this->currentDrawer) || is_null($user)) return false;
        // Do not allow drawing from a user other than the drawer
        if ($user->getId() != $this->currentDrawer->getId()) return false;
        $message = new Message(array(
            'type'   => self::MESSAGE_TYPE_DRAWING_UPDATE,
            'update' => $update,
        ));
        $clients = [];
        foreach ($this->room->getUsers() as $roomUser) {
            if ($user->getId() != $roomUser->getId()) {
                $clients[] = $roomUser->getClient();
            }
        }
        $this->server->send($message, $clients);
    }

    public function usersUpdate(array $addedUsers, array $removedUsers)
    {
        if (count($this->getRoom()->getUsers()) > 1 && $this->status == self::STATUS_WAITING_FOR_PLAYERS) {
            $this->setStatus(self::STATUS_GAME);
        }
        if (count($this->getRoom()->getUsers()) < 2 && $this->status == self::STATUS_GAME) {
            $this->setStatus(self::STATUS_WAITING_FOR_PLAYERS);
        }
    }

    /**
     * @param \Server\Server $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return \Server\Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        Timer::clearTimeout($this->nextActionTimeout);
        $this->status = $status;
        switch ($status) {
            case self::STATUS_WAITING_FOR_PLAYERS:
                foreach ($this->getRoom()->getUsers() as $user) {
                    $user->getGameUser()->setScore(0);
                }
                break;
            case self::STATUS_GAME:
                $this->newGame();
                break;
        }
    }

    public function newGame()
    {
        $this->status      = self::STATUS_GAME;
        $this->roundNumber = 0;
        $this->resetGameUsers();
        $this->initDrawers();
        $this->server->send(new Message(self::MESSAGE_TYPE_NEW_ROUND), $this->getRoom()->getClients());
        $this->newRound();
    }

    public function newRound()
    {
        $this->server->log('Start new round');
        $this->guessers = [];
        // Increase das number
        $this->roundNumber++;
        // Get the drawer for this round
        $this->currentDrawer = $this->getNextDrawer();
        // Add to drawers list again!
        $this->drawers[] = $this->currentDrawer;
        // Get the word for this round
        $this->currentWord = $this->getNextWord();
        // Send the message!
        $messageVars  = array(
            'role'          => 'guess',
            'roundDuration' => $this->roundDuration,
            'users'         => $this->getRoom()->getUsers(),
            'drawer'        => $this->currentDrawer->toArray(),
        );
        $guessMessage = new Message(self::MESSAGE_TYPE_NEW_ROUND, $messageVars);
        $guessClients = [];
        foreach ($this->getRoom()->getUsers() as $user) {
            if ($user->getId() != $this->currentDrawer->getId()) {
                $guessClients[] = $user->getClient();
            }
        }
        $messageVars['role'] = 'draw';
        $messageVars['word'] = $this->currentWord;
        $drawMessage         = new Message(self::MESSAGE_TYPE_NEW_ROUND, $messageVars);
        $this->server->send($guessMessage, $guessClients);
        $this->server->send($drawMessage, [$this->currentDrawer->getClient()]);
        // End the round
        $this->nextActionTimeout = Timer::setTimeout([$this, 'endRound'], $this->roundDuration);
        return;
    }

    public function endRound()
    {
        $this->server->log('End round');
        $messageVars         = array(
            'role'     => 'guess',
            'word'     => $this->currentWord,
            'users'    => $this->getRoom()->getUsers(),
            'drawer'   => $this->currentDrawer->toArray(),
            'guessers' => array_values($this->guessers),
        );
        $this->currentDrawer = null;
        $this->currentWord   = null;
        $this->server->send(new Message(self::MESSAGE_TYPE_END_ROUND, $messageVars), $this->getRoom()->getClients());
        $this->nextActionTimeout = Timer::setTimeout([$this, 'newRound'], 5);
    }

    protected function resetGameUsers()
    {
        foreach ($this->getRoom()->getUsers() as $user) {
            /** @var PicturneryUser $gameUser */
            $gameUser = $user->getGameUser();
            $gameUser->setScore(0);
        }
    }

    protected function initDrawers()
    {
        $this->drawers = $this->getRoom()->getUsers();
        shuffle($this->drawers);
    }

    /**
     * @return User;
     */
    protected function getNextDrawer()
    {
        do {
            // The following shouldn't happen
            if (!$this->drawers) {
                $this->initDrawers();
            }
            $user = array_shift($this->drawers);
        } while ($this->drawers && !$this->getRoom()->hasUser($user));
        return $user;
    }

    protected function getNextWord()
    {
        $words = ['koe', 'zebra', 'leeuw', 'dolfijn', 'giraf', 'emoe', 'haai', 'olifant', 'neushoorn', 'krokodil', 'octopus', 'schildpad', 'muis', 'hond', 'kat', 'beer'];
        return $words[array_rand($words)];
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /** @return array */
    public function toArray()
    {
        return array(
            'status' => $this->status,
        );
    }


}