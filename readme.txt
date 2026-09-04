=== LanceDesk Form Guard ===
Contributors: lancedesk
Tags: spam, forms, security, ai, antispam
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.1.11
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block contact form and comment spam with local heuristics and optional AI before emails send or comments publish.

== Description ==

LanceDesk Form Guard protects contact forms and WordPress post comments from spam using a two-layer defense system:

* **Layer 1 — Heuristics:** Fast deterministic checks for BBCode injection, link stuffing, suspicious image/shortener hosts, commercial/SEO cold outreach (including polite sales pitches), honeypot fields, and too-fast submissions.
* **Layer 2 — AI Analysis:** Optional semantic spam classification via Groq, OpenAI, or Anthropic (off by default; requires privacy acknowledgment and your API keys).

**Supported integrations**

* WPForms, Contact Form 7, Gravity Forms, Quform, and Ninja Forms (per-form toggles)
* **WordPress Core** — native post comment forms (site-wide toggle under Settings)

Blocked submissions are logged with hashed IP and email values. Legitimate submissions flow normally.

== Installation ==

1. Upload the `lancedesk-form-guard` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Open **Form Guard** in the admin menu and complete the setup checklist
4. Enable protection on individual forms (or turn on **Protect WordPress comment forms** in Settings)
5. Optionally configure an AI provider under **Protection** and **AI Providers**

== Frequently Asked Questions ==

= Which form plugins are supported? =

WPForms, Contact Form 7, Gravity Forms, Quform, and Ninja Forms when installed and active. Native WordPress post comment forms are supported without any third-party plugin — enable **Protect WordPress comment forms** under **Form Guard → Settings**.

= Are WordPress comments protected? =

Yes. When the master switch is on and **Protect WordPress comment forms** is enabled, comments run through the same Layer 1 heuristics (and optional Layer 2 AI) as contact forms. **Blocked comments are not saved to WordPress** — they exist only in Form Guard logs. The visitor is redirected back to the post (silent discard) or shown a generic error (soft fail), depending on your Protection default action.

= What happens if the AI provider is unavailable? =

You can configure fail-open (allow submission when heuristics pass) or fail-closed behavior in Protection Rules.

= Is submission data sent to third parties? =

When Layer 2 AI analysis is enabled **and** you have acknowledged the privacy notice **and** configured a provider with an API key, truncated form field text may be sent to that provider for spam classification. Heuristics-only protection stays on your site. See **External services** below.

= Does this plugin use the WordPress AI Client? =

Not in this release. Form Guard uses site-owner API keys with a multi-provider fallback (Groq, OpenAI, Anthropic) so spam classification works on WordPress 6.4+ without requiring core AI Client setup. We may evaluate WordPress AI Client integration in a future release.

== External services ==

This plugin optionally connects to third-party AI APIs to classify form submissions for spam. No submission text is sent until AI analysis is enabled, the privacy acknowledgment is checked in Settings, and at least one provider is enabled with an API key.

**Groq** — https://groq.com/
* Used for: Optional Layer 2 spam classification of form submissions.
* Data sent: Truncated form submission text (not stored passwords or payment data collected by this plugin).
* When: AI analysis is enabled, privacy is acknowledged, heuristics pass or the submission is ambiguous, and Groq is selected/enabled with a valid API key.
* Terms of Service: https://groq.com/terms-of-use/
* Privacy Policy: https://groq.com/privacy-policy/

**OpenAI** — https://openai.com/
* Used for: Optional Layer 2 spam classification of form submissions.
* Data sent: Truncated form submission text.
* When: AI analysis is enabled, privacy is acknowledged, and OpenAI is reached in the provider fallback order with a valid API key.
* Terms of Service: https://openai.com/policies/terms-of-use/
* Privacy Policy: https://openai.com/policies/privacy-policy/

**Anthropic** — https://www.anthropic.com/
* Used for: Optional Layer 2 spam classification of form submissions.
* Data sent: Truncated form submission text.
* When: AI analysis is enabled, privacy is acknowledged, and Anthropic is reached in the provider fallback order with a valid API key.
* Terms of Service: https://www.anthropic.com/legal/consumer-terms
* Privacy Policy: https://www.anthropic.com/legal/privacy

== Screenshots ==

1. First-run setup checklist to get Form Guard protecting forms quickly.
2. Integrations screen showing WordPress Core and supported form plugins.
3. Forms list with per-form protection toggles.
4. Protection rules: default action and Layer 1 heuristic controls.
5. AI providers with encrypted API keys and connection testing.
6. Security logs with verdict, layer, confidence, and reason.
7. Log detail drawer with integration, layer, provider, confidence, reason, and captured fields.

== Changelog ==

= 0.1.11 =
* External adapters via `ld_fg_adapters` filter for custom form integrations.
* `ld_fg_trap_fields()` helper for rendering honeypot/timing traps on custom widgets.
* Front-end JS discovers forms marked with `data-ld-fg-integration` / `data-ld-fg-form`.

= 0.1.10 =
* Layer 1 commercial outreach expanded into full vendor/agency solicitation detection: contact-card CTAs, sales mailboxes + URLs, ecommerce/dev service pitches, and brand/domain glue — blocks submissions even when Quform honeypot is empty.

= 0.1.9 =
* Quform: treat Quform’s built-in honeypot as spam when filled (block + log before Quform validation).
* Layer 1: Random / Gibberish Content rule for bot keyboard fills and dotted obfuscated emails.
* Quform logs: strip CSRF/uid/ajax/loaded meta keys from Captured Fields.

= 0.1.8 =
* Settings and Protection: Save buttons stay disabled until a setting actually changes.

= 0.1.7 =
* Quform: show the real form name in Security Logs (e.g. “Contact Us”) instead of the numeric id.
* Quform: merge form values with flattened POST so message bodies are always scored (fixes soft SEO pitches being Allowed while still saving Quform entries).
* Quform: block responses short-circuit correctly so silent discard does not create entries.

= 0.1.6 =
* Safer default: plugin data (settings, keys, logs) is kept on uninstall unless “Delete all plugin data on uninstall” is turned on.

= 0.1.5 =
* Admin UX: hide Logs search and Forms filter/search when there are fewer than 20 items.
* Integrations intro text uses full content width.
* Providers: Save Key stays disabled until an API key is entered.

= 0.1.4 =
* Layer 1 catches soft content-collaboration and link-opportunity outreach (e.g. “came across your website”, “link opportunities”, “both audiences”).
* Layer 1 corpus hardening: soft SEO proposals, backlink swaps, Xrumer/GSA hosts, token scams, Russian tehosmotr/darknet/gambling SEO spam, product dump pitches.
* Settings toggles for delete-on-uninstall and AI privacy acknowledgment match the master switch style.

= 0.1.3 =
* **WordPress Core comment protection** — honeypot/timestamp on comment forms; toggle under Settings → WordPress Core; Integrations card.
* **Blocked comments never reach wp_comments** — discarded before save (fixes auto-approve for logged-in moderators); log-only record in Form Guard.
* **Layer 1 tightening** — commercial outreach threshold lowered; catches polite SEO/sales pitches (e.g. “quick call”, “grow your business online”) without AI.
* **Security logs UX** — per-user read/unread state, unread badge on Logs nav, improved table layout and layer labels.
* **Plugin Check** — i18n translators, Tested up to 7.1, PHPCS suppressions for false positives.

= 0.1.2 =
* Stronger Layer 1 heuristics when AI is off: max 2 links, suspicious image/shortener hosts, commercial/SEO outreach patterns, and handle dumps.

= 0.1.1 =
* Log detail drawer shows integration, layer, provider, action, confidence, reason, and captured fields.
* AI outbound calls require privacy acknowledgment; AI analysis defaults off.
* Improved external services documentation in the readme.

= 0.1.0 =
* Initial release.
