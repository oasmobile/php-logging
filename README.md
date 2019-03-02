# MLogger - logging for Oasis projects

oasis/logging provides classes and functions used to write logs.

The component can be referred to as **MLog** or **MLogger** in short.

There are a list of log handlers provided by default:

- Console Handler
- Local File Handler
- Local Error Handler
- AWS SNS Handler

MLog adhere strictly to the [PSR-3] standard and uses [monolog/monolog]
as its underlying implementation.


## Installation

Install the latest version with command below:

```bash
$ composer require oasis/logging
```

## Basic Usage

It is easy to use MLogger withour any configuration:


```php
<?php

// quick logging methods as global functions
mdebug("This is a debug message");
minfo("This is a info-level log message");
mnotice("Notice is also available");
mwarning("WARNING: something is possibly wrong!");
merror("ERROR: something is definitely wrong!");
mcritical("This is CRITICAL!");
malert("ALERT! ALERT!");
memergency("URGENT!");

// sprintf compatible logging
$name = 'test';
mdebug("The object %s is being processed", $name);

```

## Using the Logger directly

A `Monolog\Logger` can be used directly. This provides the freedom to
integrate MLog with other [PSR-3] compatible components who need logging
tools.

```php
<?php

use Monolog\Logger as MonoLogger;
use Oasis\Mlib\Logging\MLogging;

/** @var MonoLogger $logger */
$logger = MLogging::getLogger();

// the $logger object can then be injected into any place in need of a MonoLogger

```

## Add Handler

Thanks to the mature community of [monolog/monolog], MLog can take use
of all the existing Handlers for monolog.

In addition, you can also write your own handler that implements the
`Monolog\Handler\HandlerInterface` interface.

Adding a handler to MLog is as simple as:

```php
<?php

use Oasis\Mlib\Logging\ConsoleHandler;
use Oasis\Mlib\Logging\LocalFileHandler;
use Oasis\Mlib\Logging\MLogging;

MLogging::addHandler(new ConsoleHandler());

// or

(new LocalFileHandler())->install();

```

## Use auto-rotated-timestamp for file handler

The `Oasis\Mlib\Logging\LocalFileHandler` can be easily configured to rotate its filename based on time changes. This is a very useful feature if your script is a longlive script which runs more than just a few seconds. Provided the correct name pattern, the local filename will rotate each time the preset interval has passed. Here is an example:

```php
<?php
use Oasis\Mlib\Logging\LocalFileHandler;

$lfh = new LocalFileHandler('/my-log-path', '%date%/%hour%-%minute%-%script%.log');

// This tells the filename to rotate every 30 minutes
$lfh->setRefreshRate(1800);

```

The supported name patterns are:

|pattern | meaning|
|:--- |: ---|
%date% | substituted by date of now, in the format: yyyymmdd
%hour% | substituted by hour of now, in the format: HH (00-23)
%minute% | substituted by minute of now, in the format: ii (00-59)
%second% | substituted by second of now, in the format: ss (00-59)
%script% | substituted by name of current script, filename only without directory names
%pid% | substituted by current process ID



## Using AWS SNS to auto handle alert

The `Oasis\Mlib\Logging\AwsSnsHandler` provides a handler which is only
processed when log above certain level (default to ALERT) is triggered.

It is an especially useful tool under production environment. It buffers
all the logs and discard them if the script exits without error. If
anything like a fatal error has put the script to exit abnormally, the
handler will publish all the buffer as well as an ALERT log to the
specified [AWS SNS] topic.

The AwsSnsHandler is an optional feature and it depends on the
[oasis/aws-wrappers] component:

```bash
$ composer require oasis/aws-wrappers
```

To use the AwsSnsHandler, try the code below:

```php
<?php

use Oasis\Mlib\Logging\AwsSnsHandler;
use Oasis\Mlib\AwsWrappers\SnsPublisher;

/** @var string $the_topic_arn      the topic's AWS Resource Name */
/** @var array $some_aws_config     config data to initialize an Sns Publisher */
$publisher = new SnsPublisher($some_aws_config, $the_topic_arn);

$snsHandler = new AwsSnsHandler($publisher, 'This is the subject');
$snsHandler->enableAutoPublishingOnFatalError();
$snsHandler->install();

```

[PSR-3]: http://www.php-fig.org/psr/psr-3/
[monolog/monolog]: https://github.com/Seldaek/monolog
[oasis/aws-wrappers]: https://github.com/oasmobile/php-aws-wrappers
[AWS SNS]: http://docs.aws.amazon.com/sns/latest/dg/GettingStarted.html
