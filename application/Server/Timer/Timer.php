<?php
/**
 * Timer.php
 */

namespace Server\Timer;

/**
 * Timer
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-31 14:18
 * @author      ruud.seberechts
 */
class Timer
{
    /** @var TimerEntry[] */
    protected static $timerEntries = [];

    public static function setTimeout($function, $seconds, array $arguments = [])
    {
        $now = microtime(true);
        $id = (string) $now;
        $timerEntry = new TimerEntry();
        $timerEntry->time = $now + $seconds;
        $timerEntry->function = $function;
        $timerEntry->arguments = $arguments;
        self::$timerEntries[$id] = $timerEntry;
        return $id;
    }

    public static function clearTimeout($id)
    {
        unset(self::$timerEntries[$id]);
    }

    public static function tick()
    {
        $now = microtime(true);
        foreach (self::$timerEntries as $i => $timerEntry) {
            if ($timerEntry->time <= $now) {
                call_user_func_array($timerEntry->function, $timerEntry->arguments);
                unset(self::$timerEntries[$i]);
            }
        }
    }
}