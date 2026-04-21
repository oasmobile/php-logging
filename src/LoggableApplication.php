<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 20:26
 */

namespace Oasis\Mlib\Logging;

use Monolog\Handler\NullHandler;
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

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
            MLogging::getLogger()->pushHandler(new NullHandler());
        }
        else {
            $level = match ($output->getVerbosity()) {
                OutputInterface::VERBOSITY_NORMAL       => Level::Warning,
                OutputInterface::VERBOSITY_VERBOSE      => Level::Notice,
                OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
                OutputInterface::VERBOSITY_DEBUG        => Level::Debug,
                default => throw new \LogicException("Unknown output verbosity: " . $output->getVerbosity()),
            };

            (new ConsoleHandler($level))->install();
        }
    }

}
