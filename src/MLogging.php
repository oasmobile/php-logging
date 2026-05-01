<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 16:59
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Oasis\Mlib\Utils\CommonUtils;

class MLogging
{
    private static ?Logger $logger                     = null;
    private static bool $autoPublishingOnFatalError = false;
    private static bool $autoPublisherRegistered    = false;
    private static array $handlers                     = [];
    private static Level $minLevelForFileTrace = Level::Debug;
    
    public static function enableAutoPublishingOnUnexpectedShutdown(Level $publishLevel = Level::Alert): void
    {
        self::$autoPublishingOnFatalError = true;
        if (\class_exists(CommonUtils::class) && !self::$autoPublisherRegistered) {
            register_shutdown_function(
                function () use ($publishLevel) {
                    self::handleUnexpectedShutdown($publishLevel);
                }
            );
            self::$autoPublisherRegistered = true;
        }
    }
    
    /**
     * Handle unexpected shutdown (fatal error).
     * Extracted for testability — called by the registered shutdown function.
     *
     * @internal
     */
    public static function handleUnexpectedShutdown(Level $publishLevel = Level::Alert): void
    {
        // After an OOM fatal error the process has almost no
        // headroom left.  Remove the limit so the log call below
        // can allocate the memory it needs.
        \ini_set('memory_limit', '-1');
        if (self::$autoPublishingOnFatalError) {
            $error = error_get_last();
            self::publishFatalIfNeeded($error, $publishLevel);
        }
    }
    
    /**
     * Publish a log message if the given error is a fatal error (E_ERROR).
     *
     * @internal
     */
    public static function publishFatalIfNeeded(?array $error, Level $publishLevel): void
    {
        if ($error && $error['type'] == E_ERROR) {
            self::log(
                $publishLevel,
                "Auto publishing because fatal error occurred: %s (%s:%d)",
                $error['message'],
                basename($error['file']),
                intval($error['line'])
            );
        }
    }
    
    public static function disableAutoPublishingOnUnexpectedShutdown(): void
    {
        self::$autoPublishingOnFatalError = false;
    }
    
    /**
     * Reset all static state (logger, handlers, file-trace level).
     * Intended for test isolation — call in tearDown() to prevent
     * handler leakage across test cases.
     */
    public static function reset(): void
    {
        self::$logger            = null;
        self::$handlers          = [];
        self::$minLevelForFileTrace = Level::Debug;
    }
    
    public static function addHandler(HandlerInterface $handler, ?string $name = null): void
    {
        if ($handler instanceof ProcessableHandlerInterface) {
            $handler->pushProcessor([self::class, "lnProcessor"]);
        }
        
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
    
    public static function getLogger(): Logger
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
