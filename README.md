# LanceDesk Form Guard

WordPress plugin that protects contact form submissions with local heuristics and optional AI classification **before** notification emails are sent.

**Status:** Admin UI implemented from Stitch templates · core engine + adapters scaffolded · ready for local testing / Plugin Check.

**Current version:** 0.1.0

## Features

- Soft-detect form plugins (no hard dependency)
- Adapters: WPForms, Contact Form 7, Gravity Forms, Quform
- Per-form enable toggles
- **Layer 1** local heuristics (honeypot, fast-submit, link/BBCode stuffing)
- **Layer 2** optional AI (BYO keys: Groq, OpenAI, Anthropic) with fallback
- Actions: silent discard, soft fail, or quarantine
- Stats dashboard, logs drawer, CSV-ready log table, retention controls
- Encrypted API keys; hashed IPs; truncated message snippets

## Naming

| Item | Value |
|------|--------|
| Display name | LanceDesk Form Guard |
| Slug / text domain | `lancedesk-form-guard` |
| Prefix | `ld_fg_` / `LD_FG_` |
| License | GPL-2.0-or-later |
| Requires | WordPress 6.4+, PHP 8.0+ |

## Admin UI

Screens match Stitch HTML under [`html_templates/`](html_templates/). Compiled CSS (no CDN):

```bash
npm install
npm run build:css
```

Fonts (Inter, Plus Jakarta Sans, Material Symbols Outlined) and Chart.js are **bundled locally** for Plugin Check compliance.

## Docs

| File | Purpose |
|------|---------|
| [`docs/prompt.txt`](docs/prompt.txt) | Original spam problem / idea |
| [`docs/plan1.md`](docs/plan1.md) | Feasibility + Layer 1/2 architecture |
| [`docs/plan2.md`](docs/plan2.md) | Locked product plan, schema, checklist |
| [`docs/stitch-ui-prompt.md`](docs/stitch-ui-prompt.md) | Google Stitch prompts for wp-admin UI |

## Release zip

```bash
bash zip-plugin.sh
```

Excludes `docs/`, `html_templates/`, `node_modules/`, and other dev paths (see `.distignore`).

## License

[GPL-2.0-or-later](LICENSE)
