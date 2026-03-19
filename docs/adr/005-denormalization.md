# ADR-5: Denormalized Fields

**Status:** Accepted
**Date:** 2026-03-19
**Context:** The Laravel production application maintains denormalized fields on certain entities for query performance. Listing pages and dashboards rely on these fields to avoid expensive joins and aggregate counts at query time.
**Decision:** Keep denormalized fields matching Laravel for query performance.
- Keep: `trades_count` on Challenge, `offered_item_id` and `received_item_id` on Trade.
**Rationale:** These fields are used in listing queries and avoid expensive joins/counts. Laravel's production code relies on them.
**Consequences:** Add `trades_count` (integer) to Challenge field definitions. Add `offered_item_id` and `received_item_id` (entity_reference) to Trade field definitions. Update tests.
