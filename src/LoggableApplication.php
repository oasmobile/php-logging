<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 20:26
 */

namespace Oasis\Mlib\Logging;

use Monolog\Level;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoggableApplication
 *
 * Serves as the application class for symfony/console application
 *
 * If a wrapper for symfony/console is to be written in the future, this class should be moved there
 *
 * @package Oasis\Mlib\Logging
 */
class LoggableApplication extends Application
{
    /**
     * @inheritdoc
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        parent::configureIO($input, $output);

        $consoleHandler = new ConsoleHandler();
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_QUIET:
                return;
            case OutputInterface::VERBOSITY_NORMAL:
                $consoleHandler->setLevel(Level::Warning);
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $consoleHandler->setLevel(Level::Notice);
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $consoleHandler->setLevel(Level::Info);
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $consoleHandler->setLevel(Level::Debug);
                break;
            default:
                throw new \LogicException("Unknown output verbosity: " . $output->getVerbosity());
        }
        $consoleHandler->install();
    }

}
