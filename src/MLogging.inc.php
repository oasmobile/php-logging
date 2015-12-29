<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:54
 */

use Monolog\Logger;
use Oasis\Mlib\Logging\MLogging;

function mdebug($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function minfo($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function mnotice($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function mwarning($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function merror($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function mcritical($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function malert($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function memergency($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

function mtrace(\Exception $e, $prompt_string = "", $logLevel = Logger::INFO)
{
    MLogging::log(
        $logLevel,
        $prompt_string . PHP_EOL . MLogging::getExceptionDebugInfo($e)
    );
}

function mdump($obj)
{
    return print_r($obj, true);
}
