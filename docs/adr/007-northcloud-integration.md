# ADR-7: NorthCloud Integration

**Status:** Accepted
**Date:** 2026-03-19
**Context:** NorthCloud (`jonesrussell/northcloud-laravel`) provides admin dashboard functionality for articles and users. It is an external package with its own data model, Redis pipeline, and migration requirements. It does not affect core trade-up functionality.
**Decision:** Defer NorthCloud integration to P2. No implementation now.
**Rationale:** NorthCloud (articles, users admin) is an external package with its own migration path. It doesn't block core trade-up functionality.
**Consequences:** No code changes. Document integration points for future reference.
