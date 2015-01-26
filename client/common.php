<?php

if (!defined('ENV')) {
    define('ENV', 'cmd');
}

// Directory definitions
define('ROOT_DIR', dirname(dirname(__FILE__)));
define('APPLICATION_DIR', ROOT_DIR . '/application');
define('DATA_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'data');

ini_set("log_errors", 1);
ini_set("error_log", ROOT_DIR . "/php-error.log");

function __autoload($className) {
    $path = APPLICATION_DIR . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($path)) {
        require($path);
        return true;
    }
    return false;
}

$users = [];
$user1 = new \User\User();
$user1->setId(1);
$user1->setUsername('Qronicle');
$user1->setHash('1tgr515ger1re');
$user2 = new \User\User();
$user2->setUsername('DarthGrey');
$user2->setHash('4ht5e6r9y4ht6e5');
$user2->setId(2);
$users = array(
    1 => $user1,
    2 => $user2,
);

###############################################################################################################
# DUMP and all variations #####################################################################################
###############################################################################################################


/**
 * Dump browser-formatted variables.
 *
 * Works a lot like var_dump.
 *
 * Usage:
 * <code>dump($foo, $bar);</code>
 *
 * @return void
 */
function dump()
{
    if (!dumpEnabled()) return false;
    $args = func_get_args();
    __printCallSource();
    echo '';
    foreach ($args as $i => $obj) {
        if ($i) print "\n";
        print mdump($obj);
    }
    echo '';
}

function __printCallSource()
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    do {
        $caller = array_shift($backtrace);
    } while ($caller && (!isset($caller['line']) || $caller['file'] == __FILE__));
    echo "---------\nDump called at " . $caller['file'] . ' (line ' . $caller['line'] . ')' . "\n";
}

function hdump()
{
    if (!dumpEnabled()) return false;
    echo '<hr/>';
    $args = func_get_args();
    call_user_func_array('dump', $args);
}


/**
 * Converts all types to a string
 *
 * Kindoff mimics var_dump, but only of one variable, and returns the result instead of printing it.
 *
 * @param mixed $obj    The object that should be converted to a string
 * @return string
 */
function objToString($obj)
{
    if ($obj instanceof __PHP_Incomplete_Class) {
        var_dump($obj);die;
    }
    if (is_bool($obj)) {
        return $obj ? 'bool(true)' : 'bool(false)';
    } elseif (is_string($obj)) {
        return 'string(' . strlen($obj) . ') "' . $obj . '"';
    } elseif (is_null($obj)) {
        return 'NULL';
    } elseif (is_array($obj) || is_object($obj)) {
        return print_r($obj, true);
    } else {
        return $obj;
    }
}

/**
 * Creates exception debug info string
 *
 * @param Exception $ex
 */
function exceptionToString(Exception $ex)
{
    return get_class($ex) . " Object\n{\n"
    . "    [message]  => " . $ex->getMessage() . "\n"
    . "    [code]     => " . $ex->getCode() . "\n"
    . "    [file]     => " . $ex->getFile() . "\n"
    . "    [line]     => " . $ex->getLine() . "\n"
    . "    [previous] => " . $ex->getPrevious() . "\n"
    . '    [trace]    => <div style="margin: 10px 0 10px 60px">' . $ex->getTraceAsString() . "</div>"
    . '}';
}

/**
 * Dump variables, then die.
 *
 * Usage:
 * <code>ddump($foo, $bar);</code>
 *
 * @return void
 */
function ddump()
{
    if (!dumpEnabled()) return false;
    $args = func_get_args();
    call_user_func_array('dump', $args);
    die;
}

/**
 * Get the dump of variables as a string
 *
 * Usage:
 * <code>$log = sdump($foo, $bar);</code>
 *
 * @return string
 */
function sdump()
{
    $args  = func_get_args();
    $sdump = '';
    foreach ($args as $obj) {
        $sdump .= mdump($obj) . "\n\n";
    }

    return $sdump;
}

/**
 * Dump variables to the browser's Firebug console.
 *
 * Usage:
 * <code>cdump($foo, $bar);</code>
 *
 * @return void
 *
function cdump()
{
if (!dumpEnabled()) return false;
$args = func_get_args();
$writer = new Zend_Log_Writer_Firebug();
$logger = new Zend_Log($writer);
$numargs = func_num_args();
$arg_list = func_get_args();
$logger->log(sdump($args), Zend_Log::INFO);
}*/

$__dump_enabled  = null;
$__fdump_cleaned = array();
/**
 * Dump variables to a file in the web root to the 'f.dump' file.
 *
 * Usage:
 * <code>fdump($foo, $bar, ..., $fileName, $clearPerRequest);</code>
 *
 * This method can be used the same as the other dump functions, there are only two optional arguments you can end with:
 * <ul>
 *  <li><i>string</i> <b>$fileName</b>: Optional, a filename for the dump file (should end with .dump). Defaults to 'f.dump'.</li>
 *  <li><i>bool</i> <b>$clearPerRequest</b>: Optional, whether the file should be cleared when this is the first time this request this file is used to dump to. Defaults to 'true'.</li>
 * </ul>
 *
 * @return void
 */
function fdump()
{
    $fileName        = 'f.dump';
    $clearPerRequest = false;

    $args    = func_get_args();
    $numArgs = count($args);

    //check last argument is a string that ends in .dump
    if ($numArgs) {
        $last = end($args);
        if (is_string($last) && substr($last, -5) == '.dump') {
            $fileName = array_pop($args);
        } //check second to last argument is a string that ends in .dump and last argument is a boolean
        elseif ($numArgs > 1) {
            $last  = end($args);
            $flast = $args[$numArgs - 2];
            if (is_bool($last) && is_string($flast) && substr($flast, -5) == '.dump') {
                $clearPerRequest = array_pop($args);
                $fileName        = array_pop($args);
            }
        }
    }

    // Clear file when clearPerRequest is true, this file wasn't cleared yet, and exists
    global $__fdump_cleaned;
    if ($clearPerRequest && !isset($__fdump_cleaned[$fileName]) && file_exists($fileName)) {
        unlink($fileName);
    }
    $__fdump_cleaned[$fileName] = true;

    // Append args dump to file
    $file = fopen($fileName, 'a+');
    fwrite($file, '== ' . date('Y-m-d H:i:s') . " ====================================================\n\n");
    foreach ($args as $obj) {
        fwrite($file, sdump($obj) . "\n\n");
    }
    fwrite($file, "\n\n");

    fclose($file);
}

/**
 * Whether dumping is enabled
 *
 * @return bool
 */
function dumpEnabled()
{
    // Check whether the dump is enabled via the Zend Application
    global $__dump_enabled;
    if (!is_null($__dump_enabled)) {
        return $__dump_enabled;
    }

    // Otherwise it is enabled when not in production
    if (defined('APPLICATION_ENV') && APPLICATION_ENV == APPLICATION_ENV__PRODUCTION) {
        return false;
    }

    return true;
}

$__mdumpDone = array();

/**
 * Mini dump
 * print_r replacement for Zend Framework 2 applications.
 * Ignores the undumpable members like the serviceLocator
 *
 * @param mixed $arg    The object that will be dumped
 * @param int $level
 *
 * @return string       The dumped object
 */
function mdump($arg, $level = 0)
{
    global $__mdumpDone;
    if (!$level) {
        $__mdumpDone = array();
    }

    $indent   = str_repeat(' ', $level * 4);
    $indented = str_repeat(' ', ($level + 1) * 4);
    $output   = '';

    if ($arg instanceof Zend\Stdlib\Parameters) {
        return 'Zend\Stdlib\Parameters' . substr(print_r($arg->toArray(), true), 5);
    } elseif ($arg instanceof DateTime) {
        return "DateTime (" . $arg->format('Y-m-d H:i:s') . ")\n";
    } elseif ($arg instanceof Zend\Db\Sql\Select || $arg instanceof Zend\Db\Sql\Delete) {
        if (isset($arg->sql) && $arg->sql instanceof Zend\Db\Sql\Sql) {
            return $arg->sql->getSqlStringForSqlObject($arg);
        } else {
            return $arg->getSqlString();
        }
    } elseif ($arg instanceof Exception) {
        return exceptionToString($arg);
    }

    $endRecursion = $level >= 30;
    if (!$endRecursion && $level && is_object($arg)) {
        $className    = get_class($arg);
        $endRecursion = in_array($className, array(
            'Zend\View\HelperPluginManager',
            'Zend\ServiceManager\ServiceManager',
            'Zend\View\Helper\Navigation\PluginManager',
            'Zend\Mvc\Controller\PluginManager',
            'UwBase\Website',
            'Zend\Http\PhpEnvironment\Request',
            'Zend\Http\PhpEnvironment\Response',
            'Doctrine\ORM\EntityManager',
            'Doctrine\ORM\Mapping\ClassMetadata',
            'Zend\Form\FormElementManager',
            'Zend\Form\Factory',
        ));
    }

    if ($endRecursion) {
        if (is_object($arg)) {
            return "[" . get_class($arg) . "] * Ignored *\n";
        } elseif (is_array($arg)) {
            return "array (" . count($arg) . ") * Ignored *\n";
        } else {
            return objToString($arg) . "\n";
        }
    }

    if (is_array($arg)) {
        $output .= "array\n" . $indent . "(\n";
        foreach ($arg as $key => $val) {
            $output .= $indented . "$key => " . mdump($val, $level + 2);
        }
        $output .= $indent . ")\n";
    } elseif ($arg instanceof stdClass) {
        $output .= "stdClass\n" . $indent . "(\n";
        foreach ($arg as $key => $val) {
            $output .= $indented . "$key => " . mdump($val, $level + 2);
        }
        $output .= $indent . ")\n";
    } elseif (is_object($arg)) {
        //ddump(get_class($arg), $__mdumpDone);
        if (@in_array($arg, $__mdumpDone, true)) {
            $name = get_class($arg);
            if ($arg instanceof \UwBase\Entity\AbstractEntity) {
                $name .= ':' . $arg->getId();
            }
            $output .= '[' . $name . "] * Recursion *\n";

            return $output;
        }
        if ($arg instanceof stdClass) {
            $output .= str_replace("\n", "\n" . $indent, trim(print_r($arg, true), "\n")) . "\n";

            return $output;
        }
        $__mdumpDone[] = $arg;
        $output .= get_class($arg) . "\n" . $indent . "(\n";
        $reflClass = new ReflectionClass($arg);
        $props     = $reflClass->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $output .= $indented . $prop->name . " => " . mdump($prop->getValue($arg), $level + 2);
        }
        $output .= $indent . ")\n";
    } else {
        return objToString($arg) . "\n";
    }

    return $output;
}
