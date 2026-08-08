# LanceDesk Form Guard — Plan 2 (product + architecture)

**Status:** Locked naming + v1 scope (pre-code, pre-Stitch implementation)  
**Working folder:** `/home/robert/local-network/lancedesk-form-guard`  
**Prior docs:** `prompt.txt` (problem), `plan1.md` (feasibility + Layer 1/2)

---

## 1. Naming (locked)

| Item | Value |
|------|--------|
| Display name | **LanceDesk Form Guard** |
| Slug / folder / text domain | `lancedesk-form-guard` |
| Main file | `lancedesk-form-guard.php` |
| Constants | `LD_FG_*` |
| Hooks / options prefix | `ld_fg_` |
| Classes | `LD_FG_*` |
| `@package` | `LanceDesk_Form_Guard` |
| License | `GPL-2.0-or-later` |
| Requires at least | `6.4` |
| Requires PHP | `8.0` |
| Tested up to | current WP stable major (keep header ↔ readme in sync) |

**wp.org rules:** Do not put WordPress, Groq, OpenAI, Claude, WPForms, Contact Form 7, Gravity Forms, or Quform in the **display name**, **slug**, **tags**, or **short description**. Mention integrations only in the long Description / FAQ. Max **5 tags**.

**Suggested tags:** `spam`, `forms`, `security`, `ai`, `antispam`

**Suggested one-line description:**  
Protects contact form submissions with local heuristics and optional AI classification before notification emails are sent.

---

## 2. Product thesis

CAPTCHA stops dumb bots. It does not stop humans (or semi-automated tools) stuffing SEO backlinks into Name/Message fields. Form Guard hooks major form plugins **before mail**, runs **Layer 1 local heuristics**, then **Layer 2 AI only when needed**, and logs outcomes for the site owner.

**v1 distribution model:** Self-contained WordPress plugin. Site owner brings their own AI API key(s). No LanceDesk hosted API required for v1. Shared threat intel / SaaS can be a later premium layer.

---

## 3. v1 scope

### In scope

- Soft-detect form plugins; no hard `Requires Plugins` header
- Adapters: **WPForms**, **Contact Form 7**, **Gravity Forms**, **Quform**, **Ninja Forms**
- Auto-list forms; **per-form enable** (+ optional “protect all forms of this type”)
- Layer 1 heuristics (always free/local)
- Layer 2 LLM via Groq + optional OpenAI / Anthropic fallback (encrypted keys)
- Verdict actions: silent discard | soft validation fail | quarantine
- Stats dashboard + blocked/quarantine log + CSV export
- Retention setting + uninstall cleanup
- External services documented in `readme.txt`

### Out of scope (v1)

- Custom LanceDesk contact form builder
- Comment spam / `wp_mail` global filtering
- Shared cross-site IP blocklist SaaS
- Hard IP ban on first offense
- Fluent Forms / Formidable (adapters later)
- Stitch → production CSS pixel-perfect in same PR as scaffold (Stitch first, then implement)

---

## 4. Architecture

```
Submission (form plugin)
    → Adapter normalizes fields
    → Layer 1 Heuristics (instant)
         ├─ clear spam     → action + log (no AI)
         ├─ clear ham      → allow
         └─ ambiguous      → Layer 2 AI
              → cache by content hash
              → Groq → fallback providers
              → action + log
```

### Adapter contract

```
LD_FG_Form_Adapter
  is_available(): bool
  get_label(): string
  list_forms(): array{id, title, status}
  register_hooks(): void
  extract( $context ): LD_FG_Submission
  apply_block( $context, LD_FG_Verdict ): void
```

**Normalized submission (`LD_FG_Submission`):**
- `integration` (slug), `form_id`, `form_title`
- `name`, `email`, `message`, `fields` (key→value map)
- `ip_hash`, `user_agent_hash` (optional)
- `page_url`, `submitted_at`

**Verdict (`LD_FG_Verdict`):**
- `is_spam`, `confidence`, `layer` (`heuristic`|`ai`), `reason`, `action`

### Hook targets (implementation notes)

| Integration | Prefer intercept |
|-------------|------------------|
| WPForms | Early process / pre-email filters |
| Contact Form 7 | `wpcf7_before_send_mail` / validation |
| Gravity Forms | `gform_validation` / pre-submission |
| Quform | Pre-process / validation filters |
| Ninja Forms | `ninja_forms_submit_data` / `ninja_forms_submission_actions` |

Exact hook names confirmed against each plugin’s current docs at coding time.

---

## 5. Layer 1 heuristics (local)

Run in order; short-circuit on high-confidence spam:

1. Honeypot filled (if injected)
2. Submission too fast (e.g. &lt; 2s from form render token)
3. BBCode `[url=…]` / link stuffing density
4. URL count above threshold
5. Non-local script density vs site language (configurable)
6. Reply-To / email pattern oddities (obfuscated addresses)
7. Optional: cached blocklist hit (local option / file — no live paid API required in v1)

Ambiguous → Layer 2. Clear ham → allow without AI.

---

## 6. Layer 2 AI

- Prompt returns strict JSON: `{ "spam": bool, "confidence": 0-1, "reason": "…" }`
- Send minimal payload: name + email + message (+ truncated other fields), not full mail headers
- Content-hash cache (transients or custom table) to skip repeat templates
- Provider chain: Groq → OpenAI → Claude (user-ordered)
- Timeout + **fail-open** default (allow + log `ai_error`) so legit forms never die when AI is down
- Circuit breaker if provider errors spike
- Encrypt API keys at rest (same pattern as LanceDesk AI Chatbot)

**Privacy:** Message text may leave the site. Document in Settings + External services + optional admin acknowledgment.

---

## 7. Actions & safety

| Mode | Behavior |
|------|----------|
| `silent_discard` | No notification email; success UX optional (config); always log |
| `soft_fail` | Generic validation/error; no “you are spam” copy |
| `quarantine` | Hold for admin review; no outbound mail until release |

Defaults for v1: **silent_discard** for high-confidence Layer 1; **quarantine** for medium AI confidence (tunable).

**Do not** hard-ban IP on first offense. Support cool-down / allowlist / rising friction later.

---

## 8. Admin information architecture

Top-level menu: **Form Guard** (`ld-fg`)

| Screen | Slug | Purpose |
|--------|------|---------|
| Dashboard | `ld-fg` | KPIs + charts + recent blocks |
| Integrations | `ld-fg-integrations` | Detected plugins + status |
| Forms | `ld-fg-forms` | Per-form enable toggles |
| Protection | `ld-fg-protection` | Layer 1/2 rules, action modes, fail-open |
| AI Providers | `ld-fg-providers` | Keys, models, test connection, priority |
| Logs | `ld-fg-logs` | Search, detail drawer, quarantine actions, CSV |
| Settings | `ld-fg-settings` | Retention, enable plugin, privacy notice |

Stitch designs these screens (see `stitch-ui-prompt.md`). Implementation uses wp-admin patterns + bundled assets (no CDN).

---

## 9. Data model (v1)

### Options (`ld_fg_*`)

- Global enable, retention days, default action, fail-open/closed
- Provider settings (encrypted keys, model, order)
- Heuristic thresholds
- Per-integration “protect all” flags
- Per-form enable map: `{ integration: { form_id: bool } }`

### Table `{$wpdb->prefix}ld_fg_logs`

| Column | Notes |
|--------|--------|
| id | PK |
| created_at | datetime |
| integration | varchar |
| form_id | varchar |
| form_title | varchar |
| verdict | spam/ham/quarantine/error |
| layer | heuristic/ai |
| confidence | decimal |
| reason | text |
| action | silent_discard/soft_fail/quarantine/allow |
| ip_hash | varchar (never store raw IP long-term) |
| email_hash | optional |
| snippet | truncated sanitized message |
| meta | JSON (provider used, cache hit, etc.) |
| status | logged / quarantined / released / discarded |

Indexes: `created_at`, `verdict`, `integration+form_id`, `status`.

### Table `{$wpdb->prefix}ld_fg_cache` (optional v1)

- `content_hash`, `verdict_json`, `expires_at`

### Stats

Aggregate from logs (or daily rollup option later): blocked count, allowed, quarantined, by form, heuristic vs AI, top reasons.

---

## 10. Folder structure (scaffold target)

```
lancedesk-form-guard/
  lancedesk-form-guard.php
  uninstall.php
  readme.txt
  LICENSE
  includes/
    class-ld-fg-plugin.php
    class-ld-fg-activator.php
    class-ld-fg-submission.php
    class-ld-fg-verdict.php
    class-ld-fg-heuristics.php
    class-ld-fg-ai-router.php
    class-ld-fg-crypto.php
    class-ld-fg-logger.php
    adapters/
      interface-ld-fg-form-adapter.php
      class-ld-fg-adapter-wpforms.php
      class-ld-fg-adapter-cf7.php
      class-ld-fg-adapter-gravity.php
      class-ld-fg-adapter-quform.php
  admin/
    class-ld-fg-admin.php
    css/ js/ (bundled; Chart.js local if used)
  public/
    (honeypot / timing token assets if needed)
  languages/
  docs/          ← planning only; excluded from release zip
  .distignore
  bin/build-release.sh
```

Follow `wordpress-development/plugin-main-file-template.php` and `wordpress-plugin-creation-checklist.md`.

---

## 11. Checklist alignment (non-negotiables)

- [ ] Header metadata complete; no `Network` unless multisite-only
- [ ] `Version` ↔ readme `Stable tag`; `Tested up to` synced
- [ ] Literal text domain `'lancedesk-form-guard'` in all i18n calls
- [ ] Translator comments above placeholder strings
- [ ] Nonces on admin forms + AJAX; sanitize/escape everywhere
- [ ] External services section for Groq / OpenAI / Anthropic
- [ ] No CDN fonts/scripts; bundle vendors
- [ ] `filemtime` for admin asset versions during development
- [ ] Release zip excludes `docs/`, tests, tooling (`.distignore`)
- [ ] Plugin Check + PHPCS clean before wp.org submit

---

## 12. Implementation order

1. ~~Name + docs (`plan2`, Stitch)~~ ← current
2. Stitch admin UI frames → pick layouts
3. Scaffold main file + activator + empty admin shell
4. Logger + stats queries
5. Heuristics engine + unit-ish fixtures from `prompt.txt` spam sample
6. AI router (BYO keys, encrypt, cache, fail-open)
7. Adapters one-by-one (WPForms first — matches prompt sample)
8. Forms / Integrations / Protection screens
9. Quarantine workflow + CSV + retention cron
10. readme.txt + Plugin Check pass + release zip

---

## 13. Open decisions (defaults if unset)

| Topic | Default |
|-------|---------|
| Display name | LanceDesk Form Guard |
| AI-down behavior | Fail-open + log |
| High-confidence Layer 1 | Silent discard |
| Medium AI confidence | Quarantine |
| Store message body | Truncated snippet only |
| IP storage | Hash only |
| Shared SaaS blocklist | Not in v1 |

---

## 14. Success criteria for v1

- Spam like the Solution Chapel dump is blocked **without** an LLM call (Layer 1)
- Ambiguous short messages still get a fast AI verdict when keys configured
- Site with no AI keys still benefits from Layer 1 + honeypot/timing
- Owner can enable protection per form in under 2 minutes after install
- Dashboard shows blocked count and reasons clearly
- Plugin Check ready for WordPress.org
