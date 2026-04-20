# Manual

本目录用于记录系统的**使用与理解说明**（面向人）。

---

## 作用

回答：

- 系统如何使用？
- 系统如何理解？
- 常见操作方式是什么？

---

## 内容范围

- 使用说明
- 示例（examples）
- 常见问题（FAQ）
- 简要架构说明（必要时）

---

## 与 state 的区别

| 维度 | state | manual |
|------|-------|--------|
| 目的 | 定义系统 | 解释系统 |
| 是否 SSOT | 是 | 否 |
| 面向对象 | agent / dev | human |
| 内容类型 | 精确、结构化 | 解释性、示例性 |

---

## 原则

- 必须与实际行为一致（release 前同步）
- 不定义系统规则（规则在 state）
- 可以比 state 更易读，但不能更"权威"

---

## 当前文件索引

| 文件 | 覆盖范围 |
|------|----------|
| `data-provider.md` | ArrayDataProvider 使用方法、嵌套访问、异常处理 |
| `validators.md` | 各验证器用法示例、链式组合、strict vs 宽松模式 |
| `crypto.md` | Rc4 与 CaesarCipher 使用方法、查找表持久化 |
| `utils.md` | StringUtils / DataPacker / CommonUtils / AnsiColorizer |
