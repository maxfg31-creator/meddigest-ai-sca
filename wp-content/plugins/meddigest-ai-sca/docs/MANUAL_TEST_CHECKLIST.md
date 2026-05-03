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

## Milestone 3

- Configure exactly 12 Clinical Experience Group term IDs in Settings > MedDigest AI SCA.
- Confirm the Full Mock coverage panel shows missing groups until each group has at least one published, AI-enabled, mock-pool-enabled, approved case.
- Confirm `[meddigest_ai_full_mock_strip]` renders once on `/sca-cases/` below intro and above filters/case loading.
- Confirm the strip hydrates from `/wp-json/meddigest-ai/v1/me/state` and cached public HTML does not contain user-specific credit state.
- Confirm public/non-premium users see Join SCA Cases Premium.
- Confirm premium users with 0-11 available credits see Buy AI Credits and the 12-credit note.
- Confirm premium users with 12+ available credits see Start Full Mock SCA.
- Confirm an active mock shows Resume Full Mock SCA and blocks new station starts.
- Confirm `/sca-ai/mock/launch/` requires login, active SCA Cases Premium, 12 credits, coverage, microphone readiness, and checkboxes.
- Confirm missing group coverage blocks launch cleanly for users and shows admin coverage detail.
- Confirm launch allocates exactly 12 unique cases, one per configured group, and snapshots selected case content.
- Confirm launching creates a 12-credit hold and commits it when station 1 reading begins.
- Confirm `/sca-ai/mock/{mock_uuid}/run/` is owner-only and server-time phase driven.
- Confirm each station has 3 minutes reading, 12 minutes live consultation, and a 10-minute break after station 6.
- Confirm there is no pause, skip, end-early button, or feedback between stations.
- Confirm every live station opens a fresh Realtime client session and no one long session spans the full mock.
- Confirm no live transcript appears and transcript events are stored as text/JSON only.
- Confirm refresh/reconnect resumes from server time without restoring missed time.
- Confirm final results are generated asynchronously and `/results/` polls without triggering re-grading on old completed results.
- Confirm completed mock results remain owner-accessible after SCA Cases Premium lapses.
- Confirm all archive behavior passes with existing Code Snippets active.
