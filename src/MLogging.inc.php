<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:54
 */

use Monolog\Level;
use Oasis\Mlib\Logging\MLogging;

function mdebug(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function minfo(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function mnotice(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function mwarning(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function merror(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function mcritical(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function malert(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function memergency(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}

function mtrace(\Throwable $e, string $prompt_string = "", string|Level $logLevel = Level::Info): void
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
