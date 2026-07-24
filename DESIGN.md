# Design

<!-- impeccable:design-schema 1 -->

## Direction Contract

**THESIS:** The admin panel is a live command center monitoring ministry operations across Kenya — not a form-filler, but a nerve center. The category default (white sidebar, pastel accents, card-everywhere dashboard) is replaced by a dark navy operations strip with lime green as the only signal color.

**OWN-WORLD:** Navy Blue (`#1A2253`) fills the sidebar and top bar as a solid dark ground. Lime Green (`#9DE35D`) is the sole accent — active nav items, status badges, progress bars, chart highlights, interactive states. Content ground is near-white (`#F7F9FC`). Gray scale shifts cool-blue to harmonize with navy. Sharp corners (2px), thin 1px borders, compact spacing. Tables are the primary surface.

**STORY:** The visitor opens the panel and immediately sees the state of operations — active missions, pending approvals, financial flow — at a glance, like a network operations center. Every element earns its space through information density, not decoration.

**FIRST VIEWPORT:** Dark navy sidebar (240px) on the left with white nav text, lime green active indicator (left border + subtle background tint). Top bar in white with navy text. Content area: compact stats strip (6-8 metrics in a single horizontal row), then a 3-column grid of chart widgets below. No hero, no welcome banner — straight to the data.

**FORM:** Direction 1 of 7. Operate mode. Filament 5 custom theme.

## Visual Tokens

### Colors (OKLCH for Filament palette)

| Token | Hex | OKLCH |
|---|---|---|
| Navy 50 | `#E8EAF2` | `oklch(0.93 0.02 270)` |
| Navy 100 | `#C8CCE0` | `oklch(0.85 0.04 270)` |
| Navy 200 | `#A3A9CC` | `oklch(0.76 0.06 270)` |
| Navy 300 | `#7E85B8` | `oklch(0.66 0.08 270)` |
| Navy 400 | `#5A62A4` | `oklch(0.56 0.10 270)` |
| Navy 500 | `#3D4790` | `oklch(0.48 0.11 270)` |
| Navy 600 | `#2A3376` | `oklch(0.40 0.10 270)` |
| Navy 700 | `#1F2860` | `oklch(0.33 0.09 270)` |
| Navy 800 | `#1A2253` | `oklch(0.28 0.08 270)` |
| Navy 900 | `#121840` | `oklch(0.20 0.07 270)` |
| Navy 950 | `#0B0F2A` | `oklch(0.12 0.05 270)` |

### Accent

| Token | Hex | OKLCH |
|---|---|---|
| Lime 50 | `#F4FBE8` | `oklch(0.96 0.04 130)` |
| Lime 100 | `#E5F5C6` | `oklch(0.92 0.08 130)` |
| Lime 200 | `#CEED9F` | `oklch(0.87 0.13 130)` |
| Lime 300 | `#B8E46E` | `oklch(0.82 0.17 130)` |
| Lime 400 | `#9DE35D` | `oklch(0.78 0.19 130)` |
| Lime 500 | `#7EC844` | `oklch(0.70 0.18 130)` |
| Lime 600 | `#5FA82E` | `oklch(0.62 0.16 130)` |
| Lime 700 | `#458420` | `oklch(0.52 0.14 130)` |
| Lime 800 | `#2F6016` | `oklch(0.42 0.11 130)` |
| Lime 900 | `#1E3E0E` | `oklch(0.30 0.08 130)` |
| Lime 950 | `#122608` | `oklch(0.18 0.05 130)` |

### Status (carried from mobile app)

| Role | Hex |
|---|---|
| Success | `#0FA678` |
| Warning | `#F59E0B` |
| Error | `#D14343` |
| Info | `#2E7AF8` |

### Neutrals (cool-blue tinted)

| Step | Hex |
|---|---|
| 50 | `#F7F9FC` |
| 100 | `#F0F3F8` |
| 200 | `#E6EAF2` |
| 300 | `#D6DDE9` |
| 400 | `#B5C0D3` |
| 500 | `#8F9BB3` |
| 600 | `#6B758D` |
| 700 | `#4B5368` |
| 800 | `#2F3547` |
| 900 | `#171C29` |

### Typography

- **Body:** Figtree (existing) — clean, geometric, excellent at small sizes for data tables
- **No decorative fonts** — this direction earns hierarchy through weight and size, not typeface variety

### Component Language

- Border radius: 2px maximum (cards, buttons, badges, inputs)
- Borders: 1px solid, gray-200 default, navy-800 for emphasis
- Sidebar: 240px width, solid navy-800 background, white text, lime-400 active indicator
- Tables: primary surface, compact rows, alternating gray-50 tint
- Cards: minimal — used only for dashboard widgets, not page structure
- Buttons: sharp corners, solid fills, lime-400 for primary actions
- Badges: small, rounded-full, filled with status colors
- Stats: single horizontal strip, small labels, large numbers
