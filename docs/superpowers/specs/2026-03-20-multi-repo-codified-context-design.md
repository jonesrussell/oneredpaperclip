# Multi-Repo Codified Context Integration

**Date:** 2026-03-20
**Status:** Draft
**Scope:** Waaseyaa framework, One Red Paperclip, Claudriel, GoFormX

## Problem

AI agents working on Waaseyaa apps have no way to know the framework's architectural invariants. When an agent sees patterns that look like Laravel (Actions/, Models/, artisan), it defaults to Laravel conventions — importing Illuminate facades, using Eloquent, reaching for `DB::transaction()`. This produces code that works but violates Waaseyaa's architecture and creates foreign dependencies that compound over time.

The root cause: framework-level rules exist only in Waaseyaa's own CLAUDE.md and specs, which are invisible to agents working in consumer apps like One Red Paperclip.

## Solution

A two-layer inheritance model that gives every Waaseyaa app access to framework rules at two levels of depth:

1. **Skeleton inheritance** — constitutional rules shipped in `.claude/rules/waaseyaa-*.md`, loaded every session (hot memory)
2. **MCP federation** — deep framework specs retrieved on demand via Waaseyaa's MCP server (cold memory)

Framework rules are owned by Waaseyaa and updated via an explicit CLI command. App rules are owned by the app and never touched by the framework.

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Integration model | Skeleton + MCP (Model 3) | Constitutional rules need hot loading; deep specs need on-demand retrieval |
| Inheritance model | Separate files (Model B) | Framework rules must be replaceable without merge conflicts in app CLAUDE.md |
| Update mechanism | Explicit CLI (Option B) | Predictable, safe, no magic — fits standard update ritual |

## 1. Skeleton `.claude/` Directory

New Waaseyaa apps created via `composer create-project waaseyaa/waaseyaa` (the skeleton is published as the `waaseyaa/waaseyaa` Composer package, type `project`) inherit:

```
skeleton/
├── .claude/
│   └── rules/
│       ├── waaseyaa-framework.md
│       ├── waaseyaa-data-freshness.md
│       └── waaseyaa-shell-compat.md
├── CLAUDE.md
└── ... (existing skeleton files)
```

Rules are prefixed `waaseyaa-` to distinguish framework rules from app-specific rules added later. The skeleton does NOT ship `.claude/settings.json` — apps configure their own MCP servers and hooks.

## 2. Framework Rule Content

### `waaseyaa-framework.md` — Core Invariants

**Identity:**
- Waaseyaa is a Symfony 7-based, entity-first PHP framework
- It is NOT Laravel. It does not use Illuminate components
- PHP 8.4+, full dependency injection, no global state

**Forbidden dependencies (the "never" list):**
- `Illuminate\Support\Facades\*` — no Laravel facades
- `Illuminate\Database\*` / Eloquent — no Laravel ORM
- `DB::transaction()`, `DB::table()` — no Laravel DB layer
- `Model::create()`, `Model::query()` — no Eloquent patterns
- `env()`, `config()` (Laravel helpers) — use Waaseyaa's config system
- No ActiveRecord patterns (`$entity->save()`, `$entity->delete()`, `$entity->refresh()`) — entities are pure data objects managed by storage drivers

**Required abstractions (the "use instead" list):**
- `DatabaseInterface` — transactions and raw queries
- `SqlEntityStorage` + `StorageRepositoryAdapter` — persistence
- `EntityRepositoryInterface` — data access
- `EntityTypeManager` — entity registration and discovery
- `AccessPolicyInterface` + `FieldAccessPolicyInterface` — authorization
- `SelectInterface` — query building
- Symfony DI container — dependency injection

**7-layer architecture:**
- Layer 0: Foundation (cache, plugin, typed-data, database-legacy)
- Layer 1: Core Data (entity, field, entity-storage, access, user, config)
- Layer 2: Services (routing, queue, state, validation)
- Layer 3: Content Types (node, taxonomy, media, path, menu, workflows)
- Layer 4: API (api, graphql, routing)
- Layer 5: AI (ai-schema, ai-agent, ai-vector, ai-pipeline)
- Layer 6: Interfaces (cli, ssr, admin, mcp, telescope)
- Dependencies flow downward only. Never import from a higher layer.

**Entity patterns:**
- Entities are typed, registered in `config/entity-types.php`, use field definitions
- Entities are NOT ActiveRecord models — they are immutable except through storage operations
- Persistence is handled by storage drivers, not by the entity itself

**MCP spec retrieval:**
- For deeper framework knowledge, query the Waaseyaa MCP server
- `waaseyaa_list_specs`, `waaseyaa_get_spec`, `waaseyaa_search_specs`

### `waaseyaa-data-freshness.md` — Data Freshness Discipline

Adapted from Claudriel's proven rule:
- Store pointers ("where to find it"), not values ("what it currently is")
- Volatile data (counts, statuses, dates, lists, financial figures) must be queried live, not cached in memory or MEMORY.md
- Source attribution hierarchy: user_stated > extracted > inferred > corrected
- Canonical source hierarchy: individual files > context markdown > auto-memory
- Before reporting any quantitative fact, verify it against the canonical source

### `waaseyaa-shell-compat.md` — Platform-Safe Shell Patterns

Direct adoption of Claudriel's shell-compatibility rule:
- Reserved variable names to avoid (`status`, `path`, `prompt`, `precmd`, `RANDOM`)
- Safe patterns: `$(command)` over backticks, quoted paths, `[[ ]]` conditionals
- Cross-platform awareness for WSL2, macOS, Linux environments

## 3. MCP Federation

Apps register Waaseyaa's MCP server in `.claude/settings.json`:

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

The MCP server ships inside the `waaseyaa/mcp` Composer package. **Specs are bundled inside the MCP package** at `vendor/waaseyaa/mcp/docs/specs/` — the MCP server reads specs relative to its own package directory. Specs update automatically with `composer update waaseyaa/mcp`.

**Implementation note:** The monorepo's `packages/mcp/` must include a copy of (or symlink to) the framework's `docs/specs/` directory so that specs are distributed with the package. The MCP server's `server.js` uses `join(import.meta.dirname, "../docs/specs")` — this path must resolve correctly in both monorepo development and Composer-installed contexts. If specs live at the monorepo root, the build/split pipeline must copy them into the MCP package before publishing.

**Available tools:**
- `waaseyaa_list_specs` — index of all framework specs
- `waaseyaa_get_spec("entity-system")` — full spec content
- `waaseyaa_search_specs("transaction locking")` — keyword search across specs

**Boundary principle:**

| Layer | Location | Loaded | Content |
|-------|----------|--------|---------|
| Rules | `.claude/rules/waaseyaa-*.md` | Every session (hot) | Invariants, forbidden patterns, required abstractions |
| Specs | `vendor/waaseyaa/mcp/docs/specs/` | On demand via MCP (cold) | Implementation details, interface signatures, data flows, examples |

Rules say "never use Eloquent, use EntityRepositoryInterface instead." Specs say "here's how EntityRepositoryInterface works, its methods, patterns, and edge cases."

## 4. `sync-rules` CLI Command

**Command:** `php bin/waaseyaa sync-rules`

**Source:** `vendor/waaseyaa/foundation/.claude/rules/waaseyaa-*.md` — canonical, always matches the installed framework version.

**Prerequisite:** The `waaseyaa/foundation` package must be updated to include a `.claude/rules/` directory containing the canonical rule files. This directory does not exist today — creating it is part of step 1 of the migration sequence (Section 6).

**CLI distribution:** The `bin/waaseyaa` binary ships with the `waaseyaa/cli` package and is installed to the app's `vendor/bin/` via Composer. Consumer apps run `vendor/bin/waaseyaa sync-rules` or, if Composer's bin directory is in PATH, simply `waaseyaa sync-rules`. The skeleton's `bin/waaseyaa` is a thin bootstrap that delegates to the CLI package.

**Behavior:**

1. Scans source directory for `waaseyaa-*.md` files
2. Compares each against `.claude/rules/waaseyaa-*.md` in the app
3. For each file:
   - **New** — copies it in, reports "Added waaseyaa-framework.md"
   - **Changed** — shows diff, asks for confirmation before overwriting
   - **Unchanged** — skips silently
   - **Locally modified** — warns, shows diff of both upstream and local changes, asks whether to overwrite or skip
4. Reports summary: "2 updated, 1 added, 0 skipped"

**Safety rules:**
- Never touches non-`waaseyaa-*` files in `.claude/rules/` — app rules are sacred
- Creates `.claude/rules/` directory if it doesn't exist
- `--force` flag skips confirmation prompts (for CI)
- `--dry-run` flag shows what would change without writing

**Integration:** Part of the standard update ritual:

```bash
composer update waaseyaa/*
php bin/waaseyaa migrate
php bin/waaseyaa sync-rules
```

## 5. CLAUDE.md Template

The skeleton ships a minimal, app-specific CLAUDE.md:

```markdown
# CLAUDE.md

## Project Overview

**[App Name]** — [one-line description]

## Commands

php bin/waaseyaa serve        # Start development server
php bin/waaseyaa migrate      # Run database migrations
php bin/waaseyaa sync-rules   # Update framework rules from Waaseyaa

## Architecture

<!-- App-specific domain model, bounded contexts, key entities -->

## Orchestration

| File Pattern | Skill | Spec |
|-------------|-------|------|
| src/Entity/* | waaseyaa:entity-system | entity-system.md |
| src/Access/* | waaseyaa:access-control | access-control.md |
| docs/specs/** | updating-codified-context | — |

<!-- Note: waaseyaa:* skills are placeholders. They will not function
     until the skills are built (see Out of Scope). The orchestration
     table entries document the intended routing for when skills exist. -->

## MCP Federation

Register Waaseyaa's MCP server for on-demand framework specs:

{ "mcpServers": { "waaseyaa": { "command": "node", "args": ["vendor/waaseyaa/mcp/server.js"], "cwd": "." } } }

## Known Gaps

<!-- Track technical debt and migration items here -->
```

**What's NOT in the template:** framework invariants (in rules), framework architecture docs (in MCP specs), Laravel references, boilerplate that becomes stale.

**What IS in the template:** app identity, commands, orchestration table stub pre-wired for common Waaseyaa patterns, MCP registration instructions, sections the developer fills in as the app grows.

## 6. Migration Path for Existing Apps

### Sequencing

1. Build framework rules, publish in `waaseyaa/foundation`
2. Build `sync-rules` CLI command
3. Migrate Claudriel (lowest risk — additive)
4. Migrate One Red Paperclip (highest value — prevents Illuminate drift)
5. Migrate GoFormX

### Claudriel (additive)

Claudriel already has a full three-tier system. Migration adds framework rules alongside existing app rules:

1. Add `waaseyaa-*.md` rule files to `.claude/rules/`
2. Remove framework-level content from Claudriel's CLAUDE.md now covered by framework rules
3. Register Waaseyaa MCP server in `.claude/settings.json`
4. Existing app-specific rules (`trust-north-star.md`, `claudriel-principles.md`) stay unchanged

### One Red Paperclip (transformative)

ORP's CLAUDE.md is full of Laravel references (legacy debt). Migration rewrites the app's constitutional layer:

1. Add `.claude/rules/waaseyaa-*.md` framework rule files
2. Rewrite CLAUDE.md to follow the skeleton template — remove Laravel/Illuminate references, reframe around Waaseyaa abstractions
3. Register Waaseyaa MCP server
4. Existing domain content (trade-up flow, challenge model, offers/trades) stays as app-specific content
5. Legacy Illuminate code in the codebase migrated incrementally (separate work)

### GoFormX

Same steps as ORP, expected to have less legacy debt.

## Out of Scope

- Migrating ORP's codebase from Illuminate to Waaseyaa abstractions (separate implementation plans)
- Building Waaseyaa DB features like `lockForUpdate()` on SelectInterface (separate work)
- Creating Waaseyaa-specific skills referenced in orchestration tables (separate work)
- Third-party Waaseyaa app guidance (future work once the model is proven)
