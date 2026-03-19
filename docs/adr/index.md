# Architecture Decision Records

This directory contains Architecture Decision Records (ADRs) for the Waaseyaa migration of the One Red Paperclip platform.

| ADR | Status | Summary |
|-----|--------|---------|
| [ADR-1: Enum Alignment](001-enum-alignment.md) | Accepted | Align Waaseyaa enums to match Laravel production values (Published->Active, Archived->Paused). |
| [ADR-2: Column Name Alignment](002-column-names.md) | Accepted | Align Waaseyaa column names to match Laravel for direct data compatibility. |
| [ADR-3: Notification ID Format](003-notification-ids.md) | Accepted | Use UUID primary keys for notifications, matching Laravel's notifications table. |
| [ADR-4: Timestamps](004-timestamps.md) | Accepted | Add created_at and updated_at timestamp fields to all 7 entity types as ISO 8601 UTC strings. |
| [ADR-5: Denormalized Fields](005-denormalization.md) | Accepted | Keep denormalized fields (trades_count, offered/received_item_id) for query performance. |
| [ADR-6: Frontend Strategy](006-frontend-strategy.md) | Accepted | Reuse existing Inertia.js + Vue 3 components from the Laravel app (deferred to Phase 7). |
| [ADR-7: NorthCloud Integration](007-northcloud-integration.md) | Accepted | Defer NorthCloud integration to P2; no implementation now. |
| [ADR-8: Timestamp Storage Format](008-timestamp-format.md) | Accepted | Store timestamps as ISO 8601 UTC strings in varchar(32) columns. |
