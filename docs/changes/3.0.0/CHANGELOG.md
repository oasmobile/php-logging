# Changelog v3.0.0

本文件记录 v3.0.0 release 的变更内容。

---

## 概述

Major version bump（2.x → 3.0.0），将项目代码风格与 PHP 8.2 最低版本要求完全对齐。这是一次纯语法层面的现代化，不涉及功能变更。

---

## Breaking Changes

- `LocalFileHandler::$path` 和 `$namePattern` 标记为 `readonly`，子类无法覆写这些属性
- 全局日志函数（`mdebug`、`minfo` 等）参数类型从无类型收紧为 `string|\Stringable`，传入 `int`、`float` 等类型将触发 TypeError
- `LocalFileHandler::$path` 属性类型从 `?string` 收窄为 `string`（非 nullable）
- `oasis/utils` 依赖从 `^2.0` 升级到 `^3.0`，下游项目如同时依赖 `oasis/utils ^2.0` 将产生版本冲突

---

## 语法现代化

### MLogging 静态属性类型声明

- 为 `$logger`（`?Logger`）、`$autoPublishingOnFatalError`（`bool`）、`$autoPublisherRegistered`（`bool`）、`$handlers`（`array`）、`$minLevelForFileTrace`（`Level`）添加显式类型声明
- 移除 `$handlers` 的 `/** @var HandlerInterface[] */` PHPDoc 注解

### 全局函数类型声明

- 8 个日志级别函数（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency`）添加参数类型 `string|\Stringable $msg, mixed ...$args` 和返回类型 `: void`
- `mdump` 添加参数类型 `mixed $obj` 和返回类型 `: string`

### LoggableApplication 构造函数

- 为 `__construct()` 的 `$name` 和 `$version` 参数添加 `string` 类型声明
- 移除冗余的 `@inheritdoc` PHPDoc 注释块

### LocalFileHandler 属性现代化

- `$namePattern` 改为 constructor promotion + `readonly`
- `$path` 改为手动声明的 `readonly string`（非 nullable），构造函数内条件赋值
- `$refreshRate` 和 `$lastFileCreationTimestamp` 保持可变，不标记 `readonly`

---

## 依赖升级

| 依赖 | 旧版本 | 新版本 |
|------|--------|--------|
| oasis/utils | ^2.0 | ^3.0 |

---

## 测试覆盖

- 全量测试：22 tests, 54 assertions, 0 failures

