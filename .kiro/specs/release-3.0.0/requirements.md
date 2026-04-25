# Requirements Document

> 本文件为 `.kiro/specs/release-3.0.0/` 的需求规格，定义 Release 3.0.0 的验收标准。

## Introduction

`oasis/logging`（MLogging）是基于 Monolog 的日志封装库，提供简化的日志接口（全局快捷函数、自动文件追踪、控制台彩色输出）、本地文件/错误日志 handler，以及 Symfony Console 集成。

本次 Release 3.0.0 是一次纯语法层面的现代化。项目的 `composer.json` 已声明 PHP ≥ 8.2，核心逻辑在 v2.0.0 升级时已迁移到 PHP 8.x 风格，但属性声明和函数签名中仍有旧写法残留：静态属性缺少类型声明、全局函数缺少返回类型、构造函数参数无类型声明、未使用 constructor promotion 和 `readonly`。本次 release 将这些残留旧写法统一清理，使代码风格与 PHP 8.2 最低版本要求完全一致。

**不涉及的内容（Non-scope）**：
- 不修改任何功能逻辑或运行时行为
- 不调整 Handler 体系结构
- 不升级依赖版本
- 不引入 PHP 8.2 以上的新特性（如 DNF types、`#[\Override]` 等），仅清理已有代码的类型声明和属性声明

---

## Glossary

- **MLogging_Facade**: 核心日志门面类（`MLogging`），管理 Logger 实例、Handler 注册和 Processor 逻辑，包含多个静态属性
- **Global_Functions**: 全局快捷函数集合（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency`、`mtrace`、`mdump`），通过 Composer autoload 自动加载
- **LoggableApplication**: Symfony Console 集成组件，继承 Application 并在构造函数中接受应用名称和版本参数
- **LocalFileHandler**: 本地文件日志 handler，支持文件名模式和定时刷新机制，包含构造后不再变更的属性和运行时可变属性
- **Test_Suite**: 项目测试套件，用于验证所有变更未引入回归

---

## Requirements

### Requirement 1: MLogging 静态属性类型声明

**User Story:** 作为开发者，我希望 MLogging_Facade 的静态属性具有显式类型声明，以便获得静态分析支持并符合 PHP 8.2 的类型声明规范。

#### Acceptance Criteria

1. THE MLogging_Facade SHALL 为所有缺少类型声明的静态属性添加显式类型，包括：logger 实例属性（nullable）、自动发布开关属性（bool）、自动发布注册状态属性（bool）、handler 集合属性（array）；handler 集合属性的现有 PHPDoc 泛型注解应移除，仅保留原生类型
2. THE MLogging_Facade SHALL 保持各属性的现有默认值不变
3. WHEN 修改完成后执行测试套件时，THE Test_Suite SHALL 报告零失败和零错误

---

### Requirement 2: 全局函数类型声明

**User Story:** 作为开发者，我希望 Global_Functions 具有显式的返回类型和参数类型声明，以便函数签名完整且符合 PHP 8.2 规范。

#### Acceptance Criteria

1. THE Global_Functions 中的日志级别函数（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency`）SHALL 声明返回类型为 `void`
2. THE Global_Functions 中的对象转储函数（`mdump`）SHALL 声明返回类型为 `string`
3. THE Global_Functions SHALL 为尚未声明类型的参数添加显式类型声明（日志消息参数声明为 `string|Stringable`，转储对象参数声明为 `mixed`）
4. WHEN 修改完成后执行测试套件时，THE Test_Suite SHALL 报告零失败和零错误

---

### Requirement 3: LoggableApplication 构造函数参数类型声明

**User Story:** 作为开发者，我希望 LoggableApplication 的构造函数参数具有显式类型声明，以便参数签名完整且符合 PHP 8.2 规范。

#### Acceptance Criteria

1. THE LoggableApplication SHALL 为构造函数的名称参数和版本参数添加 `string` 类型声明
2. THE LoggableApplication SHALL 保留构造函数重写及现有默认值
3. WHEN 修改完成后执行测试套件时，THE Test_Suite SHALL 报告零失败和零错误

---

### Requirement 4: LocalFileHandler 属性现代化

**User Story:** 作为开发者，我希望 LocalFileHandler 的属性声明使用 PHP 8.2 的现代语法（constructor promotion 和 readonly），以便代码更简洁且明确表达属性的不可变语义。

#### Acceptance Criteria

1. THE LocalFileHandler 中构造后不再变更的属性（路径和文件名模式）SHALL 标记为 `readonly`
2. THE LocalFileHandler 的文件名模式属性 SHALL 通过 constructor promotion 声明
3. THE LocalFileHandler 的路径属性 SHALL 不使用 constructor promotion（因存在条件赋值逻辑且 `sys_get_temp_dir()` 不能作为参数默认值），改为手动声明 readonly 属性（类型为非 nullable `string`，反映构造函数保证的实际不变量）并在构造函数体内完成赋值
4. THE LocalFileHandler 中运行时可变的属性（刷新间隔、上次文件创建时间戳）SHALL 保持原有的显式属性声明方式，不标记 readonly
5. THE LocalFileHandler 的构造函数外部调用签名（参数顺序、类型、默认值）SHALL 保持不变
6. WHEN 修改完成后执行测试套件时，THE Test_Suite SHALL 报告零失败和零错误

---

## Socratic Review

### SR-1: readonly + constructor promotion 对路径属性的可行性

**问题**：`LocalFileHandler` 的路径属性当前在构造函数中有条件赋值（null 时使用系统临时目录），readonly 属性通过 constructor promotion 声明后只能赋值一次。这是否冲突？

**回答**：不冲突，但需要区别处理。Constructor promotion 会在进入构造函数体之前完成赋值，因此不能在体内再次赋值给 promoted readonly 属性。正确做法是：路径属性不使用 promotion，手动声明为 `readonly`，在构造函数体内完成条件赋值（仅赋值一次）。文件名模式属性无条件赋值问题，可以使用 promotion + readonly。REQ-4 的 AC 已体现此区分。

### SR-2: 原 REQ-4 和 REQ-5 的合并合理性

**问题**：原文将 constructor promotion（REQ-4）和 readonly 标记（REQ-5）拆为两条独立 requirement。这两者是否应合并？

**回答**：应合并。两者都针对 LocalFileHandler 的属性声明现代化，且 constructor promotion 和 readonly 在实现上紧密耦合（promoted 属性同时声明 readonly）。拆分会导致 AC 重复（两条 requirement 都需要说明哪些属性标 readonly、哪些不标）。合并为一条 requirement 更清晰。

### SR-3: AC 2.3 参数类型补全是否超出 goal 范围

**问题**：goal.md 仅提及"为全局函数添加返回类型"，但 AC 2.3 同时要求为参数添加类型声明。这是否超出 scope？

**回答**：属于合理的 scope 扩展。本次 release 的目标是"使代码风格与 PHP 8.2 最低版本要求完全一致"，函数参数缺少类型声明同样属于旧写法残留。goal.md 的"待改进项"列表是示例性的，不是穷举性的。为参数补全类型声明与 release 目标一致，且改动量极小。

### SR-4: 各 requirement 之间是否存在矛盾或重叠

**问题**：四条 requirement 分别针对不同的源文件和改动类型，是否存在交叉？

**回答**：不存在。REQ-1 针对 MLogging 静态属性，REQ-2 针对全局函数，REQ-3 针对 LoggableApplication 构造函数，REQ-4 针对 LocalFileHandler 属性。各自独立，无重叠。

### SR-5: 是否有遗漏的改进项

**问题**：除了 goal.md 列出的 5 项改进，源代码中是否还有其他不符合 PHP 8.2 规范的旧写法？

**回答**：经检查，`ConsoleHandler`、`LocalErrorHandler`、`MLoggingHandlerTrait` 的属性和方法签名已符合 PHP 8.2 规范（使用了 Level enum、LogRecord 类型等）。goal.md 列出的 5 项（合并后为 4 条 requirement）已覆盖所有需要清理的旧写法。


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 补充一级标题 `# Requirements Document` 及定位说明（原标题 `# Requirements: PHP 8.2 Syntax Modernization` 不符合约定格式）
- [结构] 补充 `## Introduction` section，描述 feature 范围和 Non-scope
- [结构] 补充 `## Glossary` section，定义 AC 中使用的领域术语
- [结构] 补充 `## Requirements` 包裹层，Requirement 标题格式从 `## REQ-N:` 改为 `### Requirement N:`
- [结构] 合并原 REQ-4（constructor promotion）和 REQ-5（readonly 属性）为 Requirement 4: LocalFileHandler 属性现代化——两者针对同一组件的同一组属性，拆分导致 AC 重复
- [语体] 为每条 Requirement 补充 User Story（`作为 <角色>，我希望 <能力>，以便 <价值>`）
- [语体] AC 从子标题 + 列表格式改为 EARS 模式编号列表（THE...SHALL / WHEN...SHALL）
- [内容] AC 中移除具体属性名（`$logger`、`$autoPublishingOnFatalError` 等）和具体类型声明语法（`?Logger`、`private readonly string`），改为描述属性的语义角色（如"logger 实例属性"、"路径属性"）
- [内容] Socratic Review 从 1 条扩充至 5 条，覆盖合并合理性、scope 扩展、requirement 交叉、遗漏检查等维度

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
- [x] Requirements section 存在且包含 4 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Socratic Review 存在且覆盖充分

**术语表校验**
- [x] Glossary 中的术语在正文 AC 中被实际使用（无孤立术语）
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] 术语格式正确（`- **Term**: 定义`）

**Requirement 条款校验**
- [x] 每条 requirement 包含 User Story 和 Acceptance Criteria
- [x] User Story 使用中文行文
- [x] AC 使用 EARS 模式（THE...SHALL / WHEN...SHALL）
- [x] Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续，无跳号

**内容边界校验**
- [x] AC 聚焦外部可观察行为，不包含具体属性名或内部实现语法
- [○] AC 中保留了部分具体函数名（`mdebug`、`minfo` 等）——这些是 Global_Functions 术语的组成部分，且 goal.md 明确列出，属于领域概念而非实现细节

**目的性审查**
- [x] Goal CR 回应：goal.md 中 3 个 Clarification 决策均已体现（Q1 全部纳入、Q2 保留构造函数重写、Q3 两者都标 readonly）
- [x] Goal 清晰度：Introduction 清楚传达了 PHP 8.2 语法现代化的目标
- [x] Non-goal / Scope 边界：Non-scope 明确列出
- [x] 完成标准：AC 整体构成充分的验收条件
- [x] 可 design 性：requirements 提供了足够信息开始技术方案设计

### Clarification Round

**状态**: 已完成

**Q1:** REQ-1 要求为 MLogging_Facade 的 handler 集合属性添加 `array` 类型声明。当前代码中该属性有 `@var HandlerInterface[]` 的 PHPDoc 注解。添加原生 `array` 类型后，是否保留 PHPDoc 注解以提供泛型信息（`/** @var HandlerInterface[] */`），还是移除 PHPDoc 仅保留原生类型？
- A) 保留 PHPDoc 注解（原生 `array` + PHPDoc `HandlerInterface[]`，提供更精确的静态分析信息）
- B) 移除 PHPDoc 注解（仅保留原生 `array` 类型，减少冗余）
- C) 其他（请说明）

**A:** B — 移除 PHPDoc 注解，仅保留原生 `array` 类型

**Q2:** REQ-4 要求路径属性使用手动 readonly 声明。当前路径属性类型为 `?string`（nullable），经过构造函数的条件赋值后实际值始终为非 null 的 `string`。readonly 声明时应使用 `string`（非 nullable，反映实际语义）还是 `?string`（保持与构造函数参数一致的 nullable 类型）？
- A) 使用 `string`（非 nullable）——构造函数保证赋值后不为 null，readonly 属性类型应反映实际不变量
- B) 使用 `?string`（nullable）——与构造函数参数类型保持一致，避免类型收窄带来的意外
- C) 其他（请说明）

**A:** A — 使用 `string`（非 nullable），反映构造函数保证的实际不变量

**Q3:** REQ-2 AC 3 要求为全局函数的消息参数添加 `string` 类型声明。当前 `MLogging::log()` 的 `$msg` 参数已声明为 `string`，但全局函数作为其包装层，添加 `string` 类型后会拒绝非字符串参数（如 `int`、`Stringable` 对象）。是否需要考虑使用 `string|Stringable` 以保持更宽松的接受范围，还是严格使用 `string` 与底层保持一致？
- A) 使用 `string`——与 `MLogging::log()` 保持一致，调用方应自行转换类型
- B) 使用 `string|Stringable`——提供更宽松的接口，符合 PSR-3 的 message 约定
- C) 其他（请说明）

**A:** B — 使用 `string|Stringable`，提供更宽松的接口
