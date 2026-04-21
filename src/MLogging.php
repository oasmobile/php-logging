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
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Oasis\Mlib\Utils\CommonUtils;

class MLogging
{
    protected static $logger                     = null;
    protected static $autoPublishingOnFatalError = false;
    protected static $autoPublisherRegistered    = false;
    
    /** @var HandlerInterface[] */
    protected static $handlers             = [];
    protected static $minLevelForFileTrace = Level::Debug;
    
    public static function enableAutoPublishingOnUnexpectedShutdown(Level $publishLevel = Level::Alert)
    {
        self::$autoPublishingOnFatalError = true;
        if (\class_exists(CommonUtils::class) && !self::$autoPublisherRegistered) {
            register_shutdown_function(
                function () use ($publishLevel) {
                    CommonUtils::monitorMemoryUsage();
                    if (self::$autoPublishingOnFatalError) {
                        $error = error_get_last();
                        if ($error && $error['type'] == E_ERROR) {
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
    
    public static function addHandler(HandlerInterface $handler, ?string $name = null): void
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
    
    public static function setMinLogLevel(Level $level, ?string $namePattern = null): void
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
    
    public static function log(string|Level $level, string $msg, mixed ...$args): void
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
    
    public static function setMinLogLevelForFileTrace(Level $level): void
    {
        self::$minLevelForFileTrace = $level;
    }
    
    public static function lnProcessor(LogRecord $record): LogRecord
    {
        $channel = (string) getmypid();
        $message = $record->message;

        if (self::$minLevelForFileTrace->includes($record->level)) {
            $callStack        = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
            $self_encountered = false;
            $last_file        = '';
            $last_line        = 0;
            $passed_src       = false;
            foreach ($callStack as $trace) {
                if (isset($trace['class'])
                    && isset($trace['function'])
                    && $trace['class'] == Logger::class
                    && in_array(
                        $trace['function'],
                        [
                            'log',
                            'addRecord',
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
                    if (isset($trace['file']) && isset($trace['line'])) {
                        $last_file = $trace['file'];
                        $last_line = $trace['line'];
                    }
                    continue;
                }
                elseif (!$self_encountered) {
                    continue;
                }
                elseif (isset($trace['file']) && dirname($trace['file']) == __DIR__) {
                    $passed_src = true;
                    continue;
                }
                
                // Found external caller frame
                if ($passed_src && isset($trace['file']) && isset($trace['line'])) {
                    // If we traversed src/ frames, the external caller's
                    // file/line is where the outermost src/ function was called from
                    $last_file = $trace['file'];
                    $last_line = $trace['line'];
                }
                // If no src/ frames were traversed, last_file/last_line
                // from the outermost Logger frame is already correct
                break;
            }
            
            if ($self_encountered && $last_file && $last_line) {
                if (!str_ends_with($message, "\n")) {
                    $message .= " ";
                }
                $message .= "(" . basename($last_file) . ":" . $last_line . ")";
            }
        }
        
        return $record->with(channel: $channel, message: $message);
    }
    
    public static function getExceptionDebugInfo(\Throwable $exception): string
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
