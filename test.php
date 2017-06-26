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
use Oasis\Mlib\Logging\ShutdownFallbackHandler;

require_once __DIR__ . "/vendor/autoload.php";
(new ConsoleHandler())->install();
(new LocalFileHandler('/tmp'))->install();
(new LocalErrorHandler('/tmp'))->install();

$alertHandler = new LocalFileHandler('/tmp/alerts');
$shutdown     = new ShutdownFallbackHandler($alertHandler);
MLogging::addHandler($shutdown);

