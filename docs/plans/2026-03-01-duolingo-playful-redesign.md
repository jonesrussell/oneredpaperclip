# Duolingo-Style Playful Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Transform the site from a dark/gradient aesthetic to a bright, flat, Duolingo-style playful UI with chunky borders, bold press-down shadows, and cartoon-like elements.

**Architecture:** CSS-variable-driven theming. Change the design tokens in `app.css`, then update components to use chunky borders instead of shadows/gradients. Replace every gradient with a flat solid color. Update dark mode from obsidian purple to neutral charcoal.

**Tech Stack:** Tailwind CSS v4, Vue 3, CSS custom properties

---

### Task 1: Update Light Mode Color Tokens

**Files:**
- Modify: `resources/css/app.css:122-168` (`:root` block)

**Step 1: Replace `:root` CSS variables**

Change the `:root` block to:

```css
:root {
    --brand-red: hsl(0 80% 50%);
    --brand-red-hover: hsl(0 80% 44%);
    --brand-red-muted: var(--brand-red);
    --paper: hsl(0 0% 100%);
    --ink: hsl(0 0% 15%);
    --ink-muted: hsl(0 0% 55%);
    --background: hsl(0 0% 100%);
    --foreground: hsl(0 0% 12%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(0 0% 12%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(0 0% 12%);
    --primary: hsl(0 0% 12%);
    --primary-foreground: hsl(0 0% 98%);
    --secondary: hsl(220 14% 96%);
    --secondary-foreground: hsl(0 0% 14%);
    --muted: hsl(220 14% 96%);
    --muted-foreground: hsl(0 0% 48%);
    --accent: hsl(220 14% 92%);
    --accent-foreground: hsl(0 0% 14%);
    --destructive: hsl(0 84.2% 60.2%);
    --destructive-foreground: hsl(0 0% 98%);
    --border: hsl(220 10% 84%);
    --input: hsl(220 10% 84%);
    --ring: hsl(0 0% 25%);
    --chart-1: hsl(12 76% 61%);
    --chart-2: hsl(162 65% 45%);
    --chart-3: hsl(205 85% 55%);
    --chart-4: hsl(45 95% 55%);
    --chart-5: hsl(265 55% 65%);
    --radius: 1rem;
    --sidebar-background: hsl(0 0% 100%);
    --sidebar-foreground: hsl(0 0% 20%);
    --sidebar-primary: hsl(0 80% 50%);
    --sidebar-primary-foreground: hsl(0 0% 100%);
    --sidebar-accent: hsl(220 14% 92%);
    --sidebar-accent-foreground: hsl(0 0% 14%);
    --sidebar-border: hsl(220 10% 84%);
    --sidebar-ring: hsl(205 85% 55%);
    --sidebar: hsl(0 0% 100%);
    --electric-mint: hsl(88 62% 40%);
    --sunny-yellow: hsl(45 100% 50%);
    --hot-coral: hsl(0 100% 65%);
    --soft-lavender: hsl(275 70% 65%);
    --sky-blue: hsl(199 89% 52%);
}
```

**Step 2: Verify no syntax errors**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Commit**

```
feat: update light mode tokens to Duolingo-style bright palette
```

---

### Task 2: Replace Dark Mode (Obsidian -> Charcoal)

**Files:**
- Modify: `resources/css/app.css:179-261` (`.dark` block and dark base styles)

**Step 1: Replace the entire `.dark` block**

Remove the obsidian comment, all `--obsidian-*` variables, and `--shadow-card`. Replace with:

```css
.dark {
    --brand-red: hsl(0 80% 58%);
    --brand-red-hover: hsl(0 80% 65%);
    --brand-red-muted: hsl(0 80% 70%);
    --paper: hsl(220 15% 17%);
    --ink: hsl(0 0% 95%);
    --ink-muted: hsl(220 10% 60%);
    --background: hsl(220 15% 13%);
    --foreground: hsl(0 0% 95%);
    --card: hsl(220 15% 17%);
    --card-foreground: hsl(0 0% 95%);
    --popover: hsl(220 15% 20%);
    --popover-foreground: hsl(0 0% 95%);
    --primary: hsl(0 0% 95%);
    --primary-foreground: hsl(220 15% 13%);
    --secondary: hsl(220 12% 22%);
    --secondary-foreground: hsl(0 0% 95%);
    --muted: hsl(220 12% 18%);
    --muted-foreground: hsl(220 10% 60%);
    --accent: hsl(220 12% 22%);
    --accent-foreground: hsl(0 0% 95%);
    --destructive: hsl(0 84% 60%);
    --destructive-foreground: hsl(0 0% 98%);
    --border: hsl(220 10% 25%);
    --input: hsl(220 10% 25%);
    --ring: hsl(0 80% 50%);
    --chart-1: hsl(0 80% 58%);
    --chart-2: hsl(88 62% 40%);
    --chart-3: hsl(199 89% 52%);
    --chart-4: hsl(45 100% 50%);
    --chart-5: hsl(275 70% 65%);
    --sidebar-background: hsl(220 15% 17%);
    --sidebar-foreground: hsl(0 0% 95%);
    --sidebar-primary: hsl(0 80% 58%);
    --sidebar-primary-foreground: hsl(0 0% 100%);
    --sidebar-accent: hsl(220 12% 22%);
    --sidebar-accent-foreground: hsl(0 0% 95%);
    --sidebar-border: hsl(220 10% 25%);
    --sidebar-ring: hsl(0 80% 58%);
    --sidebar: hsl(220 15% 17%);
    --electric-mint: hsl(88 62% 45%);
    --sunny-yellow: hsl(45 100% 50%);
    --hot-coral: hsl(0 100% 65%);
    --soft-lavender: hsl(275 70% 70%);
    --sky-blue: hsl(199 89% 58%);
}
```

**Step 2: Remove dark mode overrides in `@layer base`**

Remove these blocks (lines 249-261):
- `.dark img { ... }` (image dimming)
- `.dark .bg-card { ... }` (shadow override)
- `.dark *:focus-visible { ... }` (cyan outline)

**Step 3: Remove `.surface-light` utility**

Remove lines 174-177 (the `.surface-light` class). It forced light ink colors for cards in dark mode — no longer needed since charcoal dark mode uses CSS variables that work properly.

**Step 4: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 5: Commit**

```
feat: replace obsidian dark theme with neutral charcoal
```

---

### Task 3: Update Button Variants (Chunky Press-Down Style)

**Files:**
- Modify: `resources/js/components/ui/button/index.ts`

**Step 1: Rewrite button variants**

Replace the entire `buttonVariants` definition:

```typescript
export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-bold transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive active:translate-y-[2px] active:border-b-2",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground border-2 border-b-4 border-primary/80 hover:brightness-110",
        destructive:
          "bg-destructive text-white border-2 border-b-4 border-red-700 hover:brightness-110 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border-2 border-b-4 border-[var(--border)] bg-background hover:bg-accent hover:text-accent-foreground",
        secondary:
          "bg-secondary text-secondary-foreground border-2 border-b-4 border-secondary-foreground/15 hover:brightness-95",
        ghost:
          "hover:bg-accent hover:text-accent-foreground border-2 border-transparent active:translate-y-0 active:border-b-2",
        link: "text-primary underline-offset-4 hover:underline border-0 active:translate-y-0 active:border-b-0",
        success:
          "bg-[var(--electric-mint)] text-white border-2 border-b-4 border-[hsl(88,62%,30%)] hover:brightness-110",
        social:
          "bg-[var(--sky-blue)] text-white border-2 border-b-4 border-[hsl(199,89%,40%)] hover:brightness-110",
        brand:
          "bg-[var(--brand-red)] text-white border-2 border-b-4 border-[hsl(0,70%,35%)] hover:brightness-110",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8 rounded-lg gap-1.5 px-3 has-[>svg]:px-2.5",
        "lg": "h-10 rounded-xl px-6 has-[>svg]:px-4",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
```

**Step 2: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Commit**

```
feat: restyle buttons with chunky Duolingo-style press-down effect
```

---

### Task 4: Update Card and Input Components

**Files:**
- Modify: `resources/js/components/ui/card/Card.vue`
- Modify: `resources/js/components/ui/input/Input.vue`

**Step 1: Update Card.vue**

Change the class string on line 15 from:
```
'bg-card text-card-foreground flex flex-col gap-6 rounded-2xl border border-[var(--border)] py-6 shadow-sm transition-shadow hover:shadow-md'
```
to:
```
'bg-card text-card-foreground flex flex-col gap-6 rounded-2xl border-2 border-[var(--border)] py-6 transition-colors hover:border-[var(--brand-red)]/30'
```

**Step 2: Update Input.vue**

In the `:class` binding (line 30), change:
```
'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm'
```
to:
```
'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-xl border-2 bg-transparent px-3 py-1 text-base transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm'
```

Changes: `rounded-md` -> `rounded-xl`, `border` -> `border-2`, removed `shadow-xs`

**Step 3: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 4: Commit**

```
feat: update Card and Input with chunky borders, remove shadows
```

---

### Task 5: Remove Blobs and Grain from PublicLayout

**Files:**
- Modify: `resources/js/layouts/PublicLayout.vue`

**Step 1: Remove decorative blobs (lines 21-41)**

Delete the entire "Decorative background blobs" div and its four child blobs.

**Step 2: Remove grain overlay (lines 43-47)**

Delete the `welcome-grain` div.

**Step 3: Remove the scoped style (lines 171-175)**

Delete the `<style scoped>` block with the `.welcome-grain` definition.

**Step 4: Clean up dark: overrides in PublicLayout**

- Header (line 53): Remove `dark:bg-[var(--paper)]/90` — the `bg-[var(--paper)]/90` already works in both modes via CSS variables.
- Footer (line 128): Remove `dark:bg-[var(--paper)]/80` — same reason.

**Step 5: Update header CTA button styling**

Lines 95 and 110 have `shadow-md hover:-translate-y-0.5 hover:shadow-lg` — replace with chunky border style:
```
border-2 border-b-4 border-[hsl(0,70%,35%)] transition-all active:translate-y-[2px] active:border-b-2
```

Remove `shadow-md`, `hover:-translate-y-0.5`, `hover:shadow-lg` from those buttons.

**Step 6: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 7: Commit**

```
feat: remove decorative blobs and grain from PublicLayout
```

---

### Task 6: Remove All Gradients from Welcome.vue

**Files:**
- Modify: `resources/js/pages/Welcome.vue`

**Step 1: Hero gradient background (line 94)**

Remove the entire gradient overlay div (lines 92-96):
```html
<!-- Full-bleed hero gradient (edge to edge, no rounded corners) -->
<div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[var(--hot-coral)]/12 via-transparent to-[var(--sunny-yellow)]/10" aria-hidden="true" />
```

**Step 2: Paperclip glow (line 123)**

Remove the gradient glow div:
```html
<div class="absolute -inset-4 rounded-full bg-gradient-to-br from-[var(--brand-red)]/20 to-transparent blur-2xl" aria-hidden="true" />
```

**Step 3: House container (line 128)**

Remove `shadow-lg dark:bg-[var(--paper)] dark:shadow-[var(--shadow-card)]`. Add `border-2 border-[var(--border)]`. Result:
```html
class="hero-house relative flex h-48 w-48 shrink-0 items-center justify-center border-2 border-[var(--border)] bg-[var(--paper)]"
```

**Step 4: Hero CTA buttons (lines 153, 159, 166)**

Replace `rounded-md bg-[var(--brand-red)] px-5 py-2.5 font-medium text-white shadow-sm transition-colors hover:bg-[var(--brand-red-hover)]` with:
```
rounded-xl bg-[var(--brand-red)] border-2 border-b-4 border-[hsl(0,70%,35%)] px-5 py-2.5 font-bold text-white transition-all hover:brightness-110 active:translate-y-[2px] active:border-b-2
```

Replace outline button (line 166) `rounded-md border border-[var(--ink)]/25 px-5 py-2.5 font-medium text-[var(--ink)] transition-colors hover:border-[var(--ink)]/40 hover:bg-[var(--ink)]/5` with:
```
rounded-xl border-2 border-b-4 border-[var(--border)] px-5 py-2.5 font-bold text-[var(--ink)] transition-all hover:bg-[var(--accent)] active:translate-y-[2px] active:border-b-2
```

**Step 5: Social proof section (line 179)**

Change `bg-[var(--card)]/80 dark:bg-white/5` to `bg-[var(--muted)]`. Remove `dark:bg-white/5`.

**Step 6: How-it-works vertical line (line 237)**

Replace `bg-gradient-to-b from-[var(--hot-coral)] via-[var(--sunny-yellow)] to-[var(--brand-red)] opacity-40` with:
```
bg-[var(--brand-red)] opacity-25
```

**Step 7: How-it-works cards (line 261)**

Change `shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md` to:
```
border-2 border-[var(--border)] transition-colors hover:border-[var(--brand-red)]/30
```

Remove `border border-[var(--ink)]/10` (replacing with `border-2`).

**Step 8: CTA strip (line 365)**

Change `bg-[hsl(0_0%_11%)] dark:bg-background` to `bg-[var(--brand-red)]`. This makes the strip brand-colored instead of near-black.

Update the heading text class (line 369) — already white, keep it.

Update CTA buttons (lines 383, 392): Remove `surface-light`. Change `bg-white text-[var(--ink)]` button to:
```
rounded-xl border-2 border-b-4 border-white/40 bg-white px-5 py-2.5 font-bold text-[var(--brand-red)] transition-all hover:brightness-95 active:translate-y-[2px] active:border-b-2
```

The outline button: change `border border-white/40 ... hover:border-white/70 hover:bg-white/10` to:
```
rounded-xl border-2 border-b-4 border-white/30 px-5 py-2.5 font-bold text-white transition-all hover:bg-white/10 active:translate-y-[2px] active:border-b-2
```

**Step 9: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 10: Commit**

```
feat: remove all gradients from Welcome page, add chunky borders
```

---

### Task 7: Remove Gradients from ChallengeCard

**Files:**
- Modify: `resources/js/components/ChallengeCard.vue`

**Step 1: Card wrapper (line 81)**

Remove `surface-light`. Change:
- `border border-[var(--ink)]/10` -> `border-2 border-[var(--border)]`
- Remove `hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(28,18,8,0.12)]`
- Add `hover:border-[var(--brand-red)]/30`
- Remove inline `style="box-shadow: ..."` on line 82

**Step 2: Hero image area (line 96)**

Change `bg-gradient-to-br from-[var(--ink)]/8 to-[var(--ink)]/4` to `bg-[var(--muted)]`

**Step 3: Category accent strip (line 87)**

Change `w-1` to `w-1.5`

**Step 4: ChallengeCard dark: overrides**

Remove all hardcoded `dark:text-[hsl(...)]` classes throughout. The component already uses CSS variable-based colors like `text-[var(--ink)]`, `text-[var(--ink-muted)]`. The hardcoded warm-toned dark colors (e.g., `dark:text-[hsl(38,15%,88%)]`) no longer match the neutral charcoal theme. Replace:

- Line 126: Remove `dark:text-[hsl(38,15%,88%)]` — the `text-[hsl(28,18%,26%)]` should become `text-foreground`
- Line 136: Remove `dark:text-[hsl(38,8%,68%)]` — use `text-muted-foreground`
- Line 184: Remove `dark:text-[hsl(38,12%,82%)]` — use `text-foreground`
- Line 199: Remove `dark:text-[hsl(38,8%,62%)]` — use `text-muted-foreground`
- Line 206: Remove `dark:text-[hsl(38,8%,62%)]` — use `text-muted-foreground`
- Line 215: Remove `dark:text-white` — use `text-foreground`

**Step 5: Avatar (line 208)**

Change `rounded-lg` to `rounded-full` for circular avatars.

**Step 6: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 7: Commit**

```
feat: remove gradients and dark overrides from ChallengeCard
```

---

### Task 8: Remove Gradients from StatsPanel

**Files:**
- Modify: `resources/js/components/StatsPanel.vue`

**Step 1: Panel container (line 81)**

Change `dark:shadow-[var(--shadow-card)]` to nothing (remove it). Also change `bg-card/80 backdrop-blur-sm` to `bg-card`.

**Step 2: XP section background (line 89)**

Change `bg-gradient-to-br from-[var(--soft-lavender)]/10 to-[var(--sky-blue)]/10` to `bg-[var(--sky-blue)]/10`

**Step 3: Level badge (line 95)**

Change `bg-gradient-to-br` and remove `:class="levelBadgeColor"`. Replace with a static `bg-[var(--sky-blue)]`. Result:
```html
class="flex size-10 items-center justify-center rounded-full bg-[var(--sky-blue)] font-display text-lg font-bold text-white border-2 border-[hsl(199,89%,40%)]"
```
Remove the `levelBadgeColor` computed property (lines 62-66).

**Step 4: XP progress bar (line 114)**

Change `bg-gradient-to-r from-[var(--soft-lavender)] to-[var(--sky-blue)]` to `bg-[var(--sky-blue)]`

**Step 5: Streak section background (line 137)**

Change `bg-gradient-to-br from-orange-500/10 to-red-500/10` to `bg-[var(--hot-coral)]/10`

**Step 6: Trades section background (line 185)**

Change `bg-gradient-to-br from-[var(--electric-mint)]/10 to-emerald-500/10` to `bg-[var(--electric-mint)]/10`

**Step 7: Days active section background (line 212)**

Change `bg-gradient-to-br from-[var(--sunny-yellow)]/10 to-amber-500/10` to `bg-[var(--sunny-yellow)]/10`

**Step 8: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 9: Commit**

```
feat: remove gradients from StatsPanel, use flat solid fills
```

---

### Task 9: Remove Gradients from CelebrationOverlay

**Files:**
- Modify: `resources/js/components/CelebrationOverlay.vue`

**Step 1: Level up badge (line 236)**

Change `bg-gradient-to-br from-violet-500 to-purple-600` to `bg-[var(--soft-lavender)] border-2 border-[hsl(275,70%,50%)]`

Remove `shadow-lg`.

**Step 2: XP toast (line 255)**

Change `bg-gradient-to-r from-[var(--sunny-yellow)] to-amber-500` to `bg-[var(--sunny-yellow)] border-2 border-[hsl(45,100%,38%)]`

Remove `shadow-2xl`.

**Step 3: Modal container (line 202)**

Change `shadow-2xl` to `border-2 border-[var(--border)]`. Keep `border border-border` and change to `border-2`.

**Step 4: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 5: Commit**

```
feat: remove gradients from CelebrationOverlay, use flat fills
```

---

### Task 10: Remove Gradients from challenges/Show.vue

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

**Step 1: Header gradient (line 266)**

Change `bg-gradient-to-br from-[var(--hot-coral)]/15 via-[var(--sunny-yellow)]/10 to-[var(--electric-mint)]/10` to `bg-[var(--muted)]`

**Step 2: Level badge (line 334)**

Change `bg-gradient-to-r from-violet-500 to-purple-600` to `bg-[var(--soft-lavender)] border border-[hsl(275,70%,50%)]`

**Step 3: Offer CTA box (line 404)**

Change `bg-gradient-to-br from-[var(--brand-red)]/5 to-[var(--hot-coral)]/5` to `bg-[var(--brand-red)]/5`

**Step 4: Remove dark:shadow-[var(--shadow-card)] everywhere**

Lines 379, 465, 541 — remove `dark:shadow-[var(--shadow-card)]`

**Step 5: Offer CTA button (around line 412)**

Remove `shadow-[var(--brand-red)]/25 shadow-lg hover:-translate-y-0.5` — add `border-2 border-b-4 border-[hsl(0,70%,35%)]`

**Step 6: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 7: Commit**

```
feat: remove gradients and shadow overrides from challenge Show page
```

---

### Task 11: Clean Up Remaining Dark Mode Overrides

**Files:**
- Modify: `resources/js/components/AppearanceTabs.vue`
- Modify: `resources/js/components/OfferCard.vue`
- Modify: `resources/js/components/TradeCard.vue`
- Modify: `resources/js/pages/About.vue`

**Step 1: AppearanceTabs.vue**

Replace hardcoded neutral colors with theme variables:
- Line 16: `bg-neutral-100 dark:bg-neutral-800` -> `bg-muted`
- Line 25: `bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100` -> `bg-card shadow-xs`
- Line 26: `text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60` -> `text-muted-foreground hover:bg-accent hover:text-foreground`

**Step 2: OfferCard.vue (line 66) and TradeCard.vue (line 64)**

Remove `dark:shadow-[var(--shadow-card)]` from both.

**Step 3: About.vue**

- Line 16: Remove `dark:bg-[var(--brand-red)]/20 dark:text-[var(--brand-red-muted)]` — the light mode values work in both modes now via CSS vars.
- Line 38: Remove `dark:bg-[var(--paper)]` — `bg-white/50` needs to change to `bg-[var(--muted)]/50` so it works in both modes.

**Step 4: Remove surface-light references in Welcome.vue**

Lines 383 and 392: Remove `surface-light` class.

**Step 5: Verify build**

Run: `npm run build`
Expected: Build succeeds

**Step 6: Run lint**

Run: `vendor/bin/pint --dirty --format agent && npm run lint`

**Step 7: Commit**

```
feat: clean up remaining dark mode overrides across components
```

---

### Task 12: Run Full Test Suite

**Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests pass (CSS changes don't break PHP tests, but verify)

**Step 2: Run frontend build**

Run: `npm run build`
Expected: Build succeeds with no errors

**Step 3: Final commit if any fixes needed**

```
fix: resolve any test/build issues from playful redesign
```
