<?php
/**
 * MDPoetry: Namespaced URL rewrites and redirects.
 *
 * Implements the URL design from the v0 handoff:
 *   - /u/{user-id}/poets/              (personalised poets index)
 *   - /u/{user-id}/poems/             (personalised poems index)
 *   - /u/{user-id}/poet/{slug}/        (single poet)
 *   - /u/{user-id}/poem/{slug}/        (single poem)
 *   - /poets/                          (shortcut: redirects to the viewer's namespace)
 *   - /poems/                          (shortcut: redirects to the viewer's namespace)
 *
 * Also 301-redirects the legacy default-archive URLs so links stay clean.
 *
 * @package MDPoetry
 */

namespace MDPoetry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL rewrites and related redirects.
 *
 * @category class
 * @since 0.0.7
 * @author Eleanor Martin
 */
class Rewrites {

	public const QV_USER_ID  = 'mdp_user_id';
	public const QV_ARCHIVE  = 'mdp_archive';
	public const ARCHIVE_POETS = 'poets';
	public const ARCHIVE_POEMS = 'poems';

	/**
	 * Wires up hooks.
	 *
	 * Called from Main::init() which itself runs on the `init` action, so
	 * add_rewrite_rule() is called directly here (no nested init hook). This
	 * ensures the rules are present in the rewrite array before the version-
	 * stamped flush runs further down in Main::init().
	 */
	public static function setup() {
		$self = new self();
		$self->add_rewrite_rules();
		add_filter( 'query_vars', array( $self, 'register_query_vars' ) );
		add_filter( 'post_type_link', array( $self, 'filter_permalink' ), 10, 2 );
		add_filter( 'redirect_canonical', array( $self, 'suppress_canonical_for_namespaced' ), 10, 2 );
		add_action( 'pre_get_posts', array( $self, 'pre_get_posts' ) );
		add_action( 'template_redirect', array( $self, 'redirect_shortcut' ) );
		add_action( 'template_redirect', array( $self, 'redirect_singular_poet_to_archive' ) );
		add_action( 'template_redirect', array( $self, 'redirect_legacy_singles' ) );
		add_action( 'template_redirect', array( $self, 'redirect_legacy_archive' ) );
	}

	/**
	 * Registers our custom query vars with WP_Query.
	 *
	 * @param  array $vars Existing public query vars.
	 * @return array
	 */
	public function register_query_vars( $vars ) {
		$vars[] = self::QV_USER_ID;
		$vars[] = self::QV_ARCHIVE;
		return $vars;
	}

	/**
	 * Registers the rewrite rules.
	 *
	 * Rules are added at the top so they win over WP's default CPT rules.
	 */
	public function add_rewrite_rules() {
		// /u/{id}/poets/ -> poets archive for that user.
		add_rewrite_rule(
			'^u/([0-9]+)/poets/?$',
			'index.php?' . self::QV_USER_ID . '=$matches[1]&' . self::QV_ARCHIVE . '=' . self::ARCHIVE_POETS,
			'top'
		);
		// /u/{id}/poems/ -> poems archive for that user.
		add_rewrite_rule(
			'^u/([0-9]+)/poems/?$',
			'index.php?' . self::QV_USER_ID . '=$matches[1]&' . self::QV_ARCHIVE . '=' . self::ARCHIVE_POEMS,
			'top'
		);
		// /u/{id}/poet/{slug}/ -> single poet, namespaced.
		add_rewrite_rule(
			'^u/([0-9]+)/poet/([^/]+)/?$',
			'index.php?' . PostTypes::POST_TYPE_POET . '=$matches[2]&' . self::QV_USER_ID . '=$matches[1]',
			'top'
		);
		// /u/{id}/poem/{slug}/ -> single poem, namespaced.
		add_rewrite_rule(
			'^u/([0-9]+)/poem/([^/]+)/?$',
			'index.php?' . PostTypes::POST_TYPE_POEM . '=$matches[2]&' . self::QV_USER_ID . '=$matches[1]',
			'top'
		);
		// /poets/ shortcut -> redirected on template_redirect.
		add_rewrite_rule(
			'^poets/?$',
			'index.php?' . self::QV_ARCHIVE . '=' . self::ARCHIVE_POETS,
			'top'
		);
		// /poems/ shortcut -> redirected on template_redirect.
		add_rewrite_rule(
			'^poems/?$',
			'index.php?' . self::QV_ARCHIVE . '=' . self::ARCHIVE_POEMS,
			'top'
		);
	}

	/**
	 * Rewrites permalinks for our CPTs into the namespaced form.
	 *
	 * @param  string   $post_link Default permalink.
	 * @param  \WP_Post $post      Post being linked.
	 * @return string
	 */
	public function filter_permalink( $post_link, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return $post_link;
		}
		if ( PostTypes::POST_TYPE_POEM !== $post->post_type && PostTypes::POST_TYPE_POET !== $post->post_type ) {
			return $post_link;
		}
		$author_id = (int) $post->post_author;
		if ( $author_id <= 0 ) {
			return $post_link;
		}
		$segment = ( PostTypes::POST_TYPE_POEM === $post->post_type ) ? 'poem' : 'poet';
		return home_url( sprintf( '/u/%d/%s/%s/', $author_id, $segment, $post->post_name ) );
	}

	/**
	 * Enforces author scoping on single CPT queries that came in via a
	 * namespaced URL.
	 *
	 * If the slug doesn't actually belong to the user-id in the URL, this
	 * causes WP to return no posts -> 404. Prevents cross-user slug access.
	 *
	 * @param \WP_Query $query The query being prepared.
	 */
	public function pre_get_posts( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$user_id = (int) $query->get( self::QV_USER_ID );
		if ( $user_id <= 0 ) {
			return;
		}
		// Only constrain single-CPT lookups; the archive route runs its own
		// queries from inside the template.
		if ( $query->get( PostTypes::POST_TYPE_POEM ) || $query->get( PostTypes::POST_TYPE_POET ) ) {
			$query->set( 'author', $user_id );
		}
	}

	/**
	 * Redirects /u/{id}/poet/ (no slug) to /u/{id}/poets/.
	 */
	public function redirect_singular_poet_to_archive() {
		if ( preg_match( '#/u/(\d+)/poet/?$#', self::current_url(), $matches ) ) {
			wp_safe_redirect( home_url( sprintf( '/u/%d/poets/', (int) $matches[1] ) ), 301 );
			exit;
		}
	}

	/**
	 * Prevents WP's redirect_canonical from redirecting namespaced URLs.
	 *
	 * Without this, WP calls get_permalink() on the found post (which returns
	 * the real author's namespaced URL via filter_permalink) and redirects —
	 * turning a wrong-user-ID 404 into a silent redirect to the real author.
	 *
	 * @param  string $redirect_url  The canonical URL WP wants to redirect to.
	 * @param  string $requested_url The current request URL.
	 * @return string|false
	 */
	public function suppress_canonical_for_namespaced( $redirect_url, $requested_url ) {
		if ( preg_match( '#/u/\d+/#', $requested_url ) ) {
			return false;
		}
		return $redirect_url;
	}

	/**
	 * /poets/ and /poems/ shortcuts: redirect to /u/{viewer-id}/{archive}/.
	 *
	 * - Logged-in user -> their own namespace.
	 * - Logged-out visitor -> user 1 (site owner; works for the single-user
	 *   install today, revisit when multi-user is enabled).
	 */
	public function redirect_shortcut() {
		$archive = get_query_var( self::QV_ARCHIVE );
		if ( self::ARCHIVE_POETS !== $archive && self::ARCHIVE_POEMS !== $archive ) {
			return;
		}
		if ( (int) get_query_var( self::QV_USER_ID ) > 0 ) {
			return;
		}
		$target = get_current_user_id();
		if ( $target <= 0 ) {
			$target = 1;
		}
		wp_safe_redirect( home_url( sprintf( '/u/%d/%s/', $target, $archive ) ), 302 );
		exit;
	}

	/**
	 * 301-redirects legacy single CPT URLs (default WP permalinks) to their
	 * namespaced equivalents.
	 *
	 * If you hit /md_poet/sylvia-plath/ directly, this sends you to
	 * /u/{author}/poet/sylvia-plath/ instead.
	 */
	public function redirect_legacy_singles() {
		if ( ! is_singular( array( PostTypes::POST_TYPE_POEM, PostTypes::POST_TYPE_POET ) ) ) {
			return;
		}
		if ( (int) get_query_var( self::QV_USER_ID ) > 0 ) {
			return;
		}
		// URL already looks namespaced (/u/{id}/...) but our query var wasn't set,
		// meaning WP resolved it via its own default rule. Don't redirect to the
		// real author — that would expose cross-user slugs. 404 instead.
		if ( preg_match( '#/u/\d+/#', self::current_url() ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		// get_permalink runs through filter_permalink and returns the namespaced URL.
		$canonical = get_permalink( $post );
		if ( $canonical && $canonical !== self::current_url() ) {
			wp_safe_redirect( $canonical, 301 );
			exit;
		}
	}

	/**
	 * 301-redirects the legacy /md_poet/ and /md_poem/ post-type archives to
	 * the /poets/ and /poems/ shortcuts, which then redirect to the viewer's
	 * namespaced index.
	 */
	public function redirect_legacy_archive() {
		if ( is_post_type_archive( PostTypes::POST_TYPE_POET ) ) {
			wp_safe_redirect( home_url( '/poets/' ), 301 );
			exit;
		}
		if ( is_post_type_archive( PostTypes::POST_TYPE_POEM ) ) {
			wp_safe_redirect( home_url( '/poems/' ), 301 );
			exit;
		}
	}

	/**
	 * Returns the current request URL.
	 *
	 * @return string
	 */
	private static function current_url() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return $scheme . '://' . $host . $uri;
	}
}
