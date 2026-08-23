# LanceDesk Form Guard

WordPress plugin that protects **contact form submissions** and **WordPress post comments** with local heuristics and optional AI classification — before notification emails are sent or comments are saved.

**Repository:** https://github.com/lancedesk/lancedesk-form-guard  
**Current version:** 0.1.3  
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
