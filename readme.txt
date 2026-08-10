=== LanceDesk Form Guard ===
Contributors: lancedesk
Tags: spam, forms, security, ai, antispam
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protects contact form submissions with local heuristics and optional AI classification before notification emails are sent.

== Description ==

LanceDesk Form Guard protects contact and registration forms from spam using a two-layer defense system:

* **Layer 1 — Heuristics:** Fast deterministic checks for BBCode injection, link stuffing, honeypot fields, and too-fast submissions.
* **Layer 2 — AI Analysis:** Optional semantic spam classification via Groq, OpenAI, or Anthropic.

Supports WPForms, Contact Form 7, Gravity Forms, Quform, and Ninja Forms. Logs blocked and quarantined submissions with hashed IP and email values for privacy.

== Installation ==

1. Upload the `lancedesk-form-guard` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Open **Form Guard** in the admin menu and complete the setup checklist
4. Enable protection on individual forms and configure an AI provider

== Frequently Asked Questions ==

= Which form plugins are supported? =

WPForms, Contact Form 7, Gravity Forms, Quform, and Ninja Forms are supported when installed and active.

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

== Changelog ==

= 0.1.1 =
* Log detail drawer shows integration, layer, provider, action, confidence, reason, and captured fields.
* AI outbound calls require privacy acknowledgment; AI analysis defaults off.
* Improved external services documentation in the readme.

= 0.1.0 =
* Initial release.
