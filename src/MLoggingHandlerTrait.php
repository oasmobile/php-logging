<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 20:35
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\HandlerInterface;

/**
 * Trait MLoggingHandlerTrait
 *
 * This trait is to be used on
 *
 * @package Oasis\Mlib\Logging
 */
trait MLoggingHandlerTrait
{
    public function install()
    {
        if ($this instanceof HandlerInterface) {
            MLogging::addHandler($this, static::class);
            
            return $this;
        }
        else {
            throw new \LogicException("Installed logging handler is not of correct type! class = " . static::class);
        }
    }
}
