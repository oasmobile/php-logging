# Requirements Document

> 本文件为 `.kiro/specs/release-2.0.0/` 的需求规格，定义 Release 2.0.0 的验收标准。

## Introduction

`oasis/logging` 是 Monolog 的封装库，提供简化的日志接口（全局函数 `mdebug`/`minfo` 等）、自动文件追踪、控制台彩色输出、本地文件/错误日志 handler，以及 Symfony Console 集成。

本次 Release 2.0.0 是一次 major version bump（1.x → 2.0.0），目标是将整个项目升级到 PHP 8 生态：最低支持 PHP 8.2，运行环境目标 PHP 8.5。所有核心依赖（Monolog、PHPUnit、Symfony、bramus formatter）均升级到最新稳定线。这是一次纯兼容性升级，不引入新功能，不保留 PHP 7.x 向后兼容。

**不涉及的内容（Non-scope）**：
- 不增加新功能，仅做兼容性升级
- 不保留 PHP 7.x 向后兼容
- 不处理 `oasis/utils` 自身的 PHP 8 兼容性（由该包自行保证）
- 不重构现有架构或代码结构（除非 PHP 8 / Monolog 3 兼容性要求必须调整）

---

## Glossary

- **Composer_Config**: 项目依赖配置文件，定义依赖版本约束、autoload 配置和元数据
- **MLogging**: 核心日志门面，管理 handler 注册、日志分发和 processor 逻辑
- **Global_Functions**: 全局快捷函数集合（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency`、`mtrace`、`mdump`）
- **ConsoleHandler**: 控制台日志 handler，向标准错误流输出彩色日志
- **LocalFileHandler**: 本地文件日志 handler，支持文件名模式和定时刷新机制
- **LocalErrorHandler**: 错误日志 handler，在触发级别达到阈值时输出缓冲的全部日志
- **LoggableApplication**: Symfony Console 集成组件，根据 verbosity 自动配置日志级别
- **Test_Suite**: 项目测试套件，包含测试类和测试运行器配置
- **PHPUnit_Config**: 测试运行器配置文件，定义测试套件结构和 schema

---

## Requirements

### Requirement 1: Composer 依赖版本升级

**User Story:** 作为开发者，我希望项目的依赖版本约束升级到 PHP 8 生态版本，以便项目可以在 PHP 8.2+ 环境中安装和运行。

#### Acceptance Criteria

1. THE Composer_Config SHALL 声明 PHP 最低版本要求为 8.2
2. THE Composer_Config SHALL 声明 `monolog/monolog` 版本约束为 `^3.0`
3. THE Composer_Config SHALL 声明 `bramus/monolog-colored-line-formatter` 版本约束为 `^3.0`
4. THE Composer_Config SHALL 声明 `oasis/utils` 的版本约束兼容 PHP 8
5. THE Composer_Config SHALL 声明 `phpunit/phpunit` 版本约束为 `^11.0`
6. THE Composer_Config SHALL 声明 `symfony/console` 版本约束为 `^7.0`（开发依赖和推荐依赖）
7. THE Composer_Config SHALL 声明 `symfony/finder` 版本约束为 `^7.0`
8. WHEN 在 PHP 8.2+ 环境中执行依赖解析时，THE Composer_Config SHALL 允许所有依赖无冲突地解析完成

---

### Requirement 2: Monolog 3.x 日志记录数据结构适配

**User Story:** 作为开发者，我希望所有日志组件适配 Monolog 3.x 的日志记录数据结构，以便日志处理管线在升级后正常工作。

#### Acceptance Criteria

1. THE MLogging SHALL 在 processor 中接受并返回 Monolog 3.x 的日志记录对象（替代旧版数组结构）
2. THE MLogging SHALL 在 processor 中正确读取日志级别以执行文件追踪逻辑
3. THE MLogging SHALL 在 processor 中正确修改日志消息以追加文件追踪信息
4. THE MLogging SHALL 在 processor 中将 channel 设置为当前进程 ID
5. THE ConsoleHandler SHALL 接受 Monolog 3.x 的日志记录对象进行处理判断
6. THE LocalFileHandler SHALL 接受 Monolog 3.x 的日志记录对象进行写入操作
7. WHEN Monolog 3.x handler 管线调用各组件时，THE MLogging SHALL 产生与 1.x 版本格式一致的日志输出（包含文件追踪注解）

---

### Requirement 3: ConsoleHandler Monolog 3.x 兼容

**User Story:** 作为开发者，我希望 ConsoleHandler 兼容 Monolog 3.x 的 handler 接口和 bramus formatter 3.x，以便控制台日志功能继续正常工作。

#### Acceptance Criteria

1. WHEN 应用在命令行环境运行时，THE ConsoleHandler SHALL 正常处理日志记录并输出彩色格式
2. WHEN 应用不在命令行环境运行时，THE ConsoleHandler SHALL 拒绝处理日志记录
3. THE ConsoleHandler SHALL 兼容 `bramus/monolog-colored-line-formatter` ^3.0 的格式化器接口

---

### Requirement 4: LocalFileHandler Monolog 3.x 兼容

**User Story:** 作为开发者，我希望 LocalFileHandler 兼容 Monolog 3.x 的 handler 接口，以便文件日志功能继续正常工作。

#### Acceptance Criteria

1. THE LocalFileHandler SHALL 正确将日志记录写入文件
2. THE LocalFileHandler SHALL 保持现有的文件名刷新和写入重试逻辑

---

### Requirement 5: LocalErrorHandler Monolog 3.x 兼容

**User Story:** 作为开发者，我希望 LocalErrorHandler 兼容 Monolog 3.x 的 API，以便错误触发的日志缓冲功能继续正常工作。

#### Acceptance Criteria

1. THE LocalErrorHandler SHALL 使用 Monolog 3.x 兼容的激活策略配置
2. WHEN 触发级别达到阈值时，THE LocalErrorHandler SHALL 将所有缓冲的日志记录刷新到底层文件 handler

---

### Requirement 6: 日志级别配置与分发兼容

**User Story:** 作为开发者，我希望日志级别配置和分发机制兼容 Monolog 3.x，以便日志级别过滤和路由正常工作。

#### Acceptance Criteria

1. WHEN 调用最低日志级别设置功能时，THE MLogging SHALL 正确设置各 handler 的日志级别
2. WHEN 调用日志记录功能时，THE MLogging SHALL 使用 Monolog 3.x 兼容的级别值进行日志分发
3. THE LoggableApplication SHALL 使用 Monolog 3.x 兼容的级别常量配置 handler 级别
4. THE Global_Functions SHALL 使用 Monolog 3.x 兼容的级别常量作为默认参数

---

### Requirement 7: Handler 注册机制兼容

**User Story:** 作为开发者，我希望 handler 注册机制兼容 Monolog 3.x 的 Logger API，以便 handler 可以正确添加和管理。

#### Acceptance Criteria

1. THE MLogging SHALL 使用 Monolog 3.x 兼容的 API 向 handler 添加 processor
2. THE MLogging SHALL 使用 Monolog 3.x 兼容的 API 添加和重新安装 handler
3. THE MLogging SHALL 使用 Monolog 3.x 兼容的 API 检查已注册的 handler

---

### Requirement 8: PHPUnit 11.x 测试适配

**User Story:** 作为开发者，我希望测试套件兼容 PHPUnit 11.x，以便所有现有测试可以在 PHP 8.2+ 上运行。

#### Acceptance Criteria

1. THE Test_Suite SHALL 使用 PHPUnit 11.x 要求的基类和方法签名
2. THE PHPUnit_Config SHALL 使用 PHPUnit 11.x 兼容的 XML schema
3. THE PHPUnit_Config SHALL 保留现有的测试套件配置
4. WHEN 在 PHP 8.2+ 上使用 PHPUnit 11.x 执行时，THE Test_Suite SHALL 通过所有现有测试用例

---

### Requirement 9: PHP 8.x 语法与行为兼容

**User Story:** 作为开发者，我希望所有源代码消除 PHP 8.x 的弃用警告和不兼容项，以便库在 PHP 8.2+ 上无警告运行。

#### Acceptance Criteria

1. IF 任何源文件中存在 PHP 8.x 弃用的语法或行为，THEN THE MLogging SHALL 使用 PHP 8.x 推荐的替代方式
2. THE LocalFileHandler SHALL 使用与 Monolog 3.x 兼容的内部属性访问方式
3. THE MLogging SHALL 使用 PHP 8.x 推荐的异常类型层次进行错误信息提取
4. THE Global_Functions SHALL 使用 PHP 8.x 推荐的异常类型层次作为异常追踪函数的参数类型

---

### Requirement 10: 全量测试通过验证

**User Story:** 作为开发者，我希望确认升级后所有现有测试通过，以便确信升级未引入回归。

#### Acceptance Criteria

1. WHEN 在 PHP 8.2+ 上执行测试套件时，THE Test_Suite SHALL 报告零失败和零错误
2. WHEN 在 PHP 8.2+ 上执行测试套件时，THE Test_Suite SHALL 报告零来自测试框架自身的弃用警告
3. THE Test_Suite SHALL 验证所有八个日志级别函数（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency`）产生正确输出
4. THE Test_Suite SHALL 验证异常追踪功能产生正确输出
5. THE Test_Suite SHALL 验证 LocalErrorHandler 正确缓冲并在错误级别触发时刷新
6. THE Test_Suite SHALL 验证日志级别过滤功能正常工作
7. THE Test_Suite SHALL 验证文件追踪注解正确追加文件名和行号信息

---

## Socratic Review

### 审查 log

**Q1: requirements 是否完整覆盖了 goal.md 中的所有目标？**

A1: 是。goal.md 列出的目标逐一对应：
- Composer 依赖升级 → Req 1
- Monolog 3.x API 不兼容（日志记录数据结构、Logger API、Handler 注册）→ Req 2–7
- PHPUnit 11.x 适配 → Req 8
- PHP 8.x 语法/行为兼容 → Req 9
- 全量测试通过 → Req 10

**Q2: goal.md Clarification Round 中的决策是否已体现？**

A2: 是。Q1 Monolog 3.x → Req 2–7 均基于 Monolog 3.x；Q2 bramus ^3.0 → Req 3 AC3；Q3 oasis/utils 假设兼容 → Req 1 AC4；Q4 PHPUnit 11.x → Req 8；Q5 Symfony 7.x → Req 1 AC6–AC7。

**Q3: 是否存在遗漏的兼容性场景？**

A3: 已覆盖主要兼容性场景：日志记录数据结构变更（Req 2）、各 handler 适配（Req 3–5）、级别配置与分发（Req 6）、handler 注册（Req 7）。Monolog 3.x 的 `StreamHandler` 内部属性变更在 Req 9 AC2 中覆盖。

**Q4: Non-Goals 是否被正确排除？**

A4: 是。无新功能需求；无 PHP 7.x 兼容需求；oasis/utils 仅升级版本约束（Req 1 AC4），不处理其内部兼容性；无架构重构需求。Introduction 已明确列出 Non-scope。

**Q5: 每条 AC 是否描述外部可观察行为而非实现细节？**

A5: 是。AC 聚焦于各组件的行为契约（接受什么、产出什么、在什么条件下如何表现），不涉及具体方法签名、属性名或内部实现策略。Glossary 中定义的术语（如 MLogging、ConsoleHandler）作为领域概念在 AC 中使用是合理的。

**Q6: 各 requirement 之间是否存在矛盾或重叠？**

A6: Req 2 与 Req 3–5 存在一定重叠（Req 2 AC5–6 涉及 ConsoleHandler 和 LocalFileHandler 的数据结构适配，Req 3–5 各自也涉及兼容性）。但 Req 2 聚焦数据结构层面的统一适配，Req 3–5 聚焦各组件特有的行为保持，分工合理。

**Q7: scope 边界是否清晰？**

A7: 是。Introduction 和 Non-scope 明确了"纯兼容性升级"的边界。goal.md 中的约束（PHP 8.2 最低、全依赖升级到最新稳定线、breaking changes 可接受、oasis/utils 外部依赖）均已体现。


---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 一级标题下补充了一句话说明本文件定位和所属 spec 目录
- [结构] Introduction 中补充了 Non-scope 段落，明确列出不涉及的内容
- [结构] 各 section 之间补充了 `---` 分隔线
- [语体] 所有 User Story 从英文改为中文（`作为 <角色>，我希望 <能力>，以便 <价值>`）
- [内容] Req 2–7 的 AC 移除了具体方法名（`lnProcessor`、`isHandling`、`write`、`pushProcessor`、`setHandlers`、`pushHandler`、`getHandlers`、`addHandler`）、属性名（`level`、`message`、`$url`）和内部 API 细节（`Logger::toMonologLevel()`、`AbstractHandler`、`ErrorLevelActivationStrategy`），改为描述外部可观察行为
- [内容] Req 8 的 AC 移除了具体类名和方法签名（`PHPUnit\Framework\TestCase`、`PHPUnit_Framework_TestCase`、`: void` return type），改为描述框架兼容性要求
- [内容] Req 9 的 AC 移除了具体属性名和类型提示细节（`$url`、`Throwable`、`\Exception`），改为描述兼容性行为
- [内容] Glossary 移除了 `LogRecord` 术语（Monolog 内部类型，非本项目领域术语）；其余术语定义移除了文件路径和继承关系等实现细节
- [内容] Socratic Review 重写，移除了涉及实现细节的 Q6/A6（`$record->with()` 等），补充了关于 AC 行为聚焦度和 scope 边界的审查

### 合规检查

**机械扫描**
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、术语表中的术语在正文中使用）
- [x] 无 markdown 格式错误

**结构校验**
- [x] 一级标题存在且正确，含一句话定位说明
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空
- [x] Requirements section 存在且包含 10 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Socratic Review 存在且覆盖充分

**术语表校验**
- [x] Glossary 中的术语在正文 AC 中被实际使用（无孤立术语）
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] 术语格式正确（`- **Term**: 定义`）

**Requirement 条款校验**
- [x] 每条 requirement 包含 User Story 和 Acceptance Criteria
- [x] User Story 使用中文行文
- [x] AC 使用 EARS 模式（THE...SHALL / WHEN...SHALL / IF...THEN...SHALL）
- [x] Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续，无跳号

**内容边界校验**
- [x] AC 聚焦外部可观察行为，不包含具体方法签名、属性名或内部实现策略
- [x] 库名引用合理（goal.md 已明确选型）

**目的性审查**
- [x] Goal CR 回应：goal.md 中 5 个 Clarification 决策均已体现
- [x] Goal 清晰度：Introduction 清楚传达了 PHP 8 兼容性升级的目标
- [x] Non-goal / Scope 边界：Non-scope 明确列出
- [x] 完成标准：AC 整体构成充分的验收条件
- [x] 可 design 性：requirements 提供了足够信息开始技术方案设计

### Clarification Round

**状态**: 已回答

**Q1:** Monolog 3.x 的 `LogRecord` 是 readonly class，processor 无法直接修改属性，需要通过 `with()` 方法创建新实例。Req 2 要求 processor 修改 channel 和 message——design 阶段是否应严格遵循 `LogRecord::with()` 模式，还是可以考虑其他方式（如通过 extra 字段附加信息而非修改 message）？
- A) 使用 `LogRecord::with()` 创建新实例，保持与 1.x 完全一致的输出格式
- B) 将文件追踪信息放入 extra 字段，由 formatter 负责输出，不修改 message 本身
- C) 其他（请说明）

**A:** A — 使用 `LogRecord::with()` 创建新实例，保持与 1.x 完全一致的输出格式

**Q2:** Req 9 要求异常追踪函数的参数类型从 `\Exception` 扩展到 `\Throwable`。这会扩大函数的接受范围（包括 `Error` 类型）。现有测试仅覆盖 `\Exception` 场景。design 阶段是否需要同时调整测试以覆盖 `\Throwable`（如 `TypeError`）场景？
- A) 仅修改类型声明，不增加新测试（现有测试已覆盖核心路径）
- B) 修改类型声明并增加 `\Throwable` 场景的测试用例
- C) 保持 `\Exception` 不变，不扩展类型（纯兼容性升级，不扩大接口）
- D) 其他（请说明）

**A:** B — 修改类型声明并增加 `\Throwable` 场景的测试用例

**Q3:** Req 5 涉及 `FingersCrossedHandler` 在 Monolog 3.x 中的构造函数变更。Monolog 3.x 中 `ErrorLevelActivationStrategy` 已被移除，改为使用 `ChannelLevelActivationStrategy` 或直接传入 level 值。design 阶段应采用哪种替代方案？
- A) 直接传入 trigger level 值（Monolog 3.x 支持 `FingersCrossedHandler` 构造函数直接接受 level 参数）
- B) 使用 `ChannelLevelActivationStrategy` 作为替代
- C) 先调研 Monolog 3.x 的实际 API 再决定
- D) 其他（请说明）

**A:** A — 直接传入 trigger level 值

**Q4:** 现有代码中 `MLogging::setMinLogLevel` 使用 `AbstractHandler::setLevel()` 设置级别。Monolog 3.x 中 `AbstractHandler::setLevel()` 的参数类型从 `int|string` 变为 `Level` enum。design 阶段是否需要在 `setMinLogLevel` 的公共接口中也改为接受 `Level` enum，还是保持接受 `int` 并在内部转换？
- A) 公共接口保持接受 `int`，内部转换为 `Level` enum（向调用方兼容）
- B) 公共接口改为接受 `Level` enum（与 Monolog 3.x 对齐，这是 major version bump 可以 break）
- C) 同时接受 `int` 和 `Level` enum（union type）
- D) 其他（请说明）

**A:** B — 公共接口改为接受 `Level` enum（与 Monolog 3.x 对齐）
