# Challenge Show Page Redesign

## Problem

The challenge show page (`/challenges/{challenge}`) is a public route but uses `AppLayout` (the sidebar dashboard shell). This creates a mismatch: guests see irrelevant dashboard navigation ("Dashboard", "My Challenges"), and the sidebar wastes horizontal space on what should be a content-focused page. The content feels "shoehorned into the dashboard."

## Solution

Switch to `PublicLayout` and reorganize the page content into a flowing vertical layout that uses the full width.

## Layout Change

- **From:** `AppLayout` (wraps `AppSidebarLayout` — sidebar, breadcrumbs, dashboard chrome)
- **To:** `PublicLayout` (sticky header, footer, mobile bottom tab bar, full-width content area)

`PublicLayout` already handles auth/guest states — shows "Dashboard" link for logged-in users, "Log in"/"Get started" for guests. Used by Welcome and About pages.

## Page Structure (top to bottom)

### 1. Hero Section

Full-width hero area, constrained to `max-w-5xl mx-auto`.

- **Category accent strip** — thin colored bar at the top using the challenge's category accent color (same pattern as `ChallengeCard`)
- **Background** — `bg-muted/50`, `rounded-2xl`, padded
- **Top row** — Status badge (left), action buttons (right): follow heart, share dropdown, edit (owner only)
- **Middle** — Challenge title (`font-display text-3xl lg:text-4xl`), owner row (avatar + name + level badge)
- **Bottom row** — Stats pills (left), "Make an Offer" brand button (right)

**Stats pills:** Horizontal flex row (`gap-3`) of small rounded cards (`rounded-xl bg-card p-3`), each with icon + number + label. Same data as the current `StatsPanel` (level/XP, streak, trades, days active) but displayed inline instead of stacked vertically in a sidebar.

**CTA rules:**
- Non-owner, logged in: show "Make an Offer" brand button
- Non-owner, guest: show "Sign in to make an offer" link
- Owner: hide CTA

**Mobile:** Stats pills wrap to a 2x2 grid. CTA goes full-width below the pills.

### 2. Trade Path Map Section

- `max-w-4xl mx-auto`, `py-8`
- "Trade Journey" heading
- Same `TradePathMap` component — no changes to the component itself, just given more breathing room
- `PaperclipMascot` positioned near the map (small, decorative)

### 3. Tabs Section

- `max-w-4xl mx-auto`
- Same tab bar: Story | Offers | Trades | Comments (with count badges)
- Same tab content — no structural changes, just inherits more width

### 4. Mobile Sticky CTA

Kept as-is — fixed "Make an Offer" button at bottom of viewport when the hero CTA scrolls out of view. Only shown on mobile for non-owners.

## What Gets Removed

- `StatsPanel` sidebar — data moves into hero stats pills
- Breadcrumbs header — PublicLayout doesn't use breadcrumbs; the header nav provides context
- The 3-column grid layout — replaced by flowing vertical sections

## What Stays the Same

- All tab content (Story, Offers, Trades, Comments) and their components
- `TradePathMap`, `OfferCard`, `TradeCard` components
- `CreateOfferDialog`, `CelebrationOverlay`, `ShareDropdown`
- Mobile sticky CTA behavior
- `PaperclipMascot` (repositioned from sidebar area to near trade path)

## Affected Files

- `resources/js/pages/challenges/Show.vue` — layout swap and content reorganization (primary change)
- No backend changes needed — controller, routes, and props stay the same
- No new components needed — reuses existing `PublicLayout` and existing child components
- `StatsPanel` component becomes unused on this page (may still be used elsewhere)
