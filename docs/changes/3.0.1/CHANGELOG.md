# Changelog v3.0.1

本文件记录 v3.0.1 hotfix 的变更内容。

---

## 工程变更

- 所有 `src/` 和 `ut/` 文件添加 `declare(strict_types=1)`
- `MLogging` 提取 `handleUnexpectedShutdown()` 和 `publishFatalIfNeeded()` 为 public static 方法，提升可测试性
- 新增 `ut/CoverageBoostTest.php`，行覆盖率从 82.78% 提升至 97.16%
- `phpunit.xml` 注册新测试文件

---

## 测试覆盖

- 行覆盖率：97.16%（207/211）
- 方法覆盖率：83.33%（20/24）
- `LocalFileHandler`、`LocalErrorHandler`、`MLoggingHandlerTrait` 达到 100%
