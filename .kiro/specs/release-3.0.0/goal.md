# Spec Goal: PHP 8.2 Syntax Modernization

## 来源

- 分支: `release/3.0.0`
- 需求文档: `docs/notes/php82-syntax-modernization.md`

## 背景摘要

oasis/logging 项目的 `composer.json` 已声明 `php >= 8.2`，核心逻辑（enum、`match`、`str_ends_with()` 等）在 v2.0.0 升级时已迁移到 PHP 8.x 风格。但属性声明和函数签名中仍有旧写法残留：静态属性缺少类型声明、全局函数缺少返回类型、构造函数参数无类型声明、未使用 constructor promotion 和 `readonly` 等。

本次 release 的目标是将这些残留的旧写法统一清理，使代码风格与 PHP 8.2 最低版本要求完全一致。这是一次纯语法层面的现代化，不涉及功能变更。

## 目标

- 为 `MLogging` 类的静态属性添加类型声明（`$logger`、`$autoPublishingOnFatalError`、`$autoPublisherRegistered`、`$handlers`）
- 为 `MLogging.inc.php` 中的全局函数添加返回类型（`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency` 标 `void`；`mdump` 标 `string`）
- 为 `LoggableApplication::__construct()` 的参数添加 `string` 类型声明
- 将 `LocalFileHandler` 改用 constructor promotion 简化属性初始化
- 将 `LocalFileHandler` 构造后不再变更的属性（`$path`、`$namePattern`）标记为 `readonly`
- 将 `oasis/utils` 依赖从 `^2.0` 升级到 `^3.0`，与本项目 major 版本对齐

## 不做的事情（Non-Goals）

- 不修改任何功能逻辑或行为
- 不调整 Handler 体系结构
- 不引入新的 PHP 8.2+ 特性（如 DNF types、`#[\Override]` 等），仅清理已有代码的类型声明和属性声明

## Clarification 记录

### Q1: Scope 边界——note 中列出的 5 项改进是否全部纳入？

- 选项: A) 全部纳入 / B) 只做类型声明相关 / C) 补充说明
- 回答: A — 全部纳入，一次性清理完

### Q2: `LoggableApplication::__construct()` 处理方式

- 选项: A) 加 `string` 类型，保留构造函数重写 / B) 直接删除构造函数重写 / C) 补充说明
- 回答: A — 最小改动，加类型声明保留重写

### Q3: `LocalFileHandler` 的 `readonly` 范围

- 选项: A) `$path` 和 `$namePattern` 都标 readonly / B) 只对 `$namePattern` 标 readonly / C) 补充说明
- 回答: A — 两者都标 readonly，配合 constructor promotion 重构赋值逻辑

## 约束与决策

- 纯语法现代化，不改变运行时行为，所有现有测试必须继续通过
- `LoggableApplication::__construct()` 保留重写，仅加类型声明
- `LocalFileHandler` 的 `$path` 和 `$namePattern` 使用 constructor promotion + readonly，需重构条件赋值逻辑（`$path` 的默认值处理移到参数默认值或构造函数体内）
- `$refreshRate` 因有 setter 不标 readonly
