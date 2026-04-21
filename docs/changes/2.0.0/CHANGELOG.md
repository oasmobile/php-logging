# Changelog v2.0.0

本文件记录 v2.0.0 release 的变更内容。

---

## 概述

Major version bump（1.x → 2.0.0），将项目从 PHP 5.x/7.x 生态升级到 PHP 8.2+ 生态。这是一次纯兼容性升级，不引入新功能。

---

## Breaking Changes

- PHP 最低版本要求从 5.x 提升到 8.2
- `setMinLogLevel()` 参数类型从 `int` 改为 `Monolog\Level` enum
- `setMinLogLevelForFileTrace()` 参数类型从 `int` 改为 `Monolog\Level` enum
- `enableAutoPublishingOnUnexpectedShutdown()` 的 `$publishLevel` 参数从 `int` 改为 `Monolog\Level` enum
- `MLogging::log()` 的 `$level` 参数从 `int|string` 改为 `string|Level`
- Handler 构造函数的 `$level` 参数从 `int`（`Logger::DEBUG` 等）改为 `Level` enum（`Level::Debug` 等）
- `mtrace()` 的 `$logLevel` 参数默认值从 `Logger::INFO` 改为 `Level::Info`

---

## 依赖升级

| 依赖 | 旧版本 | 新版本 |
|------|--------|--------|
| PHP | >=5.x | >=8.2 |
| monolog/monolog | ^1.17 | ^3.0 |
| bramus/monolog-colored-line-formatter | ^2.0 | ^3.0 |
| oasis/utils | ^1.6 | ^2.0 |
| phpunit/phpunit | ^5.1 | ^11.0 |
| symfony/console | ^3.0 | ^7.0 |
| symfony/finder | ^3.0 | ^7.0 |

---

## 内部适配

- `MLogging::lnProcessor`：参数从 `array $record` 改为 `LogRecord $record`，使用 `LogRecord::with()` 创建新实例
- `ConsoleHandler::isHandling`：参数从 `array $record` 改为 `LogRecord $record`
- `LocalFileHandler::write`：参数从 `array $record` 改为 `LogRecord $record`
- `LocalErrorHandler`：移除 `ErrorLevelActivationStrategy`，直接传入 `Level` enum 作为触发级别
- `LoggableApplication`：`Logger::WARNING` 等常量替换为 `Level` enum
- `mtrace()` / `getExceptionDebugInfo()`：参数类型从 `\Exception` 扩展为 `\Throwable`
- `StringUtils::stringEndsWith()` 替换为 PHP 8 内置 `str_ends_with()`
- `addHandler()` 增加 `ProcessableHandlerInterface` 检查，适配 Monolog 2+ 接口拆分

---

## 测试覆盖

- 测试框架从 PHPUnit 5.x 升级到 PHPUnit 11.x
- 测试基类从 `PHPUnit_Framework_TestCase` 迁移到 `PHPUnit\Framework\TestCase`
- 新增测试用例：`testThrowableTracing`、`testLogWithLevelEnum`、`testSetMinLogLevelWithLevelEnum`、`testSetMinLogLevelForFileTraceWithLevelEnum`、`testConsoleHandlerNotHandlingInNonCli`、`testLocalFileHandlerRefreshRate`、`testHandlerReinstallation`、`testLoggableApplicationVerbosity`
- 全量测试：22 tests, 54 assertions, 0 failures

---

## 文档

- 更新 `docs/manual/usage.md`：代码示例中的 `Logger::INFO` 等旧常量替换为 `Level::Info` 等
- 创建手工测试 checklist：`docs/manual/manual-testing-2.0.0.md`
