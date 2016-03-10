<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 16:59
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oasis\Mlib\Utils\StringUtils;

class MLogging
{
    protected static $logger = null;
    /** @var HandlerInterface[] */
    protected static $handlers             = [];
    protected static $minLevelForFileTrace = Logger::DEBUG;

    public static function addHandler(HandlerInterface $handler, $name = null)
    {
        $handler->pushProcessor([self::class, "lnProcessor"]);

        if ($name) {
            $reinstall_required    = isset(self::$handlers[$name]);
            self::$handlers[$name] = $handler;
        }
        else {
            $reinstall_required = false;
            self::$handlers[]   = $handler;
        }

        if ($reinstall_required) {
            self::getLogger()->setHandlers(self::$handlers);
        }
        else {
            self::getLogger()->pushHandler($handler);
        }
    }

    public static function setMinLogLevel($level, $namePattern = null)
    {
        foreach (self::$handlers as $name => $handler) {
            if ($namePattern == null
                || $name == $namePattern
                || @preg_match($namePattern, $name)
            ) {
                if ($handler instanceof AbstractHandler) {
                    $handler->setLevel($level);
                }
            }
        }

        if ($namePattern === null) {
            self::setMinLogLevelForFileTrace($level);
        }
    }

    public static function log($level, $msg, ...$args)
    {
        if ($args) {
            $msg = vsprintf($msg, $args);
        }
        self::getLogger()->log($level, $msg);
    }

    public static function getLogger()
    {
        if (self::$logger instanceof Logger) {
            return self::$logger;
        }

        self::$logger = new Logger('mlogging-logger');

        return self::$logger;
    }

    public static function setMinLogLevelForFileTrace($level)
    {
        self::$minLevelForFileTrace = Logger::toMonologLevel($level);
    }

    public static function lnProcessor(array $record)
    {
        $record['channel'] = getmypid();
        if ($record['level'] >= self::$minLevelForFileTrace) {
            $callStack        = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
            $self_encountered = false;
            foreach ($callStack as $trace) {
                if (isset($trace['class'])
                    && isset($trace['function'])
                    && $trace['class'] == Logger::class
                    && $trace['function'] == 'log'
                ) {
                    $self_encountered = true;
                    continue;
                }
                elseif (!$self_encountered) {
                    continue;
                }
                elseif (isset($trace['file']) && dirname($trace['file']) == __DIR__) {
                    continue;
                }
                if (!StringUtils::stringEndsWith($record['message'], "\n")) {
                    $record['message'] .= " ";
                }
                if (isset($trace['file']) && isset($trace['line'])) {
                    $record['message'] .= "(" . basename($trace['file']) . ":" . $trace['line'] . ")";
                }
                break;
            }
        }

        return $record;
    }

    public static function getExceptionDebugInfo(\Exception $exception)
    {
        return
            "Exception info: " . $exception->getMessage()
            . PHP_EOL
            . ("code = " . $exception->getCode() . ", at " . $exception->getFile() . ":" . $exception->getLine())
            . PHP_EOL
            . $exception->getTraceAsString()
            . PHP_EOL;
    }

}
