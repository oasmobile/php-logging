# Changelog v3.1.1

本文件记录 v3.1.1 hotfix 的变更内容。

---

## 修复与改进

- 升级 `oasis/utils` 依赖从 `^3.0` 到 `^3.1`
- MLogging 源码语法现代化：`get_class()` → `::class`、短闭包、多参数 `isset`

---

## 测试覆盖

- 新增 Eris property-based testing（PBT），覆盖日志写入、级别过滤、格式化等 10 个属性
- 添加 `giorgiosironi/eris` ^1.1 开发依赖
- 全量测试通过：61 tests, 1893 assertions
