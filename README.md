# LanceDesk Form Guard

WordPress plugin that protects contact form submissions with local heuristics and optional AI classification **before** notification emails are sent.

CAPTCHA stops scripted bots. It does not stop humans (or semi-automated tools) stuffing SEO backlinks into name and message fields. Form Guard hooks major form plugins, runs a free local Layer 1 check first, and only calls AI for ambiguous cases.

**Status:** Early development — planning + Stitch admin UI → plugin implementation.

**Current version:** 0.1.0 (planned)

## Features (planned)

- Soft-detect installed form plugins (no hard dependency)
- Adapters: WPForms, Contact Form 7, Gravity Forms, Quform
- Per-form enable toggles
- **Layer 1** local heuristics (honeypot, fast-submit, link/BBCode stuffing, etc.)
- **Layer 2** optional AI (BYO keys: Groq, OpenAI, Anthropic) with fallback
- Actions: silent discard, soft fail, or quarantine
- Stats dashboard, logs, CSV export, retention controls
- Encrypted API keys; hashed IPs; truncated message snippets

## Naming

| Item | Value |
|------|--------|
| Display name | LanceDesk Form Guard |
| Slug / text domain | `lancedesk-form-guard` |
| Prefix | `ld_fg_` / `LD_FG_` |
| License | GPL-2.0-or-later |
| Requires | WordPress 6.4+, PHP 8.0+ |

## Docs

| File | Purpose |
|------|---------|
| [`docs/prompt.txt`](docs/prompt.txt) | Original spam problem / idea |
| [`docs/plan1.md`](docs/plan1.md) | Feasibility + Layer 1/2 architecture |
| [`docs/plan2.md`](docs/plan2.md) | Locked product plan, schema, checklist |
| [`docs/stitch-ui-prompt.md`](docs/stitch-ui-prompt.md) | Google Stitch prompts for wp-admin UI |

Stitch HTML output can land in [`html_templates/`](html_templates/).

## Development

Follow LanceDesk WordPress standards in the sibling `wordpress-development` repo:

- Plugin main-file template
- Plugin creation checklist (Plugin Check / PHPCS / release zip)

## License

[GPL-2.0-or-later](LICENSE)
