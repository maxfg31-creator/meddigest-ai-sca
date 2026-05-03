=== MedDigest AI SCA ===
Contributors: meddigest
Tags: memberpress, sca, ai, credits
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0

Custom MedDigest AI SCA plugin for integrating AI credit packs, wallet state, and practice entry points into the existing WordPress + MemberPress site.

== Description ==

This plugin is intentionally scoped to the existing MedDigest WordPress site. It does not create a second login, second checkout, iframe tool, redesigned archive, or separate mock platform.

Milestone 1 includes:

* Plugin scaffold and dependency checks.
* Wallet and immutable ledger tables.
* MemberPress product mapping settings.
* Idempotent credit issue handling for completed MemberPress transactions.
* Protected `/wp-json/meddigest-ai/v1/me/state` endpoint.
* `[meddigest_ai_credit_packs]` shortcode for the existing `/pricing/` page.

== Installation ==

Place this folder at `wp-content/plugins/meddigest-ai-sca/` and activate it from WordPress admin.

== Configuration ==

After activation, go to Settings > MedDigest AI SCA and map:

* SCA Cases Premium membership product.
* Essential Pack MemberPress product.
* Practice Pro MemberPress product.
* Exam Ready MemberPress product.

== Security ==

OpenAI keys, hidden prompts, patient notes, rubrics, and marking schemes must never be exposed to the browser. Milestone 1 does not make OpenAI calls.

