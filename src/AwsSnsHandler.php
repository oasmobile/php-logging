<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-30
 * Time: 10:10
 */

namespace Oasis\Mlib\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Oasis\Mlib\AwsWrappers\SnsPublisher;

class AwsSnsHandler extends AbstractHandler
{
    use MLoggingHandlerTrait;

    /** @var SnsPublisher */
    protected $publisher;
    protected $subject;
    protected $publishLevel;
    protected $bufferLimit;

    protected $buffer                     = [];
    protected $isBatchHandling            = false;
    protected $pendingPublish             = false;
    protected $autoPublishingOnFatalError = false;

    public function __construct(SnsPublisher $publisher,
                                $subject,
                                $bufferLimit = 100,
                                $publishLevel = Logger::ALERT,
                                $level = Logger::DEBUG,
                                $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->publisher    = $publisher;
        $this->subject      = $subject;
        $this->publishLevel = $publishLevel;
        $this->bufferLimit  = intval($bufferLimit);

        $datetime_format = "Ymd-His P";
        $output_format   = "[%channel%] %datetime% | %level_name% | %message% \n"; // %context% %extra%
        $line_formatter  = new LineFormatter(
            $output_format,
            $datetime_format,
            true
        );
        $line_formatter->includeStacktraces();

        $this->setFormatter($line_formatter);
    }

    /**
     * @inheritdoc
     */
    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }

        if ($record['level'] >= $this->publishLevel) {
            $this->pendingPublish = true;
        }

        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        $this->buffer[] = $record;
        while ($this->bufferLimit > 0 && count($this->buffer) > $this->bufferLimit) {
            array_shift($this->buffer);
        }

        if ($this->pendingPublish && !$this->isBatchHandling) {
            $this->publish();
        }

        return false;
    }

    public function handleBatch(array $records)
    {
        $this->isBatchHandling = true;
        parent::handleBatch($records);
        $this->isBatchHandling = false;
        if ($this->pendingPublish) {
            $this->publish();
        }
    }

    public function publish()
    {
        if (!$this->pendingPublish) {
            return;
        }

        $body = '';
        foreach ($this->buffer as &$record) {
            $body .= $this->getFormatter()->format($record);
        }

        $this->publisher->publish(
            $this->subject,
            $body
        );

        $this->buffer = [];

        $this->pendingPublish = false;
    }

    /**
     * @return SnsPublisher
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @param SnsPublisher $publisher
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return int
     */
    public function getPublishLevel()
    {
        return $this->publishLevel;
    }

    /**
     * @param int $publishLevel
     */
    public function setPublishLevel($publishLevel)
    {
        $this->publishLevel = $publishLevel;
    }

    /**
     * @return int
     */
    public function getBufferLimit()
    {
        return $this->bufferLimit;
    }

    /**
     * @param int $bufferLimit
     */
    public function setBufferLimit($bufferLimit)
    {
        $this->bufferLimit = $bufferLimit;
    }

    public function enableAutoPublishingOnFatalError()
    {
        $this->autoPublishingOnFatalError = true;

        register_shutdown_function(
            function () {
                if ($this->autoPublishingOnFatalError) {
                    $error = error_get_last();
                    if ($error['type'] == E_ERROR) {
                        MLogging::log(
                            $this->publishLevel,
                            "Auto publishing because fatal error occured: %s (%s:%d)",
                            $error['message'],
                            basename($error['file']),
                            intval($error['line'])
                        );
                    }
                }
            }
        );

        return $this;
    }

    public function disableAutoPublishingOnFatalError()
    {
        $this->autoPublishingOnFatalError = false;

        return $this;
    }
}
