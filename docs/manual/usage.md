# MLogging 使用手册

## 安装

```bash
composer require oasis/logging
```

## 快速开始

库通过 Composer autoload files 自动加载全局函数，无需额外配置即可使用：

```php
mdebug("This is a debug message");
minfo("Info-level log");
mnotice("Notice");
mwarning("WARNING: something is possibly wrong!");
merror("ERROR: something is definitely wrong!");
mcritical("Critical!");
malert("Alert!");
memergency("Emergency!");
```

支持 sprintf 风格的格式化：

```php
$name = 'test';
mdebug("The object %s is being processed", $name);
```

未手动安装 Handler 时，首次调用会自动安装默认 Handler：
- CLI 环境 → ConsoleHandler（输出到 stderr）
- 非 CLI 环境 → LocalFileHandler（写入临时目录）

## 异常追踪

```php
try {
    throw new \RuntimeException("something went wrong", 99);
} catch (\Exception $e) {
    mtrace($e);                          // 默认 INFO 级别
    mtrace($e, "上下文说明: ", Logger::ERROR); // 自定义级别
}
```

## 直接使用 Logger

获取底层 `Monolog\Logger` 实例，可注入到任何 PSR-3 兼容组件：

```php
use Oasis\Mlib\Logging\MLogging;

$logger = MLogging::getLogger();
$logger->info("direct call", ['key' => 'value']);
```

## Handler 配置

### 手动安装 Handler

```php
use Oasis\Mlib\Logging\MLogging;
use Oasis\Mlib\Logging\ConsoleHandler;
use Oasis\Mlib\Logging\LocalFileHandler;
use Oasis\Mlib\Logging\LocalErrorHandler;

// 方式一：通过 install()
(new ConsoleHandler())->install();
(new LocalFileHandler('/var/log/myapp'))->install();
(new LocalErrorHandler('/var/log/myapp'))->install();

// 方式二：通过 addHandler()
MLogging::addHandler(new ConsoleHandler(), 'console');
```

`install()` 使用类名作为 Handler 名称注册，同类 Handler 重复 install 会替换前一个。

### LocalFileHandler 文件名模式

构造函数：`new LocalFileHandler($path, $namePattern, $level)`

| 占位符 | 含义 | 格式 |
|--------|------|------|
| `%date%` | 当前日期 | `Ymd`（如 20260421） |
| `%hour%` | 当前小时 | `HH`（00-23） |
| `%minute%` | 当前分钟 | `ii`（00-59） |
| `%second%` | 当前秒 | `ss`（00-59） |
| `%script%` | 脚本文件名 | 不含目录和 .php 后缀 |
| `%pid%` | 进程 ID | 数字 |

默认模式：`%date%/%script%.log`

### 文件名定时轮转

适用于长时间运行的脚本：

```php
$lfh = new LocalFileHandler('/my-log-path', '%date%/%hour%-%minute%-%script%.log');
$lfh->setRefreshRate(1800); // 每 30 分钟轮转
$lfh->install();
```

### LocalErrorHandler

仅在触发级别（默认 ERROR）时才输出，输出时包含此前缓冲的全部日志：

```php
// 默认：触发级别 ERROR，缓冲 1000 条
(new LocalErrorHandler('/var/log/myapp'))->install();

// 自定义
(new LocalErrorHandler(
    '/var/log/myapp',
    '%date%/%script%.error',
    Logger::DEBUG,      // 缓冲的最低级别
    Logger::CRITICAL,   // 触发级别
    500                 // 缓冲上限
))->install();
```

## 日志级别控制

```php
use Monolog\Logger;
use Oasis\Mlib\Logging\MLogging;

// 设置所有 Handler 的最低级别
MLogging::setMinLogLevel(Logger::WARNING);

// 按名称或正则匹配设置特定 Handler
MLogging::setMinLogLevel(Logger::DEBUG, 'console');
MLogging::setMinLogLevel(Logger::ERROR, '/file/i');
```

## 文件追踪（调用位置标注）

日志消息末尾会自动追加 `(filename:line)`，标注实际调用位置。

控制生效的最低级别：

```php
// 仅 ERROR 及以上才追加文件位置
MLogging::setMinLogLevelForFileTrace(Logger::ERROR);
```

`setMinLogLevel()` 在不指定 Handler 名称时会同步设置文件追踪级别。

## Fatal Error 自动发布

注册 shutdown function，在 fatal error 时自动记录日志：

```php
MLogging::enableAutoPublishingOnUnexpectedShutdown();          // 默认 ALERT 级别
MLogging::enableAutoPublishingOnUnexpectedShutdown(Logger::EMERGENCY);

// 关闭
MLogging::disableAutoPublishingOnUnexpectedShutdown();
```

## Symfony Console 集成

使用 `LoggableApplication` 替代 `Application`，自动根据 verbosity 设置日志级别：

```php
use Oasis\Mlib\Logging\LoggableApplication;

$app = new LoggableApplication('myapp', '1.0.0');
$app->run();
```

| Verbosity 参数 | 日志级别 |
|----------------|----------|
| `-q` (quiet) | 不安装 Handler |
| 无参数 (normal) | WARNING |
| `-v` (verbose) | NOTICE |
| `-vv` (very verbose) | INFO |
| `-vvv` (debug) | DEBUG |

## 辅助函数

```php
// 对象转字符串（print_r）
$str = mdump($someObject);
```
