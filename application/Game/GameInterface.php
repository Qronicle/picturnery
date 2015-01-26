<?php
/**
 * GameInterface.php
 */

namespace Game;

use Server\Room\Room;
use User\User;

/**
 * GameInterface
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-29 0:05
 * @author      ruud.seberechts
 */
interface GameInterface 
{
    /** @return UserInterface */
    public function createGameUser();

    public function setRoom(Room $room);

    public function processRequest($request, User $user = null);

    public function usersUpdate(array $addedUsers, array $removedUsers);

    /** @return array */
    public function toArray();
}