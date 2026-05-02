# Changelog v3.1.0

本文件记录 v3.1.0 release 的变更内容。

---

## 工程变更

- PHP 最低版本要求从 `>=8.2` 提升到 `>=8.5`
- 升级 `symfony/console` 和 `symfony/finder` 从 `^7.0` 到 `^8.0`
- 升级 `phpunit/phpunit` 从 `^11.0` 到 `^13.0`
- 适配 Symfony 8.0 breaking change：`Application::add()` → `Application::addCommand()`

---

## 测试覆盖

- 全量测试通过：51 tests, 93 assertions
