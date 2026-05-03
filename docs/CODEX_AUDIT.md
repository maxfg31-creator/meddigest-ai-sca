# MedDigest AI SCA Repository Audit

Date: 2026-05-03

Sources read from the public repository:

- `AGENTS.md` at commit `2df2e83`
- `docs/MEDDIGEST_AI_SCA_SPEC.md` at commit `2df2e83`

## Repository State

The public repository currently contains planning/specification files only:

```text
AGENTS.md
docs/
  MEDDIGEST_AI_SCA_SPEC.md
```

There is no WordPress plugin scaffold, theme code, test suite, Composer setup, WordPress bootstrap, Elementor template export, MemberPress product configuration, or existing MedDigest PHP code in this repository yet.

That means implementation should start by creating the required custom plugin at:

```text
wp-content/plugins/meddigest-ai-sca/
```

The audit below is based on the exact repository rules and v8 technical specification, plus the supplied environment context:

- Installed plugins: Elementor, MemberPress, Advanced Custom Fields (ACF), Code Snippets
- Active theme: Genesis Block Theme (no custom child theme)
- Critical warning: because the developer made custom modifications and there is no custom code in the file system, assume all existing custom site logic currently lives in the database through the Code Snippets plugin.

## Non-Negotiable Direction

The AI SCA build must be a maintainable WordPress plugin integrated into the existing MedDigest site. It must not become a second website, iframe product, separate login, separate checkout, redesigned mock platform, floating chatbot, or a set of scattered snippets.

The existing site surfaces stay in place:

- Keep `/sca-cases/`.
- Keep `/pricing/`.
- Keep existing SCA case pages and their tabs.
- Keep the existing MemberPress account area.

AI functionality must be inserted into these existing surfaces only:

- `/sca-cases/`: one inline Full Mock SCA strip below the intro paragraph and above filters/case loading.
- `/pricing/`: one AI credit packs section below existing membership cards.
- Individual case pages: one AI CTA block below the title and above existing tabs.
- MemberPress account: one AI Practice tab inside the existing account area.

## Critical Database-Level Code Snippets Warning

The live site has no custom child theme and no custom filesystem code, but it does have Code Snippets installed and known developer modifications. Therefore, existing custom behavior should be treated as database-resident production code until proven otherwise.

Implementation must not assume that the visible file system represents the whole application. Existing `/sca-cases/`, `/pricing/`, SCA case tabs, filters, shortcodes, MemberPress customizations, access rules, redirects, and frontend behavior may be driven by active snippets stored in database tables.

Safe integration rules:

- Inventory all active and inactive Code Snippets before implementing plugin behavior.
- Export or otherwise back up the snippets before testing changes.
- Do not disable snippets wholesale to make the new plugin work.
- Identify snippet-owned hooks, shortcodes, REST endpoints, AJAX actions, classes, global functions, CSS, JavaScript, and query filters.
- Treat snippet behavior as existing production functionality that the new plugin must coexist with.
- Add plugin features through explicit, namespaced hooks and shortcodes.
- Avoid generic shortcode names, function names, REST namespaces, CSS classes, JavaScript globals, option names, table names, and query vars.
- Prefer a `meddigest_ai_` or `mdsca_` prefix consistently.
- Add duplicate shortcode/mount detection and admin notices where the spec requires exactly one rendered section.
- Test with all existing snippets active on staging before considering implementation complete.
- If a snippet must be replaced by plugin code, migrate it deliberately in a separate, reversible step with before/after screenshots and functional checks.

## Rendering Decisions

### `/sca-cases/` With The Full Mock Strip

`/sca-cases/` must remain the existing SCA cases archive page. We should not replace it with a fake mock page, redesigned archive, separate landing page, or case-card demo.

Rendering approach:

- Add one compact full-width Full Mock SCA strip to the existing archive template or Elementor layout.
- Position it directly below the intro paragraph.
- Position it above the search, sorting, specialty filters, Applied Filters area, and dynamic case-loading/grid area.
- Keep the existing Case Count block, filter controls, and case grid as they are.
- Use one explicit shortcode or template placeholder so the strip renders once only.
- Do not inject it by scraping the frontend DOM after the case grid loads.
- Do not place it inside the reactive case grid, loading container, or filter UI.

Recommended mount:

```text
[meddigest_ai_full_mock_strip]
```

CTA states required by the spec:

| User state | Strip CTA | Link target | Rule |
| --- | --- | --- | --- |
| Public, logged out, or not premium | Join SCA Cases Premium | `/pricing/#sca-cases-premium` | Static default state allowed |
| Logged in + active SCA Cases Premium + 0-11 credits | Buy AI Credits | `/pricing/#ai-credits` | Show that 12 credits are required |
| Logged in + active SCA Cases Premium + 12+ credits | Start Full Mock SCA | `/sca-ai/mock/launch/` | Show credits available |
| User has active mock | Resume Full Mock SCA | `/sca-ai/mock/{mock_uuid}/run/` | Resume has priority |

Important wording rule:

- Do not use "Buy 12 credits" because there is no 12-credit product.
- Use "Buy AI Credits".

Caching/state rule:

- The strip's user-specific state must be hydrated from `/wp-json/meddigest-ai/v1/me/state`.
- Cached public HTML must not contain user-specific credit/account state.

### Individual SCA Case Pages With The AI CTA

Existing case study pages and tabs must remain. AI must not become a fifth tab, and we should not edit 168 posts manually.

Rendering approach:

- Add one AI CTA block at template level.
- Position it below the case title and above the existing tabs.
- Render only on AI-enabled cases.
- Keep the normal tabs, Add Notes & Highlights behavior, locked preview flow, and case content behavior unchanged.
- Dedicated `/sca-ai/*` routes may use minimal templates where unrelated case widgets are absent.

Recommended mount:

```text
[meddigest_ai_case_cta]
```

CTA states required by the spec:

| User state | What to show | Primary action | Rule |
| --- | --- | --- | --- |
| Public or non-premium | Existing locked preview/upgrade flow | Upgrade to SCA Cases Premium | Do not show AI credits |
| Premium + 1+ credits | Start 12-Min AI Consultation | Spend 1 credit | Show remaining credits and Buy more link |
| Premium + 0 credits | AI consultation credits required | Buy AI Credits | Link to `/pricing/#ai-credits` |
| Active attempt for same case | Resume AI Consultation | Resume current attempt | Resume has priority |

New single-station routes:

```text
/sca-ai/station/{case-slug}/setup/
/sca-ai/station/{attempt_uuid}/live/
/sca-ai/station/{attempt_uuid}/feedback/
```

Access rules:

- Setup and new launches require active SCA Cases Premium.
- Live can continue if the attempt began while premium was active.
- Completed feedback pages require login + ownership, but not active premium.
- Feedback pages must render from saved snapshots and must not re-grade or call OpenAI on view.

### `/pricing/` With Credit Packs

`/pricing/` must remain the existing pricing page with the current membership cards. The AI Consultation Credits section must appear once, below the visible membership cards.

Rendering approach:

- Add one explicit shortcode or template placeholder below existing membership cards.
- Do not mount by searching the DOM for pricing cards.
- Do not render credit packs twice.
- Add stable anchor `#sca-cases-premium` to the existing SCA Cases Premium card.
- Add stable anchor `#ai-credits` to the AI credits section.

Recommended mount:

```text
[meddigest_ai_credit_packs]
```

Visibility:

- Public visitors: hidden.
- Logged-out users: hidden.
- Free users: hidden.
- Guideline Summaries Premium users: hidden.
- Logged-in active SCA Cases Premium users: visible.

Required credit packs:

| Pack | Price | Credits | Display note |
| --- | ---: | ---: | --- |
| Essential Pack | £10 | 8 | Good for trying single stations |
| Practice Pro | £25 | 25 | Most popular |
| Exam Ready | £45 | 50 | Best value |

Required note:

```text
12 credits required to launch Full Mock SCA.
```

Payment rule:

- MemberPress products/transactions must issue credits only after completed payment or webhook.
- The thank-you page must not be used as the source of truth.
- Duplicate webhook retries, duplicate clicks, refreshes, and manual status changes must not duplicate credits.

### MemberPress Account Area With AI Practice

The account area must remain the existing MemberPress account UI. AI Practice must be added inside that UI, not as a standalone account-style page.

Rendering approach:

- Add one `AI Practice` tab using MemberPress account hooks.
- Render current credit balance, locked balance state, Buy AI Credits link, active resume links, single-station history, mock history, and result links.
- Paginate history with 20 items per page by default.
- Keep the tab visible when the logged-in user has AI history, even after SCA Cases Premium lapses.

Recommended hooks:

```text
mepr_account_nav
mepr_account_nav_content
```

Visibility/access:

- Logged-in active SCA Cases Premium users should see the tab.
- Logged-in users with AI history should continue to see the tab after premium lapses.
- Completed results and AI history require login + owner-only access.
- New AI launches, spending credits, setup, and premium case content still require active SCA Cases Premium.
- Credits remain stored when premium lapses, but become locked until SCA Cases Premium reactivates.

History performance:

- Use database indexes by `user_id`, `status`, and `created_at`.
- Do not let account page queries become unbounded as history grows.

## ACF Usage For Custom Meta Boxes

The spec allows ACF or custom meta boxes for case editor data entry. Since ACF is installed, use ACF for the editorial/admin input layer, but do not use ACF as the runtime system for credit balances, attempts, transcripts, mocks, wallets, or ledgers.

Recommended approach:

- Register ACF local field groups in the plugin on `acf/init`.
- Keep field definitions version-controlled inside the plugin.
- Check `function_exists('acf_add_local_field_group')` before registering.
- Add an admin notice if ACF is inactive.
- Use stable ACF field keys.
- Hide sensitive fields from all frontend responses.
- Sanitize and validate on save.
- Sync or normalize complex runtime-critical case configuration into the spec-required custom tables.

Important distinction:

- ACF is good for the existing SCA Cases editor UI.
- Custom tables are required for wallet, ledger, attempts, feedback, mock runs, mock stations, consent records, and normalized AI case runtime data.

Recommended ACF field groups for existing SCA case posts:

### Basic AI Settings

- Enable AI
- Consultation mode
- First speaker
- AI version
- Reviewed by
- Reviewed date

### Patient Profile

- Display name
- Age
- Gender text
- Occupation
- Tone
- Communication style
- Opening line
- Default voice

### Candidate-Visible Overrides

- Doctor brief override
- Pre-start instructions override

### Hidden Facts

Repeater fields:

- Label
- Fact text
- Reveal condition
- Reveal example
- Domain relevance
- Critical flag
- Notes

### ICE / Psychosocial Cues

Repeater fields:

- Cue
- When to reveal
- Importance

### Red Flags And Safety

Repeater fields:

- Item
- Must ask
- Fail if missing
- Linked domain

### Marking Items

Separate repeaters for:

- DG&D
- CM&C
- RTO

Each item should include:

- Item text
- Weight
- Critical flag
- Fail if missing

### Internal Clinical Notes

- Expected working diagnosis
- Acceptable differentials
- Expected management
- Explanation
- Follow-up/safety-net
- Fail patterns

### Mock Pool Fields

- `mock_pool_enabled`
- `mock_ready_status`
- `mock_primary_group_term_id`

The mock selection rule specifically requires a dedicated single field for `mock_primary_group_term_id`; do not rely only on multi-term taxonomy assignment.

Admin list enhancements:

- Add columns for AI Enabled, Consultation Mode, Mock Pool Enabled, Mock Ready Status, and AI Reviewed Date.
- Add filters for AI Enabled, Video, Telephone, Mock Pool Enabled, and Mock Ready Status.
- Add a mock coverage dashboard showing whether each of the 12 configured Clinical Experience Groups has at least one approved mock-ready case.

Migration helper:

- Prefill AI fields from existing Doctor Notes, Patient Notes, Marking Scheme, and Example Consultation content.
- Strip MemberPress shortcodes and hidden fragments.
- Treat migrated data as draft/review-required until approved.

## MemberPress Products And IDs

Never hard-code MemberPress product IDs in templates, shortcodes, JavaScript, or route logic. Product IDs are environment-specific.

Current memberships that must remain:

- Free
- Guideline Summaries Premium
- SCA Cases Premium

Required AI credit products:

- Essential Pack: £10 = 8 credits
- Practice Pro: £25 = 25 credits
- Exam Ready: £45 = 50 credits

Recommended reference strategy:

- Add a plugin settings screen under WordPress admin.
- Let admins map the SCA Cases Premium MemberPress product.
- Let admins map each AI credit pack to its MemberPress one-time product.
- Store mappings in one WordPress option.
- Validate saved IDs against MemberPress product post type/classes.
- Generate checkout/product URLs from MemberPress data.
- Use configured IDs only inside service classes.

Conceptual mapping:

```php
[
  'sca_cases_premium_product_id' => 123,
  'credit_pack_products' => [
    'essential' => [
      'product_id' => 456,
      'credits' => 8,
      'price_label' => '£10',
    ],
    'practice_pro' => [
      'product_id' => 457,
      'credits' => 25,
      'price_label' => '£25',
    ],
    'exam_ready' => [
      'product_id' => 458,
      'credits' => 50,
      'price_label' => '£45',
    ],
  ],
]
```

MemberPress transaction integration:

- Issue credits on completed payment/transaction events or webhook.
- Do not rely on the thank-you page.
- Record every issue/debit/hold/release/refund in an immutable ledger.
- Every ledger mutation must have an idempotency key.
- Use negative or compensating ledger entries for refunds/reversals.

## Required Plugin File And Folder Map

Recommended scaffold:

```text
wp-content/plugins/meddigest-ai-sca/
  meddigest-ai-sca.php
  composer.json
  readme.txt
  uninstall.php

  includes/
    Plugin.php
    Activation.php
    Deactivation.php
    Dependencies.php

    Admin/
      Admin.php
      SettingsPage.php
      ProductMappings.php
      SnippetInventory.php
      SnippetConflictChecker.php
      CaseColumns.php
      CaseFilters.php
      MockCoverageDashboard.php
      Notices.php
      MigrationHelper.php

    ACF/
      CaseFieldGroups.php
      FieldSanitizer.php
      CaseConfigSync.php

    Assets/
      FrontendAssets.php
      AdminAssets.php

    Cases/
      CasePostTypeIntegration.php
      CaseConfigRepository.php
      CaseFactRepository.php
      CaseMarkingRepository.php
      CaseSnapshotService.php
      ClinicalGroupCoverage.php

    Credits/
      WalletRepository.php
      LedgerRepository.php
      CreditService.php
      CreditHoldService.php
      Idempotency.php

    Database/
      Schema.php
      Installer.php
      Migrations.php

    Elementor/
      ShortcodeMounts.php

    Frontend/
      Shortcodes.php
      TemplateLoader.php
      NoCache.php
      CtaStatePresenter.php
      RouteRegistrar.php

    MemberPress/
      AccountTab.php
      EligibilityService.php
      ProductMappingService.php
      TransactionHandler.php
      WebhookIdempotency.php

    OpenAI/
      RealtimeTokenService.php
      PatientAgentPromptBuilder.php
      GraderPromptBuilder.php
      ResponsesFeedbackClient.php
      StructuredOutputSchemas.php
      SafetyGuards.php

    Practice/
      StationAttemptService.php
      StationAccess.php
      StationTimer.php
      FeedbackJob.php
      QuickCancelPolicy.php

    Mock/
      MockLaunchService.php
      MockAllocationService.php
      MockRunner.php
      MockSchedule.php
      MockResultsAggregator.php
      MockCoverageService.php

    REST/
      RestApi.php
      MeStateController.php
      ConsentController.php
      StationController.php
      MockController.php
      HistoryController.php

    Security/
      Nonces.php
      Permissions.php
      Ownership.php
      Sanitization.php

    Support/
      Container.php
      Clock.php
      Uuid.php
      Logger.php

  templates/
    archive/
      full-mock-strip.php

    case/
      ai-case-cta.php

    pricing/
      credit-packs.php

    account/
      ai-practice-tab.php
      ai-history-list.php

    sca-ai/
      station-setup.php
      station-live.php
      station-feedback.php
      mock-launch.php
      mock-run.php
      mock-results.php
      processing.php

    components/
      credit-pack-card.php
      status-notice.php
      resume-link.php
      consent-checks.php

  assets/
    css/
      frontend.css
      admin.css
      station.css
      mock.css
    js/
      me-state.js
      station-live.js
      mock-runner.js
      feedback-polling.js
      admin.js

  languages/
    meddigest-ai-sca.pot

  tests/
    phpunit/
      CreditLedgerTest.php
      MemberPressTransactionHandlerTest.php
      AccessRulesTest.php
      MockAllocationTest.php
      RouteAccessTest.php

  docs/
    MANUAL_TEST_CHECKLIST.md
    ADMIN_SETUP.md
```

Composer/autoloading:

- Use PHP namespaces.
- Use Composer autoloading if practical.
- Keep classes small and organized.

## Required Database Tables

The spec requires custom tables. Use `$wpdb`, prepared SQL, and `dbDelta` for creation/migration.

Required tables:

```text
wp_meddigest_ai_wallets
wp_meddigest_ai_ledger
wp_meddigest_ai_case_config
wp_meddigest_ai_case_facts
wp_meddigest_ai_case_marking_items
wp_meddigest_ai_attempts
wp_meddigest_ai_feedback
wp_meddigest_ai_mock_runs
wp_meddigest_ai_mock_stations
wp_meddigest_ai_consents
```

Important indexes/constraints:

- Wallet: `user_id UNIQUE`
- Ledger: `idempotency_key UNIQUE`
- Case config: `case_post_id UNIQUE`
- Attempts: `attempt_uuid UNIQUE`, indexes for `user_id`, `case_post_id`, `status`, `created_at`
- Feedback: `attempt_uuid UNIQUE`
- Mock runs: `mock_uuid UNIQUE`, indexes for `user_id`, `status`, `created_at`
- Mock stations: indexes for `mock_uuid`, `station_number`, `attempt_uuid`
- History queries: indexes that support `user_id/status/created_at`

## Required REST Endpoints

Namespace:

```text
/wp-json/meddigest-ai/v1/
```

Endpoints:

| Endpoint | Method | Purpose |
| --- | --- | --- |
| `/me/state` | GET | Hydrate premium, credit, active attempt, history, CTA state |
| `/consent` | POST | Save AI attempt/mock consent |
| `/station/start` | POST | Create station attempt and 1-credit hold |
| `/station/{attempt_uuid}/realtime-token` | POST | Mint short-lived Realtime/WebRTC token |
| `/station/{attempt_uuid}/status` | GET | Return timer/status/progress |
| `/station/{attempt_uuid}/end` | POST | End station and queue feedback |
| `/station/{attempt_uuid}/feedback` | GET | Return owner-only feedback when ready |
| `/mock/start` | POST | Create mock, allocate 12 stations, place hold |
| `/mock/{mock_uuid}/status` | GET | Return current mock phase |
| `/mock/{mock_uuid}/results` | GET | Return final mock results or processing state |
| `/history` | GET | Return paginated AI Practice history |

Endpoint rules:

- Logged-in checks.
- Nonce validation.
- Ownership validation.
- Server-side access validation.
- No hidden prompts, hidden facts, rubrics, raw grading instructions, or API keys in responses.
- Completed result endpoints remain owner-accessible after premium lapse.

## AI Flow Requirements

### Single Station

Setup page:

- Show case title.
- Show consultation mode badge.
- Show doctor notes only.
- Show microphone/device check.
- Show educational/privacy checkboxes.
- Keep Begin disabled until all server and client prerequisites pass.
- Do not show patient notes, marking scheme, example consultation, transcript, or tips.

Live station:

- 12-minute server-authoritative countdown.
- One fresh OpenAI Realtime/WebRTC session per station.
- No live transcript on screen.
- Resume same attempt on refresh/reconnect using server time.
- Missed time is not restored.
- No second active station/mock may start for the same user.

Feedback:

- Generate asynchronously.
- Poll while feedback is processing.
- Retry transient failures up to 3 times.
- Use structured output.
- Grade only transcript evidence.
- Patient AI must never see the marking scheme.
- Grader AI must not give credit for actions absent from the transcript.

Quick cancel:

- Fewer than 2 meaningful turns and under 30 seconds means cancelled attempt and credit release.

### Full Mock SCA

Launch:

- Entry points are the inline `/sca-cases/` strip and AI Practice.
- The launch page is `/sca-ai/mock/launch/`.
- Show total duration: 3 hours 10 minutes.
- Require active SCA Cases Premium, 12 available credits, microphone/device check, and start confirmation.
- If an active mock exists, resume instead of creating another.

Schedule:

- Exactly 12 stations.
- 3 minutes reading time before each station.
- 12 minutes live consultation per station.
- 10-minute break after station 6.
- No pause.
- No skip.
- No feedback between stations.
- No end-early button after station 1 reading begins.
- One fresh Realtime session per live station.
- Server time controls every phase.

Allocation:

- Allocate one case from each of the 12 configured Clinical Experience Groups.
- No duplicate cases in the same mock.
- Use stored term IDs and `mock_primary_group_term_id`.
- Eligible cases must be published, AI enabled, mock pool enabled, and approved.
- If any required group has zero eligible cases, block launch and show admin coverage problem.
- Snapshot selected case IDs and grading config at launch.
- Do not re-randomize after launch.

Results:

- Grade each station asynchronously after it ends.
- Final results page polls while aggregation is incomplete.
- Old result pages load stored snapshots only.
- Opening old results must not trigger OpenAI usage.

## Recommended Integration Points

### Elementor

- Use Elementor only as the existing page/layout surface for `/sca-cases/` and `/pricing/`.
- Add explicit shortcode placeholders in the correct positions.
- Do not use fragile JavaScript DOM insertion.
- Do not duplicate sections with multiple shortcode mounts.
- Do not assume Elementor Pro Theme Builder unless production confirms it.
- Plugin templates and shortcodes should own dynamic UI, CTA state, and server-backed output.

### MemberPress

- Use existing MemberPress accounts and products.
- Add AI Practice through `mepr_account_nav` and `mepr_account_nav_content`.
- Use MemberPress payment/transaction hooks to issue credits after completed payment.
- Gate new AI launches by active SCA Cases Premium.
- Keep completed AI result access owner-only but not premium-required.
- Keep credits stored but locked after premium lapse.
- Use configured product mappings rather than hard-coded IDs.

### ACF

- Use ACF local field groups for SCA case editor fields.
- Use ACF for editor convenience, not for financial/session state.
- Sync normalized case configuration into custom tables when needed for selection, snapshotting, prompts, grading, and performance.
- Do not expose hidden ACF fields to frontend JavaScript or REST responses.

### Code Snippets

- Treat Code Snippets as an active production integration layer, not as disposable test code.
- Assume existing custom MedDigest behavior lives in database snippets until inspected.
- Before implementation, create a snippet inventory covering title, active state, scope, priority/load area, hooks, shortcodes, REST/AJAX actions, query filters, global functions/classes, CSS, JavaScript, and affected pages.
- Keep snippets active during staging compatibility testing so conflicts are found early.
- Do not place new MedDigest AI SCA business logic in Code Snippets.
- Final AI logic belongs in `wp-content/plugins/meddigest-ai-sca/`.
- If an existing snippet already controls the needed insertion point, either integrate through a stable shortcode placeholder inside that snippet/page content or migrate that one snippet carefully into the plugin after approval.
- Do not redeclare snippet-owned global functions or classes.
- Do not reuse existing snippet shortcode names.
- Do not remove or deactivate snippets unless the replacement is identified, tested, and reversible.
- Add admin warnings if required AI shortcode placeholders are missing, duplicated, or rendered inside a known dynamic container.

### Genesis Block Theme

- Do not modify theme files.
- Do not require a child theme.
- Use the plugin for PHP logic, routes, templates, shortcodes, assets, and database schema.
- Keep CSS scoped with a plugin prefix such as `.mdsca-`.
- Because there is no child theme, do not use `functions.php` or theme template edits as the integration path.
- Any existing theme-like custom behavior should be searched for in Code Snippets and Elementor content.

## Risks Before Implementation

1. The repository has no WordPress code yet
   - Only `AGENTS.md` and the v8 spec are present.
   - Risk: integration details from the live site are unknown.
   - Mitigation: inspect the actual existing `/sca-cases/`, `/pricing/`, case template, and MemberPress account implementation before mounting shortcodes.

2. Exact placement depends on existing templates/layouts
   - The spec requires precise placement below intro text, below membership cards, and above case tabs.
   - Risk: Elementor layouts or theme templates may not have obvious insertion points.
   - Mitigation: add explicit shortcode/template placeholders and fail safely if missing or duplicated.

3. Cached HTML can show stale user state
   - Archive/pricing pages may be cached.
   - Risk: wrong credit balance, wrong CTA, or leaked state.
   - Mitigation: render public shell HTML and hydrate user state through protected REST endpoints.

4. Product IDs will differ by environment
   - MemberPress product IDs cannot be assumed.
   - Risk: credits issued for wrong product or checkout links break.
   - Mitigation: admin mapping screen with validation and clear setup docs.

5. Credit ledger idempotency is critical
   - Webhooks, duplicate clicks, refreshes, and multi-tab starts can repeat operations.
   - Risk: duplicate credit issue/debit or inconsistent balances.
   - Mitigation: immutable ledger, unique idempotency keys, and transaction-safe hold/commit/release flows.

6. Premium lapse rules are nuanced
   - New AI launches require SCA Cases Premium, but completed results remain accessible to the owner.
   - Risk: one blanket MemberPress rule on `/sca-ai/*` would violate the spec.
   - Mitigation: split access by route purpose and status.

7. Credits are locked, not deleted, after premium lapse
   - Risk: account UI or wallet code may incorrectly show zero balance or allow spending.
   - Mitigation: separate available and locked balances in wallet presentation.

8. Hidden case data must stay server-side
   - Patient facts, notes, marking schemes, rubrics, and grader prompts are sensitive.
   - Risk: frontend REST payloads or JS boot data may leak them.
   - Mitigation: strict presenter classes and response schemas for public/user UI.

9. Full mock allocation can fail due to coverage
   - Each of 12 groups needs at least one approved mock-ready case.
   - Risk: launch fails late or creates invalid mock.
   - Mitigation: coverage dashboard and launch preflight block.

10. Long-running mock timing is easy to get wrong
    - Browser timers cannot be trusted for a 3h10m flow.
    - Risk: pause/skip/time restore bugs.
    - Mitigation: server-authoritative schedule and resume from saved phase times.

11. OpenAI Realtime session boundaries matter
    - Full mock must not use one long Realtime session.
    - Risk: cost, reliability, and prompt isolation issues.
    - Mitigation: one fresh Realtime/WebRTC session per live station.

12. Result retention and privacy need a deletion path
    - Results remain accessible while account exists, but account deletion/erasure must remove or anonymize records.
    - Risk: privacy workflow gaps.
    - Mitigation: implement user erasure hooks for plugin tables.

13. Raw audio is forbidden in v1
    - Risk: accidental media capture/storage.
    - Mitigation: store transcript text/JSON and feedback JSON only.

14. AI Practice history can become slow
    - Risk: account area performance degrades.
    - Mitigation: pagination and indexes from the first migration.

15. ACF Pro should not be assumed
    - ACF is installed, but Pro is not specified.
    - Risk: relying on Pro-only option pages/repeaters if unavailable.
    - Mitigation: confirm ACF edition; use Settings API for plugin settings. If repeaters are unavailable, use custom meta boxes for repeatable fields.

16. Existing custom logic is probably hidden in Code Snippets
    - The live file system may look clean while the database contains active PHP, JavaScript, CSS, hooks, shortcodes, and redirects.
    - Risk: the new plugin could duplicate, override, or be overridden by snippet behavior that cannot be found with file search.
    - Mitigation: inventory and export Code Snippets before implementation; test all AI mounts with snippets active; document any snippet that touches `/sca-cases/`, `/pricing/`, case pages, MemberPress, ACF, REST/AJAX, login, redirects, or access rules.

17. Shortcode and hook collisions with database snippets
    - Existing snippets may already register shortcodes, actions, filters, REST routes, AJAX actions, classes, or global functions.
    - Risk: fatal redeclaration errors, duplicated UI, altered query behavior, broken filters, or unexpected account/pricing output.
    - Mitigation: use namespaced PHP classes, unique `meddigest_ai_` or `mdsca_` prefixes, `shortcode_exists()` checks, mount-count checks, and explicit admin notices for collisions.

18. Snippet load order may change behavior
    - Code Snippets can run on different scopes and priorities, and may load before or after plugin hooks.
    - Risk: plugin output may appear in the wrong place, access checks may be bypassed, or snippet filters may modify plugin queries.
    - Mitigation: choose stable WordPress and MemberPress hooks, avoid depending on snippet load order, and write integration tests/manual checks for all user states with snippets active.

19. Existing developer usability modifications may be business-critical
    - Snippets may contain custom UX improvements that users depend on but that are not represented in the repository.
    - Risk: a technically correct AI plugin could regress the current site experience.
    - Mitigation: capture before screenshots and flow notes for `/sca-cases/`, `/pricing/`, case pages, and account area; compare after each milestone on staging.

20. No child theme means no safe theme override layer
    - Genesis Block Theme is active directly.
    - Risk: theme updates can overwrite edits, and theme file edits would violate the plugin-only architecture.
    - Mitigation: keep all new code in the plugin; use Elementor/shortcode placeholders and plugin template routing; search Code Snippets for any existing theme-like customizations before choosing insertion points.

## Milestone Plan

The implementation order should follow the v8 spec phases.

### Milestone 0: Live Site Discovery

Purpose:

- Inspect existing WordPress templates, Elementor pages, MemberPress product setup, SCA case page structure, account area, and all database-level Code Snippets.
- Establish a safe integration plan before creating plugin behavior that could collide with snippets.

Deliverables:

- Export or back up all Code Snippets from staging/production before changes.
- Build a snippet inventory with active state, scope, hook names, shortcode names, function/class names, REST/AJAX actions, query filters, CSS/JS output, and affected pages.
- Identify snippets touching `/sca-cases/`, `/pricing/`, individual SCA case pages, MemberPress account pages, access gating, ACF fields, redirects, login, checkout, and case filters.
- Capture before screenshots and notes for the existing custom UX on the four integration surfaces.
- Confirm exact insertion point for `/sca-cases/` strip.
- Confirm exact insertion point for `/pricing/` credits.
- Confirm exact insertion point for case CTA.
- Confirm MemberPress product IDs and checkout URL behavior.
- Confirm ACF edition and available field types.
- Confirm whether existing SCA cases are posts, pages, CPTs, or another structure.
- Confirm which existing behavior is Elementor content, which is MemberPress, which is theme/default WordPress, and which is Code Snippets.
- Decide whether each AI insertion point should be added through an Elementor shortcode widget, an existing snippet-controlled placeholder, a plugin template hook, or a narrowly migrated snippet.

Exit criteria:

- No UI mount relies on guesses.
- Product mapping requirements are known.
- Snippet conflicts and dependencies are documented.
- No existing snippet has been disabled as a workaround.
- The plugin namespace, shortcode names, REST namespace, table names, option names, CSS prefix, and JS globals are confirmed not to collide with known snippets.

### Milestone 1: Wallet, Ledger, Pricing Products, State Endpoint, Pricing Credits

Matches spec Phase 1.

Deliverables:

- Plugin scaffold.
- Dependency checks for MemberPress and ACF.
- Snippet conflict checker for planned shortcodes, REST namespace, option names, and table names.
- Database migrations for wallets and ledger.
- Admin product mapping settings.
- Credit pack product mappings.
- MemberPress completed-transaction credit issuance.
- Immutable ledger with idempotency keys.
- `/me/state` endpoint.
- `[meddigest_ai_credit_packs]` section on `/pricing/`.

Acceptance checks:

- Credit packs appear once only.
- Credit packs appear only for logged-in active SCA Cases Premium users.
- Essential Pack issues 8 credits.
- Practice Pro issues 25 credits.
- Exam Ready issues 50 credits.
- Duplicate completed-payment events do not duplicate credits.
- Public, Free, and Guideline Summaries Premium users cannot see or use credit packs.
- Pricing behavior passes with all existing Code Snippets active.
- Any snippet touching MemberPress transactions, pricing, checkout, or access rules is documented and tested against the credit ledger.

### Milestone 2: Case CTA, Single Station Flow, Admin AI Settings

Matches spec Phase 2.

Deliverables:

- ACF local field groups or custom meta boxes for case AI settings.
- Admin list columns and filters.
- Case config/facts/marking custom tables.
- Migration helper for existing case material.
- Case CTA mount below title and above tabs.
- Station setup route.
- Station live route.
- Station feedback route.
- Single active AI session lock.
- 1-credit hold and commit/release flow.
- Realtime token endpoint.
- Feedback background job with retries.

Acceptance checks:

- AI CTA appears in the correct location on AI-enabled cases.
- Existing case tabs and any snippet-powered case UX still work.
- Public/non-premium users see upgrade flow, not credits.
- Premium users with zero credits see Buy AI Credits.
- Premium users with credits can start.
- Active attempt resumes instead of creating a duplicate.
- Live station has server-authoritative 12-minute timer.
- No live transcript appears.
- Patient AI does not receive marking scheme.
- Feedback remains owner-accessible after premium lapse.
- Case-page behavior passes with all existing Code Snippets active.

### Milestone 3: Archive Full Mock Strip, Mock Allocation, Mock Run And Results

Matches spec Phase 3.

Deliverables:

- `[meddigest_ai_full_mock_strip]` mount on `/sca-cases/`.
- Mock launch route.
- Mock allocation service.
- Mock coverage validation.
- Mock run route.
- Mock results route.
- 12-credit hold/commit/release flow.
- 12-station schedule with reading/live/break phases.
- Per-station fresh Realtime sessions.
- Async grading and final aggregation.

Acceptance checks:

- Full Mock strip appears once only below intro and above filters/case loading.
- Existing `/sca-cases/` filters, Case Count, dynamic loading, and any snippet-powered archive UX still work.
- CTA states match public, non-premium, premium low-credit, premium ready, and resume states.
- Launch requires active SCA Cases Premium and 12 available credits.
- Missing group coverage blocks launch cleanly.
- Mock allocates exactly 12 unique cases, one per configured group.
- No pause, skip, or feedback between stations.
- Refresh/reconnect resumes from server time.
- Results remain owner-accessible after premium lapse.
- Archive behavior passes with all existing Code Snippets active.

### Milestone 4: Coverage Dashboard, AI Practice Tab, QA Hardening, Staging Sign-Off

Matches spec Phase 4.

Deliverables:

- MemberPress AI Practice tab.
- Credit balance and locked balance display.
- Buy AI Credits link.
- Active station/mock resume links.
- Paginated single-station and mock history.
- Mock coverage dashboard.
- Manual test checklist.
- Privacy/account erasure handling.
- No-cache headers for owner-only routes.
- Staging QA pass.

Acceptance checks:

- AI Practice appears inside the existing account area.
- Existing MemberPress account tabs and any snippet-powered account UX still work.
- AI Practice remains visible after premium lapse if history exists.
- Completed feedback/results open for the owner after premium lapse.
- New launches remain blocked after premium lapse.
- Direct logged-out result URL redirects to login and returns to same URL.
- Another account cannot access stored results.
- Old result pages do not trigger OpenAI calls.
- Account history remains performant with a larger data set.
- Final staging sign-off is performed with all existing Code Snippets active unless a specific snippet has been intentionally migrated and retired.

## Final Pre-Implementation Checklist

Before writing plugin code:

- Export/back up all Code Snippets.
- Inventory active and inactive snippets, including hooks, shortcodes, REST/AJAX actions, global functions/classes, CSS/JS, query filters, redirects, and affected pages.
- Identify snippet-controlled custom behavior on `/sca-cases/`, `/pricing/`, SCA case pages, and the MemberPress account area.
- Confirm no planned plugin shortcode, PHP symbol, REST namespace, option name, table name, query var, CSS prefix, or JavaScript global collides with snippet-owned names.
- Capture before screenshots and flow notes for the current customized site behavior.
- Decide which snippets stay active, which are untouched dependencies, and which, if any, should be migrated into the plugin later.
- Confirm the live WordPress site's existing page/template structure.
- Confirm Elementor free vs Pro.
- Confirm ACF free vs Pro.
- Confirm MemberPress product IDs for SCA Cases Premium and three credit packs.
- Confirm the 12 Clinical Experience Groups and their term IDs.
- Confirm existing SCA case storage model.
- Confirm how existing tabs and locked preview flows are implemented.
- Confirm whether Action Scheduler is already available.
- Confirm OpenAI model/environment configuration and secret storage policy.
- Confirm privacy/retention rules for transcript JSON and feedback JSON.
- Confirm the staging test plan keeps Code Snippets active while validating the new plugin.
