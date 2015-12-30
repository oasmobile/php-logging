#! /usr/local/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:16
 */
use Monolog\Logger;
use Oasis\Mlib\Logging\ConsoleHandler;
use Oasis\Mlib\Logging\LocalErrorHandler;
use Oasis\Mlib\Logging\LocalFileHandler;
use Oasis\Mlib\Logging\MLogging;

require_once __DIR__ . "/vendor/autoload.php";
(new ConsoleHandler())->install();
(new LocalFileHandler('/tmp'))->install();
(new LocalErrorHandler('/tmp'))->install();
(
new \Oasis\Mlib\Logging\AwsSnsHandler(
    new \Oasis\Mlib\AwsWrappers\SnsPublisher(
        [
            "profile" => "minhao",
            "region"  => "us-east-1",
        ],
        "arn:aws:sns:us-east-1:315771499375:alert-log"
    ),
    "auto log handler alert!"
)
)->install();

MLogging::setMinLogLevelForFileTrace("warning");
//MLogging::setMinLogLevel(Logger::INFO);

MLogging::log(Logger::INFO, "hello world");
MLogging::log(Logger::INFO, "Good day %1\$s %1\$d %2\$s", "john", "nash");

minfo("hook %s %d", "122", 989);
mdebug("hook %s", "122");
mnotice("hook %s", "122");
mwarning("hook %s", "122");
//merror("hook %s", "122");
//mcritical("hook %s", "122");
malert("hook %s", "122");
//memergency("hook %s", "122");
