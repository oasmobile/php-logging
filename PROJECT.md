# MLogging (oasis/logging)

## 技术栈

- 语言：PHP
- 包管理：Composer
- 日志底层：monolog/monolog ^1.17
- 测试框架：PHPUnit ^5.1

## 命令

| 用途 | 命令 |
|------|------|
| 安装依赖 | `composer install` |
| 运行测试 | `vendor/bin/phpunit` |

## 版本号位置

- `composer.json` → `version` 字段（当前未显式声明，由 Packagist 从 git tag 推断）

## 敏感文件

无（纯库项目，不含凭证或环境配置）
