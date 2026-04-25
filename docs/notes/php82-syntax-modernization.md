# PHP 8.2 Syntax Modernization

> 来源：日常观察

项目 `composer.json` 已声明 `php >= 8.2`，核心逻辑（enum、`match`、`str_ends_with()` 等）已迁移到 8.x 风格，但属性声明和函数签名仍有旧写法残留，值得统一清理。

---

## 待改进项

- `MLogging` 静态属性缺少类型声明（`$logger`、`$autoPublishingOnFatalError`、`$autoPublisherRegistered`、`$handlers`）
- `MLogging.inc.php` 全局函数缺少返回类型（`mdebug`、`minfo` 等应标 `void`；`mdump` 应标 `string`）
- `LoggableApplication::__construct()` 参数无类型声明
- `LocalFileHandler` 可使用 constructor promotion 简化属性初始化
- 构造后不再变更的属性可标记 `readonly`（如 `LocalFileHandler::$path`、`$namePattern`）
