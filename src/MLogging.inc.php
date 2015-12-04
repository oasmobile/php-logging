<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 18:54
 */

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
