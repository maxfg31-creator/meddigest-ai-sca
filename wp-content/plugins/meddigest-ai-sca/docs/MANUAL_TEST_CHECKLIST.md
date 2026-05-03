# MedDigest AI SCA Manual Test Checklist

## Milestone 1

- Activate the plugin with MemberPress, ACF, Elementor, and Code Snippets active.
- Confirm no fatal errors on activation.
- Confirm wallet and ledger tables are created.
- Confirm Settings > MedDigest AI SCA loads.
- Export and review active Code Snippets before changing mappings.
- Map SCA Cases Premium and the three credit pack products.
- Confirm `[meddigest_ai_credit_packs]` renders once on `/pricing/`.
- Confirm credit packs are hidden from logged-out users, Free users, and Guideline Summaries Premium users.
- Confirm credit packs are visible to active SCA Cases Premium users only.
- Complete each mapped MemberPress credit pack purchase and confirm the expected credits are issued.
- Re-run or replay the same completed transaction hook and confirm credits are not duplicated.
- Confirm `/wp-json/meddigest-ai/v1/me/state` requires login.
- Confirm logged-in state returns credit totals but no hidden prompts, rubrics, or patient data.

## Milestone 2

- Confirm ACF case fields appear only on configured/existing SCA case post types.
- Confirm AI Enabled, Consultation Mode, Mock Pool Enabled, Mock Ready Status, and AI Reviewed Date columns appear on case admin lists.
- Confirm case admin filters work with all existing Code Snippets active.
- Confirm the migration helper can prefill empty AI fields without approving cases automatically.
- Confirm `[meddigest_ai_case_cta]` renders only for AI-enabled cases.
- Confirm the case CTA appears below the title and above existing tabs/content when auto-prepend is enabled for the case post type.
- Confirm public/non-premium users see the upgrade CTA and no credit balance details.
- Confirm premium users with zero credits see Buy AI Credits.
- Confirm premium users with credits see Start 12-Min AI Consultation.
- Confirm `/sca-ai/station/{case-slug}/setup/` shows doctor brief only.
- Confirm setup never displays patient notes, hidden facts, marking items, rubrics, or grader prompts.
- Confirm setup requires login, active SCA Cases Premium, AI-enabled case, 1 available credit, and checkboxes.
- Confirm starting setup creates a 1-credit hold and an immutable ledger entry.
- Confirm `/sca-ai/station/{attempt_uuid}/live/` requires owner access.
- Confirm Realtime client-secret endpoint fails closed when no server OpenAI key is configured.
- Confirm live station does not show a transcript.
- Confirm refreshing after the 12-minute hard stop cannot mint a new Realtime token for the ended station.
- Confirm Realtime transcript events are saved as transcript JSON only, with no raw audio retention.
- Confirm ending before live starts releases the hold.
- Confirm quick cancel under 30 seconds refunds the credit.
- Confirm normal end queues structured feedback and redirects to owner-only feedback.
- Confirm the feedback page polls until feedback reaches a terminal status.
- Confirm transient feedback errors retry automatically up to 3 times.
- Confirm completed/cancelled feedback routes are owner-only and do not require active premium.
- Confirm duplicate start clicks and multi-tab attempts do not create concurrent active station sessions.
