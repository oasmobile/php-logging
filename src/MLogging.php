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
use Oasis\Mlib\Utils\CommonUtils;
use Oasis\Mlib\Utils\StringUtils;

class MLogging
{
    protected static $logger                     = null;
    protected static $autoPublishingOnFatalError = false;
    protected static $autoPublisherRegistered    = false;
    
    /** @var HandlerInterface[] */
    protected static $handlers             = [];
    protected static $minLevelForFileTrace = Logger::DEBUG;
    
    public static function enableAutoPublishingOnUnexpectedShutdown($publishLevel = Logger::ALERT)
    {
        self::$autoPublishingOnFatalError = true;
        if (\class_exists(CommonUtils::class) && !self::$autoPublisherRegistered) {
            register_shutdown_function(
                function () use ($publishLevel) {
                    @CommonUtils::monitorMemoryUsage();
                    if (self::$autoPublishingOnFatalError) {
                        /** @var array $error */
                        $error = error_get_last();
                        if ($error['type'] == E_ERROR) {
                            /** @noinspection PhpParamsInspection */
                            self::log(
                                $publishLevel,
                                "Auto publishing because fatal error occured: %s (%s:%d)",
                                $error['message'],
                                basename($error['file']),
                                intval($error['line'])
                            );
                        }
                    }
                }
            );
            self::$autoPublisherRegistered = true;
        }
    }
    
    public static function disableAutoPublishingOnUnexpectedShutdown()
    {
        self::$autoPublishingOnFatalError = false;
    }
    
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
        if (!self::getLogger()->getHandlers()) {
            if (CommonUtils::isRunningFromCommandLine()) {
                (new ConsoleHandler())->install();
            }
            else {
                (new LocalFileHandler())->install();
            }
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
            $last_file        = '';
            $last_line        = 0;
            foreach ($callStack as $trace) {
                if (isset($trace['file']) && isset($trace['line'])) {
                    $last_file = $trace['file'];
                    $last_line = $trace['line'];
                }
                
                if (isset($trace['class'])
                    && isset($trace['function'])
                    && $trace['class'] == Logger::class
                    && in_array(
                        $trace['function'],
                        [
                            'log',
                            'debug',
                            'info',
                            'notice',
                            'warning',
                            'error',
                            'emergency',
                            'alert',
                            'critical',
                            'warn',
                            'err',
                            'crit',
                            'emerg',
                        ]
                    )
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
                if ($last_file && $last_line) {
                    $record['message'] .= "(" . basename($last_file) . ":" . $last_line . ")";
                }
                break;
            }
        }
        
        return $record;
    }
    
    public static function getExceptionDebugInfo(\Exception $exception)
    {
        return sprintf(
            "Exception (%s) info: %s\n" .
            "(code = #%d, at %s, %d)\n" .
            "%s\n",
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
    
}
