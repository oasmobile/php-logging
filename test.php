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
use Oasis\Mlib\Logging\MLogging;

require_once __DIR__ . "/vendor/autoload.php";
(new ConsoleHandler())->install();
$lfh = (new LocalFileHandler('/tmp', '%date%/%hour%-%minute%-%second%-%script%.log'))->install();
(new LocalErrorHandler('/tmp'))->install();

$lfh->setRefreshRate(3);

for ($i = 0; $i < 20; ++$i) {
    mdebug("hi");
    sleep(1);
}


