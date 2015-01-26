<?php
/**
 * Message.php
 */

namespace Server\Message;

use Server\Server;

/**
 * Message
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-28 12:06
 * @author      ruud.seberechts
 */
class Message implements MessageInterface
{
    const TYPE_SYSTEM_MESSAGE = 'systemMessage';
    const TYPE_USER_MESSAGE   = 'userMessage';
    const TYPE_UNKNOWN        = 'unknown';

    protected $data;

    function __construct($type, $message = null)
    {
        $this->data = is_array($type) ? $type : (is_array($message) ? $message : array('message' => $message));
        if (!is_array($type)) {
            $this->data['type'] = $type;
        }
        if (!isset($this->data['type']) || !$this->data['type']) {
            Server::$instance->log("Warning: Message without type sent");
            $this->data['type'] = self::TYPE_UNKNOWN;
        }
    }

    public function toArray()
    {
        return $this->data;
    }
}