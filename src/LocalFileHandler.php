<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:02
 */

namespace Oasis\Mlib\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LocalFileHandler extends StreamHandler
{
    use MLoggingHandlerTrait;
    
    private $path        = null;
    private $namePattern = "%date%/%script%.log";
    
    /**
     * @var int interval between every filename recheck
     */
    private $refreshRate               = 0;
    private $lastFileCreationTimestamp = 0;
    
    public function __construct($path = null, $namePattern = "%date%/%script%.log", $level = Logger::DEBUG)
    {
        if (!$path) {
            $path = sys_get_temp_dir();
        }
        
        $this->path        = $path;
        $this->namePattern = $namePattern;
        
        parent::__construct($this->generateCurrentPath(), $level);
        
        $datetime_format = "Ymd-His P";
        $output_format   = "[%channel%] %datetime% | %level_name% | %message%  %context% %extra%\n"; // %context% %extra%
        $line_formatter  = new LineFormatter(
            $output_format,
            $datetime_format,
            true,
            true
        );
        $line_formatter->includeStacktraces();
        
        $this->setFormatter($line_formatter);
    }
    
    protected function generateCurrentPath()
    {
        $translationTable = [
            "%date%"   => date('Ymd'),
            "%hour%"   => date('H'),
            "%minute%" => date('i'),
            "%second%" => date('s'),
            "%script%" => basename($_SERVER['SCRIPT_FILENAME'], ".php"),
            "%pid%"    => getmypid(),
        ];
        
        $logFile = strtr($this->namePattern, $translationTable);
        
        $path = $this->path . "/" . $logFile;
        
        return $path;
    }
    
    /**
     * @return int
     */
    public function getRefreshRate()
    {
        return $this->refreshRate;
    }
    
    /**
     * @param int $refreshRate
     */
    public function setRefreshRate($refreshRate)
    {
        $this->refreshRate = $refreshRate;
    }
    
    protected function write(array $record)
    {
        try {
            $this->checkFilenameRefresh();
            parent::write($record);
        } catch (\UnexpectedValueException $e) {
            // try again because there might be another process writing to same file/dir
            $this->checkFilenameRefresh();
            parent::write($record); // if the second call still throws, exception will bubble
        }
    }
    
    protected function checkFilenameRefresh()
    {
        if ($this->refreshRate > 0) {
            $now = \time();
            if ($this->lastFileCreationTimestamp === 0) {
                $this->lastFileCreationTimestamp = $now;
            }
            elseif ($now - $this->lastFileCreationTimestamp >= $this->refreshRate) {
                $this->close();
                $this->url = $this->generateCurrentPath();
                $dir       = \dirname($this->url);
                if (!\file_exists($dir) && false === @\mkdir($dir, 0777, true)) {
                    throw new \UnexpectedValueException(
                        "Unable to create directory for " . $dir . ", error = " . json_encode(\error_get_last())
                    );
                }
                $this->lastFileCreationTimestamp = $now;
            }
        }
    }
}
