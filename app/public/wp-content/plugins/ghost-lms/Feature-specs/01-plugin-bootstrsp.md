# 01 — Plugin Bootstrap

Read `context/AGENTS.md` and all six context files before starting.

## Goal
Create the plugin skeleton: the main plugin file, Composer autoloading, activation/
deactivation hooks, and PHPCS configuration. Nothing functional yet — this unit
just has to produce a plugin that activates cleanly with no notices/warnings and
gives every later unit a place to put its code.

## Design decisions
- Plugin slug/folder: `ghost-lms`. Main file: `ghost-lms.php`.
- PHP namespace root: `GhostLMS\` (per `02-architecture.md`), PSR-4 autoloaded via Composer, `vendor/` git-ignored.
- Minimum requirements enforced on activation: PHP 8.1+, WordPress 6.4+, WooCommerce active. If unmet, deactivate self and show an admin notice — don't fatal-error.
- Version constant `GHOST_LMS_VERSION` defined in the main file, bumped manually per release; used later for DB migration versioning (see spec 03).
- Follow WordPress plugin header conventions exactly (Plugin Name, Version, Requires PHP, Requires Plugins: woocommerce, Text Domain: ghost-lms).

## Implementation
1. Create the folder structure from `02-architecture.md` (`src/`, `assets/`, `build/`, `templates/`, `languages/`) with `.gitkeep` placeholders where empty.
2. `composer.json`: PSR-4 autoload mapping `GhostLMS\\` → `src/`, require PHP `^8.1`.
3. `ghost-lms.php`:
   - Standard plugin header block.
   - Guard against direct access (`if (!defined('ABSPATH')) exit;`).
   - Require `vendor/autoload.php` (fail gracefully with an admin notice if missing, since this repo will be built with Composer as part of CI/deploy, not shipped with vendor committed).
   - Define `GHOST_LMS_VERSION`, `GHOST_LMS_PLUGIN_DIR`, `GHOST_LMS_PLUGIN_URL` constants.
   - `register_activation_hook` → requirements check (PHP/WP/WooCommerce versions) → bail with `deactivate_plugins()` + admin notice if unmet, otherwise call a `GhostLMS\Core\Activator::activate()` stub (empty for now, later units add to it — e.g. DB table creation in spec 03).
   - `register_deactivation_hook` → `GhostLMS\Core\Deactivator::deactivate()` stub (empty for now — no data deletion on mere deactivation, only on uninstall).
   - Bootstraps a `GhostLMS\Core\Plugin` class on `plugins_loaded` that will later wire up all the subsystems (post types, REST, admin, etc.) — for this unit it can just load the text domain.
4. `uninstall.php` stub (checks `WP_UNINSTALL_PLUGIN` constant, does nothing yet — later units add real cleanup).
5. `.phpcs.xml.dist` using the `WordPress-Extra` ruleset, text-domain `ghost-lms`, scanning `src/`.
6. `.gitignore`: `vendor/`, `node_modules/`, `build/`.

## Verification checklist
- [ ] Plugin appears in `wp-admin` plugin list and activates with zero PHP notices/warnings in debug log.
- [ ] Activating with WooCommerce deactivated shows the admin notice and does not fatal-error.
- [ ] `composer install` succeeds and autoloading resolves a test class under `src/`.
- [ ] `phpcs --standard=.phpcs.xml.dist src/` runs clean (or only against currently-empty `src/`, since no functional code exists yet).
- [ ] Plugin deactivates and reactivates without leaving orphaned options/notices.