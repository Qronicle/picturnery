<?php
/**
 * User.php
 */

namespace User;

use Game\UserInterface;
use Server\Room\Room;

/**
 * User
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-28 19:06
 * @author      ruud.seberechts
 */
class User
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $hash;
    /** @var string */
    protected $username;
    /** @var Resource */
    protected $client;
    /** @var Room */
    protected $room;
    /** @var UserInterface */
    protected $gameUser;
    /** @var string */
    protected $ip;

    public function toArray()
    {
        return array(
            'id'       => $this->getId(),
            'username' => $this->getUsername(),
            'ip'       => $this->getIp(),
            'game'     => $this->getGameUser()->toArray(),
        );
    }

    /**
     * @param Resource $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return Resource
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param \Game\UserInterface $gameUser
     */
    public function setGameUser($gameUser)
    {
        $this->gameUser = $gameUser;
    }

    /**
     * @return \Game\UserInterface
     */
    public function getGameUser()
    {
        return $this->gameUser;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
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
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param \Server\Room\Room $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
    }

    /**
     * @return \Server\Room\Room
     */
    public function getRoom()
    {
        return $this->room;
    }
}