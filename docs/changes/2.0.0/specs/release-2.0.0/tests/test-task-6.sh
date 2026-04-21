#!/usr/bin/env bash
# =============================================================================
# Manual Test Script — Task 6: 手工测试
# Spec: .kiro/specs/release-2.0.0
#
# 本脚本自动执行所有手工测试场景，输出 PASS/FAIL 判定。
# 彩色输出效果需人工目视确认（脚本仅验证内容正确性）。
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
PASS_COUNT=0
FAIL_COUNT=0
VISUAL_COUNT=0

pass() {
  echo -e "  \033[32m✓ PASS\033[0m $1"
  PASS_COUNT=$((PASS_COUNT + 1))
}

fail() {
  echo -e "  \033[31m✗ FAIL\033[0m $1"
  FAIL_COUNT=$((FAIL_COUNT + 1))
}

visual() {
  echo -e "  \033[33m👁 VISUAL\033[0m $1"
  VISUAL_COUNT=$((VISUAL_COUNT + 1))
}

section() {
  echo ""
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "  $1"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

# ─────────────────────────────────────────────────────────────────────────────
# 准备临时目录
# ─────────────────────────────────────────────────────────────────────────────
TMPDIR_BASE=$(mktemp -d /tmp/mlogging-manual-test.XXXXXX)
trap "rm -rf $TMPDIR_BASE" EXIT

echo "Project root: $PROJECT_ROOT"
echo "Temp dir:     $TMPDIR_BASE"
echo "PHP version:  $(php -r 'echo PHP_VERSION;')"

# =============================================================================
# Scenario 1: 8 个全局日志函数控制台输出
# Requirements: 10.3
# =============================================================================
section "Scenario 1: 8 个全局日志函数（mdebug ~ memergency）控制台输出"

SCENARIO1_OUTPUT=$(php -r '
require_once "'"$PROJECT_ROOT"'/vendor/autoload.php";
(new \Oasis\Mlib\Logging\ConsoleHandler())->install();
mdebug("test-debug-message");
minfo("test-info-message");
mnotice("test-notice-message");
mwarning("test-warning-message");
merror("test-error-message");
mcritical("test-critical-message");
malert("test-alert-message");
memergency("test-emergency-message");
' 2>&1)

for func_level in "debug:DEBUG" "info:INFO" "notice:NOTICE" "warning:WARNING" "error:ERROR" "critical:CRITICAL" "alert:ALERT" "emergency:EMERGENCY"; do
  func="${func_level%%:*}"
  level="${func_level##*:}"
  if echo "$SCENARIO1_OUTPUT" | grep -q "test-${func}-message"; then
    pass "m${func}() 输出包含 test-${func}-message"
  else
    fail "m${func}() 输出未包含 test-${func}-message"
  fi
  if echo "$SCENARIO1_OUTPUT" | grep -q "$level"; then
    pass "m${func}() 输出包含级别标识 $level"
  else
    fail "m${func}() 输出未包含级别标识 $level"
  fi
done

# 文件追踪注解检查（每条日志应包含调用文件名和行号）
TRACE_COUNT=$(echo "$SCENARIO1_OUTPUT" | grep -c '(Command line code:' || true)
if [ "$TRACE_COUNT" -ge 8 ]; then
  pass "所有 8 条日志均包含文件追踪注解"
else
  # php -r 模式下文件名可能不同，检查是否有括号注解
  PAREN_COUNT=$(echo "$SCENARIO1_OUTPUT" | grep -cE '\([^)]+:[0-9]+\)' || true)
  if [ "$PAREN_COUNT" -ge 8 ]; then
    pass "所有 8 条日志均包含文件追踪注解"
  else
    fail "文件追踪注解不足 8 条（实际 $PAREN_COUNT 条）"
  fi
fi

# 彩色输出在管道中丢失，无法通过脚本验证；需在终端中直接运行 test.php 确认
echo ""
echo "--- 控制台输出（彩色在管道中丢失）---"
echo "$SCENARIO1_OUTPUT"
echo "--- 控制台输出结束 ---"

# =============================================================================
# Scenario 2: mtrace() 传入 \TypeError 实例
# Requirements: 9.3
# =============================================================================
section "Scenario 2: mtrace() 传入 \\TypeError 实例"

SCENARIO2_OUTPUT=$(php -r '
require_once "'"$PROJECT_ROOT"'/vendor/autoload.php";
(new \Oasis\Mlib\Logging\ConsoleHandler())->install();
try {
    throw new \TypeError("test type error for manual verification");
} catch (\Throwable $e) {
    mtrace($e, "Caught TypeError: ");
}
' 2>&1)

if echo "$SCENARIO2_OUTPUT" | grep -q "TypeError"; then
  pass "mtrace() 输出包含异常类名 TypeError"
else
  fail "mtrace() 输出未包含异常类名 TypeError"
fi

if echo "$SCENARIO2_OUTPUT" | grep -q "test type error for manual verification"; then
  pass "mtrace() 输出包含异常消息"
else
  fail "mtrace() 输出未包含异常消息"
fi

if echo "$SCENARIO2_OUTPUT" | grep -q "Caught TypeError:"; then
  pass "mtrace() 输出包含 prompt_string"
else
  fail "mtrace() 输出未包含 prompt_string"
fi

if echo "$SCENARIO2_OUTPUT" | grep -qE '#[0-9]+'; then
  pass "mtrace() 输出包含堆栈追踪信息"
else
  fail "mtrace() 输出未包含堆栈追踪信息"
fi

echo ""
echo "--- mtrace 输出开始 ---"
echo "$SCENARIO2_OUTPUT"
echo "--- mtrace 输出结束 ---"

# =============================================================================
# Scenario 3: LoggableApplication 不同 verbosity 级别
# Requirements: 6.3
# =============================================================================
section "Scenario 3: LoggableApplication 不同 verbosity 级别"

# 创建临时 Symfony Console 命令脚本
CONSOLE_SCRIPT="$TMPDIR_BASE/test-console-app.php"
cat > "$CONSOLE_SCRIPT" << 'CONSOLE_PHP'
<?php
require_once '__AUTOLOAD__';

use Oasis\Mlib\Logging\LoggableApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestLogCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('test:log')
             ->setDescription('Test logging at various levels');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        mdebug("verbosity-test-debug");
        minfo("verbosity-test-info");
        mnotice("verbosity-test-notice");
        mwarning("verbosity-test-warning");
        merror("verbosity-test-error");
        return Command::SUCCESS;
    }
}

$app = new LoggableApplication('test', '1.0');
$app->add(new TestLogCommand());
$app->setDefaultCommand('test:log', true);
$app->run();
CONSOLE_PHP

sed -i '' "s|__AUTOLOAD__|$PROJECT_ROOT/vendor/autoload.php|" "$CONSOLE_SCRIPT"

echo ""
echo "  Testing QUIET mode (-q)..."
QUIET_OUTPUT=$(php "$CONSOLE_SCRIPT" -q 2>&1)
QUIET_LINE_COUNT=$(echo "$QUIET_OUTPUT" | grep -c "verbosity-test" || true)
if [ "$QUIET_LINE_COUNT" -eq 0 ]; then
  pass "QUIET 模式：NullHandler 吞掉所有日志，无输出"
else
  fail "QUIET 模式：不应有日志输出（实际 $QUIET_LINE_COUNT 条）"
fi

echo "  Testing NORMAL mode (default)..."
NORMAL_OUTPUT=$(php "$CONSOLE_SCRIPT" 2>&1)
# NORMAL = WARNING level, should see warning and error, not debug/info/notice
if echo "$NORMAL_OUTPUT" | grep -q "verbosity-test-warning"; then
  pass "NORMAL 模式：包含 warning 级别日志"
else
  fail "NORMAL 模式：未包含 warning 级别日志"
fi
if echo "$NORMAL_OUTPUT" | grep -q "verbosity-test-error"; then
  pass "NORMAL 模式：包含 error 级别日志"
else
  fail "NORMAL 模式：未包含 error 级别日志"
fi
if echo "$NORMAL_OUTPUT" | grep -q "verbosity-test-debug"; then
  fail "NORMAL 模式：不应包含 debug 级别日志"
else
  pass "NORMAL 模式：正确过滤了 debug 级别日志"
fi
if echo "$NORMAL_OUTPUT" | grep -q "verbosity-test-info"; then
  fail "NORMAL 模式：不应包含 info 级别日志"
else
  pass "NORMAL 模式：正确过滤了 info 级别日志"
fi

echo "  Testing VERBOSE mode (-v)..."
VERBOSE_OUTPUT=$(php "$CONSOLE_SCRIPT" -v 2>&1)
# VERBOSE = NOTICE level, should see notice/warning/error
if echo "$VERBOSE_OUTPUT" | grep -q "verbosity-test-notice"; then
  pass "VERBOSE 模式：包含 notice 级别日志"
else
  fail "VERBOSE 模式：未包含 notice 级别日志"
fi
if echo "$VERBOSE_OUTPUT" | grep -q "verbosity-test-debug"; then
  fail "VERBOSE 模式：不应包含 debug 级别日志"
else
  pass "VERBOSE 模式：正确过滤了 debug 级别日志"
fi

echo "  Testing VERY_VERBOSE mode (-vv)..."
VV_OUTPUT=$(php "$CONSOLE_SCRIPT" -vv 2>&1)
# VERY_VERBOSE = INFO level, should see info/notice/warning/error
if echo "$VV_OUTPUT" | grep -q "verbosity-test-info"; then
  pass "VERY_VERBOSE 模式：包含 info 级别日志"
else
  fail "VERY_VERBOSE 模式：未包含 info 级别日志"
fi
if echo "$VV_OUTPUT" | grep -q "verbosity-test-debug"; then
  fail "VERY_VERBOSE 模式：不应包含 debug 级别日志"
else
  pass "VERY_VERBOSE 模式：正确过滤了 debug 级别日志"
fi

echo "  Testing DEBUG mode (-vvv)..."
VVV_OUTPUT=$(php "$CONSOLE_SCRIPT" -vvv 2>&1)
# DEBUG = all levels
if echo "$VVV_OUTPUT" | grep -q "verbosity-test-debug"; then
  pass "DEBUG 模式：包含 debug 级别日志"
else
  fail "DEBUG 模式：未包含 debug 级别日志"
fi
if echo "$VVV_OUTPUT" | grep -q "verbosity-test-info"; then
  pass "DEBUG 模式：包含 info 级别日志"
else
  fail "DEBUG 模式：未包含 info 级别日志"
fi

# =============================================================================
# Scenario 4: LocalFileHandler 文件名模式和 refreshRate 刷新行为
# Requirements: 3.1 (CLI 环境), 10.3
# =============================================================================
section "Scenario 4: LocalFileHandler 文件名模式和 refreshRate 刷新行为"

LOG_DIR="$TMPDIR_BASE/logs"
mkdir -p "$LOG_DIR"

echo ""
echo "  Testing file name pattern..."
SCENARIO4A_OUTPUT=$(php -r '
require_once "'"$PROJECT_ROOT"'/vendor/autoload.php";
$lfh = new \Oasis\Mlib\Logging\LocalFileHandler("'"$LOG_DIR"'", "%date%/%script%.log");
$lfh->install();
mdebug("file-pattern-test-message");
' 2>&1)

# 检查日志文件是否按模式创建
DATE_DIR=$(date +%Y%m%d)
if [ -d "$LOG_DIR/$DATE_DIR" ]; then
  pass "文件名模式：日期目录 $DATE_DIR 已创建"
else
  fail "文件名模式：日期目录 $DATE_DIR 未创建"
fi

LOG_FILES=$(find "$LOG_DIR" -name "*.log" -type f 2>/dev/null)
if [ -n "$LOG_FILES" ]; then
  pass "文件名模式：日志文件已创建"
  # 检查日志内容
  if grep -rq "file-pattern-test-message" "$LOG_DIR"; then
    pass "文件名模式：日志文件包含测试消息"
  else
    fail "文件名模式：日志文件未包含测试消息"
  fi
else
  fail "文件名模式：未找到日志文件"
fi

echo "  Testing refreshRate (2s interval, 4 writes over ~5s)..."
REFRESH_LOG_DIR="$TMPDIR_BASE/refresh-logs"
mkdir -p "$REFRESH_LOG_DIR"

php -r '
require_once "'"$PROJECT_ROOT"'/vendor/autoload.php";
$lfh = new \Oasis\Mlib\Logging\LocalFileHandler(
    "'"$REFRESH_LOG_DIR"'",
    "%date%/%hour%-%minute%-%second%-%script%.log"
);
$lfh->install();
$lfh->setRefreshRate(2);

for ($i = 0; $i < 4; $i++) {
    mdebug("refresh-test-iteration-$i");
    if ($i < 3) sleep(2);
}
' 2>&1

REFRESH_FILE_COUNT=$(find "$REFRESH_LOG_DIR" -name "*.log" -type f 2>/dev/null | wc -l | tr -d ' ')
if [ "$REFRESH_FILE_COUNT" -ge 2 ]; then
  pass "refreshRate：生成了 $REFRESH_FILE_COUNT 个日志文件（≥2，说明文件名刷新生效）"
else
  fail "refreshRate：仅生成了 $REFRESH_FILE_COUNT 个日志文件（预期 ≥2）"
fi

# =============================================================================
# 汇总
# =============================================================================
section "测试汇总"

TOTAL=$((PASS_COUNT + FAIL_COUNT))
echo ""
echo "  自动验证: $PASS_COUNT passed / $FAIL_COUNT failed (共 $TOTAL 项)"
echo "  人工确认: $VISUAL_COUNT 项"
echo ""

if [ "$FAIL_COUNT" -gt 0 ]; then
  echo -e "  \033[31m结果: 存在失败项，请检查上方输出\033[0m"
  exit 1
else
  echo -e "  \033[32m结果: 所有自动验证项通过\033[0m"
  if [ "$VISUAL_COUNT" -gt 0 ]; then
    echo -e "  \033[33m请人工确认 $VISUAL_COUNT 项目视检查项\033[0m"
  fi
  exit 0
fi
