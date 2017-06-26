<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-30
 * Time: 10:10
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oasis\Mlib\Utils\CommonUtils;

/**
 * Class ShutdownFallbackHandler
 *
 * This handler will fallback to the fallback-handler passed in, when:
 * - the desired fallback level is triggered
 * OR
 * - the program shuts down with error
 *
 * When publishing to the fallback-handler, this handler will fallback all the bufferred records together
 *
 */
class ShutdownFallbackHandler extends AbstractProcessingHandler
{
    use MLoggingHandlerTrait;
    
    protected $publishLevel;
    protected $bufferLimit;
    
    /** @var  \SplQueue */
    protected $buffer;
    protected $isBatchHandling            = false;
    protected $pendingPublish             = false;
    protected $autoPublishingOnFatalError = true;
    /**
     * @var HandlerInterface
     */
    private $fallbackHandler;
    
    public function __construct(HandlerInterface $fallbackHandler,
                                $bufferLimit = 100,
                                $publishLevel = Logger::ALERT,
                                $level = Logger::DEBUG,
                                $bubble = true)
    {
        parent::__construct($level, $bubble);
        
        $this->fallbackHandler = $fallbackHandler;
        $this->publishLevel    = $publishLevel;
        $this->bufferLimit     = intval($bufferLimit);
        
        $this->buffer = new \SplQueue();
        
        $this->enableAutoPublishingOnFatalError();
    }
    
    function __destruct()
    {
        $this->disableAutoPublishingOnFatalError();
    }
    
    /**
     * @inheritdoc
     */
    public function write(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }
        
        if ($record['level'] >= $this->publishLevel) {
            $this->pendingPublish = true;
        }
        
        $this->buffer->push($record);
        while ($this->bufferLimit > 0 && $this->buffer->count() > $this->bufferLimit) {
            $this->buffer->shift();
        }
        
        if ($this->pendingPublish && !$this->isBatchHandling) {
            $this->fallback();
        }
        
        return false;
    }
    
    public function handleBatch(array $records)
    {
        $this->isBatchHandling = true;
        parent::handleBatch($records);
        $this->isBatchHandling = false;
        
        if ($this->pendingPublish) {
            $this->fallback();
        }
    }
    
    public function fallback()
    {
        if (!$this->pendingPublish) {
            return;
        }
        
        $batch = [];
        foreach ($this->buffer as $record) {
            $batch[] = $record;
        }
        $this->fallbackHandler->handleBatch($batch);
        
        $this->buffer         = new \SplQueue();
        $this->pendingPublish = false;
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
                CommonUtils::monitorMemoryUsage();
                if ($this->autoPublishingOnFatalError) {
                    $error = error_get_last();
                    if ($error['type'] == E_ERROR) {
                        /** @noinspection PhpParamsInspection */
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
