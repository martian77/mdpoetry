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

**Poem visibility filter.** A `the_content` filter on `md_poem` returns the full body when `get_post_field('post_author', $post) === get_current_user_id()`, otherwise returns excerpt + source. Same code path for single- and multi-user; in a single-user install every poem is yours, so it behaves transparently.

**Poet pages are public**, with the same visibility filter applied to the poem list shown on them. Visitors see:
- bio, photo, external links (the author's own writing — not copyrighted)
- total poem count
- alphabetical list of poems (titles linking to single-poem pages)
- list of tags used across that poet's poems with per-tag usage counts (e.g. "grief (4), nature (2)")

The author, when viewing their own poem, sees full body. Everyone else sees first line + source.

## URLs

Namespaced by numeric user ID, not username:

- `/u/{user-id}/poet/{slug}/`
- `/u/{user-id}/poem/{slug}/`

User ID rather than username so URLs stay stable across username changes. Implemented via WordPress rewrite rules; not yet built (see MVP plan in handoff).

## Taxonomy

A single flat `poem_tags` taxonomy on poems, used primarily for theme/mood. Form (sonnet/haiku/etc.) is not split into a separate taxonomy — keep it simple; can split later if needed.

UI is the default WordPress tag pill/list. Tag clouds were considered as an atmospheric homepage element but are not the primary tag UI (accessibility, usability).

## Editor

Both CPTs opt into the block editor via `show_in_rest => true` on `register_post_type()`. Default `supports` (title, editor) is fine; featured image must be added to `md_poet`'s supports list for the photo field.

Meta fields (`poet_id`, `source`, `external_links`) are edited via **classic PHP meta boxes**, which render correctly inside Gutenberg without a JS toolchain.

**Poem bodies should use the core Poetry block** (slug: `core/verse`, CSS class `wp-block-verse` — the inserter label was renamed from "Verse" to "Poetry"). One block per stanza. It preserves line breaks and whitespace, which the default Paragraph block does not — Enter inside a Paragraph starts a new block, not a new line. `Poem::compute_excerpt()` handles the block markup correctly: the first line of the first stanza becomes the excerpt.

Future direction: replace meta boxes with React-based Gutenberg sidebar panels (`PluginDocumentSettingPanel`), which requires registering meta with `show_in_rest` and adding a JS build. Not blocking — pursue when a richer meta UX is wanted.

## Deliberately deferred

The following are *not* decided. "We chose not to decide yet" is itself the current design state — revisit when there's real pressure to do so.

- **Books / collections as a first-class entity.** Source is a freeform string. If "all poems I've logged from *Ariel*" becomes a real desire, introduce a book/collection model and migrate source strings into structured records.
- **Poet sharing model.** Locked to per-user. If this ever flips to shared, plan a migration: poets gain a canonical record, user-specific annotations move into a separate per-user layer.
- **Repeatable meta field UI** for `external_links`: custom code vs. ACF/Meta Box/CMB2. Decide at implementation time.
- **Discovery / homepage.** What should someone landing on the plugin's root URL see? Not designed.
- **Public archive and search.** Defaults for now. Revisit when the collection is large enough that defaults stop being useful.
