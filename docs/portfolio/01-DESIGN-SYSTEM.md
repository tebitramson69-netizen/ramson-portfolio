# Design System — "Editorial Engineering"

**Status:** Tokens locked. Implemented in Phase 1 as `public/assets/css/main.css`.
**Direction:** Swiss-editorial typographic discipline · dark primary surface · near-monochrome
palette · one restrained accent · hairline borders instead of shadows · generous asymmetric
whitespace.

> **The governing idea:** this design must look expensive because of typography, spacing,
> composition and restraint — never because of effects. A junior portfolio is over-decorated;
> a senior one is under-decorated.

---

## 1. Colour

Dark-first. Every token is a CSS custom property on `:root`, with a `[data-theme="light"]`
override block so the light theme (post-launch) is a token swap, never a second stylesheet.

### 1.1 Surfaces

A five-step neutral ramp, **cool-shifted to hue ~222°** so it reads intentional rather than
like default grey. Warm-grey text on cool-grey surface is what makes a dark UI feel designed.

```css
--surface-base:    #0A0B0D;   /* page background          */
--surface-raised:  #101216;   /* cards, elevated sections */
--surface-overlay: #16181D;   /* modals, dropdowns        */
--surface-sunken:  #07080A;   /* footer, code blocks      */
--surface-inverse: #FAFAFA;   /* light-on-dark inversions */
```

### 1.2 Foreground

Four steps, all contrast-verified against `--surface-base`.

```css
--fg-primary:   #F4F5F7;   /* headings        ~17.8:1  AAA */
--fg-secondary: #A8ADB8;   /* body             ~8.1:1  AAA */
--fg-tertiary:  #6E7480;   /* meta, eyebrows   ~4.6:1  AA  */
--fg-disabled:  #43474F;   /* non-text only — NEVER readable text */
```

### 1.3 Borders — the structural device of this system

```css
--border-subtle:  rgba(255,255,255,0.06);
--border-default: rgba(255,255,255,0.10);
--border-strong:  rgba(255,255,255,0.18);
```

### 1.4 Accent — `#2DD4A7` (locked)

```css
--accent:         #2DD4A7;
--accent-hover:   #4FE0BA;
--accent-pressed: #1FBF95;
--accent-subtle:  rgba(45,212,167,0.10);   /* tinted backgrounds */
--accent-border:  rgba(45,212,167,0.28);
--accent-fg:      #04211A;                  /* text ON accent — ~13:1 */
```

Chosen deliberately: it reads as competence and calm, it is distinct from the default blue
`#007bff` that marks every student project, and it is unusual enough in developer portfolios
to be memorable without being loud.

> ### The accent discipline rule
>
> **The accent appears on at most THREE elements per viewport.**
>
> **Permitted:** the primary button · the availability dot · a link underline on hover · a
> focus ring · the active nav indicator.
>
> **Forbidden:** accent-coloured headings · accent gradients · accent card borders by default ·
> accent icons everywhere.
>
> **Scarcity is what makes the accent read as intentional.** This is the rule most likely to be
> violated under pressure, and the one that most directly separates premium from template.

### 1.5 Semantic — CMS only, never decorative on the public site

```css
--success: #34D399;
--warning: #FBBF24;
--danger:  #F87171;
--info:    #60A5FA;
```

---

## 2. Typography

Two families, self-hosted WOFF2, subsetted to `latin` + `latin-ext` (French accents matter for
a Cameroonian audience), `font-display: swap`, variable weights.

| Role | Family | Rationale |
|---|---|---|
| Display / headings | **Instrument Serif** or **Fraunces** (variable) | A high-contrast serif for display is the single most effective way to escape the generic all-sans developer-portfolio look. Immediately reads editorial |
| Body / UI | **Inter** (variable) | Excellent at small sizes, wide weight range, proven UI font |
| Code / meta | **JetBrains Mono** | Code blocks, technology tags, micro-labels |

*Final display face to be chosen in Phase 1 by setting the hero name in both and comparing.*

### 2.1 Scale — modular ~1.25 ratio, fluid via `clamp()`

```css
--fs-display-1: clamp(2.75rem, 7vw, 5.25rem);   /* hero name             */
--fs-display-2: clamp(2.25rem, 5vw, 3.75rem);   /* section headings      */
--fs-display-3: clamp(1.75rem, 3.5vw, 2.5rem);  /* project titles        */
--fs-heading-1: clamp(1.5rem, 2.5vw, 1.875rem); /* card titles           */
--fs-heading-2: 1.25rem;
--fs-body-lg:   1.125rem;                        /* value proposition     */
--fs-body:      1rem;
--fs-body-sm:   0.9375rem;
--fs-caption:   0.8125rem;
--fs-micro:     0.75rem;                         /* eyebrows, tags        */
```

### 2.2 The rules that do most of the work

- **Display type: `line-height: 1.0–1.1`, `letter-spacing: -0.02em` to `-0.035em`.** Large type needs *tighter* tracking and leading than browser defaults give it. **Loose display type is the most common reason a heading looks amateur.**
- Body: `line-height: 1.65`, `letter-spacing: 0`.
- Eyebrows / labels: `--fs-micro`, `letter-spacing: 0.12em`, uppercase, `--fg-tertiary`.
- **Measure:** prose capped at `68ch`; the hero value proposition at `~34ch`, so it breaks into two or three confident lines.
- **Weights: 400 body, 500 UI, 600 headings. Never 700+ for the display serif** — it muddies the contrast that makes the serif work.

---

## 3. Spacing, Grid, Containers

```css
--space-1: 0.25rem;  --space-2: 0.5rem;   --space-3: 0.75rem;
--space-4: 1rem;     --space-5: 1.5rem;   --space-6: 2rem;
--space-7: 3rem;     --space-8: 4rem;     --space-9: 6rem;
--space-10: 8rem;    --space-11: 12rem;
```

```css
--container-xs:  480px;   /* forms, login           */
--container-sm:  680px;   /* prose, case-study body */
--container-md:  900px;   /* narrow sections        */
--container-lg: 1200px;   /* default page container */
--container-xl: 1440px;   /* wide feature sections  */
--gutter: clamp(1.25rem, 5vw, 4rem);
```

> **Section vertical rhythm: `clamp(5rem, 10vw, 10rem)` top and bottom.**
>
> This one generous, consistently-applied value is **the highest-leverage single decision** for
> making the site feel premium. Cramped vertical spacing is the number-one reason a portfolio
> reads as a template.

**Grid:** 12 columns desktop, 8 tablet, 4 mobile, `gap: var(--space-5)`. CSS Grid for layout,
Flexbox for components. **Asymmetric splits (7/5, 5/7, 8/4) are the default;** symmetric 6/6 is
the template look and is used rarely and deliberately.

---

## 4. Radii, Elevation, Motion

```css
--radius-sm: 4px;  --radius-md: 6px;  --radius-lg: 10px;  --radius-full: 999px;
```

**Restrained by rule:** cards `6px` · images `6px` · buttons `6px` · inputs `6px` · **portrait
frame `4px`**. `--radius-full` is permitted **only** on the availability dot and avatar
thumbnails. No 24px blobs, no pill-shaped buttons.

**Elevation is borders, not shadows.** Depth comes from `--border-subtle` and surface-tone
steps. Shadows appear only on genuinely floating layers:

```css
--shadow-overlay: 0 16px 48px -12px rgba(0,0,0,0.6);
```

**Motion:**

```css
--ease-out:    cubic-bezier(0.16, 1, 0.3, 1);     /* entrances  */
--ease-in-out: cubic-bezier(0.65, 0, 0.35, 1);    /* transforms */
--dur-fast: 150ms;  --dur-base: 250ms;
--dur-slow: 400ms;  --dur-slower: 700ms;
```

**Animate only:** opacity, transform, colour, border-colour.

**Scroll reveals:** 16px translate-Y + opacity, `--dur-slower`, staggered 60ms, **fired once**
via `IntersectionObserver` then unobserved. *Re-animating on every scroll-past is nauseating
and immediately reads as amateur.*

**Hover on cards:** border-colour shift + 1.5% image scale. Nothing more.

**Two hard requirements:**
1. Everything inside `@media (prefers-reduced-motion: reduce)` reduces to `0.01ms` and disables transforms.
2. **Content must be fully visible with JavaScript disabled** — reveals start *visible*, and JS opts them into hiding.

---

## 5. Components

### Buttons
Four variants — `primary` (solid accent), `secondary` (bordered ghost), `ghost` (text only),
`danger` (CMS only). Three sizes — sm 36px, md 44px, lg 52px. Six states — default, hover,
active, `focus-visible`, disabled, loading (inline spinner, label swapped, **width locked** to
prevent layout jump).
**Minimum 44×44px touch target everywhere.**
Focus: `outline: 2px solid var(--accent); outline-offset: 2px`. **Never `outline: none` without
a replacement.**

### Cards
`--surface-raised`, `1px solid --border-subtle`, `--radius-md`, `--space-6` padding.
Hover: border → `--border-default`.
Where the whole card is a link, the `<a>` wraps the heading and a pseudo-element covers the
card — screen readers get sensible link text, and text inside stays selectable.
Variants: project-featured · project-compact · service · skill-group · info · case-study-nav.

### Forms
**Labels always visible above the field.** Never placeholder-as-label — it disappears on focus
and fails accessibility.
`--surface-sunken` background, `1px solid --border-default`, accent focus ring.
Inline error below the field in `--danger`, wired with `aria-describedby` and `aria-invalid`.
Help text in `--fg-tertiary`. Character counters where the design constrains length.
Required marked in **both** the label text and `aria-required`.

### Navigation
**Desktop:** transparent over the hero; past 80px acquires `--surface-base` at 88% opacity with
`backdrop-filter: blur(12px)` and a bottom hairline, condensing 88px → 64px over `--dur-base`.
Links get an animated 1px underline on hover. Active section indicated by `--fg-primary` plus a
2px accent underline, driven by `IntersectionObserver`.
**Mobile:** a full-screen overlay panel — *not* a cramped dropdown — with large type, staggered
link entrance, focus trap, `Escape` to close, `aria-expanded` on the trigger, and scroll lock.

### Tables (CMS only)
Hairline row separators, **no vertical borders, no zebra striping** — a border-based table reads
more refined. Sticky header, `--fg-tertiary` uppercase micro column labels, row hover,
right-aligned action column.
**On mobile: transform into stacked cards** with `data-label` pseudo-element labels, rather
than horizontal scrolling.

### Modals
`--surface-overlay`, `--radius-lg`, `--shadow-overlay`, `rgba(0,0,0,0.6)` backdrop with light
blur, max-width `--container-xs`. Focus trap, focus restored to the trigger on close, `Escape`
to close, `role="dialog"` + `aria-modal="true"` + `aria-labelledby`, body scroll lock.
**Destructive actions require typing the item name.**

### Notifications (toasts)
Top-right desktop, top-centre mobile. `role="status"` for success/info, `role="alert"` for
errors. **Auto-dismiss at 5s for success; never for errors.** Manual dismiss always available.
Maximum three stacked. Plus a server-rendered flash region for non-JS paths.

### Empty states
**Every list has a designed one:** restrained line-art icon, heading, one sentence, primary
action. Public site: "Projects are being added" with a contact CTA. **Never a blank section,
never an unstyled "No records found".**

### Loading states
Skeleton screens (surface blocks, subtle shimmer, reduced-motion aware) for content areas.
Inline button spinners with locked width. A 2px accent progress bar with percentage for
uploads. `content-visibility: auto` and explicit image dimensions so nothing shifts.

### Error states
- **Field** — inline, `aria-describedby`
- **Form** — a summary block at the top listing every error as an in-page link to the field
- **Page** — styled 404 with routes to work and contact; styled 500 with **no stack trace** and a logged reference ID the user can quote
- **Network** — a toast with a retry action

### Success states
A toast, plus — for meaningful actions — **a change of state in the UI itself**: the row moves,
the badge changes, the count decrements.
**Public contact form:** replace the form in place with a confirmation block repeating what was
sent and the expected response time. **Never redirect to a separate "thanks" page** — it loses
context.
