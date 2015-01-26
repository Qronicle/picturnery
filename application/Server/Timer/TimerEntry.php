<?php
/**
 * TimerEntry.php
 */

namespace Server\Timer;

/**
 * TimerEntry
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-31 14:39
 * @author      ruud.seberechts
 */
class TimerEntry
{
    public $time;
    public $function;
    public $arguments = [];
}