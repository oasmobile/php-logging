<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:48
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Level;

class LocalErrorHandler extends FingersCrossedHandler
{
    use MLoggingHandlerTrait;

    public function __construct(?string $path = null,
                                string $namePattern = "%date%/%script%.error",
                                Level $level = Level::Debug,
                                Level $triggerLevel = Level::Error,
                                int $bufferLimit = 1000
    )
    {
        $handler = new LocalFileHandler($path, $namePattern, $level);

        parent::__construct(
            $handler,
            $triggerLevel,
            $bufferLimit, /* buffer size, 0 means no limit */
            true, /* bubbles */
            false /* stop buffering on strategy activated */
        );
    }
    
}
