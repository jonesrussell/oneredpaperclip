# ADR-1: Enum Alignment

**Status:** Accepted
**Date:** 2026-03-19
**Context:** Waaseyaa's ChallengeStatus enum uses `Published` and `Archived` cases, but the Laravel production database stores these as `active` and `paused`. Mismatched enum values would require data migration during the conversion, adding complexity and risk.
**Decision:** Align Waaseyaa enums to match Laravel production values.
- ChallengeStatus: rename `Published` -> `Active`, `Archived` -> `Paused` (values: `active`, `paused`)
- ChallengeVisibility: keep `Private` case as a P2 new feature (not in Laravel, no data migration needed)
**Rationale:** Production database uses Laravel values. Zero data migration needed if we match exactly. The `Private` visibility case does not exist in Laravel production data, so adding it as a new feature in P2 introduces no migration burden.
**Consequences:** Update ChallengeStatus enum, ChallengeStatusTest, Challenge entity defaults, all integration tests using Published/Archived.
