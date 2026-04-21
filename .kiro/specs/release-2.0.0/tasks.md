# Implementation Plan: PHP 8 Support (Release 2.0.0)

## Overview

将 `oasis/logging` 从 PHP 5.x/7.x 生态迁移到 PHP 8.2+ 生态。按组件拆分 task，composer.json 先行执行验证依赖解析，随后逐组件适配 Monolog 3.x / PHPUnit 11.x / PHP 8.x 语法。每个 top-level task 遵循 Test First（RED → GREEN）编排，首个 sub-task 为 alpha tag 递增，末尾 sub-task 为 checkpoint。

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

- [x] 2. MLogging.php + MLogging.inc.php — 核心门面适配
  - [x] 2.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [x] 2.2 编写 MLoggingTest 新增测试用例（RED）
    - 在 `ut/MLoggingTest.php` 中新增以下测试方法（此时测试应编译失败或运行失败，因为实现尚未修改）：
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
    - 使用 `$record->level->value` 读取级别进行比较（或 `Level::includes()`）
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
    - 更新 `use` 语句：添加 `use Monolog\Level;`，移除不再需要的 `use Monolog\Logger;`（如果 Logger 不再被引用）
    - _Requirements: 6.4, 9.3, 9.4_
  - [x] 2.7 Checkpoint: 运行 `vendor/bin/phpunit`，确认 lnProcessor 相关测试通过（testLocalFileHandler、testFileTraceSwitch、testContext、testExceptionTracing、testThrowableTracing 以及新增的 Level enum 测试），如有问题请向用户确认

- [-] 3. ConsoleHandler.php — Monolog 3.x + bramus 3.x 兼容
  - [x] 3.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [x] 3.2 编写 testConsoleHandlerNotHandlingInNonCli 测试用例（RED）
    - 在 `ut/MLoggingTest.php` 中新增 `testConsoleHandlerNotHandlingInNonCli()`：使用 PHPUnit 内置 mock 模拟 `CommonUtils::isRunningFromCommandLine()` 返回 false，验证 ConsoleHandler 的 `isHandling()` 返回 false
    - _Requirements: 3.2_
  - [x] 3.3 适配 ConsoleHandler
    - `isHandling()` 方法签名：`array $record` → `LogRecord $record`，添加 `: bool` 返回类型
    - 构造函数 level 参数：`$level = Logger::DEBUG` → `Level $level = Level::Debug`
    - bramus `ColoredLineFormatter` 构造函数：将 `includeStacktraces` 作为第 6 个构造函数参数传入（`true`），移除单独的 `$colored_formatter->includeStacktraces()` 调用
    - 添加 `use Monolog\LogRecord;` 和 `use Monolog\Level;` import，移除 `use Monolog\Logger;`
    - _Requirements: 2.5, 3.1, 3.2, 3.3_
  - [-] 3.4 Checkpoint: 运行 `vendor/bin/phpunit`，确认所有已通过的测试仍然通过（包括 testConsoleHandlerNotHandlingInNonCli），如有问题请向用户确认

- [ ] 4. LocalFileHandler.php — Monolog 3.x 兼容
  - [ ] 4.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 4.2 编写 testLocalFileHandlerRefreshRate 测试用例（RED）
    - 在 `ut/MLoggingTest.php` 中新增 `testLocalFileHandlerRefreshRate()`：验证 `setRefreshRate()` 后文件名刷新逻辑正常工作
    - _Requirements: 4.2_
  - [ ] 4.3 适配 LocalFileHandler
    - `write()` 方法签名：`array $record` → `LogRecord $record`，添加 `: void` 返回类型
    - 构造函数 level 参数：`$level = Logger::DEBUG` → `Level $level = Level::Debug`，添加 `?string` 和 `string` 类型提示
    - 添加 `use Monolog\LogRecord;` 和 `use Monolog\Level;` import，移除 `use Monolog\Logger;`
    - _Requirements: 2.6, 4.1, 4.2, 9.2_
  - [ ] 4.4 Checkpoint: 运行 `vendor/bin/phpunit`，确认 testLocalFileHandler 和 testLocalFileHandlerRefreshRate 通过，如有问题请向用户确认

- [ ] 5. LocalErrorHandler.php — FingersCrossedHandler 适配
  - [ ] 5.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 5.2 适配 LocalErrorHandler
    - 移除 `use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;` import
    - 移除 `use Monolog\Logger;` import
    - 添加 `use Monolog\Level;` import
    - 构造函数参数类型：`$level = Logger::DEBUG` → `Level $level = Level::Debug`，`$triggerLevel = Logger::ERROR` → `Level $triggerLevel = Level::Error`
    - 移除 `$activationStrategy = new ErrorLevelActivationStrategy($triggerLevel)` 行
    - `parent::__construct()` 第二个参数从 `$activationStrategy` 改为直接传入 `$triggerLevel`（`Level` enum）
    - _Requirements: 5.1, 5.2_
  - [ ] 5.3 Checkpoint: 运行 `vendor/bin/phpunit`，确认 testErrorHandlerWithContent 和 testErrorHandlerWithoutContent 通过，如有问题请向用户确认

- [ ] 6. LoggableApplication.php — Level enum 常量替换
  - [ ] 6.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 6.2 适配 LoggableApplication
    - 将 `Logger::WARNING` → `Level::Warning`、`Logger::NOTICE` → `Level::Notice`、`Logger::INFO` → `Level::Info`、`Logger::DEBUG` → `Level::Debug`
    - 添加 `use Monolog\Level;` import，移除 `use Monolog\Logger;` import
    - _Requirements: 6.3_
  - [ ] 6.3 Checkpoint: 运行 `vendor/bin/phpunit`，确认所有测试通过，如有问题请向用户确认

- [ ] 7. MLoggingTest.php — Handler 相关新增测试 + 全量回归
  - [ ] 7.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 7.2 编写剩余新增测试用例
    - `testHandlerReinstallation()`：验证同名 handler 重复 `install()` 后日志仍正确输出（覆盖 `setHandlers` 路径）— _Requirements: 7.2_
    - _Requirements: 7.2, 10.1_
  - [ ] 7.3 全量回归验证
    - 运行 `vendor/bin/phpunit`，确认所有测试（现有 8 个 + 新增 7 个 = 15 个）全部通过
    - 确认零失败、零错误、零 PHPUnit 框架弃用警告、零 PHP 8.x 弃用警告
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_
  - [ ] 7.4 Checkpoint: 确认 `vendor/bin/phpunit` 输出 OK 且零失败零错误，如有问题请向用户确认

- [ ] 8. 手工测试
  - [ ] 8.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 8.2 编写手工测试 checklist
    - 在 `docs/manual/` 下创建手工测试文档，覆盖以下场景：
    - 使用 `test.php` 或临时脚本验证 8 个全局日志函数（`mdebug` ~ `memergency`）在 PHP 8.2+ 环境下的控制台彩色输出
    - 验证 `mtrace()` 传入 `\TypeError` 实例时的输出格式
    - 验证 `LoggableApplication` 在不同 verbosity 级别下的日志输出行为
    - 验证 `LocalFileHandler` 文件名模式和 refreshRate 刷新行为
    - _Requirements: 3.1, 6.3, 9.3, 10.3_
  - [ ] 8.3 Checkpoint: 确认手工测试 checklist 已创建，如有问题请向用户确认

- [ ] 9. Code Review
  - [ ] 9.1 Increment alpha tag
    - 查询已有 `v2.0.0-alpha*` tag，取最大序号 +1，打新 tag
  - [ ] 9.2 执行 Code Review
    - 委托给 code-reviewer agent，基于当前分支的 diff 进行 code review
    - _Requirements: 全部_
  - [ ] 9.3 Checkpoint: 确认 code review 完成，所有发现的问题已修复，如有问题请向用户确认

## Issues

（初始为空，stabilize 阶段新发现的 issue 记录于此）

## Execution Notes

- 执行时须遵循 `spec-execution.md` 中的规范
- **Commit 时机**：每个 top-level task 的 checkpoint 通过后进行一次 commit
- **composer update 先行**：Task 1 必须最先执行并验证通过，后续 task 依赖已解析的依赖环境
- **Test First 编排**：Task 2 中先编写测试（2.2），再修改实现（2.4–2.6）；Task 4 中先编写测试（4.2），再修改实现（4.3）
- **Alpha tag 规则**：每个 top-level task 开始时打 alpha tag，格式为 `v2.0.0-alpha.N`（N 从 1 递增）
- **PHP 环境要求**：执行环境须为 PHP 8.2+，可通过 `php -v` 确认
- **import 清理**：修改每个文件时注意清理不再需要的 `use Monolog\Logger;` import，添加 `use Monolog\Level;` 和 `use Monolog\LogRecord;`
- **不做架构重构**：仅修改兼容性要求的部分，不顺手重构
- **`oasis/utils` 兼容性**：假设 `oasis/utils ^2.0` 提供 PHP 8 兼容版本，如 `composer update` 解析失败需向用户确认

## Socratic Review

### 审查 log

**Q1: tasks 是否完整覆盖了 design 中的所有实现项？有无遗漏的模块或接口？**

A1: 是。design 中 11 个 section 逐一对应：Section 1（composer.json）→ Task 1；Section 2 + 11（lnProcessor + 级别比较）→ Task 2.4；Section 3（ConsoleHandler）→ Task 3；Section 4（LocalFileHandler）→ Task 4；Section 5（LocalErrorHandler）→ Task 5；Section 6（MLogging 级别配置）→ Task 2.5；Section 7（LoggableApplication）→ Task 6；Section 8（全局函数）→ Task 2.6；Section 9（MLoggingTest）→ Task 2.2 + 3.2 + 4.2 + 7.2；Section 10（phpunit.xml）→ Task 2.3。`MLoggingHandlerTrait` 无需变更（仅使用 `HandlerInterface`，Monolog 3.x 中不变）。

**Q2: task 之间的依赖顺序是否正确？是否存在隐含的前置依赖未体现在排序中？**

A2: 是。Task 1（composer update）必须先行，后续 task 依赖已解析的依赖环境。Task 2（MLogging 核心 + 测试基类适配）在 Task 3–6 之前，因为后续 handler 的测试依赖已适配的测试基类和 MLogging 核心。Task 7（全量回归）在所有实现 task 之后。Task 8（手工测试）和 Task 9（Code Review）在最后。

**Q3: 每个 task 的粒度是否合适？是否有过粗或过细的 task？**

A3: 合适。每个 top-level task 对应一个源文件（或紧密相关的文件组），sub-task 按 test-first 编排拆分为测试编写、实现适配、checkpoint。Task 5（LocalErrorHandler）和 Task 6（LoggableApplication）变更较小但仍值得独立 task，因为各自有独立的 checkpoint 验证。

**Q4: checkpoint 的设置是否覆盖了关键阶段？**

A4: 是。每个 top-level task 末尾都有 checkpoint，且 checkpoint 描述包含具体的验证命令（`vendor/bin/phpunit`）和预期结果。Task 1 的 checkpoint 验证依赖解析，Task 2–7 的 checkpoint 验证测试通过。

**Q5: 手工测试是否覆盖了 requirements 中的关键用户场景？**

A5: 是。手工测试覆盖了控制台彩色输出（Req 3）、全局函数在 PHP 8.2+ 下的行为（Req 10.3）、`\Throwable` 追踪输出（Req 9.3）、LoggableApplication verbosity 行为（Req 6.3）、LocalFileHandler 文件名刷新（Req 4）。

**Q6: design Gatekeep Log 中的 4 个 CR 决策是否已体现？**

A6: 是。Q1（按组件拆分）→ 每个源文件独立 top-level task；Q2（composer 先行）→ Task 1 先行执行；Q3（PHPUnit 内置 mock）→ Task 3.2 明确使用 PHPUnit 内置 mock；Q4（仅替换一处 str_ends_with）→ 仅 Task 2.4 中替换。

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

### 合规检查

**机械扫描**
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 中的模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误

**结构校验**
- [x] `## Tasks` section 存在
- [x] `## Issues` section 存在（Release spec 必需）
- [x] `## Execution Notes` section 存在
- [x] `## Socratic Review` section 存在
- [x] 每个 top-level task 的第一个 sub-task 是 "Increment alpha tag"（1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1）
- [x] 最后一个 top-level task (9) 是 Code Review
- [x] 倒数第二个 top-level task (8) 是手工测试
- [x] 自动化实现 task (1–7) 排在手工测试和 Code Review 之前

**Task 格式校验**
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1–9）
- [x] sub-task 有层级序号（N.1, N.2...）
- [x] 序号连续，无跳号

**Requirement 追溯校验**
- [x] Req 1（Composer 依赖）→ Task 1.2 (AC 1–7), Task 1.3 (AC 8)
- [x] Req 2（LogRecord 适配）→ Task 2.4 (AC 1–4, 7), Task 3.3 (AC 5), Task 4.3 (AC 6)
- [x] Req 3（ConsoleHandler）→ Task 3.2 (AC 2), Task 3.3 (AC 1–3), Task 8.2 (AC 1)
- [x] Req 4（LocalFileHandler）→ Task 4.2 (AC 2), Task 4.3 (AC 1–2)
- [x] Req 5（LocalErrorHandler）→ Task 5.2 (AC 1–2)
- [x] Req 6（级别配置与分发）→ Task 2.2 (AC 1–2), Task 2.5 (AC 1–2), Task 2.6 (AC 4), Task 6.2 (AC 3)
- [x] Req 7（Handler 注册）→ Task 2.5 (AC 1–3), Task 7.2 (AC 2)
- [x] Req 8（PHPUnit 适配）→ Task 2.2 (AC 1, 4), Task 2.3 (AC 2–3)
- [x] Req 9（PHP 8.x 语法）→ Task 2.4 (AC 1), Task 4.3 (AC 2), Task 2.2 (AC 3–4), Task 2.6 (AC 3–4)
- [x] Req 10（全量测试）→ Task 2.2 (AC 4, 6–7), Task 7.2 (AC 1), Task 7.3 (AC 1–7), Task 8.2 (AC 3)
- [x] 无遗漏的 requirement
- [x] 无悬空引用

**依赖与排序校验**
- [x] Task 1（composer）先行，后续 task 依赖已解析的依赖环境
- [x] Task 2（MLogging 核心 + 测试基类）在 Task 3–6 之前
- [x] Task 7（全量回归）在所有实现 task 之后
- [x] 无循环依赖

**Checkpoint 校验**
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 描述包含具体验证命令或验证方式
- [x] checkpoint 不是空泛的"确认完成"

**Test-first 校验**
- [x] Task 2: 2.2 (RED) → 2.4–2.6 (GREEN)
- [x] Task 3: 3.2 (RED) → 3.3 (GREEN)
- [x] Task 4: 4.2 (RED) → 4.3 (GREEN)
- [x] Task 5, 6: 无新增测试，现有测试已覆盖，合理

**Task 粒度校验**
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory（无 optional）

**手工测试 Task 校验**
- [x] 手工测试 top-level task 存在（Task 8）
- [x] 覆盖关键用户场景（控制台彩色输出、\Throwable 追踪、verbosity 行为、文件名刷新）
- [x] 场景描述具体，可执行

**Code Review Task 校验**
- [x] Code Review 是最后一个 top-level task（Task 9）
- [x] 描述为委托给 code-reviewer agent 执行
- [x] 未在 task 描述中展开 review checklist

**执行注意事项校验**
- [x] `## Execution Notes` section 存在
- [x] 明确提到 `spec-execution.md`
- [x] 明确说明 commit 时机（每个 top-level task checkpoint 通过后 commit）
- [x] 包含当前 spec 特有的执行要点（composer 先行、test-first、alpha tag 规则、PHP 环境、import 清理、不做重构、oasis/utils 兼容性）

**Design CR 决策体现**
- [x] Q1 (A) 按组件拆分 → 每个源文件独立 top-level task
- [x] Q2 (A) composer 先行 → Task 1 先行执行
- [x] Q3 (A) PHPUnit 内置 mock → Task 3.2 明确使用 PHPUnit 内置 mock
- [x] Q4 (A) 仅替换一处 str_ends_with → 仅 Task 2.4 中替换

**目的性审查**
- [x] Design CR 回应：design Gatekeep Log 中 4 个 CR 决策均已体现
- [x] Design 全覆盖：design 中 11 个 section 均有对应 task
- [x] 可独立执行：每个 sub-task 描述自包含，含具体的代码变更指引和 requirement 引用
- [x] 验收闭环：checkpoint + 手工测试 + code review 构成完整验收
- [x] 执行路径无歧义：task 排序和依赖关系清晰
