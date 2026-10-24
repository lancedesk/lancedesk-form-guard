# LanceDesk Form Guard

WordPress plugin that protects **contact form submissions** and **WordPress post comments** with local heuristics and optional AI classification — before notification emails are sent or comments are saved.

**Repository:** https://github.com/lancedesk/lancedesk-form-guard  
**Current version:** 0.1.16  
**Requires:** WordPress 6.4+, PHP 8.0+

## What it does

Form Guard sits in front of supported form plugins and native comment forms. Each submission runs through Layer 1 heuristics (always local). Optionally, Layer 2 AI can classify ambiguous content using your own API keys (Groq, OpenAI, Anthropic).

Blocked and quarantined submissions are logged with hashed IP/email and truncated field snippets. Contact forms can silent-discard, soft-fail, or quarantine. **Blocked comments are never written to `wp_comments`** — they exist only in Form Guard logs.

## Integrations

| Source | How protection is enabled |
|--------|---------------------------|
| Contact Form 7 | Per-form toggle on **Forms** |
| WPForms | Per-form toggle |
| Gravity Forms | Per-form toggle |
| Quform | Per-form toggle |
| Ninja Forms | Per-form toggle |
| **WordPress Core (comments)** | **Settings → WordPress Core** toggle (site-wide) |
| Custom (via `ld_fg_adapters`) | Appears under **Forms** when an adapter is registered (e.g. Italbox Corporate Inquiry) |

## Layer 1 heuristics (local)

- Honeypot + too-fast submission traps
- BBCode `[url=]` injection
- Link stuffing (>2 URLs)
- Suspicious shortener / image hosts (bit.ly, imgbb, etc.)
- Commercial / SEO cold outreach scoring (audit pitches, polite sales language, guest-post/link-building CTAs)
- Social handle dumps

## Layer 2 AI (optional, off by default)

- BYO API keys (encrypted at rest)
- Multi-provider fallback order
- Requires privacy acknowledgment in Settings
- Fail-open or fail-closed when providers are unavailable

## Admin

- Setup checklist, dashboard, integrations overview
- Per-form protection toggles
- Protection rules (default action, heuristic toggles, confidence thresholds)
- AI provider configuration and connection test
- Security logs with detail drawer
- Per-user log read/unread state and unread badge

## Installation

1. Upload `lancedesk-form-guard` to `/wp-content/plugins/`
2. Activate **LanceDesk Form Guard**
3. Open **Form Guard** → complete setup checklist
4. Protect forms and/or enable **Protect WordPress comment forms** in Settings
5. Optionally enable AI under Protection + Providers

## Development

```bash
npm install
npm run build:css    # compiles admin/css/ld-fg-admin-src.css → ld-fg-admin.css
bash zip-plugin.sh   # builds ../lancedesk-form-guard.zip (runtime files only)
php tools/spam-sample-check.php   # Layer 1 sample regression (dev tree only; excluded from release zip)
```

Run **Plugin Check** against the release zip, not the full dev tree (`tools/`, `docs/`, and `node_modules/` are excluded from the zip).

## Changelog

### 0.1.16

- Dashboard: Recent Blocks only lists blocked/quarantined entries (no longer shows Allowed as “Blocked”)

### 0.1.15

- Security Logs detail drawer: Delete (bottom right) opens the FG-L1d confirm modal for that entry

### 0.1.14

- Security Logs: Reset filter link only when a select filter is active
- Security Logs: Reason column is reason-only (payload stays in the detail drawer)

### 0.1.13

- Security Logs redesign (FG-L1b): KPI strip, layer/date filters, bulk mark read/unread/export/delete, unread dots, middle-aligned rows
- Header: Export CSV, Columns (hide/show), Clear All Logs with confirm modal
- CSV export respects current filters; selected-row export from bulk menu

### 0.1.12

- Security Logs pagination (25 per page) with correct total counts
- Bulk select / delete selected / delete all logs
- Server-side search + verdict filter
- Settings: Keep security logs toggle (default on); optional auto-purge at 15/30/90/180 days

### 0.1.11

- External adapters via `ld_fg_adapters` filter (e.g. Italbox Corporate Inquiry)
- `ld_fg_trap_fields()` helper + `LD_FG_Frontend::get_trap_fields()` for custom widgets
- Front-end JS discovers forms marked with `data-ld-fg-integration` / `data-ld-fg-form`

### 0.1.10

- Comprehensive vendor/agency contact-form solicitation scoring (URL + reach-us / prefer-email, sales mailboxes, service pitches, brand↔domain match)

### 0.1.9

- Quform built-in honeypot treated as Form Guard honeypot when filled
- New Layer 1 rule: Random / Gibberish Content (+ dotted spam emails)
- Quform Captured Fields omit plumbing keys (csrf, loaded, ajax, uid, …)

### 0.1.8

- Settings and Protection Save buttons disabled until the form is dirty

### 0.1.7

- Quform: logs use the form display name (`config('name')`), not the numeric id
- Quform: always merge flattened `quform_*` POST with form values so message text is never skipped
- Quform: silent discard short-circuits before entry save

### 0.1.6

- Default off for delete-on-uninstall; clearer Settings copy for a full clean removal

### 0.1.5

- Hide Logs search and Forms filter/search when fewer than 20 items
- Integrations intro uses full content width
- Providers Save Key disabled until an API key is entered

### 0.1.4

- Soft collaboration / link-opportunity outreach patterns in Layer 1
- Corpus hardening: soft SEO proposals, backlink swaps, Xrumer/GSA, token scams, Russian tehosmotr/darknet/gambling SEO, product dumps
- Settings: delete-on-uninstall and privacy ack use pill toggles
- Dev: `php tools/corpus-spam-check.php` for the real-world spam corpus

### 0.1.3

- WordPress Core comment adapter (`preprocess_comment`, honeypot injection)
- Blocked comments aborted before `wp_new_comment()` — no spam folder / approved leak for logged-in moderators
- Commercial outreach threshold 4→3; new polite pitch patterns
- Logs: read/unread per user, nav badge, table UX improvements
- Settings: WordPress Core section with comment protection toggle
- Integrations: WordPress Core card
- Version 0.1.3; readme and Plugin Check fixes

### 0.1.2

- Stronger heuristics: URL limit, suspicious hosts, commercial outreach, handle dumps

### 0.1.1

- Log detail drawer; AI privacy gate; readme external-services docs

### 0.1.0

- Initial release

## License

[GPL-2.0-or-later](LICENSE)
