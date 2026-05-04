# Architecture

## 概述

oasis/logging（MLogging）是基于 monolog/monolog 的日志封装库，提供简化的日志配置与使用方式，遵循 PSR-3 标准。

## 技术选型

| 项目 | 选型 |
|------|------|
| 语言 | PHP（最低 8.5） |
| 底层日志库 | monolog/monolog ^3.0 |
| 彩色输出 | bramus/monolog-colored-line-formatter ^3.0 |
| 工具依赖 | oasis/utils ^3.1 |
| 测试框架 | phpunit/phpunit ^13.0 |
| 包管理 | Composer |
| 命名空间 | `Oasis\Mlib\Logging` |
| 许可证 | MIT |

## 分层结构

```
MLogging（静态门面）
├── ConsoleHandler        ← StreamHandler（stderr）
├── LocalFileHandler      ← StreamHandler（文件）
├── LocalErrorHandler     ← FingersCrossedHandler（包装 LocalFileHandler）
└── LoggableApplication   ← Symfony Console Application 集成
```

- `MLogging`：静态门面类，管理 Logger 实例与 Handler 注册
- `MLoggingHandlerTrait`：为 Handler 提供 `install()` 快捷注册方法
- `MLogging.inc.php`：全局快捷函数（`mdebug`、`minfo` 等），通过 Composer autoload files 自动加载

## Handler 体系

### ConsoleHandler

- 继承 `Monolog\Handler\StreamHandler`
- 输出到 `php://stderr`
- 使用 `ColoredLineFormatter` 彩色格式化
- 仅在 CLI 环境下生效（`CommonUtils::isRunningFromCommandLine()`）

### LocalFileHandler

- 继承 `Monolog\Handler\StreamHandler`
- 支持基于时间的文件名模式（`%date%`、`%hour%`、`%minute%`、`%second%`、`%script%`、`%pid%`）
- 支持 refreshRate 定时轮转文件名
- 默认路径：`sys_get_temp_dir()`
- 默认模式：`%date%/%script%.log`
- 属性声明：`$path` 为手动声明的 `readonly string`（非 nullable，构造函数内条件赋值）；`$namePattern` 通过 constructor promotion 声明为 `readonly string`；`$refreshRate` 和 `$lastFileCreationTimestamp` 保持可变

### LocalErrorHandler

- 继承 `Monolog\Handler\FingersCrossedHandler`
- 包装 LocalFileHandler，仅在触发级别（默认 ERROR）时输出缓冲区全部日志
- 构造函数直接传入 `Level` enum 作为触发级别（Monolog 3.x 不再使用 `ErrorLevelActivationStrategy`）
- 默认文件模式：`%date%/%script%.error`

## 日志处理器（Processor）

`lnProcessor`：接受 `LogRecord` 参数，自动在日志消息末尾追加调用位置（文件名:行号），通过 `debug_backtrace` 回溯调用栈，使用 `LogRecord::with()` 返回新实例。可通过 `setMinLogLevelForFileTrace(Level)` 控制生效的最低级别。

## 自动发布机制

`enableAutoPublishingOnUnexpectedShutdown()`：注册 shutdown function，在 fatal error 时自动记录一条指定级别的日志。依赖 `CommonUtils::monitorMemoryUsage()`。

## 可选集成

### Symfony Console（LoggableApplication）

- 继承 `Symfony\Component\Console\Application`
- 根据 output verbosity 自动设置 ConsoleHandler 的日志级别
- 需要 `symfony/console` ^8.0（require-dev / suggest）

### AWS SNS Handler

- README 中提及但源码不在本仓库
- 依赖 `oasis/aws-wrappers` ^2.7（suggest）

## 测试策略

- 框架：PHPUnit 13.x
- PBT：giorgiosironi/eris ^1.1（property-based testing）
- 测试文件：`ut/MLoggingTest.php`、`ut/LoggableApplicationTest.php`、`ut/CoverageBoostTest.php`、`ut/PropertyBasedTest.php`
- 测试方式：写入临时目录，通过文件内容正则匹配验证日志输出；PBT 通过随机生成输入验证日志系统的不变性质
- 运行命令：`vendor/bin/phpunit`
