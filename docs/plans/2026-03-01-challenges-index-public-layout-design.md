# Challenges Index — Switch to PublicLayout

## Problem

The challenges index page (`/challenges`) uses `AppLayout` (sidebar-based authenticated layout), but it's a public "explore" page accessible to all visitors. PR #9 moved the challenge show page to `PublicLayout`; the index should follow suit.

## Design

### Layout Swap

- Replace `AppLayout` with `PublicLayout`
- Remove `breadcrumbs` prop (not used by PublicLayout)
- Wrap content in `max-w-6xl` centered container to match PublicLayout's content width pattern (same as challenge show page)

### Hero Section

Replace the plain `h1` heading with a compact hero:

- Rounded card with `border border-border bg-muted/50` (matches show page hero treatment)
- Title: "Explore Challenges" in `font-display` (Fredoka), large
- Subtitle: brief descriptive line, e.g. "Browse active trade-ups and find something to trade"
- Category filter pills moved inside the hero, below the subtitle

### Unchanged

- Challenge grid layout, empty state, and pagination remain as-is
- Only outer wrapper classes adjusted for the new container context
- Controller (`ChallengeController::index`) needs no changes — already works for unauthenticated users

### Tests

- Update existing challenge index tests to verify the page still renders correctly under the new layout
