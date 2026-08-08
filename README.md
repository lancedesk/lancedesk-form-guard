# LanceDesk Form Guard

WordPress plugin that protects contact form submissions with local heuristics and optional AI classification **before** notification emails are sent.

**Repository:** https://github.com/lancedesk/lancedesk-form-guard  
**Current version:** 0.1.0

## Features

- Soft-detect form plugins (no hard dependency)
- Adapters: WPForms, Contact Form 7, Gravity Forms, Quform, Ninja Forms
- Per-form enable toggles
- **Layer 1** local heuristics (honeypot, fast-submit, link/BBCode stuffing)
- **Layer 2** optional AI (BYO keys: Groq, OpenAI, Anthropic) with fallback
- Actions: silent discard, soft fail, or quarantine
- Stats dashboard, security logs, retention controls
- Encrypted API keys; hashed IPs; truncated message snippets

## Requirements

- WordPress 6.4+
- PHP 8.0+

## Installation

1. Upload the `lancedesk-form-guard` folder to `/wp-content/plugins/`
2. Activate **LanceDesk Form Guard**
3. Open **Form Guard** in wp-admin and complete setup
4. Protect forms and optionally connect an AI provider

## Naming

| Item | Value |
|------|--------|
| Display name | LanceDesk Form Guard |
| Slug / text domain | `lancedesk-form-guard` |
| Prefix | `ld_fg_` / `LD_FG_` |
| License | GPL-2.0-or-later |

## Development (CSS)

Admin UI CSS is built with Tailwind. After editing `admin/css/ld-fg-admin-src.css`:

```bash
npm install
npm run build:css
```

Fonts (Inter, Plus Jakarta Sans, Material Symbols Outlined) and Chart.js are **bundled locally** (no CDN).

## License

[GPL-2.0-or-later](LICENSE)
