# Implementation Plan: PHP 8 Support (Release 2.0.0)

## Overview

将 `oasis/logging` 从 PHP 5.x/7.x 生态迁移到 PHP 8.2+ 生态。按组件拆分 task，composer.json 先行执行验证依赖解析，随后逐组件适配 Monolog 3.x / PHPUnit 11.x / PHP 8.x 语法。每个 top-level task 遵循 Test First（RED → GREEN）编排，首个 sub-task 为 alpha tag 递增，末尾 sub-task 为 checkpoint。

> **Regroup 说明（2026-04-21）**：Task 2 的实际执行范围超出了原计划——alpha.3 commit 中一并完成了 LocalFileHandler、LocalErrorHandler、LoggableApplication 的代码适配（原 Task 4.3、5.2、6.2 的内容）。因此对 Task 4–7 进行了 regroup：将已完成的代码变更确认与剩余的测试补充合并为新的 Task 4（补充测试 + 回归验证），原 Task 5、6 的代码变更标记为已完成并入 Task 2 范围，原 Task 8、9 重编号为 Task 5、6。同时新增 Task 7 更新 `usage.md` 中的旧常量示例。

## Tasks

- [x] 1. composer.json 依赖版本升级 + 依赖解析验证
  - [x] 1.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag（若无则从 `v2.0.0-alpha.1` 开始）
  - [x] 1.2 更新 composer.json 依赖版本约束
    - `require` 中添加 `"php": ">=8.2"`，`monolog/monolog` 改为 `^3.0`，`bramus/monolog-colored-line-formatter` 改为 `^3.0`，`oasis/utils` 改为 `^2.0`
    - `require-dev` 中 `phpunit/phpunit` 改为 `^11.0`，`symfony/console` 改为 `^7.0`，`symfony/finder` 改为 `^7.0`
    - `suggest` 中 `symfony/console` 改为 `^7.0`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_
  - [x] 1.3 执行 `composer update` 验证依赖解析
    - 确认所有依赖无冲突地解析完成，`composer.lock` 正常生成
    - _Requirements: 1.8_
  - [x] 1.4 Checkpoint: 确认 `composer update` 成功完成，依赖解析无冲突

- [x] 2. 全组件 Monolog 3.x / PHPUnit 11.x / PHP 8.x 适配
  - [x] 2.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [x] 2.2 编写 MLoggingTest 新增测试用例（RED）
    - 在 `ut/MLoggingTest.php` 中新增以下测试方法：
    - `testThrowableTracing()`：使用 `\TypeError` 调用 `mtrace()`，验证输出包含异常类名和消息 — _Requirements: 9.3, 9.4, 10.4_
    - `testLogWithLevelEnum()`：使用 `Level` enum 调用 `MLogging::log()`，验证日志正确输出 — _Requirements: 6.2_
    - `testSetMinLogLevelWithLevelEnum()`：使用 `Level::Info` 调用 `setMinLogLevel()`，验证低于该级别的日志被过滤 — _Requirements: 6.1, 10.6_
    - `testSetMinLogLevelForFileTraceWithLevelEnum()`：使用 `Level::Error` 调用 `setMinLogLevelForFileTrace()`，验证文件追踪注解的级别阈值 — _Requirements: 6.1, 10.7_
    - 同时适配测试基类和方法签名为 PHPUnit 11.x（`PHPUnit\Framework\TestCase`、`setUp(): void`、`tearDown(): void`）
    - 将现有测试中的 `Logger::DEBUG`/`Logger::INFO`/`Logger::ERROR` 替换为 `Level::Debug`/`Level::Info`/`Level::Error`
    - _Requirements: 8.1, 8.4_
  - [x] 2.3 更新 phpunit.xml schema
    - 将 `xsi:noNamespaceSchemaLocation` 从 PHPUnit 5.1 schema URL 更新为 PHPUnit 11.0 schema URL
    - 保留现有 testsuite 配置不变
    - _Requirements: 8.2, 8.3_
  - [x] 2.4 适配 MLogging::lnProcessor — LogRecord 数据结构
    - 参数类型从 `array $record` 改为 `LogRecord $record`，返回类型改为 `LogRecord`
    - 使用 `Level::includes()` 进行级别比较
    - 使用 `$record->with(channel: ..., message: ...)` 创建新实例返回
    - 将 `StringUtils::stringEndsWith()` 替换为 PHP 8 内置 `str_ends_with()`
    - 添加 `use Monolog\LogRecord;` 和 `use Monolog\Level;` import
    - `$minLevelForFileTrace` 属性类型从 `int`（`Logger::DEBUG`）改为 `Level`（`Level::Debug`）
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.7, 9.1_
  - [x] 2.5 适配 MLogging 公共接口 — Level enum
    - `setMinLogLevel($level)` 参数类型改为 `Level $level`
    - `setMinLogLevelForFileTrace($level)` 参数类型改为 `Level $level`，移除 `Logger::toMonologLevel()` 调用，直接赋值
    - `log($level, $msg, ...$args)` 参数类型改为 `string|Level $level`
    - `enableAutoPublishingOnUnexpectedShutdown($publishLevel)` 参数默认值从 `Logger::ALERT` 改为 `Level::Alert`
    - `addHandler()` 添加 `?string` 类型提示和 `: void` 返回类型
    - _Requirements: 6.1, 6.2, 7.1, 7.2, 7.3_
  - [x] 2.6 适配 MLogging.inc.php — 全局函数
    - `mtrace()` 参数类型：`\Exception` → `\Throwable`，`$logLevel` 默认值从 `Logger::INFO` 改为 `Level::Info`
    - `getExceptionDebugInfo()` 参数类型：`\Exception` → `\Throwable`，添加 `: string` 返回类型
    - 更新 `use` 语句：添加 `use Monolog\Level;`，移除不再需要的 `use Monolog\Logger;`
    - _Requirements: 6.4, 9.3, 9.4_
  - [x] 2.7 适配 ConsoleHandler — Monolog 3.x + bramus 3.x 兼容
    - `isHandling()` 方法签名：`array $record` → `LogRecord $record`，添加 `: bool` 返回类型
    - 构造函数 level 参数：`$level = Logger::DEBUG` → `Level $level = Level::Debug`
    - bramus `ColoredLineFormatter` 构造函数：将 `includeStacktraces` 作为第 6 个构造函数参数传入（`true`），移除单独的 `$colored_formatter->includeStacktraces()` 调用
    - 添加 `use Monolog\LogRecord;` 和 `use Monolog\Level;` import，移除 `use Monolog\Logger;`
    - _Requirements: 2.5, 3.1, 3.2, 3.3_
  - [x] 2.8 适配 LocalFileHandler — Monolog 3.x 兼容
    - `write()` 方法签名：`array $record` → `LogRecord $record`，添加 `: void` 返回类型
    - 构造函数 level 参数：`$level = Logger::DEBUG` → `Level $level = Level::Debug`，添加 `?string` 和 `string` 类型提示
    - 添加 `use Monolog\LogRecord;` 和 `use Monolog\Level;` import，移除 `use Monolog\Logger;`
    - _Requirements: 2.6, 4.1, 4.2, 9.2_
  - [x] 2.9 适配 LocalErrorHandler — FingersCrossedHandler 适配
    - 移除 `use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;` import
    - 移除 `use Monolog\Logger;` import，添加 `use Monolog\Level;` import
    - 构造函数参数类型：`$level = Logger::DEBUG` → `Level $level = Level::Debug`，`$triggerLevel = Logger::ERROR` → `Level $triggerLevel = Level::Error`
    - 移除 `ErrorLevelActivationStrategy`，`parent::__construct()` 直接传入 `$triggerLevel`（`Level` enum）
    - _Requirements: 5.1, 5.2_
  - [x] 2.10 适配 LoggableApplication — Level enum 常量替换
    - 将 `Logger::WARNING` → `Level::Warning`、`Logger::NOTICE` → `Level::Notice`、`Logger::INFO` → `Level::Info`、`Logger::DEBUG` → `Level::Debug`
    - 添加 `use Monolog\Level;` import，移除 `use Monolog\Logger;` import
    - _Requirements: 6.3_
  - [x] 2.11 Checkpoint: 运行 `vendor/bin/phpunit`，确认所有测试通过

- [x] 3. ConsoleHandler 非 CLI 环境测试
  - [x] 3.1 编写 testConsoleHandlerNotHandlingInNonCli 测试用例
    - 在 `ut/MLoggingTest.php` 中新增 `testConsoleHandlerNotHandlingInNonCli()`：验证 ConsoleHandler 在非 CLI 环境下 `isHandling()` 返回 false
    - _Requirements: 3.2_
  - [x] 3.2 Checkpoint: 运行 `vendor/bin/phpunit`，确认所有测试通过（包括 testConsoleHandlerNotHandlingInNonCli）

- [-] 4. 补充测试 + 全量回归验证
  - [x] 4.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [x] 4.2 编写 testLocalFileHandlerRefreshRate 测试用例
    - 在 `ut/MLoggingTest.php` 中新增 `testLocalFileHandlerRefreshRate()`：验证 `setRefreshRate()` 后文件名刷新逻辑正常工作
    - _Requirements: 4.2_
  - [x] 4.3 编写 testHandlerReinstallation 测试用例
    - 在 `ut/MLoggingTest.php` 中新增 `testHandlerReinstallation()`：验证同名 handler 重复 `install()` 后日志仍正确输出（覆盖 `setHandlers` 路径）
    - _Requirements: 7.2, 10.1_
  - [x] 4.4 全量回归验证
    - 运行 `vendor/bin/phpunit`，确认所有测试（现有 13 个 + 新增 2 个 = 15 个）全部通过
    - 确认零失败、零错误、零 PHPUnit 框架弃用警告、零 PHP 8.x 弃用警告
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_
  - [x] 4.5 Checkpoint: 确认 `vendor/bin/phpunit` 输出 OK 且零失败零错误，如有问题请向用户确认

- [~] 5. 更新 usage.md 文档
  - [ ] 5.1 更新 `docs/manual/usage.md` 中的代码示例
    - 将 `Logger::INFO`、`Logger::ERROR`、`Logger::WARNING` 等旧常量替换为 `Level::Info`、`Level::Error`、`Level::Warning` 等
    - 将 `\Exception` 相关示例更新为 `\Throwable`（如适用）
    - 更新 import 示例（`use Monolog\Level;` 替代 `use Monolog\Logger;`）
    - _Requirements: 6.1, 6.2_
  - [ ] 5.2 Checkpoint: 确认 usage.md 中的代码示例与 2.0.0 API 一致

- [~] 6. 手工测试
  - [ ] 6.1 编写手工测试 checklist
    - 在 `docs/manual/` 下创建手工测试文档，覆盖以下场景：
    - 使用 `test.php` 或临时脚本验证 8 个全局日志函数（`mdebug` ~ `memergency`）在 PHP 8.2+ 环境下的控制台彩色输出
    - 验证 `mtrace()` 传入 `\TypeError` 实例时的输出格式
    - 验证 `LoggableApplication` 在不同 verbosity 级别下的日志输出行为
    - 验证 `LocalFileHandler` 文件名模式和 refreshRate 刷新行为
    - _Requirements: 3.1, 6.3, 9.3, 10.3_
  - [ ] 6.2 Checkpoint: 确认手工测试 checklist 已创建，如有问题请向用户确认

- [~] 7. Code Review
  - [ ] 7.1 执行 Code Review
    - 委托给 code-reviewer agent，基于当前分支的 diff 进行 code review
    - _Requirements: 全部_
  - [ ] 7.2 Checkpoint: 确认 code review 完成，所有发现的问题已修复，如有问题请向用户确认

## Issues

（初始为空，stabilize 阶段新发现的 issue 记录于此）

## Execution Notes

- 执行时须遵循 `spec-execution.md` 中的规范
- **Commit 时机**：每个 top-level task 的 checkpoint 通过后进行一次 commit
- **Alpha tag 规则**：仅在有代码或测试变更的 top-level task 开始时打 alpha tag，格式为 `v2.0.0-alpha.N`（N 从当前最大值递增）；纯文档 task（手工测试 checklist、usage.md 更新）和 Code Review 不打 alpha tag
- **PHP 环境要求**：执行环境须为 PHP 8.2+，可通过 `php -v` 确认
- **不做架构重构**：仅修改兼容性要求的部分，不顺手重构

## Socratic Review

### 审查 log

**Q1: regroup 后的 tasks 是否仍完整覆盖 design 中的所有实现项？**

A1: 是。Task 2 扩展为全组件适配（2.4–2.10 覆盖 design Section 1–8、10–11），Task 3 覆盖 ConsoleHandler 非 CLI 测试，Task 4 覆盖剩余新增测试和全量回归（design Section 9），Task 5 覆盖文档更新，Task 6 覆盖手工测试，Task 7 覆盖 Code Review。`MLoggingHandlerTrait` 无需变更。

**Q2: 已完成 task 的标记是否与 git 历史一致？**

A2: 是。Task 1 对应 alpha.1–alpha.2 commit，Task 2 对应 alpha.3 commit（实际范围包含了原 Task 2–6 的所有代码变更），Task 3 对应 alpha.3 之后的测试 commit。regroup 说明中已记录这一偏差。

**Q3: 剩余 task 的依赖顺序是否正确？**

A3: 是。Task 4（补充测试 + 回归）依赖 Task 1–3 已完成的代码适配。Task 5（文档更新）在 Task 4 之后。Task 6（手工测试）和 Task 7（Code Review）在所有实现和文档 task 之后，Code Review 作为最后一个 task 可覆盖全部变更。

**Q4: requirement 追溯是否仍完整？**

A4: 是。原 Task 4.3（Req 2.6, 4.1, 4.2, 9.2）、Task 5.2（Req 5.1, 5.2）、Task 6.2（Req 6.3）的 requirement 覆盖现由 Task 2.8–2.10 承接。新增 Task 7 覆盖 usage.md 中的 API 示例更新（Req 6.1, 6.2）。

**Q5: alpha tag 策略调整是否合理？**

A5: 是。原计划每个 top-level task 打一个 alpha tag，但实际执行中 Task 2 的 commit 已包含全部代码变更。剩余 task 中仅 Task 4（新增测试）有代码变更需要 tag，纯文档和 review task 不需要。

**Q6: design Gatekeep Log 中的 4 个 CR 决策是否仍体现？**

A6: 是。Q1（按组件拆分）→ Task 2 的 sub-task 仍按组件拆分（2.4–2.10）；Q2（composer 先行）→ Task 1 先行执行；Q3（PHPUnit 内置 mock）→ Task 3.1 使用匿名子类模拟非 CLI 环境；Q4（仅替换一处 str_ends_with）→ 仅 Task 2.4 中替换。

## Gatekeep Log

**校验时间**: 2025-07-15（第二次校验）
**校验结果**: ⚠️ 已修正后通过

### 修正项

**第一次校验（2025-07-15）**
- [结构] 补充了 `## Issues` section（Release spec 必需，初始为空，用于 stabilize 阶段记录新发现的 issue）
- [结构] 补充了 `## Socratic Review` section，覆盖 design 覆盖度、依赖排序、粒度、checkpoint、手工测试、CR 决策体现六个方面
- [内容] Task 9（Code Review）移除了展开的 review checklist，改为"委托给 code-reviewer agent"的等效表述

**第二次校验（2025-07-15）**
- [内容] Execution Notes 补充了明确的 commit 时机说明（"每个 top-level task 的 checkpoint 通过后进行一次 commit"），此前仅引用了 `spec-execution.md` 但未显式说明 commit 时机

**Regroup（2026-04-21）**
- [结构] Task 4–9 重组为 Task 4–7，反映实际执行进度：原 Task 4.3、5.2、6.2 的代码变更已在 Task 2 的 alpha.3 commit 中完成
- [结构] Task 2 扩展为全组件适配（新增 2.7–2.11），合并原 Task 2–6 的代码变更
- [结构] 原 Task 7（回归测试）与原 Task 4.2 合并为新 Task 4（补充测试 + 全量回归）
- [内容] 新增 Task 7（更新 usage.md），覆盖文档中旧常量示例的更新
- [内容] Execution Notes 中 alpha tag 规则调整为仅在有代码/测试变更的 task 打 tag
- [内容] 移除了已不适用的 Execution Notes 条目（composer 先行、test-first 编排、import 清理、oasis/utils 兼容性——这些已在已完成的 task 中执行）
- [内容] Socratic Review 重写，覆盖 regroup 后的覆盖度、git 历史一致性、依赖顺序、requirement 追溯、alpha tag 策略、CR 决策体现
