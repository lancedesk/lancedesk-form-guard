# Stitch UI Prompt — LanceDesk Form Guard (wp-admin)

**Status:** Design brief for Google Stitch  
**Product:** LanceDesk Form Guard (`lancedesk-form-guard`)  
**Context:** WordPress **admin** screens only (light wp-admin chrome). No public widget in v1 Stitch pass.  
**Plan reference:** `docs/plan2.md`

Use this as the master prompt (or per-screen prompts) in **Google Stitch** to generate admin UI HTML mockups. After Stitch, we implement with WordPress admin CSS/JS patterns and **bundled** assets (no CDN in the real plugin).

---

## Global design system (apply to every frame)

**Platform:** WordPress admin (`wp-admin`). Desktop primary **1280px**; also generate **tablet ~782px** for the Forms table and Dashboard. Mobile admin is secondary — one stacked Dashboard + one Logs frame is enough.

**Visual language (wp-admin-native, LanceDesk-clean):**
- **Background:** `#f0f0f1` (WP admin gray)
- **Surfaces:** White `#ffffff` panels, `1px` border `#c3c4c7`, radius **8px** (not heavy “app cards”)
- **Primary action:** WP admin blue `#2271b1` → hover `#135e96`
- **Text:** `#1d2327` primary, `#646970` secondary
- **Success:** `#00a32a` · **Warning:** `#dba617` · **Danger / blocked:** `#d63638`
- **Accent chips:** Soft tint backgrounds (green/red/amber/blue) with matching text — not neon glow
- **Typography:** System admin stack is fine for fidelity (`-apple-system`, BlinkMacSystemFont, Segoe UI). For Stitch hero titles on empty states, prefer a clean geometric sans — **not** Inter/Roboto as a branded look, and **no** purple gradients / cream-terracotta / broadsheet layouts
- **Icons:** Dashicons-style or simple line icons (20–24px). **No emoji**
- **Layout:** Left **plugin submenu** + main content. Mirror real WP: top admin bar can be simplified or omitted; include **Form Guard** left nav
- **Composition:** Each screen = one job. Dashboard may show KPI row + one chart + one recent table — not a marketing landing page
- **Avoid:** Floating promo badges, multi-shadow stacks, rounded-full pill clusters, dark-mode admin (v1 is light), external brand logos for Groq/OpenAI/Claude in chrome (use generic “Provider” labels in UI chrome; provider names OK inside form fields/help text)

**Accessibility:** 4.5:1 text contrast; visible focus; labeled toggles; tables with headers.

**Annotation:** Label frames `FG-D1`, `FG-I1`, etc. Include empty / populated / error states where listed.

---

## Left nav (every admin frame)

**Form Guard**

- Dashboard  
- Integrations  
- Forms  
- Protection  
- AI Providers  
- Logs  
- Settings  

Active item: WP-style highlight. Show a small status pill on Dashboard nav when protection is globally on (“On”).

---

## Screen inventory

| ID | Screen | Must include states |
|----|--------|---------------------|
| FG-D1 | Dashboard | Populated + empty |
| FG-I1 | Integrations | Mixed: active / inactive / not installed |
| FG-F1 | Forms | List + toggles; empty (no form plugins) |
| FG-P1 | Protection rules | Default settings |
| FG-A1 | AI Providers | Keys empty + keys configured |
| FG-L1 | Logs table | Populated |
| FG-L2 | Log detail / quarantine drawer | Spam example from real dump |
| FG-S1 | Settings | Retention + privacy notice |
| FG-E1 | Empty / setup checklist | First-run after activate |

Generate each as a **separate Stitch frame**. Prefer desktop; add mobile only for FG-D1 and FG-L1.

---

### FG-D1 — Dashboard

**Job:** Answer “Is Form Guard working, and what did it stop?”

**Content:**
- Page title: **Dashboard**
- Setup banner (dismissible) if no AI key *and* no forms protected yet — link to Integrations / Providers
- KPI row (4): **Blocked (7d)**, **Allowed (7d)**, **Quarantined**, **AI calls saved** (heuristic short-circuits)
- Chart: blocked per day (7 or 30 days) — simple bar or line; legend Heuristic vs AI
- Side panel or second chart: **Top block reasons** (horizontal bars)
- Table: **Recent blocks** — time, form, layer, action, reason snippet; link “View all”
- Footer note: “IPs are stored as hashes. Message bodies are truncated.”

**Empty state (FG-D1e):** Illustration-free calm empty — headline “No submissions protected yet”, one sentence, CTA **Open Integrations**

**Do not:** Put marketing stats, pricing, or “Upgrade Pro” in v1 frames.

---

### FG-I1 — Integrations

**Job:** Show which form plugins are available and ready.

**Content:**
- Title: **Integrations**
- Intro: “Form Guard hooks your installed form plugins before notification emails are sent.”
- Cards or table rows for:
  - WPForms  
  - Contact Form 7  
  - Gravity Forms  
  - Quform  
- Each row: icon placeholder, name, status badge:
  - **Active** (green) — plugin installed & adapter hooked  
  - **Installed — inactive** (amber) — plugin present but adapter off / dependency  
  - **Not installed** (gray)  
- Secondary: form count detected (“12 forms”) when active
- CTA per active row: **Manage forms** → Forms screen filtered

---

### FG-F1 — Forms

**Job:** Enable protection per form.

**Content:**
- Title: **Forms**
- Filters: Integration dropdown, Search, Status (All / Protected / Unprotected)
- Bulk: “Protect all visible”
- Table columns: Form name, Integration, Entries hint (optional), **Protected** toggle, Last blocked
- Toggle is the primary control (WP-style or switch)
- Help text: “When protected, submissions run local checks first, then optional AI if needed.”

**Empty (FG-F1e):** No compatible form plugins — CTA to Integrations + link “Install a form plugin”

---

### FG-P1 — Protection

**Job:** Configure how spam is decided and what happens.

**Sections:**
1. **Default action** — radio/segmented: Silent discard | Soft fail | Quarantine  
2. **When AI is unavailable** — Fail open (allow + log) [default] | Fail closed (block)  
3. **Layer 1** — toggles: Honeypot, Fast-submit, Link stuffing, BBCode patterns, Script-density heuristic; threshold sliders where useful  
4. **Layer 2** — Enable AI for ambiguous only; confidence threshold for auto-discard vs quarantine  
5. **Live example** (optional panel): paste sample text → “Would block: Yes — Link stuffing (heuristic)” — static mock is fine for Stitch

Primary save button: **Save protection settings**

---

### FG-A1 — AI Providers

**Job:** BYO keys, order, test.

**Content:**
- Title: **AI Providers**
- Notice: “Keys are encrypted at rest. Message snippets may be sent to the provider you enable. See Privacy in Settings.”
- Provider blocks (stack):
  1. Groq  
  2. OpenAI  
  3. Anthropic (Claude)  
- Each: Enable toggle, API key field (password), Model select, **Test connection**, last-tested status
- **Priority order** — drag handles or numbered selects (1–3)
- Note: “Layer 1 still works with no keys.”

**State FG-A1b:** All empty keys, Test disabled  
**State FG-A1c:** Groq connected (green “Connected”), others empty

Do **not** use official provider logos if Stitch would imply endorsement; text labels only.

---

### FG-L1 — Logs

**Job:** Audit what was blocked / quarantined.

**Content:**
- Title: **Logs**
- Filters: Date range, Verdict, Layer, Integration, Search
- Actions: **Export CSV**, retention reminder link
- Table: Date, Form, Verdict badge, Layer, Confidence, Action, Reason, IP hash (short)
- Row click opens drawer (FG-L2)
- Quarantine rows: quick actions **Release** | **Discard**

---

### FG-L2 — Log detail drawer

**Job:** Inspect one spam hit (use the real SEO dump pattern).

**Content:**
- Drawer from right (~420–480px)
- Header: Verdict badge + form name + timestamp
- Meta: integration, layer, confidence, action, provider (if AI), cache hit yes/no
- **Fields:** Name, Email, Message (truncated, monospace-friendly for URL spam)
- Reason explanation in plain language
- Actions: Allowlist email hash / Release / Discard / Close
- Privacy strip: “Full raw IP is not stored.”

Use a shortened version of the `prompt.txt` spam (BBCode link farm + Cyrillic keywords) so the mock looks real.

---

### FG-S1 — Settings

**Job:** Global plugin + compliance knobs.

**Content:**
- **Enable Form Guard** master toggle
- **Log retention** — days (e.g. 30 / 90 / 180)
- **Delete logs on uninstall** checkbox
- **Privacy** — short notice + checkbox “I understand AI providers may process submission text”
- Save button

---

### FG-E1 — First-run setup checklist

**Job:** Get to first protected form in &lt; 2 minutes.

Full-width panel on Dashboard or dedicated welcome:

1. Install/activate a form plugin (status)  
2. Add an AI provider (recommended before enabling forms)  
3. Protect at least one form  
4. Send a test submission  

Checklist with checkmarks; primary CTA on the first incomplete step.

---

## Copy-paste master prompt for Stitch

```
Design a WordPress wp-admin plugin UI for “LanceDesk Form Guard”.

Product: Blocks spam in contact form submissions (WPForms, Contact Form 7, Gravity Forms, Quform) using local heuristics first, then optional AI (user’s own API keys). Admin-only screens.

Visual system:
- Light WordPress admin: background #f0f0f1, white panels, border #c3c4c7, radius 8px
- Primary #2271b1, text #1d2327 / #646970
- Success #00a32a, warning #dba617, danger #d63638
- Left submenu: Dashboard, Integrations, Forms, Protection, AI Providers, Logs, Settings
- Clean, sparse, one job per screen. No purple gradients, no dark mode, no emoji, no Upgrade Pro, no floating badges
- Dashicons-style icons. Desktop 1280px frames

Create separate frames:
1) Dashboard — KPIs (Blocked 7d, Allowed 7d, Quarantined, AI calls saved), chart blocked/day (heuristic vs AI), top reasons, recent blocks table; also empty state
2) Integrations — four form plugins with Active / Not installed badges and Manage forms
3) Forms — filterable table with per-form Protected toggles; empty state when no form plugins
4) Protection — default action (silent discard / soft fail / quarantine), fail-open when AI down, Layer 1 toggles, Layer 2 confidence
5) AI Providers — Groq / OpenAI / Anthropic text-labeled cards, API key fields, test connection, priority order; empty and connected states
6) Logs — filters + table of blocked submissions
7) Log detail drawer — spam sample with BBCode URL stuffing and foreign keywords; quarantine actions
8) Settings — master enable, retention, uninstall cleanup, AI privacy acknowledgment
9) First-run checklist — protect a form in under 2 minutes

Tone: calm security tool for site owners, not a marketing landing page.
```

---

## Per-screen mini-prompts (if Stitch works better one-at-a-time)

**Dashboard:**  
`WP admin Form Guard Dashboard, light theme, KPI cards, 7-day bar chart heuristic vs AI, recent blocks table, left nav Form Guard.`

**Forms:**  
`WP admin table of contact forms with Protected toggles, filter by WPForms/CF7/Gravity/Quform, white panel on #f0f0f1.`

**AI Providers:**  
`WP admin settings for encrypted AI API keys, three providers stacked, Test connection button, priority order, no brand logos.`

**Logs + drawer:**  
`WP admin spam log table and right-hand drawer showing BBCode link-stuffed spam message, quarantine Release/Discard.`

---

## After Stitch (implementation notes)

- Rebuild with `admin/css`, `admin/js`; Chart.js **vendored locally** if charts ship
- Use `filemtime` for asset versions during development
- Match menu slugs from `plan2.md` (`ld-fg`, `ld-fg-integrations`, …)
- Exclude this `docs/` folder from release zips via `.distignore`
- Prefer WP list tables / Settings API patterns over custom SPA chrome unless Stitch layout clearly needs it

---

## Acceptance checklist for Stitch output

- [ ] All FG-* frames present (at least D1, I1, F1, P1, A1, L1, L2, S1, E1)
- [ ] Left nav consistent across frames
- [ ] Light wp-admin look (not a SaaS dark dashboard)
- [ ] No third-party trademarks in titles/chrome beyond necessary field labels
- [ ] Real-looking spam sample in L2
- [ ] Empty + populated variants for Dashboard and Forms
- [ ] Clear primary actions; no clutter strips in the first viewport of each screen
