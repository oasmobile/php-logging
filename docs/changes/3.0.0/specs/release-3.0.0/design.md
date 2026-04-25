# Design Document

> 本文件为 `.kiro/specs/release-3.0.0/` 的技术设计，描述 PHP 8.2 语法现代化的实现方案。

## Overview

本次变更涉及 4 个源文件的语法现代化和 1 个依赖版本升级，均为声明层面的修改，不改变运行时行为。每个文件的改动相互独立，无跨文件依赖。

---

## Impact Analysis

### 受影响文件

| 文件 | 改动类型 | 风险 |
|------|----------|------|
| `src/MLogging.php` | 静态属性加类型声明、移除 PHPDoc | 低——类型与现有默认值一致 |
| `src/MLogging.inc.php` | 函数签名加参数类型和返回类型 | 中——`string\|Stringable` 参数类型比原无类型更严格，可能拒绝 `int` 等非字符串参数 |
| `src/LoggableApplication.php` | 构造函数参数加类型声明 | 低——与父类签名一致 |
| `src/LocalFileHandler.php` | constructor promotion、readonly、属性类型收窄 | 中——readonly 阻止子类覆写属性；`$path` 类型从 `?string` 收窄为 `string` |
| `composer.json` | `oasis/utils` 版本约束从 `^2.0` 改为 `^3.0` | 低——oasis/utils 3.0 为同类语法现代化升级，公共 API 不变 |

### 不受影响的文件

- `src/ConsoleHandler.php`——签名已符合 PHP 8.2 规范
- `src/LocalErrorHandler.php`——签名已符合 PHP 8.2 规范，作为 `LocalFileHandler` 的调用方，构造函数签名不变，无影响
- `src/MLoggingHandlerTrait.php`——签名已符合 PHP 8.2 规范

### Breaking Change 分析

本次变更构成 semver major bump（v3.0.0）的原因：

1. **`readonly` 属性阻止子类覆写**：`LocalFileHandler::$path` 和 `$namePattern` 标记 readonly 后，任何继承 `LocalFileHandler` 并覆写这些属性的子类将产生 fatal error
2. **全局函数参数类型收紧**：原无类型参数现在要求 `string|Stringable`，传入 `int`、`float` 等类型将触发 TypeError
3. **属性类型收窄**：`LocalFileHandler::$path` 从 `?string` 收窄为 `string`，子类如果依赖 nullable 语义将不兼容
4. **依赖 major 版本升级**：`oasis/utils` 从 `^2.0` 升级到 `^3.0`，与本项目同步 major bump；下游项目如果同时依赖 `oasis/utils ^2.0` 将产生版本冲突

### State 文档影响

- `docs/state/architecture.md`——本次变更不改变架构分层和模块关系，但 Handler 体系 section 中对 `LocalFileHandler` 的描述（属性、构造函数签名）在实现完成后应同步更新，反映 readonly 和 constructor promotion 的变化

### 配置项变更

不涉及。本次变更不新增、删除或修改任何配置项或默认值。

---

## Design Details

### D1: MLogging 静态属性类型声明（→ REQ-1）

**文件**: `src/MLogging.php`

**变更**:

```php
// Before
private static $logger                     = null;
private static $autoPublishingOnFatalError = false;
private static $autoPublisherRegistered    = false;

/** @var HandlerInterface[] */
private static $handlers             = [];

private static $minLevelForFileTrace = Level::Debug;

// After
private static ?Logger $logger                     = null;
private static bool $autoPublishingOnFatalError = false;
private static bool $autoPublisherRegistered    = false;
private static array $handlers                     = [];
private static Level $minLevelForFileTrace = Level::Debug;
```

要点：
- `$logger` 使用 `?Logger` 因为默认值为 `null`，运行时通过 `getLogger()` 惰性初始化
- `$handlers` 移除 `/** @var HandlerInterface[] */` PHPDoc 注解（CR Q1 决策）
- 默认值保持不变
- `$minLevelForFileTrace` 原无类型声明，需补充 `Level` 类型

---

### D2: 全局函数类型声明（→ REQ-2）

**文件**: `src/MLogging.inc.php`

**变更**:

8 个日志级别函数统一添加参数类型和返回类型：

```php
// Before
function mdebug($msg, ...$args)
{
    MLogging::log(substr(__FUNCTION__, 1), $msg, ...$args);
}

// After
function mdebug(string|\Stringable $msg, mixed ...$args): void
{
    MLogging::log(substr(__FUNCTION__, 1), (string) $msg, ...$args);
}
```

`mdump` 函数：

```php
// Before
function mdump($obj)
{
    return print_r($obj, true);
}

// After
function mdump(mixed $obj): string
{
    return print_r($obj, true);
}
```

要点：
- `$msg` 参数类型为 `string|\Stringable`（CR Q3 决策），需要在传递给 `MLogging::log()` 时强制转换为 `string`，因为 `MLogging::log()` 的 `$msg` 参数类型为 `string`
- `$args` 参数使用 `mixed` variadic，与 `MLogging::log()` 的 `mixed ...$args` 一致
- `mtrace` 已有完整类型声明，不需修改
- 8 个日志函数的改动完全对称，仅函数名不同

---

### D3: LoggableApplication 构造函数参数类型声明（→ REQ-3）

**文件**: `src/LoggableApplication.php`

**变更**:

```php
// Before
public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')

// After
public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
```

要点：
- 与父类 `Symfony\Component\Console\Application::__construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')` 签名一致
- 移除现有的 `@inheritdoc` PHPDoc 注释块（类型声明已自文档化，无需冗余注释）
- 保留构造函数重写（goal CR Q2 决策）

---

### D4: LocalFileHandler 属性现代化（→ REQ-4）

**文件**: `src/LocalFileHandler.php`

**变更概览**:

1. `$namePattern` → constructor promotion + readonly
2. `$path` → 手动 readonly 声明（非 nullable `string`）
3. `$refreshRate`、`$lastFileCreationTimestamp` → 保持不变

**构造函数重构**:

```php
// Before
class LocalFileHandler extends StreamHandler
{
    use MLoggingHandlerTrait;
    
    private ?string $path        = null;
    private string $namePattern = "%date%/%script%.log";
    
    private int $refreshRate               = 0;
    private int $lastFileCreationTimestamp = 0;
    
    public function __construct(?string $path = null, string $namePattern = "%date%/%script%.log", Level $level = Level::Debug)
    {
        if (!$path) {
            $path = sys_get_temp_dir();
        }
        
        $this->path        = $path;
        $this->namePattern = $namePattern;
        
        parent::__construct($this->generateCurrentPath(), $level);
        // ... formatter setup ...
    }

// After
class LocalFileHandler extends StreamHandler
{
    use MLoggingHandlerTrait;
    
    private readonly string $path;
    
    private int $refreshRate               = 0;
    private int $lastFileCreationTimestamp = 0;
    
    public function __construct(
        ?string $path = null,
        private readonly string $namePattern = "%date%/%script%.log",
        Level $level = Level::Debug,
    )
    {
        $this->path = $path ?: sys_get_temp_dir();
        
        parent::__construct($this->generateCurrentPath(), $level);
        // ... formatter setup unchanged ...
    }
```

要点：
- `$path` 属性类型为 `string`（非 nullable，CR Q2 决策），但构造函数参数保持 `?string` 以兼容现有调用方式
- `$path` 不使用 promotion 因为需要条件赋值；使用 `$path ?: sys_get_temp_dir()` 简化原有的 if 语句（语义等价：`!$path` 在原代码中对 `null` 和空字符串都为 true，`?:` 运算符行为一致）
- `$namePattern` 使用 promotion + readonly，参数默认值与原属性默认值一致
- 外部调用签名不变：`(?string $path = null, string $namePattern = "%date%/%script%.log", Level $level = Level::Debug)`
- `$refreshRate` 和 `$lastFileCreationTimestamp` 保持原样

---

### D5: oasis/utils 依赖升级（→ REQ-5）

**文件**: `composer.json`

**变更**:

```json
// Before
"oasis/utils": "^2.0"

// After
"oasis/utils": "^3.0"
```

要点：
- oasis/utils 3.0 同样是 PHP 8.2 语法现代化升级，公共 API（`CommonUtils::isRunningFromCommandLine()`、`CommonUtils::monitorMemoryUsage()` 等）保持不变
- 本项目使用的 `CommonUtils` 方法签名在 3.0 中无 breaking change，升级后无需修改调用代码
- 变更后需执行 `composer update oasis/utils` 更新 lock 文件

---

## 测试策略

本次变更为纯语法现代化，不改变运行时行为。测试策略如下：

- **自动化测试**：每个 Design section（D1–D5）的改动完成后，运行 `vendor/bin/phpunit` 全量测试套件，确认零失败零错误。这是各 requirement AC 中明确要求的验收条件。
- **手工测试**：不需要。所有变更均为声明层面的修改，现有自动化测试已覆盖相关代码路径（`ut/MLoggingTest.php` 覆盖日志写入和 handler 注册，`ut/LoggableApplicationTest.php` 覆盖 Symfony Console 集成）。

---

## Socratic Review

### SR-1: `string|\Stringable` 参数与 `MLogging::log()` 的 `string` 参数是否需要显式转换

**问题**：全局函数的 `$msg` 参数类型为 `string|\Stringable`，但 `MLogging::log()` 接受 `string`。如果传入 `Stringable` 对象，是否会触发 TypeError？

**回答**：是的。PHP 不会自动将 `Stringable` 转换为 `string` 传递给 `string` 类型参数。需要在全局函数内部对 `$msg` 进行 `(string)` 强制转换后再传递给 `MLogging::log()`。D2 设计中已包含此转换。

### SR-2: `$path ?: sys_get_temp_dir()` 与原 `if (!$path)` 是否语义等价

**问题**：原代码使用 `if (!$path) { $path = sys_get_temp_dir(); }`，改为 `$path ?: sys_get_temp_dir()` 是否等价？

**回答**：等价。`?:` 运算符在左操作数为 falsy 时返回右操作数。`!$path` 为 true 的情况包括 `null`、`""`、`false`——与 `?:` 的 falsy 判断一致。由于参数类型为 `?string`，实际只可能是 `null` 或字符串，两种写法完全等价。

### SR-3: readonly 属性对 `generateCurrentPath()` 和 `checkFilenameRefresh()` 的影响

**问题**：`generateCurrentPath()` 读取 `$this->namePattern` 和 `$this->path`，`checkFilenameRefresh()` 读取 `$this->url`。readonly 是否影响这些方法？

**回答**：不影响。`generateCurrentPath()` 只读取 `$path` 和 `$namePattern`，不写入。`checkFilenameRefresh()` 写入的是 `$this->url`（继承自 `StreamHandler`）和 `$this->lastFileCreationTimestamp`，这两个属性都不标记 readonly。

### SR-4: 是否需要同步修改 `MLogging::log()` 的 `$msg` 参数类型

**问题**：全局函数接受 `string|\Stringable`，但 `MLogging::log()` 仍然只接受 `string`。是否应该同步放宽？

**回答**：不应该。`MLogging::log()` 内部使用 `vsprintf($msg, $args)`，`vsprintf` 要求第一个参数为 `string`。放宽 `MLogging::log()` 的参数类型会引入额外的转换逻辑，超出本次 release 的 scope（纯语法现代化，不改变功能逻辑）。全局函数作为包装层负责类型转换是合理的职责分配。

### SR-5: D3 移除 `@inheritdoc` PHPDoc 注释块是否合理

**问题**：D3 提出在为 `LoggableApplication::__construct()` 添加类型声明的同时移除 `@inheritdoc` PHPDoc 注释块。这一决策未在 requirements 中明确要求，是否超出 scope？

**回答**：合理且不超出 scope。`@inheritdoc` 的作用是让 IDE 从父类继承文档，但当子类签名已通过原生类型声明完整表达时，`@inheritdoc` 成为冗余注释。移除它属于"使代码风格与 PHP 8.2 规范一致"的范畴——现代 PHP 风格倾向于用原生类型声明替代 PHPDoc 类型注解。这与 D1 中移除 `$handlers` 的 PHPDoc 注解（CR Q1 决策）是同一原则。改动量为删除 3 行注释，不影响运行时行为。


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 补充 `## 测试策略` section——release spec design 应包含测试策略，原文缺失。说明自动化测试为主要验收手段，无需手工测试
- [内容] Impact Analysis 补充 `### State 文档影响` 小节——`docs/state/architecture.md` 中 LocalFileHandler 的描述在实现完成后应同步更新
- [内容] Impact Analysis 补充 `### 配置项变更` 小节——显式标注"不涉及"
- [内容] Socratic Review 补充 SR-5——讨论 D3 移除 `@inheritdoc` PHPDoc 注释块的合理性（该决策未在 requirements 中明确要求，需论证不超出 scope）

### 合规检查

**机械扫描**
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（D1→REQ-1, D2→REQ-2, D3→REQ-3, D4→REQ-4, D5→REQ-5；CR 决策引用正确）
- [x] 代码块语法正确（语言标注 php、闭合完整）
- [x] 无 markdown 格式错误

**结构校验**
- [x] 一级标题存在且含定位说明
- [x] Overview section 存在
- [x] Impact Analysis 存在，含受影响文件、不受影响文件、Breaking Change 分析、State 文档影响、配置项变更
- [x] Design Details 存在，D1–D5 承接 REQ-1–REQ-5
- [x] 测试策略 section 存在
- [x] Socratic Review 存在（5 条）
- [x] 各 section 之间使用 `---` 分隔

**Requirements 覆盖校验**
- [x] REQ-1（MLogging 静态属性类型声明）→ D1
- [x] REQ-2（全局函数类型声明）→ D2
- [x] REQ-3（LoggableApplication 构造函数参数类型声明）→ D3
- [x] REQ-4（LocalFileHandler 属性现代化）→ D4
- [x] REQ-5（oasis/utils 依赖升级）→ D5
- [x] 无遗漏的 requirement
- [x] Design 不超出 requirements 范围

**Impact Analysis 校验**
- [x] 受影响文件列表完整（4 个源文件 + composer.json）
- [x] 不受影响文件显式列出并说明原因
- [x] Breaking Change 分析充分（4 项 breaking change 对应 major bump）
- [x] State 文档影响已标注
- [x] 配置项变更已标注（不涉及）
- [○] 外部系统交互变化——不涉及，本次为纯内部语法变更

**技术方案质量校验**
- [x] 技术选型有明确理由（每个 D section 的"要点"说明了选择原因）
- [x] Before/After 代码片段清晰，可直接作为实现参考
- [x] 无循环依赖（4 个文件改动相互独立）
- [x] 无过度设计
- [x] 与 `docs/state/architecture.md` 描述的现有架构一致

**目的性审查**
- [x] Requirements CR 回应：Q1（移除 PHPDoc）→ D1 体现；Q2（$path 非 nullable）→ D4 体现；Q3（string|Stringable）→ D2 体现
- [x] 技术选型明确：所有关键决策（promotion vs 手动声明、类型转换、PHPDoc 移除）均有结论和理由
- [x] 接口定义可执行：Before/After 代码片段足够具体，task 执行者可直接编码
- [x] Requirements 全覆盖：5 条 requirement 均有对应 Design section
- [x] Impact 充分评估：覆盖源文件、state 文档、breaking change、配置项
- [x] 可 task 化：D1–D5 相互独立，可拆为独立 task

### Clarification Round

**状态**: 已完成

**Q1:** D1–D4 四个 Design section 相互独立，拆分为 task 时的实现顺序是否有偏好？
- A) 按 D1→D2→D3→D4 顺序执行（从简单到复杂，D4 涉及构造函数重构最复杂）
- B) 按文件维度自由排序，无顺序要求（各 task 独立，谁先谁后不影响）
- C) 先做 D4（最复杂的改动先行，降低后期风险），再做其余
- D) 其他（请说明）

**A:** B — 无顺序要求，各 task 独立

**Q2:** D3 在添加类型声明的同时移除 `@inheritdoc` PHPDoc 注释块（3 行注释）。是否同意在同一 task 中一并移除？
- A) 同意——类型声明已自文档化，`@inheritdoc` 冗余，一并移除
- B) 不移除——保留 `@inheritdoc`，本次只加类型声明，最小改动
- C) 其他（请说明）

**A:** A — 同意一并移除

**Q3:** 所有 D section 改动完成后，是否需要同步更新 `docs/state/architecture.md` 中 LocalFileHandler 的属性描述（反映 readonly 和 constructor promotion 变化）？如需要，是作为独立 task 还是合并到 D4 的 task 中？
- A) 需要，作为独立 task——state 文档更新与代码改动分离，便于 review
- B) 需要，合并到 D4 的 task 中——改动关联性强，一起完成
- C) 不需要——architecture.md 描述的是模块关系和职责，不涉及属性级别的细节，无需更新
- D) 其他（请说明）

**A:** A — 需要，作为独立 task

**Q4:** D2 中 8 个日志函数的改动完全对称，拆分 task 时是作为一个 task 统一处理，还是需要更细粒度的拆分（如按函数分组）？
- A) 一个 task 统一处理——改动完全对称，拆分无意义
- B) 按"8 个日志函数"和"mdump 函数"拆为两个 task——两者改动模式不同（void vs string 返回类型，参数类型不同）
- C) 其他（请说明）

**A:** B — 拆为两个 task
