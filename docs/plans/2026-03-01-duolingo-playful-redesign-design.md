# Duolingo-Style Playful Redesign

**Date:** 2026-03-01
**Goal:** Transform One Red Paperclip from a dark/gradient aesthetic to a bright, flat, Duolingo-style playful UI with chunky borders, bold shadows, and cartoon-like elements.

## Design Decisions

- **Dark mode**: Keep it, but replace the obsidian purple-black theme with a soft neutral charcoal that still feels fun
- **Gradients**: Remove all gradients site-wide, replace with flat solid fills
- **Brand color**: Red stays primary. No shift to green.
- **Boldness level**: Full Duolingo — 2-3px borders, heavy bottom-only shadows, very rounded corners, press-down hover effects

## Color System

### Light Mode

| Token | Current | New | Notes |
|-------|---------|-----|-------|
| `--background` | `hsl(32 50% 97%)` (warm cream) | `hsl(0 0% 100%)` | Clean white like Duolingo |
| `--foreground` | `hsl(0 0% 12%)` | `hsl(0 0% 12%)` | Keep — dark text is good |
| `--card` | `hsl(35 45% 99%)` | `hsl(0 0% 100%)` | White cards with colored borders |
| `--card-foreground` | `hsl(0 0% 12%)` | `hsl(0 0% 12%)` | No change |
| `--paper` | `hsl(32 55% 97%)` | `hsl(0 0% 100%)` | White |
| `--ink` | `hsl(0 0% 15%)` | `hsl(0 0% 15%)` | No change |
| `--ink-muted` | `hsl(0 0% 45%)` | `hsl(0 0% 55%)` | Slightly lighter muted text |
| `--border` | `hsl(30 20% 90%)` | `hsl(220 10% 84%)` | Cooler, more visible borders |
| `--input` | `hsl(30 18% 90%)` | `hsl(220 10% 84%)` | Match border |
| `--secondary` | `hsl(32 35% 92%)` | `hsl(220 14% 96%)` | Cool light gray |
| `--muted` | `hsl(32 30% 94%)` | `hsl(220 14% 96%)` | Match secondary |
| `--accent` | `hsl(32 40% 92%)` | `hsl(220 14% 92%)` | Slightly darker gray |
| `--primary` | `hsl(0 0% 12%)` | `hsl(0 0% 12%)` | Keep dark primary |
| `--brand-red` | `hsl(0 95% 45%)` | `hsl(0 80% 50%)` | Slightly brighter, Duolingo-bold |
| `--brand-red-hover` | `hsl(0 95% 38%)` | `hsl(0 80% 44%)` | Darker on hover |
| `--electric-mint` | `hsl(162 65% 52%)` | `hsl(88 62% 40%)` (#58CC02) | Duolingo's exact green |
| `--sunny-yellow` | `hsl(45 95% 60%)` | `hsl(45 100% 50%)` (#FFC800) | Brighter, punchier |
| `--hot-coral` | `hsl(12 90% 65%)` | `hsl(0 100% 65%)` (#FF4B4B) | Duolingo's red |
| `--soft-lavender` | `hsl(265 55% 75%)` | `hsl(275 70% 65%)` (#CE82FF) | More saturated |
| `--sky-blue` | `hsl(205 85% 65%)` | `hsl(199 89% 52%)` (#1CB0F6) | Duolingo's blue |
| `--sidebar-background` | `hsl(34 45% 96%)` | `hsl(0 0% 100%)` | White sidebar |
| `--sidebar-border` | `hsl(30 25% 88%)` | `hsl(220 10% 84%)` | Match new border |
| `--radius` | `1rem` | `1rem` | Keep — already rounded |

### Dark Mode (Soft Charcoal — Replace Obsidian)

| Token | Current (Obsidian) | New (Charcoal) | Notes |
|-------|-------------------|-----------------|-------|
| `--background` | `hsl(260 24% 9%)` | `hsl(220 15% 13%)` | Neutral dark, not purple |
| `--foreground` | `hsl(0 0% 96%)` | `hsl(0 0% 95%)` | Slightly warmer white |
| `--card` | `hsl(252 21% 13%)` | `hsl(220 15% 17%)` | Neutral card surface |
| `--paper` | `hsl(252 21% 13%)` | `hsl(220 15% 17%)` | Same as card |
| `--ink` | `hsl(0 0% 96%)` | `hsl(0 0% 95%)` | Match foreground |
| `--ink-muted` | `hsl(252 30% 78%)` | `hsl(220 10% 60%)` | Neutral muted, not purple |
| `--popover` | `hsl(252 18% 17%)` | `hsl(220 15% 20%)` | Neutral |
| `--secondary` | `hsl(252 20% 20%)` | `hsl(220 12% 22%)` | Neutral |
| `--muted` | `hsl(252 18% 16%)` | `hsl(220 12% 18%)` | Neutral |
| `--accent` | `hsl(263 45% 24%)` | `hsl(220 12% 22%)` | Neutral |
| `--primary` | `hsl(263 90% 66%)` (purple) | `hsl(0 0% 95%)` | White text as primary in dark |
| `--primary-foreground` | white | `hsl(220 15% 13%)` | Dark text on white primary |
| `--border` | `hsl(252 15% 22%)` | `hsl(220 10% 25%)` | Neutral border |
| `--input` | `hsl(252 15% 22%)` | `hsl(220 10% 25%)` | Match border |
| `--ring` | cyan highlight | `hsl(0 80% 50%)` | Use brand red for ring |
| Sidebar vars | obsidian purple | neutral charcoal | Match card/background pattern |

Remove all `--obsidian-*` variables and `--shadow-card` variable.

### Accent Colors (Same in Both Modes)

These bright accent colors work on both white and dark charcoal backgrounds:

- `--electric-mint`: `#58CC02` — success, completion
- `--sunny-yellow`: `#FFC800` — warnings, highlights, streaks
- `--hot-coral`: `#FF4B4B` — destructive, urgent
- `--soft-lavender`: `#CE82FF` — special, badges
- `--sky-blue`: `#1CB0F6` — info, social, links

## Visual Language

### Borders (The Duolingo Signature)

**Cards**: `border-2 border-[var(--border)]` — visible 2px borders replacing the current thin 1px
**Buttons**: `border-2 border-b-4` — extra thick bottom border for the "3D pushable" look
**Inputs**: `border-2` — thicker than current, very visible
**Badges/pills**: `border-2` — chunky pills

### Shadows

Remove all `shadow-sm`, `shadow-md`, `shadow-lg` usage. Replace with:
- **Cards**: `shadow-none` — borders provide the visual weight, not shadows
- **Buttons**: `border-b-4 border-[color-darker]` — the bottom border IS the shadow
- **Hover states on buttons**: `translate-y-[2px] border-b-2` — press down, shrink bottom border (button appears to "press in")
- **Active/pressed**: `translate-y-[3px] border-b-[1px]` — fully pressed

### Border Radius

- Buttons: `rounded-xl` (12px) — pill-ish
- Cards: `rounded-2xl` (16px) — keep current
- Inputs: `rounded-xl` (12px) — more rounded than current `rounded-md`
- Badges: `rounded-full` — keep current
- Avatars: `rounded-full` — circular, not rounded-lg

### Typography

Keep existing fonts — they already work for playful:
- **Fredoka** (display): Already bouncy and fun
- **DM Sans** (body): Clean and readable
- **JetBrains Mono** (stats): Good for numbers

### Hover & Interaction States

- **Buttons**: Press-down effect (translate-y + border-b shrink), not lift-up
- **Cards**: Slight scale + border color change on hover
- **Links**: Color change only, no underline animations

## Components to Update

### 1. `app.css` — Design Tokens
- Replace all `:root` light mode values
- Replace `.dark` block completely (remove obsidian, add charcoal)
- Remove `--obsidian-*` variables
- Remove `--shadow-card` variable
- Remove `.dark img` dimming (unnecessary with charcoal theme)
- Remove `.dark .bg-card` shadow override
- Keep all keyframe animations (they're theme-independent)

### 2. `Button` (ui/button/index.ts) — Variants
- Base: add `border-2 border-b-4 rounded-xl font-bold active:translate-y-[2px] active:border-b-2`
- **default**: `border-primary/80 border-b-primary/60` + press effect
- **brand**: `border-[hsl(0,70%,40%)] border-b-[hsl(0,70%,35%)]` — darker red bottom border
- **success**: `border-[hsl(88,62%,32%)] border-b-[hsl(88,62%,28%)]` — darker green bottom border
- **social**: `border-[hsl(199,89%,42%)] border-b-[hsl(199,89%,38%)]` — darker blue
- **destructive**: `border-[hsl(0,100%,55%)] border-b-[hsl(0,100%,50%)]`
- **outline**: `border-2 border-[var(--border)]` with hover fill
- **ghost**: no border, no shadow — keep subtle
- **link**: no border — keep as-is
- Remove all `shadow-md`, `shadow-lg`, `hover:-translate-y-0.5` (lift effects replaced by press-down)
- Hover: `hover:brightness-110` — simple brightening, no translate
- Active: `active:translate-y-[2px] active:border-b-2` — press in

### 3. `Card.vue` (ui/card/Card.vue)
- Change border to `border-2 border-[var(--border)]`
- Remove `shadow-sm` and `hover:shadow-md`
- Add `hover:border-[var(--brand-red)]/30` for subtle color shift on hover

### 4. `Input.vue` (ui/input/Input.vue)
- Change border to `border-2`
- Change radius to `rounded-xl`
- Remove `shadow-xs`
- Focus: `focus-visible:border-[var(--brand-red)]` with ring

### 5. `ChallengeCard.vue`
- Remove `bg-gradient-to-br from-[var(--ink)]/8 to-[var(--ink)]/4` — replace with `bg-[var(--muted)]`
- Remove `box-shadow` inline style — borders provide the weight
- Add `border-2 border-[var(--border)]` for chunky card border
- Category accent strip: thicken from `w-1` to `w-1.5`
- Remove `hover:shadow-[...]` — replace with `hover:border-[var(--brand-red)]/40`

### 6. `PublicLayout.vue`
- Remove decorative blur blobs (lines 22-41)
- Remove grain overlay (lines 44-47)
- Clean white background is enough — Duolingo doesn't use atmospheric effects

### 7. `Welcome.vue` — Remove All Gradients
- Hero section: replace `bg-gradient-to-br from-[var(--hot-coral)]/12 via-transparent to-[var(--sunny-yellow)]/10` with solid `bg-[var(--muted)]` or clean white
- Paperclip glow: remove `bg-gradient-to-br from-[var(--brand-red)]/20 to-transparent blur-2xl`
- "How it works" vertical line: replace gradient line with solid `bg-[var(--brand-red)]`
- CTA strip: flat solid color, no gradient

### 8. `StatsPanel.vue` — Remove Gradients
- Level badge: replace `bg-gradient-to-br from-amber-400 to-yellow-500` with solid `bg-[var(--sunny-yellow)]` and chunky border
- XP progress bar: replace `bg-gradient-to-r from-[var(--soft-lavender)] to-[var(--sky-blue)]` with solid `bg-[var(--sky-blue)]`

### 9. `CelebrationOverlay.vue` — Remove Gradients
- Level up badge: replace `bg-gradient-to-br from-violet-500 to-purple-600` with solid `bg-[var(--soft-lavender)]`
- XP toast: replace gradient with solid `bg-[var(--sunny-yellow)]`

### 10. Dark Mode Cleanup
- Remove `.surface-light` helper class (used to force light ink in dark mode on cards — unnecessary if dark mode is neutral charcoal)
- Remove `.dark img` dimming filter
- Remove `.dark .bg-card` shadow override
- Update `AppearanceTabs.vue` to use theme variables instead of hardcoded `dark:bg-neutral-800`

### 11. Avatar Component Usage
- Throughout the app, change `rounded-lg` avatars to `rounded-full` for a more playful, circular look

## Out of Scope

- Font changes (current fonts are already playful)
- Layout structure changes (sidebar, bottom tab bar positioning)
- Page content/copy changes
- Admin dashboard styling (lower priority)
- Animation keyframes (they're theme-independent and work fine)
