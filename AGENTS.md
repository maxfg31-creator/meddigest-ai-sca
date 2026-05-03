# AGENTS.md - MedDigest AI SCA build rules

## Project
This repository contains custom WordPress code for MedDigest.co.uk.

The AI SCA feature must be built as a maintainable custom WordPress plugin:
wp-content/plugins/meddigest-ai-sca/

Do not create a second website, second login system, iframe tool, site-wide chatbot, or separate mock platform.

## Current site rules
- Keep the existing /sca-cases/ archive page.
- Keep the existing /pricing/ page.
- Keep existing SCA case pages and tabs.
- Keep the existing MemberPress account area.
- Add AI features into these existing areas only.

## Non-negotiable UI placement
- /sca-cases/: add one inline Full Mock SCA strip below the intro paragraph and above filters.
- /pricing/: add one AI credit packs section below existing membership cards.
- Case pages: add one AI CTA block below title and above existing tabs.
- Account: add AI Practice tab inside existing account area.

## Main plugin
All functionality belongs in:
wp-content/plugins/meddigest-ai-sca/

Do not scatter business logic through Elementor snippets, random theme functions, or post-by-post edits.

## Security rules
- Never expose OpenAI API keys in the browser.
- Never expose hidden prompts, hidden facts, patient notes, rubrics or marking schemes to the frontend.
- All start/spend actions must be server-side validated.
- REST requests must use logged-in user checks, nonce checks and ownership checks.
- Completed results are owner-only.
- Completed results remain viewable after SCA Cases Premium lapses.
- New AI launches require active SCA Cases Premium.
- Credits alone must not unlock premium SCA case content.
- Do not store raw audio in v1.

## Credit rules
- Essential Pack: £10 = 8 credits.
- Practice Pro: £25 = 25 credits.
- Exam Ready: £45 = 50 credits.
- Single AI station: 1 credit.
- Full Mock SCA: 12 credits.
- Credits do not expire.
- Credits are locked if SCA Cases Premium lapses.
- Credits become usable again when SCA Cases Premium reactivates.
- Every credit mutation must be recorded in an immutable ledger.
- Every ledger mutation must have an idempotency key.
- No duplicate issue/debit under webhook retries, duplicate clicks, page refreshes or multi-tab starts.

## AI station rules
- Setup page shows doctor brief only.
- Live station uses 12-minute server-authoritative timer.
- No live transcript on screen.
- One fresh OpenAI Realtime/WebRTC session per station.
- Patient AI must not see the marking scheme.
- Grader AI must grade only transcript evidence.

## Full Mock rules
- Exactly 12 stations.
- One case from each configured Clinical Experience Group.
- No duplicate cases in same mock.
- 3 minutes reading time before each station.
- 12 minutes live consultation per station.
- 10-minute break after station 6.
- No pause, no skip, no feedback between stations.
- One fresh Realtime session per live station.
- Server time controls all phases.

## Development standards
- Use PHP namespaces.
- Use Composer autoloading if practical.
- Use WordPress coding standards.
- Use prepared SQL through $wpdb.
- Use dbDelta for table creation/migrations.
- Add automated tests where possible.
- Add manual test checklists for WordPress/MemberPress flows.
- Keep files small and organized.
- Never commit secrets.

## Done means
A task is complete only when:
- Code is implemented.
- Tests or manual verification steps are included.
- Security implications are checked.
- Existing MedDigest pages still work.
- Codex summarizes changed files.
- Codex states what it could not verify.
