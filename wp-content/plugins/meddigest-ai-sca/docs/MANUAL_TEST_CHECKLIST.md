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

