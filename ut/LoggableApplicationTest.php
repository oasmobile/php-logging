<?php

use Monolog\Level;
use Oasis\Mlib\Logging\ConsoleHandler;
use Oasis\Mlib\Logging\LoggableApplication;
use Oasis\Mlib\Logging\MLogging;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LoggableApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        // Remove any ConsoleHandler installed during tests
        $handlers = MLogging::getLogger()->getHandlers();
        $cleaned  = [];
        foreach ($handlers as $handler) {
            if (!$handler instanceof ConsoleHandler) {
                $cleaned[] = $handler;
            }
        }
        MLogging::getLogger()->setHandlers($cleaned);
    }

    public function testConstructorSetsNameAndVersion(): void
    {
        $app = new LoggableApplication('TestApp', '1.2.3');
        $this->assertSame('TestApp', $app->getName());
        $this->assertSame('1.2.3', $app->getVersion());
    }

    public function testConstructorDefaults(): void
    {
        $app = new LoggableApplication();
        $this->assertSame('UNKNOWN', $app->getName());
        $this->assertSame('UNKNOWN', $app->getVersion());
    }

    /**
     * @dataProvider verbosityToLevelProvider
     */
    public function testConfigureIOSetsConsoleHandlerLevel(int $verbosity, Level $expectedLevel): void
    {
        $app = $this->buildAppWithNoopCommand();

        $input  = new ArrayInput(['command' => 'noop']);
        $output = new BufferedOutput($verbosity);

        $app->setAutoExit(false);
        $app->run($input, $output);

        $consoleHandler = $this->findConsoleHandler();
        $this->assertNotNull($consoleHandler, 'ConsoleHandler should be installed after run');
        $this->assertSame($expectedLevel, $consoleHandler->getLevel());
    }

    public static function verbosityToLevelProvider(): array
    {
        return [
            'normal'       => [OutputInterface::VERBOSITY_NORMAL, Level::Warning],
            'verbose'      => [OutputInterface::VERBOSITY_VERBOSE, Level::Notice],
            'very verbose' => [OutputInterface::VERBOSITY_VERY_VERBOSE, Level::Info],
            'debug'        => [OutputInterface::VERBOSITY_DEBUG, Level::Debug],
        ];
    }

    public function testQuietVerbosityDoesNotInstallConsoleHandler(): void
    {
        $app = $this->buildAppWithNoopCommand();

        $input  = new ArrayInput(['command' => 'noop']);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);

        $app->setAutoExit(false);
        $app->run($input, $output);

        $consoleHandler = $this->findConsoleHandler();
        $this->assertNull($consoleHandler, 'ConsoleHandler should NOT be installed in quiet mode');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function buildAppWithNoopCommand(): LoggableApplication
    {
        $app = new LoggableApplication('test', '0.0.1');
        $app->add(
            (new Command('noop'))
                ->setCode(function (InputInterface $input, OutputInterface $output): int {
                    return Command::SUCCESS;
                })
        );

        return $app;
    }

    private function findConsoleHandler(): ?ConsoleHandler
    {
        foreach (MLogging::getLogger()->getHandlers() as $handler) {
            if ($handler instanceof ConsoleHandler) {
                return $handler;
            }
        }

        return null;
    }
}
