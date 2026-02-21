# Swap Shop Redesign — Design Document

**Date:** 2026-02-20
**Status:** Approved
**Goal:** Redesign One Red Paperclip from proof-of-concept to MVP with a vibrant, social, gamified marketplace aesthetic targeting young adults (18–35).

## Design Direction

**Codename:** "Swap Shop"
**Aesthetic:** Depop meets Duolingo — vibrant trading marketplace that feels fun, social, and rewarding.
**Audience:** Young adults (18–35), social media native.
**Key traits:** Light colors, strong gamification, mobile-first, high energy.

---

## 1. Color System

### Base

| Token             | Value               | Usage                     |
|-------------------|----------------------|---------------------------|
| `--paper`         | `hsl(28 60% 98%)`   | Page background (warm cream) |
| `--ink`           | `hsl(28 25% 16%)`   | Primary text              |
| `--ink-muted`     | `hsl(28 15% 45%)`   | Secondary text            |
| `--card`          | `hsl(0 0% 100%)`    | Card/surface background   |

### Brand & Accents

| Token               | Value               | Usage                          |
|----------------------|----------------------|--------------------------------|
| `--brand-red`        | `hsl(4 82% 56%)`    | Primary CTA, logo, brand       |
| `--brand-red-hover`  | `hsl(4 82% 50%)`    | Hover state for brand red      |
| `--electric-mint`    | `hsl(162 65% 52%)`  | Success, completed trades      |
| `--sunny-yellow`     | `hsl(45 95% 60%)`   | Achievements, highlights       |
| `--hot-coral`        | `hsl(12 90% 65%)`   | Notifications, offers, urgency |
| `--soft-lavender`    | `hsl(265 55% 75%)`  | Tags, categories, accents      |
| `--sky-blue`         | `hsl(205 85% 65%)`  | Social actions, links, info    |

### Surfaces & Elevation

- Card shadows: `rgba(28, 18, 8, 0.08)` — warm-toned, not cool gray
- Hover elevation: `translateY(-2px)` + expanded shadow
- Active/selected backgrounds: accent color at 8–12% opacity

### Dark Mode

Dark mode tokens exist in the current system and will be preserved but are not the priority for this redesign. The focus is on the light theme.

---

## 2. Typography

| Role        | Font         | Source       | Usage                         |
|-------------|--------------|--------------|-------------------------------|
| Display     | Fredoka      | Bunny Fonts  | Headings, hero text, titles   |
| Body        | DM Sans      | Bunny Fonts  | Body text, UI labels, forms   |
| Monospace   | JetBrains Mono | Bunny Fonts | Stats, counts, timestamps    |

**Why DM Sans over Nunito:** More modern feel at small sizes, slightly more "tech" which balances Fredoka's playfulness. Better weight range for UI work.

### Scale

- Hero: 3rem/3.5rem (48/56px)
- Page title: 2rem (32px)
- Section heading: 1.5rem (24px)
- Card title: 1.125rem (18px)
- Body: 1rem (16px)
- Small/caption: 0.875rem (14px)
- Micro: 0.75rem (12px)

---

## 3. Radius & Shape Language

| Element        | Radius     |
|----------------|------------|
| Cards          | `1rem` (16px) |
| Buttons        | `0.75rem` (12px) |
| Inputs         | `0.75rem` (12px) |
| Badges/chips   | `9999px` (full pill) |
| Avatars        | `9999px` (circle) |
| Bottom tab bar | `1.25rem` top corners |

---

## 4. Navigation & Layout

### Public Pages (Welcome, Explore)

**Desktop (≥1024px):**
- Slim sticky top bar (56px): logo left, search center, "Create Campaign" CTA + avatar right
- Full-width content area, no sidebar
- Standard footer

**Mobile (<1024px):**
- Slim top bar (48px): logo + condensed controls
- Bottom tab bar (56px), 5 tabs:
  1. **Home** (house) — Welcome/feed
  2. **Explore** (compass) — Browse campaigns
  3. **Create** (circled plus, oversized + raised, brand red) — New campaign
  4. **Activity** (bell) — Notifications/offers
  5. **Profile** (avatar) — Settings

The center "Create" tab is visually elevated — slightly larger circle, brand red fill, pops out from the bar.

### Authenticated Pages (Dashboard, Campaign Management)

**Desktop:** Keep sidebar layout, restyle:
- Warm cream background
- Colorful nav icons
- Active state: accent-colored pill highlight (not dark background)
- Collapses to icon-only rail at medium widths
- Main content: generous padding, max-width 1200px

**Mobile:** Sidebar replaced by bottom tab bar (same 5 tabs).

### Auth Pages

- Centered card layout (keep existing pattern)
- Add floating paperclip/trading illustrations behind card
- Warm shadow elevation on card

---

## 5. Component Designs

### Campaign Card

```
┌──────────────────────────────┐
│ ▓▓▓ category accent strip ▓▓▓│  ← colored by category
│                          ◉ 30%│  ← progress ring, top-right
│                               │
│  Current Item Name    →  Goal │  ← bold current, muted goal
│                               │
│  🔄 3 trades                  │  ← trade count pill badge
│                               │
│  👤 Username      ● Active    │  ← avatar + status badge
└──────────────────────────────┘
```

- Hover: lift + shadow + subtle scale (1.01)
- Category accent colors map per-category

### Progress Ring

- SVG circle, animated stroke-dashoffset
- Color gradient by completion: coral (0–33%) → yellow (34–66%) → mint (67–100%)
- Center text: "3/10" or "30%"

### Milestone Timeline (Campaign Detail)

```
●──────●──────●──────○──────○──────○
Start  T1     T2    Current        Goal
(mint) (mint) (mint) (pulsing      (outlined)
                      coral)
```

- Completed nodes: filled mint circles
- Current: pulsing coral circle
- Future: outlined gray circles
- Connecting line animates progress

### Trade Counter Badge

- Pill badge with trade count
- Animated number roll-up on increment

### Achievement Toasts

- Slide in from bottom on trade completion
- Confetti particle burst (CSS-only or lightweight)
- Messages: "Trade #3 complete!", "Halfway there!", "Goal reached!"
- Auto-dismiss after 4 seconds

### User Level System

| Tier              | Trades | Badge Color |
|-------------------|--------|-------------|
| Newcomer          | 0      | Gray        |
| Trader            | 3+     | Sky blue    |
| Swapper           | 10+    | Lavender    |
| Dealer            | 25+    | Yellow      |
| Paperclip Legend   | 50+    | Brand red   |

Small colored badge next to username wherever it appears.

### Button Variants

| Variant  | Color        | Usage                        |
|----------|--------------|------------------------------|
| Brand    | Red          | Create Campaign, Make Offer  |
| Success  | Electric mint| Accept Offer, Confirm Trade  |
| Social   | Sky blue     | Share, Follow                |
| Subtle   | Transparent  | Secondary actions, filters   |

### Empty States

- Playful illustrated SVG + warm copy
- "Nothing here yet — start your first trade-up adventure!"
- "No offers yet — share your campaign to attract traders!"

---

## 6. Page Designs

### Welcome (Public Landing)

1. **Hero:** Full-width warm gradient (cream → coral fade), "Trade your way up" in Fredoka, animated floating paperclip, "Start Trading" CTA + "See how it works" link
2. **Social proof strip:** Animated count-up stats — "1,200+ trades", "500+ campaigns"
3. **How it works:** 4 step cards with numbered circles, stagger-animate on scroll
4. **Trending campaigns:** Horizontal carousel of campaign cards with progress rings
5. **CTA banner:** Warm gradient, "Ready to start?" + dual buttons
6. **Footer:** Warm-toned, organized columns

### Dashboard (Authenticated)

1. **Greeting:** "Hey {name}!" with date
2. **Quick stats:** 3 mini-cards (Active Campaigns, Pending Offers with coral dot, Completed Trades with mint check)
3. **Your campaigns:** Grid of user campaign cards + "Create new" dashed card
4. **Activity feed:** Recent actions list (avatar + action + timestamp), grouped by day
5. **Suggested campaigns:** Horizontal scroll of related campaigns

### Campaign Index (Explore)

1. **Search bar:** Prominent, centered
2. **Category chips:** Horizontal scroll, pill-shaped with colored dots, filled when active
3. **Sort tabs:** "Trending" / "New" / "Almost there"
4. **Campaign grid:** 2-col mobile, 3-col desktop
5. **Load more** button (not pagination)

### Campaign Show (Detail)

1. **Hero:** Title, category badge, status, owner avatar + name
2. **Item showcase:** Start (small, left) → Current (large, center) → Goal (small, right, glowing ring)
3. **Progress ring** (large) + milestone timeline
4. **Tab bar:** Story | Offers (badge) | Trades (badge) | Comments (badge)
5. **Sticky mobile CTA:** "Make an Offer" fixed at bottom

### Campaign Create (Step Wizard)

- Step 1: "What do you have?" — start item
- Step 2: "What's your dream item?" — goal item
- Step 3: "Tell your story" — campaign details
- Step 4: "Review & launch" — preview card + confirm
- Progress indicator at top

---

## 7. Animation & Motion

| Element                | Animation               | Trigger        |
|------------------------|-------------------------|----------------|
| Paperclip mascot       | Float (translateY + rotate) | Continuous   |
| Background blobs       | Pulse (opacity + scale) | Continuous     |
| Campaign cards         | Lift + shadow on hover  | Hover          |
| Progress rings         | Stroke-dashoffset fill  | On mount       |
| Stats counters         | Number roll-up          | Scroll into view |
| How-it-works cards     | Stagger fade-in-up      | Scroll into view |
| Achievement toasts     | Slide up + confetti     | Trade complete |
| Milestone nodes        | Fill animation           | On mount       |
| Tab bar active         | Spring scale             | Tab switch     |
| Page transitions       | Fade (Inertia default)  | Navigation     |

Prefer CSS-only animations. Use `animation-delay` for stagger effects. Keep durations 200–400ms for UI, 600–1000ms for decorative.

---

## 8. Scope & Constraints

### In Scope (this redesign)

- Color system & design tokens update
- Typography swap (Nunito → DM Sans)
- Navigation restructure (bottom tab bar for mobile)
- All existing pages restyled (Welcome, Dashboard, Campaign Index/Create/Show, Auth pages, Settings)
- Campaign card component redesign
- Progress ring component (new)
- Milestone timeline component (new)
- Achievement toast component (new)
- Level badge component (new)
- Button variant additions (success, social)
- Empty state illustrations
- Campaign Create step wizard conversion

### Out of Scope

- New backend functionality (offers, trades, comments still use existing models/actions)
- Image upload UI (known gap, separate task)
- Real notification system (Activity tab can show placeholder)
- Dark mode refinement (preserve existing, don't prioritize)
- Native mobile app or PWA conversion

### Technical Constraints

- Vue 3 + Inertia v2 + TypeScript
- Tailwind CSS v4 with CSS variables
- shadcn-vue (new-york-v4 style)
- Existing component library preserved and extended
- Bunny Fonts for font loading
- CSS-only animations preferred (no motion library dependency)
