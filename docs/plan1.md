Yeah, this is a solid idea — and it's not hypothetical, it's actually a known gap. CAPTCHA (even Google's v3 risk-score version) only stops bot-scripted spam; it does nothing against a human (or semi-automated) spammer manually filling a form to inject SEO backlink spam like the one you pasted. That message is a textbook case: garbled BBCode link-stuffing, mismatched Reply-To vs sender, foreign-language keyword stuffing. An LLM would flag that instantly, a CAPTCHA never will.

**Is it doable?** Yes, and it's cheaper/easier than it sounds because WordPress form plugins already expose the hooks you need.

## Where to intercept

Every major form plugin has a pre-send hook you can tap into *before* the notification email fires:

- **WPForms**: `wpforms_process_before_email` or `wpforms_process_complete` (you can even short-circuit earlier at `wpforms_process` filters)
- **Contact Form 7**: `wpcf7_before_send_mail`
- **Gravity Forms**: `gform_pre_submission` / `gform_validation`

You hook in, pull the submitted field values, run your spam check, and either let it through, silently discard, or reject with a validation error (which also gives the "spammer" no signal that they were blocked — better than a hard reject that teaches them to adapt).

## Architecture: don't send everything to the LLM

This is the part worth getting right, because it affects both cost and speed:

**Layer 1 — free, instant, local heuristics** (catches ~70-80% of spam like your example without any API call):
- Reply-To domain/email mismatched with the sender's stated name/domain
- BBCode `[url=]` patterns, raw link counts above a threshold, non-Latin script density in a form expecting local-language input
- Honeypot field (invisible field bots/scripts fill in) + submission-speed check (sub-2-second fills are almost always automated)
- IP/ASN reputation lookup against a free blocklist (Spamhaus, AbuseIPDB) — a cached local list, not a live paid API

**Layer 2 — LLM classification, only for ambiguous cases** that clear layer 1:
- Send just the message body + name field (not full headers) to Groq with a small, fast model, prompt it to return strict JSON: `{"spam": true/false, "confidence": 0-1, "reason": "..."}`
- Cache the verdict by content hash so repeat-spam (same template, different domain) doesn't cost a second API call
- Fall back to OpenAI/Anthropic only if Groq errors — same fallback pattern you used on the NGO chatbot plugin

This two-layer setup is what makes it commercially viable at scale — you're not burning API calls on every legit "hi, I'd like a quote" submission.

## Plugin-only vs. external service

|  | Self-contained plugin | Plugin + external API |
|---|---|---|
| Distribution | Simple, one install, works offline-ish | Needs your service to stay up |
| IP ban list | Per-site only, no shared intelligence | Can share a blocklist across every site using it (huge value — one spammer caught on site A protects site B) |
| Cost control | Each site owner needs their own Groq key | You can pool usage, rate-limit, and monetize as SaaS |
| Maintainability | Easier for a solo dev to ship v1 | More moving parts, but scales better |

Given you're already comfortable running a Node backend separately from a frontend (from your Next.js architecture question), I'd lean toward: **ship the plugin with local heuristics fully self-contained (works standalone, no dependency)**, but make the LLM + shared-blocklist layer optional and routed through a small hosted API — same pattern as your NGO chatbot's rules-engine + AI fallback. That gets you a free/open-source-friendly plugin for adoption, with a premium tier (shared threat intelligence, better models, dashboard) as the business case — which fits the kind of idea you've leaned toward before: genuinely useful, not just a wrapper.

One caution on the IP-ban part: don't hard-ban on first offense. Shared IPs (mobile carriers, NAT, VPNs, offices) mean one spammer can get a legitimate user blocked. Better to do a "quarantine" (email held for manual review) or a rising friction model (extra verification step) before an outright ban.

Want me to sketch the actual plugin folder structure and the WPForms hook code to get you started?