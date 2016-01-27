<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:48
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Logger;

class LocalErrorHandler extends FingersCrossedHandler
{
    use MLoggingHandlerTrait;

    public function __construct($path = null,
                                $namePattern = "%date%/%script%.error",
                                $level = Logger::DEBUG,
                                $triggerLevel = Logger::ERROR,
                                $bufferLimit = 1000
    )
    {
        $handler            = new LocalFileHandler($path, $namePattern, $level);
        $activationStrategy = new ErrorLevelActivationStrategy($triggerLevel);

        parent::__construct(
            $handler,
            $activationStrategy,
            $bufferLimit, /* buffer size, 0 means no limit */
            true, /* bubbles */
            false /* stop bufferring on strategy activated */
        );
    }
    
}
