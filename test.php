#! /usr/local/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-12-04
 * Time: 17:16
 */
use Oasis\Mlib\Logging\ConsoleHandler;
use Oasis\Mlib\Logging\LocalErrorHandler;
use Oasis\Mlib\Logging\LocalFileHandler;

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
    "new auto log handler alert!",
    3
)
)->enableAutoPublishingOnFatalError()->install();

$s = str_repeat(' ', 1024 * 1024);

$a = $b = '';
for ($i = 0; $i < 100000; ++$i) {
    $a .= $s;
}
