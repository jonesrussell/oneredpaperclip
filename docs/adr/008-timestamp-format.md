# ADR-8: Timestamp Storage Format

**Status:** Accepted
**Date:** 2026-03-19
**Context:** Waaseyaa's entity storage system uses varchar columns rather than native database timestamp types. A consistent, unambiguous format is needed that supports sorting, is timezone-explicit, and allows straightforward migration from Laravel's timestamp format.
**Decision:** Store timestamps as ISO 8601 UTC strings in varchar(32) columns.
- Format: `YYYY-MM-DDTHH:MM:SSZ` (e.g., `2026-03-19T12:00:00Z`)
- Data migration: Laravel's `YYYY-MM-DD HH:MM:SS` format converts trivially by adding `T` separator and `Z` suffix (Laravel stores UTC).
**Rationale:** Waaseyaa's entity storage uses varchar for timestamps. ISO 8601 is sortable, unambiguous, and timezone-explicit. UI layer converts to user locale.
**Consequences:** All timestamp fields use varchar(32). Entity accessors return string|null. Presentation layer handles formatting.
