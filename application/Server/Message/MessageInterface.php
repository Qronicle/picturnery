<?php
/**
 * MessageInterface.php
 */

namespace Server\Message;

/**
 * MessageInterface
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-28 12:11
 * @author      ruud.seberechts
 */
interface MessageInterface 
{
    /** @return array */
    public function toArray();
}