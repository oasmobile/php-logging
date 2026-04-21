# Spec Goal: PHP 8 Support (Release 2.0.0)

## 来源

- 分支: `release/2.0.0`
- 需求文档: `docs/notes/php8-support.md`

## 背景摘要

`oasis/logging` 是 Monolog 的封装库，提供简化的日志接口（全局函数 `mdebug`/`minfo` 等）、自动文件追踪、控制台彩色输出、本地文件/错误日志 handler，以及 Symfony Console 集成。

当前代码库基于 PHP 5.x/7.x 生态构建，核心依赖均为旧版本：Monolog 1.x、PHPUnit 5.x、Symfony 3.x、bramus/monolog-colored-line-formatter 2.x。这些版本均不支持 PHP 8.2+，无法在现代 PHP 环境中运行。

本次 release 将项目升级到 PHP 8，最低支持 PHP 8.2，运行环境目标 PHP 8.5。这是一个 major version bump（1.x → 2.0.0），允许引入 breaking changes。

## 目标

- 将最低 PHP 版本要求设为 8.2
- 升级 `monolog/monolog` 到 `^3.0`
- 升级 `bramus/monolog-colored-line-formatter` 到 `^3.0`
- 升级 `oasis/utils` 版本约束以适配 PHP 8（假设该包会提供兼容版本）
- 升级 `phpunit/phpunit` 到 `^11.0`
- 升级 `symfony/console` 和 `symfony/finder` 到 `^7.0`
- 修复所有因 Monolog 3.x API 变更导致的代码不兼容（如 `HandlerInterface` 签名变化、`Logger` API 变更等）
- 修复所有因 PHP 8.x 语法/行为变更导致的代码不兼容
- 更新测试代码以适配 PHPUnit 11.x（类名、API、配置文件格式等）
- 确保所有现有测试在 PHP 8.2+ 环境下通过

## 不做的事情（Non-Goals）

- 不增加新功能，仅做兼容性升级
- 不保留 PHP 7.x 向后兼容
- 不处理 `oasis/utils` 自身的 PHP 8 兼容性（由该包自行保证）
- 不重构现有架构或代码结构（除非 PHP 8 / Monolog 3 兼容性要求必须调整）

## Clarification 记录

### Q1: Monolog 版本升级策略

- 选项: A) Monolog 3.x / B) Monolog 2.x / C) 补充说明
- 回答: A — 升级到 Monolog 3.x

### Q2: bramus/monolog-colored-line-formatter 兼容性处理

- 选项: A) 升级到 ^3.0 / B) 自行实现替代 / C) 先调研再决定 / D) 补充说明
- 回答: A — 升级到 ^3.0

### Q3: oasis/utils 依赖的 PHP 8 兼容性

- 选项: A) 假设有兼容版本，直接升级约束 / B) 内联工具方法去掉依赖 / C) 补充说明
- 回答: A — 假设有兼容版本，升级版本约束

### Q4: PHPUnit 升级策略

- 选项: A) PHPUnit 11.x / B) PHPUnit 10.x / C) 补充说明
- 回答: A — 升级到 PHPUnit 11.x

### Q5: Symfony 组件版本升级

- 选项: A) Symfony 7.x / B) Symfony 6.x / C) 补充说明
- 回答: A — 升级到 Symfony 7.x

## 约束与决策

- **PHP 版本范围**：最低 8.2，目标运行环境 8.5
- **全依赖升级到最新稳定线**：Monolog 3.x、PHPUnit 11.x、Symfony 7.x、bramus formatter 3.x
- **Breaking changes 可接受**：这是 major version bump，不需要向后兼容 PHP 7 或 Monolog 1.x
- **oasis/utils 外部依赖**：本 spec 不负责其 PHP 8 兼容性，仅升级版本约束
- **纯兼容性升级**：不引入新功能，不重构架构
