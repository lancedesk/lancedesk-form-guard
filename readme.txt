=== LanceDesk Form Guard ===
Contributors: lancedesk
Tags: spam, forms, security, ai, antispam
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protects contact form submissions with local heuristics and optional AI classification before notification emails are sent.

== Description ==

LanceDesk Form Guard protects contact and registration forms from spam using a two-layer defense system:

* **Layer 1 — Heuristics:** Fast deterministic checks for BBCode injection, link stuffing, honeypot fields, and too-fast submissions.
* **Layer 2 — AI Analysis:** Optional semantic spam classification via Groq, OpenAI, or Anthropic.

Supports WPForms, Contact Form 7, Gravity Forms, and Quform. Logs blocked and quarantined submissions with hashed IP and email values for privacy.

== Installation ==

1. Upload the `lancedesk-form-guard` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Open **Form Guard** in the admin menu and complete the setup checklist
4. Enable protection on individual forms and configure an AI provider

== Frequently Asked Questions ==

= Which form plugins are supported? =

WPForms, Contact Form 7, Gravity Forms, and Quform are supported when installed and active.

= What happens if the AI provider is unavailable? =

You can configure fail-open (allow submission when heuristics pass) or fail-closed behavior in Protection Rules.

= Is submission data sent to third parties? =

When AI analysis is enabled, form field text may be sent to your configured AI provider for spam classification. See **External services** below.

== External services ==

This plugin optionally connects to third-party AI APIs to classify form submissions for spam.

**Groq** — https://groq.com/
* Data sent: Truncated form submission text for spam classification.
* When: Layer 2 AI analysis is enabled and heuristics pass or submission is ambiguous.
* Terms: https://groq.com/terms/
* Privacy: https://groq.com/privacy-policy/

**OpenAI** — https://openai.com/
* Data sent: Truncated form submission text for spam classification.
* When: Layer 2 AI analysis is enabled and Groq is unavailable or lower priority.
* Terms: https://openai.com/policies/terms-of-use
* Privacy: https://openai.com/policies/privacy-policy

**Anthropic** — https://www.anthropic.com/
* Data sent: Truncated form submission text for spam classification.
* When: Layer 2 AI analysis is enabled and higher-priority providers are unavailable.
* Terms: https://www.anthropic.com/legal/consumer-terms
* Privacy: https://www.anthropic.com/legal/privacy

== Changelog ==

= 0.1.0 =
* Initial release.
