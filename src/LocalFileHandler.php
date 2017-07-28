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
    
    public function __construct($path = null, $namePattern = "%date%/%script%.log", $level = Logger::DEBUG)
    {
        if (!$path) {
            $path = sys_get_temp_dir();
        }
        
        $translation_table = [
            "%date%"   => date('Ymd'),
            "%hour%"   => date('H'),
            "%script%" => basename($_SERVER['SCRIPT_FILENAME'], ".php"),
            "%pid%"    => getmypid(),
        ];
        
        $log_file = strtr($namePattern, $translation_table);
        
        $path = $path . "/" . $log_file;
        
        parent::__construct($path, $level);
        
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
    
    protected function write(array $record)
    {
        try {
            parent::write($record);
        } catch (\UnexpectedValueException $e) {
            // try again because there might be another process writing to same file/dir
            parent::write($record); // if the second call still throws, exception will bubble
        }
    }
}
