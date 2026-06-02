# MD Poetry — Design

WordPress plugin for storing poetry and poet information in a copyright-safe way: full poem text is private to the post author; everyone else sees first line + source.

This document captures the current design. Update it when decisions change. For the discussion that led to v0, see the handoff doc in the author's notes.

## Data model

Two custom post types, linked by post meta. Per-user — each WP user has their own poet records (poets are NOT shared across users).

### Poems (`md_poem`)

| Field | Storage |
|---|---|
| Title | `post_title` |
| Full body | `post_content` |
| First-line excerpt | `post_excerpt` (computed; see below) |
| Poet | meta `poet_id` (post ID of an `md_poet`) |
| Source | meta `source` (freeform string — URL, "Ariel, p.34", whatever) |
| Tags | `poem_tags` taxonomy |

The excerpt must be derived from block-aware content: strip block-comment markers and HTML tags, split on newlines, take the first non-empty line. `explode('<br>', …)` is incorrect for Gutenberg content.

### Poets (`md_poet`)

| Field | Storage |
|---|---|
| Name | `post_title` |
| Bio / notes | `post_content` |
| Photo | featured image |
| External links | meta `external_links` (repeatable: Wikipedia, Poetry Foundation, personal site, etc.) |

## Permissions and visibility

**Per-user data.** Poets are not shared across users. User A's "Sylvia Plath" and User B's "Sylvia Plath" are distinct records. Rationale: shared records make editing ambiguous (whose bio wins?); per-user keeps the model simple at the cost of duplication.

**Poem visibility filter.** A `the_content` filter on `md_poem` returns the full body when `get_post_field('post_author', $post) === get_current_user_id()`, otherwise returns the first line + hidden-line count. Same code path for single- and multi-user; in a single-user install every poem is yours, so it behaves transparently. As fail-closed hardening, a `get_the_excerpt` filter always recomputes a poem's excerpt from the body (first line only) rather than trusting the stored `post_excerpt`, and non-authored poems are excluded from front-end search (`posts_where`) — so the body never leaks via an empty excerpt or a search match.

**Poet pages are public**, with the same visibility filter applied to the poem list shown on them. Visitors see:
- bio, photo, external links (the author's own writing — not copyrighted)
- total poem count
- alphabetical list of poems (titles linking to single-poem pages)
- list of tags used across that poet's poems with per-tag usage counts (e.g. "grief (4), nature (2)")

The author, when viewing their own poem, sees full body; everyone else sees first line + hidden-line count. The source (attribution) is shown to everyone, author included.

**The poems index** (`/u/{user-id}/poems/`) lists titles linking to single-poem pages; the visibility filter still governs what's visible on each poem page, so non-authors get first line + source there as on poet pages. Single poem and poet pages each carry a "back to index" link in their footer.

**Jetpack / WordPress.com sync.** The visibility filter is display-only and runs on this server — it does not change what's stored in `post_content`. On a Jetpack-connected site, Jetpack Sync replicates the full poem `post_content` to WordPress.com regardless of the filter. So the full text *is* backed up to WordPress.com, even though the filter is the security boundary only for what renders locally. Poems and poets do **not** appear in the site feed or the WordPress.com Reader (they are custom post types, not standard posts, and nothing wires them into feeds). Accepted as-is for now: the backup is not treated as an exposure risk, and there is no Jetpack Sync exclusion filter. Revisit if these CPTs are ever surfaced in feeds/Reader or if storing genuinely private text becomes a requirement.

## URLs

Namespaced by numeric user ID, not username:

- `/u/{user-id}/poets/` — alphabetical index of the user's poets (poem count each)
- `/u/{user-id}/poems/` — alphabetical index of the user's poems (linked poet + date added)
- `/u/{user-id}/poet/{slug}/` — single poet
- `/u/{user-id}/poem/{slug}/` — single poem

`/poets/` and `/poems/` are shortcuts that redirect to the logged-in viewer's own namespace. Logged-out visitors get a 404 — the index is personalised to a viewer, so with no one logged in there is no index to show and we don't guess a user. Legacy default-CPT single and archive URLs 301-redirect to the namespaced form. The two indexes cross-link to each other in their headers.

User ID rather than username so URLs stay stable across username changes. Implemented via WordPress rewrite rules in `Rewrites`. The index page date uses the site's configured date format (Settings → General).

## Taxonomy

A single flat `poem_tags` taxonomy on poems, used primarily for theme/mood. Form (sonnet/haiku/etc.) is not split into a separate taxonomy — keep it simple; can split later if needed.

UI is the default WordPress tag pill/list. Tag clouds were considered as an atmospheric homepage element but are not the primary tag UI (accessibility, usability).

## Editor

Both CPTs opt into the block editor via `show_in_rest => true` on `register_post_type()`. Default `supports` (title, editor) is fine; featured image must be added to `md_poet`'s supports list for the photo field.

Meta fields (`poet_id`, `source`, `external_links`) are edited via **classic PHP meta boxes**, which render correctly inside Gutenberg without a JS toolchain.

**Poem bodies should use the core Poetry block** (slug: `core/verse`, CSS class `wp-block-verse` — the inserter label was renamed from "Verse" to "Poetry"). One block per stanza. It preserves line breaks and whitespace, which the default Paragraph block does not — Enter inside a Paragraph starts a new block, not a new line. `Poem::compute_excerpt()` handles the block markup correctly: the first line of the first stanza becomes the excerpt.

Future direction: replace meta boxes with React-based Gutenberg sidebar panels (`PluginDocumentSettingPanel`), which requires registering meta with `show_in_rest` and adding a JS build. Not blocking — pursue when a richer meta UX is wanted.

## Templates

**Single poem/poet pages render through the active theme**, not a plugin template. The plugin provides no `single-md_poem.php`/`single-md_poet.php`; the theme's own single template handles the page (title, chrome, layout), so the pages look native — this matters most on block (FSE) themes, where a classic plugin template would otherwise miss the theme's header/footer chrome and constrained-layout width.

The plugin's bespoke markup is injected into `the_content` instead:
- `Poems\PoemContent` prepends the byline and appends the source, tags, the author-only notice, and the back-link. The source shows for everyone (author included) — it's attribution, not copyrighted text.
- `Poets\PoetContent` appends external links, the poem list, aggregated tag counts, and the back-link. (The featured-image photo is left to the theme's single template, which already renders it.)

Both run at `the_content` priority 30 (after `PoemVisibility` at 20, so the body is already the author-or-public version) and are guarded to `is_singular() && in_the_loop() && is_main_query()` — so the markup never leaks into feeds, the REST API, the block editor, or archive loops.

**The index pages** (`/u/{id}/poems/`, `/u/{id}/poets/`) have no underlying post, so they can't ride a theme single template. They use plugin templates (`archive-poems.php`, `archive-poets.php`, routed by `Templates::filter_template_include`; theme-overridable by filename or under `md-poetry/`). Their content is wrapped in a `wp-block-group is-layout-constrained` group so block themes apply content width, but they still use `get_header()`/`get_footer()`, which fall back to plainer theme-compat chrome on block themes. Making the index pages render with full native chrome is part of the deferred block-theme work below.

## Deliberately deferred

The following are *not* decided. "We chose not to decide yet" is itself the current design state — revisit when there's real pressure to do so.

- **Books / collections as a first-class entity.** Source is a freeform string. If "all poems I've logged from *Ariel*" becomes a real desire, introduce a book/collection model and migrate source strings into structured records.
- **Poet sharing model.** Locked to per-user. If this ever flips to shared, plan a migration: poets gain a canonical record, user-specific annotations move into a separate per-user layer.
- **Repeatable meta field UI** for `external_links`: custom code vs. ACF/Meta Box/CMB2. Decide at implementation time.
- **Block-theme-native index pages.** Single pages already render natively through the theme (see Templates). The custom index routes still use plugin PHP templates with plainer theme-compat chrome on block themes. Full native rendering for them — registering block templates and/or server-rendered blocks — is a later effort, worth it when Site-Editor customisation is wanted.
- **Discovery / homepage.** What should someone landing on the plugin's root URL see? Not designed.
- **Public archive and search.** Defaults for now. Revisit when the collection is large enough that defaults stop being useful.
- **Chapbook builder.** User-assembled lists of poems with a page-limit constraint, rendered through a printable template that folds into a zine (canonical layout: 8-page A4 imposition, but worth exploring others). Considerations to settle at implementation time: whether lists are private or shareable; how to surface public-domain vs in-copyright status per poem so the user knows what's safe to include (UK rule: author died ≤ 1955 → public domain).
