# GABAY — AI coding guide (condensed)

This repo is a PHP-based office directory and indoor navigation system. It has a desktop admin UI and a mobile visitor interface, SVG floor plans, QR code generation, and a MySQL database accessed via PDO.

Keep instructions short and actionable — focus only on discoverable repo-specific patterns.

Key entry points (start here):
- `connect_db.php` — PDO connection; `$connect` is the DB handle used across files.
- `home.php`, `officeManagement.php`, `floorPlan.php` — admin UI and floor plan editor.
- `mobileScreen/` — visitor-facing mobile pages; `explore.php?office_id={id}` is the mobile office view.
- `generate_qrcodes.php`, `update_panorama_qr_urls.php`, `panorama_qr_manager.php` — QR code and panorama QR tooling.

Concrete conventions and patterns
- Database: MySQL accessed through PDO. Use prepared statements and expect `$connect` to exist after including `connect_db.php`.
- Paths: Desktop files live in repo root; mobile pages live in `mobileScreen/` and often include parent files with `__DIR__ . '/../connect_db.php'`.
- SVG floor plans: stored under `SVG/` and use element IDs like `room-{number}-1` and `roomlabel-{number}`. Database `location` fields map to these IDs exactly.
- Images/QR: Office images in `office_images/` (names use timestamps), QR images in `qrcodes/` (pattern `{sanitized_office_name}_{office_id}.png`). Tools use `uniqid()` for filenames.
- JS/CSS: floor plan scripts in `floorjs/`; dark mode in `darkMode.js`; mobile styles under `mobileNav.css` / `mobileScreen` styles.

Developer workflows (what to run)
- Local dev: this is a PHP + MySQL app (XAMPP commonly used). Place the repo under your web root and ensure MySQL credentials in `connect_db.php` match your local environment.
- Regenerate QR codes (after adding offices): open `generate_qrcodes.php` in a browser or run via CLI PHP: `php generate_qrcodes.php` (ensure DB credentials accessible).
- Update panorama QR URLs: run `update_panorama_qr_urls.php` similarly.

Project-specific gotchas
- SVG IDs must match DB `location` exactly — mismatches break mapping and navigation.
- Mobile files use relative includes; when editing mobile files, keep `__DIR__` include paths consistent.
- Several maintenance/fix scripts exist (e.g., `fix_*` and `FLOOR3_*` docs). Read those files before large changes.

Files worth reading when making changes (examples):
- `connect_db.php` — DB connection pattern
- `floorPlan.php`, `floorjs/` — SVG editing and drag/drop persistence
- `generate_qrcodes.php` — QR generation logic and base URL handling
- `mobileScreen/explore.php` — how mobile office pages consume `office_id`
- `panorama_*` files — panorama QR and viewer integration

When editing: prefer minimal, focused changes. Run quick checks:
- Confirm DB access by including `connect_db.php` and running a small query.
- Use browser devtools to inspect SVG element IDs when fixing layout/labels.

If you need more info
- I merged and condensed the repository's existing guidance into this file. If you want, I can expand any section (DB schema details, key SQL, or step-by-step QR regeneration). Point to what you want next.

Please review and tell me any unclear or missing parts to iterate.