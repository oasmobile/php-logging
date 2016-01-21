<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:43
 */

namespace Oasis\Mlib\Logging;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\DefaultScheme;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Oasis\Mlib\Utils\CommonUtils;

class ConsoleHandler extends StreamHandler
{
    use MLoggingHandlerTrait;

    public function __construct($level = Logger::DEBUG)
    {
        $stream = fopen('php://stderr', 'w');
        parent::__construct($stream, $level);

        $datetime_format   = "Ymd-His P";
        $output_format     = "[%channel%] %datetime% | %level_name% | %message% %context% %extra%\n";
        $colored_formatter = new ColoredLineFormatter(
            new DefaultScheme(),
            $output_format,
            $datetime_format,
            true,
            true
        );
        $colored_formatter->includeStacktraces();

        $this->setFormatter($colored_formatter);
    }

    public function isHandling(array $record)
    {
        if (!CommonUtils::isRunningFromCommandLine()) {
            return false;
        }

        return parent::isHandling($record);
    }
    
}
