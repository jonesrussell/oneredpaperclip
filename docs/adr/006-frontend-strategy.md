# ADR-6: Frontend Strategy

**Status:** Accepted
**Date:** 2026-03-19
**Context:** The Laravel application has a mature frontend with 32 pages and approximately 55 custom Vue components using Inertia.js + Vue 3. Rebuilding these from scratch would be a significant effort. The Waaseyaa framework already includes an Inertia.js integration (`waaseyaa/inertia`).
**Decision:** Use Inertia.js + Vue 3, reusing existing Vue components from the Laravel app.
- Implementation: Deferred to Phase 7 (P0-13, P0-14). Vue components can be copied; only Inertia prop wiring and route helpers need updating.
**Rationale:** The Laravel app has 32 pages and ~55 custom components. Inertia.js integration exists in the Waaseyaa framework (`waaseyaa/inertia`). Reusing components minimizes frontend rewrite effort.
**Consequences:** No code changes now. Frontend work is a future phase.
