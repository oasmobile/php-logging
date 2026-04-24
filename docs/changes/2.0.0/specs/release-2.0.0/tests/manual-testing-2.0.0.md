# 手工测试 Checklist — Release 2.0.0

> PHP 8 生态升级（Monolog 3.x / PHPUnit 11.x / PHP 8.2+）的手工测试清单。

## 前置条件

- PHP 8.2+ 环境（`php -v` 确认）
- `composer install` 已完成，`vendor/` 目录存在
- `symfony/console` ^7.0 已安装（`require-dev` 依赖）

## 测试脚本

自动化测试脚本位于 `.kiro/specs/release-2.0.0/tests/test-task-6.sh`，覆盖以下全部场景。

执行方式：

```bash
bash .kiro/specs/release-2.0.0/tests/test-task-6.sh
```

---

## Scenario 1: 全局日志函数控制台输出

**覆盖 Requirements**: 10.3

**验证内容**：

- [ ] `mdebug()` ~ `memergency()` 共 8 个函数均产生输出
- [ ] 每条日志包含正确的级别标识（DEBUG / INFO / NOTICE / WARNING / ERROR / CRITICAL / ALERT / EMERGENCY）
- [ ] 每条日志包含文件追踪注解（`(filename:line)` 格式）

**自动化覆盖**：全部由脚本自动验证。彩色效果在管道中丢失，需在终端中直接运行 `test.php` 确认。

---

## Scenario 2: mtrace() 传入 \Throwable 实例

**覆盖 Requirements**: 9.3

**验证内容**：

- [ ] `mtrace()` 接受 `\TypeError` 实例（非 `\Exception`）不报错
- [ ] 输出包含异常类名 `TypeError`
- [ ] 输出包含异常消息文本
- [ ] 输出包含 `prompt_string` 前缀
- [ ] 输出包含堆栈追踪信息（`#0`、`#1` 等帧编号）

**自动化覆盖**：全部由脚本自动验证。

---

## Scenario 3: LoggableApplication 不同 verbosity 级别

**覆盖 Requirements**: 6.3

**验证内容**：

| Verbosity 模式 | 参数 | 预期可见级别 | 预期不可见级别 |
|----------------|------|-------------|---------------|
| QUIET | `-q` | 无 | 全部 |
| NORMAL | （默认） | WARNING, ERROR | DEBUG, INFO, NOTICE |
| VERBOSE | `-v` | NOTICE, WARNING, ERROR | DEBUG, INFO |
| VERY_VERBOSE | `-vv` | INFO, NOTICE, WARNING, ERROR | DEBUG |
| DEBUG | `-vvv` | 全部 | 无 |

- [ ] QUIET 模式无日志输出（NullHandler 吞掉所有日志）
- [ ] NORMAL 模式仅输出 WARNING 及以上
- [ ] VERBOSE 模式仅输出 NOTICE 及以上
- [ ] VERY_VERBOSE 模式仅输出 INFO 及以上
- [ ] DEBUG 模式输出全部级别

**自动化覆盖**：全部由脚本自动验证。

---

## Scenario 4: LocalFileHandler 文件名模式和 refreshRate

**覆盖 Requirements**: 3.1, 10.3

**验证内容**：

- [ ] 日志文件按 `%date%/%script%.log` 模式创建（日期目录 + 脚本名文件）
- [ ] 日志文件内容包含写入的测试消息
- [ ] 设置 `setRefreshRate(2)` 后，间隔 2 秒的写入产生不同的日志文件（文件名包含时间戳部分刷新）

**自动化覆盖**：全部由脚本自动验证。
