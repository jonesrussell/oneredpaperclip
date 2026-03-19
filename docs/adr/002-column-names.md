# ADR-2: Column Name Alignment

**Status:** Accepted
**Date:** 2026-03-19
**Context:** Waaseyaa entity field names differ from the Laravel database column names. For example, Waaseyaa uses `description` where Laravel uses `story`, and Offer fields like `user_id`, `item_id`, `target_item_id` differ from Laravel's `from_user_id`, `offered_item_id`, `for_challenge_item_id`. These mismatches would require column renames during data migration.
**Decision:** Align Waaseyaa column names to match Laravel for direct data compatibility.
- Challenge: `description` -> `story`, `category_tid` -> `category_id`
- Offer: `user_id` -> `from_user_id`, `item_id` -> `offered_item_id`, `target_item_id` -> `for_challenge_item_id`
**Rationale:** Production data uses Laravel names. Matching eliminates column rename in data migration.
**Consequences:** Update TradeUpServiceProvider field definitions, entity accessors/mutators, and all tests.
