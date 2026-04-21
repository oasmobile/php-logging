<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';
(new \Oasis\Mlib\Logging\ConsoleHandler())->install();

mdebug("DEBUG level message");
minfo("INFO level message");
mnotice("NOTICE level message");
mwarning("WARNING level message");
merror("ERROR level message");
mcritical("CRITICAL level message");
malert("ALERT level message");
memergency("EMERGENCY level message");

try {
    throw new \TypeError("test type error");
} catch (\Throwable $e) {
    mtrace($e, "Caught: ");
}
