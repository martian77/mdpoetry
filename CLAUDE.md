# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A WordPress plugin (`MDPoetry`) for storing poetry in a copyright-safe way. Full poem text is only visible to the post author; everyone else sees the first line and source attribution. Lives at `wp-content/plugins/mdpoetry/` in a WordPress install. Requires WP 6.7+ (needed for `register_block_template()` — see Architecture below).

## Linting

WordPress Coding Standards via phpcs. If phpcs is installed:

```bash
phpcs --standard=WordPress .
phpcbf --standard=WordPress .   # auto-fix
```

There are no automated tests and no composer.json in this repo.

## Block build

The custom blocks under `src/blocks/` need `@wordpress/scripts`:

```bash
npm install
npm run build     # writes build/blocks/, which is committed (no CI in this repo)
npm run start     # watch mode while editing a block
```

After any change under `src/blocks/`, run `npm run build` and commit the `build/` output — `includes/class-blocks.php` registers blocks from `build/blocks/`, not `src/`.

Local verification uses `wp-env` (bundled with `@wordpress/scripts`): `npx wp-env start` boots this plugin against Twenty Twenty-Four per `.wp-env.json`.

## Architecture

### Bootstrapping

`mdpoetry.php` → `Main::get_instance()` (singleton, runs on `init`). `Main::__construct` calls `define_constants()`, `includes()` (which boots the autoloader), then `init()` which wires up everything else.

### Autoloader

`includes/class-autoloader.php` maps `MDPoetry\Foo\Bar` → `includes/foo/class-bar.php`. Namespace segments become subdirectory paths; class name becomes `class-{name-with-dashes}.php`. No Composer.

### Custom post types

`PostTypes` registers two CPTs and one taxonomy:
- `md_poem` — the poem; body goes in `post_content`, excerpt in `post_excerpt` (computed, not user-entered)
- `md_poet` — the poet; photo is the featured image
- `poem_tags` taxonomy on `md_poem`

Both CPTs have `show_in_rest => true` so the block editor works. Meta fields are classic PHP meta boxes, not Gutenberg sidebar panels.

### Visibility filter

`PoemVisibility` hooks `the_content` at priority 20. For `md_poem` posts, non-authors receive `render_public_view()` instead of the full body — first line, line count, and source. The author check is `post_author === get_current_user_id()`.

`Poem::compute_excerpt()` / `extract_lines()` strips Gutenberg block comment markers (`<!-- ... -->`), converts `<br>` tags to newlines (the Poetry/Verse block uses `<br>`, not literal newlines), then strips remaining HTML before splitting on newlines. Don't use `explode('<br>', ...)` — it doesn't survive HTML encoding.

### URL structure

All CPT permalinks are namespaced by numeric user ID (`/u/{id}/poet/{slug}/`, `/u/{id}/poem/{slug}/`). This is implemented in `Rewrites`:
- Rewrite rules are added directly inside `setup()` (which runs on `init`), not inside a nested `init` hook, so they exist before `Main::maybe_flush_rewrites()` calls `flush_rewrite_rules()`.
- `filter_permalink` rewrites `get_permalink()` for both CPTs.
- `pre_get_posts` enforces author scoping — a slug that doesn't belong to the URL's user-id returns a 404.
- Legacy default-archive and single CPT URLs 301-redirect to the namespaced equivalents.
- `/poets/` shortcut redirects to `/u/{current-user-id}/poets/` (logged-out → user 1).

Version-stamped rewrite flush: `maybe_flush_rewrites()` compares `mdpoetry_version` option against `MDP_VERSION` and calls `flush_rewrite_rules()` on mismatch. Don't manually visit Settings → Permalinks after a version bump.

### Templates

Two rendering paths, chosen per-request by `wp_is_block_theme()`:

- **Classic themes**: `Templates::filter_single_template`/`filter_template_include` route to the classic PHP templates (`single-md_poem.php`, `single-md_poet.php`, `archive-poems.php`, `archive-poets.php`), which call `get_header()`/`get_footer()`. Themes can override by placing the same filename in the theme root or under a `md-poetry/` subfolder.
- **Block themes**: `single-md_poem`/`single-md_poet` are real template-hierarchy slugs, so `Templates::register_block_templates()` registers plugin defaults via `register_block_template()` (WP 6.7+) — block markup in `templates/block-templates/*.html`, composed of core blocks (`post-title`, `post-content`, `post-featured-image`, `post-terms`) plus custom dynamic blocks under `src/blocks/` for the bespoke bits (byline, visibility notice, poet bibliography/links/tag-summary, back-links). A theme override in the Site Editor still wins — `filter_single_template` returns early on block themes so it doesn't fight core's own fallback resolution. The `/u/{id}/poems/`  and `/u/{id}/poets/` routes aren't real archive queries (they're virtual pages driven by `Rewrites`), so they can't hook that fallback mechanism — `filter_template_include` instead points at `archive-poems-block.php`/`archive-poets-block.php`, which render the theme's real header/footer template parts (`block_header_area()`/`block_footer_area()`) around a `do_blocks()`-rendered body (`mdpoetry/poems-listing`/`mdpoetry/poets-listing`).

`includes/class-blocks.php` registers every block under `build/blocks/` on `init` — each block's `block.json` wires its own `render.php` (WP 6.1+ `render` field), so no per-block PHP registration code is needed.

### Per-user data model

Poets are **not shared** across users. Each WP user has their own poet records. The `PoemMetaBoxes` dropdown filters poets by `get_current_user_id()`. All queries against poems/poets should include `author => $user_id` to scope correctly.

### Meta fields

| Post type | Meta key | Constant |
|-----------|----------|----------|
| `md_poem` | `poet_id` | `PoemMetaBoxes::META_POET_ID` |
| `md_poem` | `source` | `PoemMetaBoxes::META_SOURCE` |

`external_links` on `md_poet` is noted in the design doc as not yet implemented.

### Key constants

| Constant | Value |
|----------|-------|
| `MDP_ABSPATH` | Plugin root path |
| `MDP_TEMPLATE_PATH` | `md-poetry` (theme override subfolder) |
| `MDP_VERSION` | Current plugin version string |
| `MDP_PLUGIN_SHORTNAME` | `mdpoetry` |
