# ADR-4: Timestamps

**Status:** Accepted
**Date:** 2026-03-19
**Context:** Waaseyaa entities currently lack standardized timestamp fields. Timestamps are essential for ordering, auditing, data integrity, and compatibility with Laravel's `created_at`/`updated_at` conventions.
**Decision:** Add `created_at` and `updated_at` timestamp fields to all 7 entity types.
- Format: ISO 8601 UTC strings stored in varchar(32). Example: `2026-03-19T12:00:00Z`
- Auto-population: SqlEntityStorage already auto-populates `created` and `changed` fields if present in field definitions -- we'll use this mechanism.
**Rationale:** Timestamps are essential for ordering, auditing, and data integrity.
**Consequences:** Add 2 fields to each entity type in TradeUpServiceProvider. Update SchemaInstaller tests to verify columns exist. Update integration tests.
