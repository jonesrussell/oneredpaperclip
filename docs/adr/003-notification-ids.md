# ADR-3: Notification ID Format

**Status:** Accepted
**Date:** 2026-03-19
**Context:** Waaseyaa entities typically use auto-incrementing integer primary keys. However, Laravel's built-in `notifications` table uses UUID primary keys. Importing existing notification data requires matching this format.
**Decision:** Use UUID primary keys for notifications, matching Laravel's `notifications` table.
**Rationale:** Laravel notifications use UUID PKs. Cross-system uniqueness for future federation. Existing notification data uses UUIDs.
**Consequences:** Change Notification entity to use UUID as id key (config entity style, not serial). Update SchemaInstaller to handle this. May require changes to SqlSchemaHandler or a special case.
