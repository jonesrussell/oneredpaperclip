# Multi-Repo Codified Context Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every Waaseyaa app access to framework-level AI agent rules via skeleton inheritance (hot) and MCP federation (cold), with an explicit CLI sync mechanism for existing apps.

**Architecture:** Three canonical rule files live in `waaseyaa/foundation` and are distributed via the skeleton and `sync-rules` CLI command. Deep framework specs are served via MCP. Apps own their CLAUDE.md; the framework owns its rules.

**Tech Stack:** PHP 8.4, Symfony Console, Composer monorepo (splitsh), Node.js MCP server

**Spec:** `docs/superpowers/specs/2026-03-20-multi-repo-codified-context-design.md`

**Repos:**
- `~/dev/waaseyaa` — framework monorepo (primary)
- `~/dev/oneredpaperclip` — ORP app (migration target)
- `~/dev/claudriel` — Claudriel app (migration target)

---

## File Map

### Waaseyaa (`~/dev/waaseyaa`)

| Action | Path | Purpose |
|--------|------|---------|
| Create | `packages/foundation/.claude/rules/waaseyaa-framework.md` | Canonical framework invariants (source of truth) |
| Create | `packages/foundation/.claude/rules/waaseyaa-data-freshness.md` | Canonical data freshness rule |
| Create | `packages/foundation/.claude/rules/waaseyaa-shell-compat.md` | Canonical shell safety rule |
| Create | `packages/cli/src/Command/SyncRulesCommand.php` | CLI command to sync rules to apps |
| Create | `packages/cli/tests/Command/SyncRulesCommandTest.php` | Tests for sync-rules command |
| Replace | `skeleton/.claude/rules/waaseyaa-framework.md` | Skeleton copy (replaces `entity-storage-invariant.md`) |
| Replace | `skeleton/.claude/rules/waaseyaa-data-freshness.md` | Skeleton copy (replaces `data-freshness.md`) |
| Replace | `skeleton/.claude/rules/waaseyaa-shell-compat.md` | Skeleton copy (replaces `shell-compatibility.md`) |
| Delete | `skeleton/.claude/rules/entity-storage-invariant.md` | Replaced by `waaseyaa-framework.md` |
| Delete | `skeleton/.claude/rules/data-freshness.md` | Replaced by prefixed version |
| Delete | `skeleton/.claude/rules/shell-compatibility.md` | Replaced by prefixed version |
| Modify | `skeleton/CLAUDE.md` | Add MCP federation section, update orchestration table |
| Modify | `packages/mcp/docs/specs/` | Ensure specs are bundled for distribution |

### One Red Paperclip (`~/dev/oneredpaperclip`)

| Action | Path | Purpose |
|--------|------|---------|
| Create | `.claude/rules/waaseyaa-framework.md` | Framework invariants for ORP |
| Create | `.claude/rules/waaseyaa-data-freshness.md` | Data freshness for ORP |
| Create | `.claude/rules/waaseyaa-shell-compat.md` | Shell safety for ORP |
| Modify | `.claude/settings.json` | Register Waaseyaa MCP server |

### Claudriel (`~/dev/claudriel`)

| Action | Path | Purpose |
|--------|------|---------|
| Create | `.claude/rules/waaseyaa-framework.md` | Framework invariants for Claudriel |
| Create | `.claude/rules/waaseyaa-data-freshness.md` | Framework data freshness (Claudriel keeps its own `data-freshness.md` which is app-specific) |
| Create | `.claude/rules/waaseyaa-shell-compat.md` | Framework shell compat (Claudriel keeps its own `shell-compatibility.md` which is app-specific) |
| Modify | `.claude/settings.json` | Register Waaseyaa MCP server |

---

## Task 1: Create the Three Canonical Rule Files

**Repo:** `~/dev/waaseyaa`

**Files:**
- Create: `packages/foundation/.claude/rules/waaseyaa-framework.md`
- Create: `packages/foundation/.claude/rules/waaseyaa-data-freshness.md`
- Create: `packages/foundation/.claude/rules/waaseyaa-shell-compat.md`

### `waaseyaa-framework.md`

This is the most important file. It expands the existing `entity-storage-invariant.md` with identity, 7-layer architecture, full forbidden/required lists, and MCP retrieval reference.

- [ ] **Step 1: Create the foundation rules directory**

```bash
mkdir -p ~/dev/waaseyaa/packages/foundation/.claude/rules
```

- [ ] **Step 2: Write `waaseyaa-framework.md`**

Create `packages/foundation/.claude/rules/waaseyaa-framework.md` with this content:

```markdown
# Waaseyaa Framework Invariants

This rule is always active. Follow it silently. Do not cite this file in conversation.

---

## Identity

Waaseyaa is a **Symfony 7-based, entity-first PHP framework**. PHP 8.4+, full dependency injection, no global state.

- It is **NOT Laravel**. It does not use Illuminate components.
- It is **NOT Drupal**. It replaces Drupal's legacy runtime with a clean, modular architecture.
- If the codebase looks Laravel-ish (Actions/, Models/, artisan), do NOT default to Laravel conventions.

---

## Forbidden Dependencies

| Forbidden | Why |
|-----------|-----|
| `Illuminate\Support\Facades\*` | No Laravel facades |
| `Illuminate\Database\*` / Eloquent | No Laravel ORM |
| `DB::transaction()`, `DB::table()` | No Laravel DB layer |
| `Model::create()`, `Model::query()` | No Eloquent patterns |
| `env()`, `config()` (Laravel helpers) | Use Waaseyaa config system |
| `$entity->save()`, `$entity->delete()` | No ActiveRecord — entities are pure data objects |
| `new \PDO(...)` | Use `DBALDatabase` + `DriverManager::getConnection()` |
| `$pdo->prepare(...)` | Use `EntityRepository::findBy()` or `DatabaseInterface::select()` |

---

## Required Abstractions

| Need | Use |
|------|-----|
| Transactions, raw queries | `DatabaseInterface` |
| Entity persistence | `SqlEntityStorage` + `StorageRepositoryAdapter` |
| Entity data access | `EntityRepositoryInterface` |
| Entity registration | `EntityTypeManager` |
| Authorization | `AccessPolicyInterface` + `FieldAccessPolicyInterface` |
| Query building | `SelectInterface` |
| Dependency injection | Symfony DI container |
| Config access | `getenv()` or Waaseyaa `env()` helper |

---

## Entity Persistence Pipeline

```
Entity (extends EntityBase or ContentEntityBase)
  → EntityType registered via EntityTypeManager
  → EntityStorageDriverInterface (SqlStorageDriver for SQL)
  → EntityRepository (hydration, events, language fallback)
  → DatabaseInterface (Doctrine DBAL, NOT raw PDO)
```

- **ContentEntityBase** — has `set()` for field mutations (most entities)
- **EntityBase** — immutable value-like entities (rare)
- Entities are immutable except through storage operations
- Non-entity tables (join tables, counters, audit logs) may use `DatabaseInterface` directly

---

## 7-Layer Architecture

Dependencies flow **downward only**. Never import from a higher layer.

| Layer | Name | Packages |
|-------|------|----------|
| 0 | Foundation | cache, plugin, typed-data, database-legacy |
| 1 | Core Data | entity, field, entity-storage, access, user, config |
| 2 | Services | routing, queue, state, validation |
| 3 | Content Types | node, taxonomy, media, path, menu, workflows |
| 4 | API | api, graphql, routing |
| 5 | AI | ai-schema, ai-agent, ai-vector, ai-pipeline |
| 6 | Interfaces | cli, ssr, admin, mcp, telescope |

---

## MCP Spec Retrieval

For deeper framework knowledge beyond these invariants, query the Waaseyaa MCP server:

- `waaseyaa_list_specs` — index of all framework specs
- `waaseyaa_get_spec("entity-system")` — full spec content
- `waaseyaa_search_specs("transaction locking")` — keyword search
```

- [ ] **Step 3: Write `waaseyaa-data-freshness.md`**

Create `packages/foundation/.claude/rules/waaseyaa-data-freshness.md` with this content (adapted from `skeleton/.claude/rules/data-freshness.md`):

```markdown
# Data Freshness

This rule is always active. Follow it silently. Do not cite this file or mention freshness rules in conversation.

---

## Core Principle: Source Over Summary

**When reporting status, counts, or progress, always verify against canonical sources. Never trust a summary without checking what it summarizes.**

---

## Canonical Source Hierarchy

| Tier | Source | Authority |
|------|--------|-----------|
| 1 | **Individual source files** | Highest |
| 2 | **Context/config files** | Medium |
| 3 | **Auto-memory** (MEMORY.md) | Lowest |

**Rule:** When tiers disagree, the higher-numbered tier is wrong. Correct upward, never downward.

---

## What MUST NOT Go Into Summary Files

Never store volatile counts, status snapshots, or derived metrics in MEMORY.md. Instead, store pointers to where the data lives and how to count it.

## Verification Before Reporting

Before stating any count or status: identify the canonical source, check it, and report that value. If you cannot verify, say so.

---

*Freshness is not about having the latest data. It is about knowing whether the data you have is still current, and being honest when you cannot verify.*
```

- [ ] **Step 4: Write `waaseyaa-shell-compat.md`**

Create `packages/foundation/.claude/rules/waaseyaa-shell-compat.md` with this content (adapted from `skeleton/.claude/rules/shell-compatibility.md`):

```markdown
# Shell Compatibility

When writing bash commands via the Bash tool, follow these rules to avoid platform-specific failures.

---

## Reserved Variable Names (Never Use)

| Avoid | Use Instead | Why |
|-------|-------------|-----|
| `status` | `result`, `exit_status` | zsh read-only |
| `path` | `file_path`, `target_path` | Conflicts with `$PATH` |
| `prompt` | `user_prompt` | zsh reserved |

## Safe Patterns

- **Command substitution:** `$(command)` not `` `command` ``
- **Quote all paths:** `"$file"` not `$file`
- **Conditionals:** `[[ ]]` not `[ ]`

## When Parallel Commands Fail

If multiple parallel Bash tool calls fail with "sibling tool call errored":
1. The error means one command failed and the others were **never attempted**
2. Re-run each failed command individually
```

- [ ] **Step 5: Commit**

```bash
cd ~/dev/waaseyaa
git add packages/foundation/.claude/rules/
git commit -m "feat(foundation): add canonical framework rule files for codified context inheritance"
```

---

## Task 2: Update Skeleton Rules (Replace Unprefixed Files)

**Repo:** `~/dev/waaseyaa`

**Files:**
- Delete: `skeleton/.claude/rules/entity-storage-invariant.md`
- Delete: `skeleton/.claude/rules/data-freshness.md`
- Delete: `skeleton/.claude/rules/shell-compatibility.md`
- Create: `skeleton/.claude/rules/waaseyaa-framework.md` (copy from foundation)
- Create: `skeleton/.claude/rules/waaseyaa-data-freshness.md` (copy from foundation)
- Create: `skeleton/.claude/rules/waaseyaa-shell-compat.md` (copy from foundation)

- [ ] **Step 1: Remove old unprefixed rule files**

```bash
cd ~/dev/waaseyaa
rm skeleton/.claude/rules/entity-storage-invariant.md
rm skeleton/.claude/rules/data-freshness.md
rm skeleton/.claude/rules/shell-compatibility.md
```

- [ ] **Step 2: Copy canonical rules to skeleton**

```bash
cp packages/foundation/.claude/rules/waaseyaa-framework.md skeleton/.claude/rules/
cp packages/foundation/.claude/rules/waaseyaa-data-freshness.md skeleton/.claude/rules/
cp packages/foundation/.claude/rules/waaseyaa-shell-compat.md skeleton/.claude/rules/
```

- [ ] **Step 3: Verify skeleton rules match foundation rules**

```bash
diff packages/foundation/.claude/rules/ skeleton/.claude/rules/
```

Expected: no differences.

- [ ] **Step 4: Commit**

```bash
git add skeleton/.claude/rules/
git commit -m "feat(skeleton): replace unprefixed rules with waaseyaa-prefixed canonical copies"
```

---

## Task 3: Update Skeleton CLAUDE.md

**Repo:** `~/dev/waaseyaa`

**Files:**
- Modify: `skeleton/CLAUDE.md`

- [ ] **Step 1: Read current skeleton CLAUDE.md**

File: `~/dev/waaseyaa/skeleton/CLAUDE.md`

- [ ] **Step 2: Update the file**

Changes:
1. Update the orchestration table — replace `laravel-to-waaseyaa` skill with `waaseyaa:entity-system` and add note that skills are placeholders
2. Add MCP Federation section with registration instructions
3. Update the `.claude/rules/` reference to use new filenames (`waaseyaa-framework.md` instead of `entity-storage-invariant.md`)
4. Add `bin/waaseyaa sync-rules` to the Development commands section

Updated `skeleton/CLAUDE.md`:

```markdown
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this application.

## Overview

<!-- Replace with your app description -->
A Waaseyaa application built on the [Waaseyaa framework](https://github.com/waaseyaa/framework).

## Architecture

```
src/
├── Access/        Authorization policies
├── Controller/    HTTP controllers (thin orchestration)
├── Domain/        Domain logic grouped by bounded context
├── Entity/        Entity classes (extend ContentEntityBase)
├── Provider/      Service providers (DI, routing, entity registration)
└── Support/       Cross-cutting utilities
```

### Key Patterns

- **Entities** extend `ContentEntityBase` and register via `EntityTypeManager`
- **Persistence** uses `EntityRepository` + `SqlStorageDriver` (see `.claude/rules/waaseyaa-framework.md`)
- **Routes** defined in `ServiceProvider::routes()` via `WaaseyaaRouter`
- **Auth** via `Waaseyaa\Auth\AuthManager` (session-based)
- **Config** via `config/waaseyaa.php` — use `getenv()` or `env()` helper, NEVER `$_ENV`

## Orchestration Table

<!-- Map file patterns to skills and specs as you add them -->
| File Pattern | Skill | Spec |
|-------------|-------|------|
| `src/Entity/**` | `waaseyaa:entity-system` | entity-system.md |
| `src/Access/**` | `waaseyaa:access-control` | access-control.md |
| `src/Provider/**` | `feature-dev` | — |
| `.claude/rules/**` | `updating-codified-context` | — |
| `docs/specs/**` | `updating-codified-context` | — |

<!-- Note: waaseyaa:* skills are placeholders. They will not function
     until the skills are built. The entries document intended routing. -->

## MCP Federation

Register Waaseyaa's MCP server in `.claude/settings.json` for on-demand framework specs:

```json
{
  "mcpServers": {
    "waaseyaa": {
      "command": "node",
      "args": ["vendor/waaseyaa/mcp/server.js"],
      "cwd": "."
    }
  }
}
```

## Development

```bash
composer install                    # Install dependencies
php -S localhost:8080 -t public     # Dev server
./vendor/bin/phpunit                # Run tests
bin/waaseyaa                        # CLI
bin/waaseyaa sync-rules             # Update framework rules from Waaseyaa
```

## Codified Context

This app uses a three-tier codified context system inherited from Waaseyaa:

| Tier | Location | Purpose |
|------|----------|---------|
| **Constitution** | `CLAUDE.md` (this file) | Architecture, conventions, orchestration |
| **Rules** | `.claude/rules/waaseyaa-*.md` | Framework invariants (always active, never cited) |
| **Specs** | `docs/specs/*.md` | Domain contracts for each subsystem |

Framework rules are owned by Waaseyaa. Update them via `bin/waaseyaa sync-rules` after `composer update`.

When modifying a subsystem, update its spec in the same PR.

## Known Gaps

<!-- Track technical debt and migration items here -->

## Gotchas

- **Never use `$_ENV`** — Waaseyaa's `EnvLoader` only populates `putenv()`/`getenv()`. Use `getenv()` or the `env()` helper.
- **SQLite write access** — Both the `.sqlite` file AND its parent directory need write permissions for WAL/journal files.
```

- [ ] **Step 3: Commit**

```bash
git add skeleton/CLAUDE.md
git commit -m "feat(skeleton): update CLAUDE.md with MCP federation, prefixed rules, sync-rules command"
```

---

## Task 4: Build `SyncRulesCommand`

**Repo:** `~/dev/waaseyaa`

**Files:**
- Create: `packages/cli/src/Command/SyncRulesCommand.php`
- Create: `packages/cli/tests/Command/SyncRulesCommandTest.php`

- [ ] **Step 1: Write the test**

Create `packages/cli/tests/Command/SyncRulesCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Waaseyaa\CLI\Command\SyncRulesCommand;

final class SyncRulesCommandTest extends TestCase
{
    private string $sourceDir;
    private string $targetDir;

    protected function setUp(): void
    {
        $this->sourceDir = sys_get_temp_dir() . '/sync-rules-source-' . uniqid();
        $this->targetDir = sys_get_temp_dir() . '/sync-rules-target-' . uniqid();
        mkdir($this->sourceDir, 0755, true);
        mkdir($this->targetDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->sourceDir);
        $this->removeDir($this->targetDir);
    }

    #[Test]
    public function it_copies_new_rule_files(): void
    {
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', '# Framework');

        $tester = $this->runCommand(['--force' => true]);

        $this->assertFileExists($this->targetDir . '/waaseyaa-framework.md');
        $this->assertStringContainsString('Added', $tester->getDisplay());
        $this->assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function it_skips_unchanged_files(): void
    {
        $content = '# Framework';
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', $content);
        file_put_contents($this->targetDir . '/waaseyaa-framework.md', $content);

        $tester = $this->runCommand(['--force' => true]);

        $this->assertStringContainsString('0 updated', $tester->getDisplay());
    }

    #[Test]
    public function it_overwrites_changed_files_with_force(): void
    {
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', '# Updated');
        file_put_contents($this->targetDir . '/waaseyaa-framework.md', '# Old');

        $tester = $this->runCommand(['--force' => true]);

        $this->assertStringContainsString('Updated', $tester->getDisplay());
        $this->assertSame('# Updated', file_get_contents($this->targetDir . '/waaseyaa-framework.md'));
    }

    #[Test]
    public function it_never_touches_non_waaseyaa_files(): void
    {
        file_put_contents($this->targetDir . '/app-specific-rule.md', '# Mine');

        $tester = $this->runCommand(['--force' => true]);

        $this->assertFileExists($this->targetDir . '/app-specific-rule.md');
        $this->assertSame('# Mine', file_get_contents($this->targetDir . '/app-specific-rule.md'));
    }

    #[Test]
    public function it_creates_target_directory_if_missing(): void
    {
        $this->removeDir($this->targetDir);
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', '# Framework');

        $tester = $this->runCommand(['--force' => true]);

        $this->assertFileExists($this->targetDir . '/waaseyaa-framework.md');
    }

    #[Test]
    public function it_reports_dry_run_without_writing(): void
    {
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', '# New');

        $tester = $this->runCommand(['--dry-run' => true]);

        $this->assertFileDoesNotExist($this->targetDir . '/waaseyaa-framework.md');
        $this->assertStringContainsString('dry run', strtolower($tester->getDisplay()));
    }

    #[Test]
    public function it_only_processes_waaseyaa_prefixed_files_from_source(): void
    {
        file_put_contents($this->sourceDir . '/waaseyaa-framework.md', '# Framework');
        file_put_contents($this->sourceDir . '/other-file.md', '# Other');

        $tester = $this->runCommand(['--force' => true]);

        $this->assertFileExists($this->targetDir . '/waaseyaa-framework.md');
        $this->assertFileDoesNotExist($this->targetDir . '/other-file.md');
    }

    private function runCommand(array $input = []): CommandTester
    {
        $command = new SyncRulesCommand($this->sourceDir, $this->targetDir);

        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? $this->removeDir($file) : unlink($file);
        }
        rmdir($dir);
    }
}
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
cd ~/dev/waaseyaa
./vendor/bin/phpunit packages/cli/tests/Command/SyncRulesCommandTest.php
```

Expected: FAIL — `SyncRulesCommand` class not found.

- [ ] **Step 3: Write the implementation**

Create `packages/cli/src/Command/SyncRulesCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sync-rules',
    description: 'Sync framework rules from Waaseyaa to this app',
)]
final class SyncRulesCommand extends Command
{
    public function __construct(
        private readonly string $sourceDir,
        private readonly string $targetDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite changed files without confirmation')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would change without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        if (!is_dir($this->sourceDir)) {
            $output->writeln('<error>Source directory not found: ' . $this->sourceDir . '</error>');

            return self::FAILURE;
        }

        if (!is_dir($this->targetDir)) {
            if ($dryRun) {
                $output->writeln('<comment>Would create: ' . $this->targetDir . '</comment>');
            } else {
                mkdir($this->targetDir, 0755, true);
            }
        }

        $sourceFiles = glob($this->sourceDir . '/waaseyaa-*.md');
        $added = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($sourceFiles as $sourceFile) {
            $filename = basename($sourceFile);
            $targetFile = $this->targetDir . '/' . $filename;
            $sourceContent = file_get_contents($sourceFile);

            if (!file_exists($targetFile)) {
                if ($dryRun) {
                    $output->writeln("<comment>[dry run] Would add: {$filename}</comment>");
                } else {
                    file_put_contents($targetFile, $sourceContent);
                    $output->writeln("<info>Added: {$filename}</info>");
                }
                $added++;

                continue;
            }

            $targetContent = file_get_contents($targetFile);

            if ($sourceContent === $targetContent) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $output->writeln("<comment>[dry run] Would update: {$filename}</comment>");
                $updated++;

                continue;
            }

            if (!$force) {
                $output->writeln("<comment>{$filename} has changes. Use --force to overwrite.</comment>");
                $skipped++;

                continue;
            }

            file_put_contents($targetFile, $sourceContent);
            $output->writeln("<info>Updated: {$filename}</info>");
            $updated++;
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done:</info> %d added, %d updated, %d skipped',
            $added,
            $updated,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests — verify they pass**

```bash
cd ~/dev/waaseyaa
./vendor/bin/phpunit packages/cli/tests/Command/SyncRulesCommandTest.php
```

Expected: 7 tests, 7 passed.

- [ ] **Step 5: Commit**

```bash
git add packages/cli/src/Command/SyncRulesCommand.php packages/cli/tests/Command/SyncRulesCommandTest.php
git commit -m "feat(cli): add sync-rules command for framework rule inheritance"
```

---

## Task 5: Register `SyncRulesCommand` in Kernel

**Repo:** `~/dev/waaseyaa`

**Files:**
- Modify: whichever file registers CLI commands (check `ConsoleKernel` or a service provider)

- [ ] **Step 1: Find how commands are registered**

Check `packages/foundation/src/Kernel/ConsoleKernel.php` for how commands are discovered. Commands may be auto-discovered via the `#[AsCommand]` attribute, or manually registered.

- [ ] **Step 2: Register `SyncRulesCommand` with correct source/target paths**

The command needs DI to resolve `$sourceDir` and `$targetDir`:
- `$sourceDir`: `vendor/waaseyaa/foundation/.claude/rules` (relative to app root)
- `$targetDir`: `.claude/rules` (relative to app root)

Add a service definition or factory that wires these paths based on the app root directory passed to the kernel.

- [ ] **Step 3: Test the command end-to-end from the skeleton**

```bash
cd ~/dev/waaseyaa/skeleton
../bin/waaseyaa sync-rules --dry-run
```

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(foundation): wire SyncRulesCommand with correct source/target paths"
```

---

## Task 6: Migrate One Red Paperclip

**Repo:** `~/dev/oneredpaperclip`

**Files:**
- Create: `.claude/rules/waaseyaa-framework.md`
- Create: `.claude/rules/waaseyaa-data-freshness.md`
- Create: `.claude/rules/waaseyaa-shell-compat.md`

- [ ] **Step 1: Create `.claude/rules/` directory if it doesn't exist**

```bash
mkdir -p ~/dev/oneredpaperclip/.claude/rules
```

- [ ] **Step 2: Copy canonical rule files from Waaseyaa foundation**

```bash
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-framework.md ~/dev/oneredpaperclip/.claude/rules/
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-data-freshness.md ~/dev/oneredpaperclip/.claude/rules/
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-shell-compat.md ~/dev/oneredpaperclip/.claude/rules/
```

- [ ] **Step 3: Verify rules are present**

```bash
ls -la ~/dev/oneredpaperclip/.claude/rules/
```

Expected: three `waaseyaa-*.md` files.

- [ ] **Step 4: Commit**

```bash
cd ~/dev/oneredpaperclip
git add .claude/rules/waaseyaa-*.md
git commit -m "feat: add Waaseyaa framework rules for codified context inheritance"
```

- [ ] **Step 5: Register Waaseyaa MCP server in `.claude/settings.json`**

Read the existing `.claude/settings.json` (if any) and add the Waaseyaa MCP server to the `mcpServers` section:

```json
{
  "mcpServers": {
    "waaseyaa": {
      "command": "node",
      "args": ["vendor/waaseyaa/mcp/server.js"],
      "cwd": "."
    }
  }
}
```

Merge with existing settings — do not overwrite other MCP servers or configuration.

- [ ] **Step 6: Commit MCP registration**

```bash
git add .claude/settings.json
git commit -m "feat: register Waaseyaa MCP server for on-demand framework specs"
```

**Note:** Rewriting ORP's CLAUDE.md to remove Laravel references is tracked as a follow-up in the spec (Section 6, ORP step 2). It happens when ORP's codebase migrates off Illuminate — separate plan.

---

## Task 7: Migrate Claudriel

**Repo:** `~/dev/claudriel`

**Files:**
- Create: `.claude/rules/waaseyaa-framework.md`
- Create: `.claude/rules/waaseyaa-data-freshness.md`
- Create: `.claude/rules/waaseyaa-shell-compat.md`

- [ ] **Step 1: Copy canonical rule files from Waaseyaa foundation**

```bash
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-framework.md ~/dev/claudriel/.claude/rules/
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-data-freshness.md ~/dev/claudriel/.claude/rules/
cp ~/dev/waaseyaa/packages/foundation/.claude/rules/waaseyaa-shell-compat.md ~/dev/claudriel/.claude/rules/
```

- [ ] **Step 2: Verify both framework and app-specific rules coexist**

```bash
ls -la ~/dev/claudriel/.claude/rules/
```

Expected: Claudriel's existing rules (`trust-north-star.md`, `data-freshness.md`, `claudriel-principles.md`, `shell-compatibility.md`) alongside the new `waaseyaa-*.md` files. The `waaseyaa-` prefix prevents collisions.

- [ ] **Step 3: Register Waaseyaa MCP server in `.claude/settings.json`**

Read the existing `.claude/settings.json` and add the Waaseyaa MCP server to the `mcpServers` section. Merge with existing settings — Claudriel may already have MCP servers configured.

- [ ] **Step 4: Commit**

```bash
cd ~/dev/claudriel
git add .claude/rules/waaseyaa-*.md .claude/settings.json
git commit -m "feat: add Waaseyaa framework rules and MCP server for codified context inheritance"
```

---

## Task 8: Verify MCP Spec Bundling

**Repo:** `~/dev/waaseyaa`

The MCP server reads specs from a path relative to its own package directory. Verify that specs will be available when the package is installed via Composer.

- [ ] **Step 1: Check current MCP server spec path**

```bash
grep -n "specs" ~/dev/waaseyaa/packages/mcp/server.js | head -5
```

Identify where the server resolves the specs directory.

- [ ] **Step 2: Verify specs exist at the resolved path**

In the monorepo, specs live at `~/dev/waaseyaa/docs/specs/`. The MCP server at `packages/mcp/server.js` uses `join(import.meta.dirname, "../docs/specs")` — in monorepo dev this resolves to `packages/docs/specs/` which may not exist.

If specs only exist at the repo root, ensure the splitsh build pipeline copies `docs/specs/` into `packages/mcp/docs/specs/` before publishing. If specs are already symlinked or co-located, verify the path works.

- [ ] **Step 3: Test MCP tools from a consumer app perspective**

```bash
cd ~/dev/oneredpaperclip
# If waaseyaa/mcp is installed, test the MCP tools
node vendor/waaseyaa/mcp/server.js --test 2>/dev/null || echo "MCP package not yet installed as vendor dependency"
```

**Note:** Full MCP verification depends on the consumer app having `waaseyaa/mcp` installed via Composer. If the package is not yet published, this step is deferred to post-publish verification.

- [ ] **Step 4: Document spec bundling in build pipeline (if needed)**

If the splitsh pipeline does not already handle `docs/specs/`, add the copy step to the GitHub Actions workflow.

---

## Deferred: GoFormX Migration

GoFormX follows the same steps as ORP (Task 6). Deferred because:
- GoFormX's current state was not explored in this planning session
- The steps are identical: copy rules, register MCP server, commit
- Can be executed as a standalone task once Tasks 1-5 are complete

---

## Deferred: ORP CLAUDE.md Rewrite

ORP's CLAUDE.md currently references Laravel conventions. A full rewrite to follow the Waaseyaa skeleton template is tracked in the spec (Section 6, ORP step 2) but deferred until ORP's codebase migrates off Illuminate. The framework rules in `.claude/rules/` provide the immediate guardrails.

---

## Deferred: Claudriel CLAUDE.md Cleanup

The spec (Section 6, Claudriel step 2) calls for removing framework-level content from Claudriel's CLAUDE.md that is now covered by the `waaseyaa-*.md` rule files. This requires a careful audit of which CLAUDE.md sections overlap with the new rules. Deferred to a follow-up session to avoid scope creep.

---

## Verification Checklist

After all tasks are complete:

- [ ] `~/dev/waaseyaa/packages/foundation/.claude/rules/` contains 3 `waaseyaa-*.md` files (canonical source)
- [ ] `~/dev/waaseyaa/skeleton/.claude/rules/` contains the same 3 files (no unprefixed files remain)
- [ ] `~/dev/waaseyaa/skeleton/CLAUDE.md` references MCP federation, `sync-rules`, and Known Gaps
- [ ] `SyncRulesCommand` tests pass: `./vendor/bin/phpunit packages/cli/tests/Command/SyncRulesCommandTest.php`
- [ ] `~/dev/oneredpaperclip/.claude/rules/` contains 3 `waaseyaa-*.md` files
- [ ] `~/dev/oneredpaperclip/.claude/settings.json` has Waaseyaa MCP server registered
- [ ] `~/dev/claudriel/.claude/rules/` contains 3 `waaseyaa-*.md` files alongside existing app rules
- [ ] `~/dev/claudriel/.claude/settings.json` has Waaseyaa MCP server registered
- [ ] Running `diff` between all three rule locations shows identical framework files
- [ ] MCP spec path resolves correctly from consumer app context (or documented as post-publish task)
