# Laravel Deploy Summary (2026-03-23)

## Scope (Important)
- This update is for **Laravel CRM repo only**: `https://github.com/uniquesnapbox/crm.git`
- **Flutter app repo is separate** and was **not mixed** in this deployment.

## Branch + Merge
- Working branch: `ARINDAM`
- Remote branches synced locally.
- Merged branch: `alihassan/flutter-app-changes` into `ARINDAM`

## Commits included
- `7949e54` - fix: normalize follow-up time validation and harden auth theme assets
- `869589f` - merge: bring alihassan flutter app changes into ARINDAM

## Live deployment target
- Domain: `https://crm.uniquesnapbox.com`
- Path: `/home1/uniqu700/crm.uniquesnapbox.com`

## Deployment actions completed
- Uploaded merged Laravel files to live hosting.
- Ran production migrations:
  - `2026_03_17_105119_update_employee_locations_table`
  - `2026_03_17_111605_update_employee_locations_add_tracking_fields`
  - `2026_03_17_112430_add_photo_paths_to_employee_locations`
- Cleared and rebuilt Laravel cache:
  - `php artisan optimize:clear`
  - `php artisan config:cache`

## Live sanity checks
- Home URL responding (HTTP redirect as expected).
- API login endpoint responding (validation response on invalid test payload as expected).

## Separation note
- If Flutter changes are needed, they must be pushed/deployed from the Flutter repository only.
