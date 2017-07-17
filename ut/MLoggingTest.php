<?php
use Monolog\Logger;
use Oasis\Mlib\Logging\LocalErrorHandler;
use Oasis\Mlib\Logging\LocalFileHandler;
use Oasis\Mlib\Logging\MLogging;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 20:45
 */
class MLoggingTest extends PHPUnit_Framework_TestCase
{
    public $path;
    
    protected function setUp()
    {
        $ts         = microtime(true) . "." . getmypid();
        $this->path = sys_get_temp_dir() . "/$ts";
        (new LocalFileHandler($this->path))->install();
        (new LocalErrorHandler($this->path))->install();
        MLogging::setMinLogLevel(Logger::DEBUG);
    }
    
    protected function tearDown()
    {
    }
    
    public function testLocalFileHandler()
    {
        mdebug("wow, hello!");
        minfo("wow, hello!");
        mnotice("wow, hello!");
        mwarning("wow, hello!");
        merror("woww, hello!");
        mcritical("wow, hello!");
        malert("wow, hello!");
        memergency("wow, hello!");
        
        $this->assertStringPatternInFile("/DEBUG.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/INFO.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/NOTICE.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/ERROR.*woww, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/WARNING.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/CRITICAL.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/ALERT.*wow, hello!/", $this->getLogFile());
        $this->assertStringPatternInFile("/EMERGENCY.*wow, hello!/", $this->getLogFile());
    }
    
    public function testExceptionTracing()
    {
        try {
            throw new \RuntimeException("something went wrong", 99);
        } catch (\Exception $e) {
            mtrace($e);
        }
        $this->assertStringPatternInFile("/INFO.*/", $this->getLogFile());
        $this->assertStringPatternInFile("/Exception.*RuntimeException.*something went wrong/", $this->getLogFile());
        $this->assertStringPatternInFile("/code = #99.*" . preg_quote(__FILE__, "/") . "/", $this->getLogFile());
        $this->assertStringPatternInFile("/" . preg_quote(__FUNCTION__, "/") . "/", $this->getLogFile());
    }
    
    public function testErrorHandlerWithContent()
    {
        mdebug("abc");
        merror("efg");
        
        $this->assertStringPatternInFile('/abc/', $this->getErrorFile());
        $this->assertStringPatternInFile('/efg/', $this->getErrorFile());
    }
    
    public function testErrorHandlerWithoutContent()
    {
        mdebug("abc");
        mwarning("efg");
        
        $this->expectException(LogicException::class);
        $this->assertStringPatternNotInFile('/abc/', $this->getErrorFile());
    }
    
    public function testSetLogLevel()
    {
        mdebug("cool");
        MLogging::setMinLogLevel(Logger::INFO);
        mdebug("Star");
        minfo("Lucky");
        
        $this->assertStringPatternInFile("/cool/", $this->getLogFile());
        $this->assertStringPatternNotInFile("/Star/", $this->getLogFile());
        $this->assertStringPatternInFile("/Lucky/", $this->getLogFile());
    }
    
    public function testFileTraceSwitch()
    {
        $filename = basename(__FILE__);
        
        MLogging::getLogger()->log('debug', 'chris');
        $this->assertStringPatternInFile("/chris.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        MLogging::getLogger()->notice('webber');
        $this->assertStringPatternInFile("/webber.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        mdebug('jason');
        $this->assertStringPatternInFile("/jason.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        MLogging::setMinLogLevel(Logger::INFO);
        mdebug('williams');
        $this->assertStringPatternNotInFile("/williams.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        minfo("sacramento");
        $this->assertStringPatternInFile("/sacramento.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        MLogging::setMinLogLevelForFileTrace(Logger::ERROR);
        mwarning('williams');
        $this->assertStringPatternNotInFile("/williams.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
        merror('williams');
        $this->assertStringPatternInFile("/williams.*\\($filename\\:[0-9]+\\)\\s*$/", $this->getLogFile());
    }
    
    public function testContext()
    {
        $filename = basename(__FILE__);
        
        MLogging::getLogger()->log('debug', "mark", ['abc' => 'xyz']);
        $this->assertStringPatternInFile("/mark.*\\($filename\\:[0-9]+\\).*abc.*xyz.*$/", $this->getLogFile());
    }
    
    public function testAlertOnFatalError()
    {
        $pid = pcntl_fork();
        if ($pid == 0) {
            MLogging::enableAutoPublishingOnUnexpectedShutdown();
            //exit(1);
            ini_set("display_errors", false);
            ini_set('error_reporting', ~E_ALL);
            set_error_handler(null);
            set_exception_handler(null);
            $a = [];
            while (true) {
                $a[] = $a;
            }
            exit(0);
        }
        
        pcntl_waitpid($pid, $status);
        $exitStatus = pcntl_wexitstatus($status);
        $this->assertNotEquals(0, $exitStatus);
        $this->assertStringPatternInFile('/Auto publishing/', $this->getErrorFile());
    }
    
    protected function getLogFile()
    {
        $finder = new Symfony\Component\Finder\Finder();
        $finder->in($this->path);
        $finder->path("#\\.log$#");
        /** @var SplFileInfo $info */
        foreach ($finder as $info) {
            return $info->getRealPath();
        }
        throw new LogicException("Cannot find log file!");
    }
    
    protected function getErrorFile()
    {
        $finder = new Symfony\Component\Finder\Finder();
        $finder->in($this->path);
        $finder->path("#\\.error$#");
        /** @var SplFileInfo $info */
        foreach ($finder as $info) {
            return $info->getRealPath();
        }
        throw new LogicException("Cannot find error file!");
    }
    
    protected function assertStringPatternInFile($str, $file)
    {
        $fh    = fopen($file, 'r');
        $found = false;
        while ($line = fgets($fh)) {
            if (@preg_match($str, $line)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Pattern $str cannot be found in log file $file!");
    }
    
    protected function assertStringPatternNotInFile($str, $file)
    {
        $fh    = fopen($file, 'r');
        $found = false;
        while ($line = fgets($fh)) {
            if (@preg_match($str, $line)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue(!$found, "Pattern $str should not be found in log file $file!");
    }
    
}
