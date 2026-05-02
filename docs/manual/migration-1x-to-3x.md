# Migration Guide: 1.x → ^3.1

从 `oasis/logging` 1.x 升级到 ^3.1 的迁移指南。涵盖两次 major version bump（1.x → 2.0、2.x → 3.0）的所有 breaking changes。

---

## 环境要求

| 项目 | 1.x | ^3.1 |
|------|-----|------|
| PHP | >=5.x | >=8.2 |
| monolog/monolog | ^1.17 | ^3.0 |
| bramus/monolog-colored-line-formatter | ^2.0 | ^3.0 |
| oasis/utils | ^1.6 | ^3.0 |
| phpunit/phpunit（dev） | ^5.1 | ^11.0 |
| symfony/console（dev/suggest） | ^3.0 | ^7.0 |

升级前确认 PHP 版本 ≥ 8.2，并确保下游项目不依赖 `oasis/utils ^1.x` 或 `^2.x`。

---

## 第 1 步：更新 Composer 依赖

```bash
composer require oasis/logging:^3.1
```

如果存在版本冲突，需同步升级 `oasis/utils`：

```bash
composer require oasis/utils:^3.0 oasis/logging:^3.1
```

---

## 第 2 步：日志级别常量 → Level enum

这是最主要的 breaking change。所有使用 Monolog 整数常量的地方都需要改为 `Monolog\Level` enum。

### 添加 use 语句

```php
use Monolog\Level;
```

### 替换对照表

| 1.x（整数常量） | ^3.1（Level enum） |
|-----------------|-------------------|
| `Logger::DEBUG` | `Level::Debug` |
| `Logger::INFO` | `Level::Info` |
| `Logger::NOTICE` | `Level::Notice` |
| `Logger::WARNING` | `Level::Warning` |
| `Logger::ERROR` | `Level::Error` |
| `Logger::CRITICAL` | `Level::Critical` |
| `Logger::ALERT` | `Level::Alert` |
| `Logger::EMERGENCY` | `Level::Emergency` |

### 受影响的 API

```php
// ❌ 1.x
MLogging::setMinLogLevel(Logger::WARNING);
MLogging::setMinLogLevelForFileTrace(Logger::ERROR);
MLogging::enableAutoPublishingOnUnexpectedShutdown(Logger::ALERT);
MLogging::log(Logger::INFO, "message");
mtrace($e, "context: ", Logger::ERROR);

// ✅ ^3.1
MLogging::setMinLogLevel(Level::Warning);
MLogging::setMinLogLevelForFileTrace(Level::Error);
MLogging::enableAutoPublishingOnUnexpectedShutdown(Level::Alert);
MLogging::log(Level::Info, "message");
mtrace($e, "context: ", Level::Error);
```

Handler 构造函数同理：

```php
// ❌ 1.x
new ConsoleHandler(Logger::DEBUG);
new LocalFileHandler('/var/log', '%date%/%script%.log', Logger::INFO);

// ✅ ^3.1
new ConsoleHandler(Level::Debug);
new LocalFileHandler('/var/log', '%date%/%script%.log', Level::Info);
```

---

## 第 3 步：全局日志函数参数类型收紧

`mdebug`、`minfo`、`mnotice`、`mwarning`、`merror`、`mcritical`、`malert`、`memergency` 的第一个参数从无类型收紧为 `string|\Stringable`。

```php
// ❌ 1.x — 传入非字符串不会报错
mdebug(12345);
minfo(['key' => 'value']);

// ✅ ^3.1 — 必须传入 string 或 Stringable
mdebug("12345");
mdebug((string) 12345);
minfo(json_encode(['key' => 'value']));
```

> 注意：3.x 全文件启用了 `strict_types`，类型不匹配会直接抛出 `TypeError`。

---

## 第 4 步：Exception → Throwable

`mtrace()` 和 `getExceptionDebugInfo()` 的参数类型从 `\Exception` 扩展为 `\Throwable`。

这是向后兼容的扩展——已有代码无需修改，但现在可以传入 `\Error` 等非 `\Exception` 的 Throwable：

```php
try {
    // ...
} catch (\Throwable $e) {  // 可以捕获 Error
    mtrace($e);
}
```

---

## 第 5 步：Monolog 内部 API 变更（仅影响自定义 Handler / Processor）

如果你编写了自定义 Handler 或 Processor，需要注意以下变更：

| 变更点 | 1.x | ^3.1 |
|--------|-----|------|
| Handler 方法签名 | `write(array $record)` | `write(LogRecord $record)` |
| Processor 签名 | `function(array $record)` | `function(LogRecord $record)` |
| 修改 record | 直接修改数组 | 使用 `$record->with(...)` 返回新实例 |
| FingersCrossedHandler 触发策略 | `ErrorLevelActivationStrategy` | 直接传入 `Level` enum |

```php
use Monolog\LogRecord;

// ❌ 1.x
function myProcessor(array $record): array {
    $record['message'] .= ' [custom]';
    return $record;
}

// ✅ ^3.1
function myProcessor(LogRecord $record): LogRecord {
    return $record->with(message: $record->message . ' [custom]');
}
```

---

## 第 6 步：LocalFileHandler 属性变更（仅影响子类）

如果你继承了 `LocalFileHandler`：

- `$path` 和 `$namePattern` 现在是 `readonly`，子类无法覆写
- `$path` 类型从 `?string` 收窄为 `string`（非 nullable）

如果子类依赖覆写这些属性，需要改为通过构造函数参数传入。

---

## 第 7 步：PHPUnit 升级（仅影响测试代码）

| 变更点 | 1.x | ^3.1 |
|--------|-----|------|
| 基类 | `PHPUnit_Framework_TestCase` | `PHPUnit\Framework\TestCase` |
| 版本 | ^5.1 | ^11.0 |

---

## 第 8 步：Symfony Console 升级（仅影响 LoggableApplication 用户）

`symfony/console` 从 `^3.0` 升级到 `^7.0`。如果你使用 `LoggableApplication`，需确保项目中的 Symfony 组件版本兼容。

---

## 快速检查清单

- [ ] PHP ≥ 8.2
- [ ] `composer require oasis/logging:^3.1` 无冲突
- [ ] 全局搜索 `Logger::DEBUG`、`Logger::INFO` 等常量，替换为 `Level` enum
- [ ] 全局搜索 `setMinLogLevel`、`setMinLogLevelForFileTrace`、`enableAutoPublishingOnUnexpectedShutdown`、`mtrace` 调用，确认参数类型
- [ ] 检查 `mdebug`、`minfo` 等全局函数的调用，确认第一个参数是 `string|\Stringable`
- [ ] 如有自定义 Handler/Processor，适配 `LogRecord` 签名
- [ ] 如有 `LocalFileHandler` 子类，确认不依赖覆写 `$path` / `$namePattern`
- [ ] 运行测试，确认无 `TypeError` 或 deprecation warning
