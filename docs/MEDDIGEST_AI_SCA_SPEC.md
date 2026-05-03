MedDigest AI SCA Technical Specification v8
Developer handover for the existing WordPress + MemberPress website
Use this version only.
Site	MedDigest.co.uk
Core stack	WordPress + MemberPress + custom MedDigest AI SCA plugin
AI products	Single AI station (1 credit) and Full Mock SCA (12 credits)
Main rule	Build into the existing /sca-cases/, /pricing/, case pages and existing account area. No redesigned archive page.

Do not redesign the SCA archive into a separate mock page. The Full Mock SCA entry point must be one inline strip on the existing /sca-cases/ page, below the intro paragraph and above the filters/case-loading area.
 
1. Scope and non-negotiable build rules
•	Build everything inside the existing MedDigest website and existing user accounts.
•	Keep the current memberships: Free, Guideline Summaries Premium, SCA Cases Premium.
•	Keep the current SCA case pages, pricing page and existing account area as the base UI.
•	Do not build a second website, second login system, second checkout system, iframe tool or floating site-wide chatbot.
•	Do not create a fake or redesigned 'SCA Cases archive - Full Mock SCA launch card' page. The archive stays the archive.
Area	Required approach	Not allowed
/sca-cases/	Keep the existing archive page. Add one inline Full Mock SCA strip only.	Separate redesigned archive page or fake case-card demo page
/pricing/	Keep existing membership cards. Add one AI credit section in one fixed location.	Rendering AI credits twice or exposing credit packs to non-premium users
Case pages	Keep existing tabbed case page. Add one AI CTA block below the title and above the tabs.	Turning AI into a fifth tab or editing 168 posts manually
Account area	Add AI Practice inside the existing account page/tab system and keep it visible when the user has AI history, even after premium lapses.	Standalone marketing page pretending to be an account page, or hiding completed AI history just because premium lapsed.
2. Existing pages to keep and new routes to add
Page / Route	Keep / New	Purpose	Template rule
/sca-cases/	Keep	Existing SCA cases archive page	Existing archive page/template stays; add one inline Full Mock strip only
/pricing/	Keep	Existing membership/pricing page	Existing page stays; AI credit packs rendered via one unique shortcode mount
/sca-cases/{case-slug}/	Keep	Existing case study page	Existing page stays; add one AI CTA block under title
Existing account page	Keep	Existing member account area and long-term AI history	Add AI Practice tab inside current account UI; visible when active premium OR AI history exists
/sca-ai/station/{case-slug}/setup/	New	Single-station pre-start page	Minimal AI template
/sca-ai/station/{attempt_uuid}/live/	New	Single-station live runner	Minimal AI template
/sca-ai/station/{attempt_uuid}/feedback/	New	Single-station feedback page	Minimal AI template; owner-only access; completed results remain viewable after premium lapse
/sca-ai/mock/launch/	New	Full mock pre-start page	Minimal AI template
/sca-ai/mock/{mock_uuid}/run/	New	Full mock runner	Minimal AI template
/sca-ai/mock/{mock_uuid}/results/	New	Full mock results page	Minimal AI template; owner-only access; completed results remain viewable after premium lapse
3. Exact UI placement and CTA states
3.1 Existing /sca-cases/ page
Add one compact full-width Full Mock SCA strip in the existing archive page template or Elementor layout. Place it directly below the intro paragraph and above the search / sorting / specialty filters and above the dynamic case-loading area. Keep the existing Case Count block, filter controls and case grid exactly as they are.
User state	Strip CTA	Link target	Notes
Public / not logged in / not premium	Join SCA Cases Premium	/pricing/#sca-cases-premium	Static default state allowed
Logged in + SCA Cases Premium + 0 to 11 credits	Buy AI Credits	/pricing/#ai-credits	Show note: 12 credits required to launch the mock
Logged in + SCA Cases Premium + 12 or more credits	Start Full Mock SCA	/sca-ai/mock/launch/	Show credits available
User already has an active mock	Resume Full Mock SCA	/sca-ai/mock/{mock_uuid}/run/	Resume has priority over Start
•	Do not use the old 'Buy 12 credits' wording because there is no 12-credit product. Use 'Buy AI Credits'.
•	Do not insert this strip inside the reactive case grid, Applied Filters area or loading container.
•	Do not inject it by fragile front-end DOM scraping after the case grid loads. Add it in the template/page layout itself.
•	User-specific CTA state and credit balance must be hydrated from a protected state endpoint so cached HTML cannot show stale credit/account state.
3.2 Existing /pricing/ page
Keep the current membership cards. Add one AI Consultation Credits section below the visible membership cards. This section is only visible to users who are logged in and have active SCA Cases Premium.
Section	Visibility	Implementation	Required anchors
SCA Cases Premium card	All visitors	Keep existing card; add stable anchor ID	#sca-cases-premium
AI Consultation Credits	Logged-in active SCA Cases Premium only	Render via one unique shortcode or template slot only, e.g. [meddigest_ai_credit_packs]	#ai-credits
Pack	Price	Credits	Display note
Essential Pack	£10	8	Good for trying single stations
Practice Pro	£25	25	Most popular
Exam Ready	£45	50	Best value
•	Add a note under the credit packs: '12 credits required to launch Full Mock SCA.'
•	Do not show AI credit packs to public users, Free users or Guideline Summaries Premium users.
•	Do not mount this section by searching the DOM for the pricing cards. Use one explicit shortcode or template placeholder so it renders once only.
3.3 Existing case study page
On AI-enabled cases, add one AI CTA block below the case title and above the existing tabs. Implement it at template level, not by editing every post manually.
User state	What to show	Primary action	Notes
Public / non-premium	Keep existing locked preview / upgrade flow	Upgrade to SCA Cases Premium	No AI credits shown
Premium + 1 or more credits	Start 12-Min AI Consultation	Use 1 credit	Show remaining credits and link to Buy more AI credits
Premium + 0 credits	AI consultation credits required	Buy AI Credits	Link to /pricing/#ai-credits
Active attempt for same case	Resume AI Consultation	Resume current attempt	Resume has priority over Start
•	Do not turn AI into a fifth tab.
•	Do not make any global change to Add Notes & Highlights on the normal case page.
•	If dedicated AI routes use a minimal template and the notes widget is absent there, that is acceptable. No separate site-wide hide logic is required.
3.4 Existing account area
•	Add a new AI Practice tab inside the existing account page.
•	Show current credit balance, Buy AI Credits link, single-station history, mock history, active attempt resume links and result links.
•	AI Practice must remain visible to the same logged-in account if that user has any previous AI history, even after SCA Cases Premium later lapses.
•	Ownership and access to past AI results must be tied to the same WordPress user_id. Do not create, require or merge a second “free account” for result access.
•	Paginate AI Practice history (default 20 items per page) and index queries by user_id/status/created_at so long-term history remains fast.
•	Do not create a separate standalone account-style page for this.
4. Membership, access and credit rules
•	All AI launches require active SCA Cases Premium.
•	Credits never grant case-content access by themselves.
•	Credits stay stored on the account if premium lapses, but they are locked until SCA Cases Premium becomes active again.
•	Completed single-station feedback pages and completed Full Mock result pages must remain accessible to the owning logged-in account even after SCA Cases Premium lapses.
•	Starting new AI stations, launching new mocks, spending credits and viewing premium case-study tabs still require active SCA Cases Premium.
•	Result access after lapse must use the same WordPress account; do not treat this as a separate free account or a second user profile.
•	A running station or running mock that started while premium was active may continue to completion even if membership status changes during the attempt.
•	All membership and credit checks must be enforced server-side, not only in the page UI.
Item	Type	Price	Access / usage rule
SCA Cases Premium	Recurring membership	£19.99/mo	Required to view premium SCA content and use any AI credit
Essential Pack	One-time credit product	£10	Adds 8 AI credits
Practice Pro	One-time credit product	£25	Adds 25 AI credits
Exam Ready	One-time credit product	£45	Adds 50 AI credits
Single AI station	Credit usage	1 credit	Debited only when live station begins
Full Mock SCA	Credit usage	12 credits	Debited once when station 1 reading time begins
4.1 Wallet and ledger rules
•	Use a user wallet balance table plus an immutable ledger table.
•	Issue credits only after successful completed MemberPress payment/transaction event or webhook. Do not rely on the thank-you page.
•	Every ledger mutation must use an idempotency key so retries, duplicate clicks or webhook replays cannot issue/debit twice.
•	Credits are non-transferable, have no cash value and do not expire.
4.2 Credit settlement
Flow	Hold	Commit debit	Release / refund rule
Single station	Create 1-credit hold when user confirms start	Commit when live station actually begins	Release if live never begins or attempt is cancelled before meaningful start
Full mock	Create 12-credit hold when user confirms mock launch	Commit once, when station 1 reading time begins	Release only if the mock fails before station 1 reading begins
4.3 Completed results, history and retention after premium lapse
•	Do not apply one blanket premium gate to all /sca-ai/* routes. Split route access by route purpose.
•	Setup, launch, live, run, realtime-token and new-start endpoints require active SCA Cases Premium, except a running session that began while premium was active may finish.
•	Completed feedback pages, completed mock result pages and AI Practice history require login + owner-only access, but no active premium.
•	If the user is not logged in, a direct result URL must redirect to login and then return to the same result URL after authentication.
•	Render past results from saved attempt/mock snapshots, not from the live case page, live tabs or current case wording.
•	Viewing a saved result must not trigger a new OpenAI call or re-grade the attempt. Load stored JSON/text only.
•	Store transcript text/JSON and structured feedback/results JSON only in v1. Do not store raw audio by default.
•	Show remaining credits in AI Practice after premium lapse as locked balance. They become usable again only if SCA Cases Premium is reactivated.
•	Keep completed AI results while the account exists and according to the site’s normal retention/privacy policy. If the account is deleted or erased, remove or anonymise the related AI records as part of that workflow.
•	Ownership must be based on the original WordPress user_id, not email matching. A second account with the same email later must not inherit or expose results from the first account.
5. Single AI station specification
5.1 Setup page
•	Show case title, consultation mode badge, doctor notes only, microphone/device check, educational/privacy checkboxes and Begin button.
•	Do not show patient notes, marking scheme, example consultation, transcript or tips.
•	Begin stays disabled until all checks pass: active premium, at least 1 available credit, case AI enabled, no active station/mock conflict, microphone permission granted and checkboxes ticked.
5.2 Live station
•	Server-authoritative 12:00 countdown timer.
•	One fresh OpenAI Realtime/WebRTC session for this station only.
•	No live transcript on screen.
•	User may end early via End Consultation with confirmation modal.
•	On refresh or reconnect, resume the same attempt using server time. Missed time is not restored.
•	No second active AI session may start while one station or mock is active for the user.
5.3 Feedback and grading
After the station ends, show a MedDigest examiner-style feedback report using RCGP-style domains and strict structured output.
Required output	Rule
Domain grades	DG&D, CM&C and RTO only; grades allowed: CP, P, F, CF
Global skills	Structure, progression, timing, clear language, responsiveness
Report sections	Practice verdict, strengths, critical misses, missing questions/explanations, safety-netting problems, transcript evidence, three priorities
Business rule caps	Critical misses can cap a domain at F or CF according to case configuration
•	Generate feedback asynchronously through a background job and show a 'Generating feedback...' page that polls status.
•	Retry transient feedback-generation failures automatically up to 3 times, then flag the job for admin rerun.
•	Default quick-cancel rule: fewer than 2 meaningful turns and under 30 seconds = cancelled attempt and credit released.
6. Full Mock SCA specification
6.1 Launch and entry points
•	Launch from the inline Full Mock strip on /sca-cases/ and from AI Practice inside the existing account area.
•	The launch page is a pre-start route, not a redesigned public archive page.
•	Show total duration clearly: 3 hours 10 minutes (12 stations with reading time plus fixed mid-exam break).
•	Require active SCA Cases Premium, at least 12 available credits, device/microphone check and start confirmation.
•	If the user already has an active mock, redirect to Resume instead of creating a second one.
6.2 Station schedule
Phase	Duration	Rules
Reading time before each station	3 minutes	No live AI session open
Live consultation for each station	12 minutes	Fresh Realtime session per station
Break after station 6	10 minutes	No pause/skip/stop; timer is server-authoritative
•	No pause button.
•	No skip station button.
•	No end-early button after station 1 reading begins.
•	No feedback shown between stations.
6.3 Station allocation for the mock
•	The mock must allocate exactly 12 unique stations: one from each of the 12 configured Clinical Experience Groups.
•	Use stored term IDs and a dedicated single field mock_primary_group_term_id for mock selection. Do not rely on multi-term taxonomy assignment alone.
•	Only eligible cases may be selected: published, AI enabled, mock_pool_enabled = yes, mock_ready_status = approved.
•	If any required group has zero eligible cases, block mock launch for users and show a coverage problem in admin.
•	Snapshot the selected case IDs and case grading configuration at launch. Do not re-randomise after launch.
6.4 Resume, timing and processing
•	The mock runner must be phase-driven by server time, not browser timers.
•	On refresh, reconnect or browser crash, resume the current phase using the saved schedule. Missed time is not restored.
•	Show a beforeunload warning during a running mock.
•	Grade each station asynchronously after it ends so final results are not blocked by 12 API calls at the very end.
•	If final results are still processing, show a results-processing screen that polls until aggregation is complete.
7. WordPress admin changes
Add the following to the existing SCA Cases editor. Use ACF or a custom meta box for data entry; the consultation flow itself remains custom code.
Admin area	Fields / controls	Notes
Basic AI settings	Enable AI; consultation mode; first speaker; AI version; reviewed by; reviewed date	Per case
Patient profile	Display name; age; gender text; occupation; tone; communication style; opening line; default voice	Per case
Candidate-visible overrides	Doctor brief override; pre-start instructions override	Optional
Hidden facts	Repeater with label, fact text, reveal condition, reveal example, domain relevance, critical flag, notes	Per case
ICE / psychosocial cues	Repeater with cue, when to reveal, importance	Per case
Red flags and safety	Repeater with item, must ask, fail if missing, linked domain	Per case
Marking items	Separate repeaters for DG&D, CM&C, RTO; each with item, weight, critical flag, fail if missing	Per case
Internal clinical notes	Expected working diagnosis, acceptable differentials, expected management, explanation, follow-up/safety-net, fail patterns	Per case
Mock pool fields	mock_pool_enabled; mock_ready_status; mock_primary_group_term_id	Required for mock selection
•	Add admin list columns for AI Enabled, Consultation Mode, Mock Pool Enabled, Mock Ready Status and AI Reviewed Date.
•	Add admin filters for AI Enabled, Video, Telephone, Mock Pool Enabled and Mock Ready Status.
•	Provide a migration helper to prefill AI fields from existing Doctor Notes, Patient Notes, Marking Scheme and Example Consultation content, stripping MemberPress shortcodes and hidden fragments.
•	Add a mock coverage dashboard showing whether each of the 12 configured groups currently has at least one approved mock-ready case.
8. Database schema summary
Table	Purpose	Key fields
wp_meddigest_ai_wallets	Current usable and locked credit balances per user	user_id UNIQUE, balance_available, balance_locked, updated_at
wp_meddigest_ai_ledger	Immutable credit issue/hold/commit/release log	ledger_uuid, user_id, delta, entry_type, source_type, source_uuid, idempotency_key UNIQUE, created_at
wp_meddigest_ai_case_config	Core AI config per case	case_post_id UNIQUE, enabled, mode, first_speaker, voice_id, ai_version, reviewed fields
wp_meddigest_ai_case_facts	Hidden facts and reveal rules	case_config_id, label, fact_text, reveal_condition, reveal_examples, domain, is_critical
wp_meddigest_ai_case_marking_items	Marking items and fail caps	case_config_id, domain, item_text, weight, is_critical, fail_if_missing
wp_meddigest_ai_attempts	Single-station attempts	attempt_uuid UNIQUE, user_id, case_post_id, status, membership_snapshot_json, hold_ledger_uuid, commit_ledger_uuid, transcript_json, snapshot_json, started_at, live_started_at, hard_stop_at, ended_at, created_at
wp_meddigest_ai_feedback	Single-station grading output and saved report used for long-term owner access	attempt_uuid UNIQUE, processing_status, practice_verdict, dgd_grade, cmc_grade, rto_grade, global_json, full_feedback_json, rendered_snapshot_json
wp_meddigest_ai_mock_runs	Full mock master record	mock_uuid UNIQUE, user_id, status, membership_snapshot_json, hold_ledger_uuid, commit_ledger_uuid, schedule_json, station_snapshot_json, started_at, current_phase, phase_ends_at, created_at
wp_meddigest_ai_mock_stations	Station records inside each mock	mock_uuid, station_number, attempt_uuid, case_post_id, mock_primary_group_term_id, reading_start_at, live_start_at, ended_at, grade_status
wp_meddigest_ai_consents	Attempt/mock consent log	user_id, object_type, object_uuid, consent_version, ip_address, user_agent, agreed_at
9. API endpoints and background processing
Endpoint	Method	Purpose	Key rules
/wp-json/meddigest-ai/v1/me/state	GET	Hydrate premium/credit/active-attempt state, history_exists flag and CTA state for archive strip, case CTA and account UI	Logged-in checks; safe summary only; no hidden prompts or rubrics
/wp-json/meddigest-ai/v1/consent	POST	Save AI attempt/mock consent	Nonce + ownership checks
/wp-json/meddigest-ai/v1/station/start	POST	Create single-station attempt and 1-credit hold	Server-side premium/credit/lock checks
/wp-json/meddigest-ai/v1/station/{attempt_uuid}/realtime-token	POST	Mint short-lived client token for live station	Attempt ownership required
/wp-json/meddigest-ai/v1/station/{attempt_uuid}/status	GET	Return timer/status/progress	Used for resume/polling
/wp-json/meddigest-ai/v1/station/{attempt_uuid}/end	POST	End station and queue feedback job	Idempotent end handling
/wp-json/meddigest-ai/v1/station/{attempt_uuid}/feedback	GET	Return station feedback when ready	Owner only; completed results accessible without active premium; no re-grading on view
/wp-json/meddigest-ai/v1/mock/start	POST	Create mock, allocate 12 stations, place 12-credit hold	Server-side premium/credit/coverage/lock checks
/wp-json/meddigest-ai/v1/mock/{mock_uuid}/status	GET	Return current phase, station number and phase end time	Owner only
/wp-json/meddigest-ai/v1/mock/{mock_uuid}/results	GET	Return final mock results or processing state	Owner only; completed results accessible without active premium; no re-processing on view
/wp-json/meddigest-ai/v1/history	GET	Return paginated AI Practice history, credit balance state and active resume links	Owner only; completed history available after premium lapse; no live premium content returned
•	All AI routes and endpoints require logged-in user, nonce validation, ownership validation and server-side access validation.
•	Do not expose hidden prompts, hidden facts, rubrics or raw grading instructions in any frontend response.
•	Use Action Scheduler or an equivalent reliable background job system for feedback generation and retry handling.
10. OpenAI implementation
Area	Required implementation	Notes
Live patient	OpenAI Realtime + WebRTC + Agents SDK for TypeScript	One fresh session per live station only
Client auth	Server-minted short-lived client credentials	Real API key never exposed to browser
Written feedback	Responses API + Structured Outputs	Use GPT-5.4 for strict structured grading
Voice model	Configurable environment/model setting	Default gpt-realtime-1.5; keep model IDs out of templates
Storage	Responses API store = false	Reduce retained response storage
•	Use two separate agents: Patient agent for the live role-play and Grader agent after the station ends.
•	The live patient agent must never see the marking scheme.
•	The grader must never give credit for actions that are not present in the transcript.
11. Anti-glitch and hardening requirements
•	Use minimal templates for all /sca-ai/* routes so login popups, heavy footer content and unrelated widgets do not interfere with live controls.
•	Use one explicit shortcode/template placeholder for the AI credit section on /pricing/ so it cannot render twice.
•	Use one explicit template/shortcode placeholder for the inline Full Mock strip on /sca-cases/. Do not place it in the dynamic case-grid container.
•	User-specific account/credit state must come from a protected state endpoint, not from publicly cached HTML.
•	Use UUIDs in public URLs for attempts, mocks and ledger source references.
•	Snapshot case text and grading configuration at station/mock start so later case edits do not change an in-progress attempt.
•	Use a single active-AI-session lock per user: one running station or one running mock, never both.
•	If a required shortcode placeholder is missing or duplicated, fail safely and log an admin notice rather than rendering duplicate UI.
•	MemberPress payment handling must be idempotent across retries and manual status changes.
•	Do not rely on one long WebRTC session for the full mock. Each live station opens and closes its own session.
•	Do not place one blanket MemberPress premium rule on all /sca-ai/* routes. Completed result/history routes must remain owner-accessible after premium lapse.
•	Result/history pages must send private/no-cache headers and use owner-only permission checks so cached responses cannot leak another user’s data.
•	Old result pages must render from stored snapshot JSON/text only and must not depend on current live case content, current slug, or a repeat OpenAI call.
•	AI Practice history must be paginated and backed by database indexes to avoid slow account pages as data grows.
•	Do not store raw audio in v1; text/JSON only, to control storage growth and backup size.
12. Acceptance criteria
The build is not complete until the following core checks pass on staging.
Area	Required pass condition
Archive page	Inline Full Mock strip appears once only, in the correct place, and remains visible while filters/cases load
Pricing page	AI credit packs appear once only and only for logged-in active SCA Cases Premium users
Case page	AI CTA appears in the correct place and changes state correctly for credits / resume
Payments and credits	No duplicate credit issue or debit under retries, duplicate clicks or webhook repeats
Single station	Setup, live, resume, timer, end, feedback and credit settlement all work
Full mock	12-credit launch, allocation, resume, 12 stations, break, processing and final results all work
Security	No hidden prompts or rubrics exposed; no real API key in browser; AI routes protected
Site integrity	Existing archive layout, pricing cards, case tabs and normal site pages continue to work as before
•	Test Free user, Guideline Summaries Premium user, SCA Cases Premium user with 0 credits, 1 credit and 12+ credits.
•	Test refresh/reconnect during setup, reading time, live station, break and results processing.
•	Test duplicate click / multi-tab launch attempts.
•	Test editing a case after a station/mock has already started and confirm the running attempt uses the saved snapshot.
•	Test missing mock-group coverage and verify user launch is blocked cleanly.
•	Test the same account after SCA Cases Premium lapses: AI Practice still appears if AI history exists; completed station feedback and completed mock results still open; new AI launches remain blocked; premium case tabs remain blocked; credits show as locked.
•	Test direct result URLs when logged out: redirect to login, then return to the same result page for the owner only.
•	Test a separate second account and confirm it cannot access another account’s stored results even if the email later matches.
•	Test that opening old result pages does not create any new OpenAI usage and loads from stored snapshot data only.
•	Test AI Practice pagination/performance with a larger result history dataset.
13. Do not build
•	No separate redesigned SCA archive page.
•	No separate mock marketing page pretending to replace /sca-cases/.
•	No AI credit packs visible to public users or non-premium members.
•	No 'Buy 12 credits' CTA text.
•	No second site, second login or third-party iframe tool.
•	No fake 'official RCGP score' claim.
•	No one long Realtime session spanning the entire 3h10m mock.
•	No direct front-end exposure of OpenAI API keys, hidden prompts or grading rubrics.
•	No requirement for a second free account to access past AI results.
•	No blanket premium-only lock on completed result pages or AI history belonging to the owner.
•	No raw audio retention by default in v1.
14. Developer implementation order
Phase	Deliverables	Dependency
Phase 1	Wallet/ledger, pricing credit products, state endpoint, pricing AI credits section	MemberPress products and hooks
Phase 2	Case-page AI CTA, single-station setup/live/feedback flow, admin AI settings	Phase 1 complete
Phase 3	Archive inline Full Mock strip, mock allocation engine, mock launch/run/results	Phase 2 complete
Phase 4	Coverage dashboard, My Account AI Practice tab, QA hardening and staging sign-off	Phases 1-3 complete

End of v8 specification
 
Appendix - UI Mockups
Use these mockups as visual references for implementation. They are included for layout guidance only and should be built inside the existing MedDigest templates and page structure.
Mockup	Use
Public pricing page	Show SCA Cases Premium first; AI credits remain hidden until premium is active.
Premium pricing page	Show AI credit packs and note that 12 credits are needed for Full Mock SCA.
Premium case page - credits available	Show Start 12-Min AI Consultation CTA with credit balance.
Premium case page - no credits	Show Buy AI Credits CTA instead of start.
Full Mock launch page	Pre-start screen before the 12-station mock begins.
/sca-cases/ page - public state	Inline Full Mock strip on the real archive page for non-premium users.
/sca-cases/ page - premium state	Inline Full Mock strip on the real archive page for premium users with enough credits.
 
Public Pricing Page - Premium First / Mock Locked
Display on the existing Pricing page for public users and logged-out users. AI credits do not appear yet.
 
Figure: Public Pricing Page - Premium First / Mock Locked
 
Premium Pricing Page - AI Credits + Mock Note
Display on the existing Pricing page only after the user has active SCA Cases Premium.
 
Figure: Premium Pricing Page - AI Credits + Mock Note
 
Premium Case Page - Start AI Consultation (Credits Available)
Place this AI CTA block below the case title and above the existing tabs on AI-enabled cases.
 
Figure: Premium Case Page - Start AI Consultation (Credits Available)
 
Premium Case Page - No Credits State
Replace the start CTA with a Buy AI Credits state when the premium user has zero credits.
 
Figure: Premium Case Page - No Credits State
 
Full Mock SCA Launch Page
Use this as the pre-start screen before the user enters the locked full mock flow.
 
Figure: Full Mock SCA Launch Page
 
Real /sca-cases/ Page - Public / Non-Premium State
Inline Full Mock strip added to the real /sca-cases/ page below the intro text and above filters.
 
Figure: Real /sca-cases/ Page - Public / Non-Premium State
 
Real /sca-cases/ Page - Premium Ready State
Inline Full Mock strip added to the real /sca-cases/ page with premium-ready CTA state.
 
Figure: Real /sca-cases/ Page - Premium Ready State
