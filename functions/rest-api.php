<?php
/**
 * Routes for the WordPress REST API.
 * Implement a REpresentationl State Transfer (REST) architecture from this file.
 * For an overview of REST architecture, see: http://www.slideshare.net/apigee/restful-api-design-second-edition/102
 *  (and video: https://apigee.com/about/resources/webcasts/restful-api-design-second-edition)
 *
 * @package SDES\ServicesTheme\API
 * @see http://v2.wp-api.org
 * @see https://developer.wordpress.com/docs/api/
 */

namespace SDES\ServicesTheme\API;

require_once( get_stylesheet_directory() . '/custom-posttypes.php' );
	use SDES\ServicesTheme\PostTypes\StudentService as StudentService;

/** Use abstract class as an "Enum" of custom search filters, since PHP 5.4 doesn't have native enums. */
abstract class UCF_SEARCH_FILTER {
	const SERVICES = 'services';
}

/**
 * Register the routes for the API's endpoints.
 *
 * @see https://developer.wordpress.org/reference/hooks/rest_api_init/ WP-Ref: rest_api_init hook
 * @see https://developer.wordpress.org/reference/functions/register_rest_route/ WP-Ref: register_rest_route()
 * @link http://www.regular-expressions.info/named.html Regular expression named capturing groups.
 */
function register_routes() {
	// Function signature: register_rest_route( string $namespace, string $route, array $args = array(), bool $override = false ); .
	// Route: ~/wp-json/rest/v1/services/titles .
	register_rest_route( 'rest/v1', '/categories', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\route_categories',
	) );

	// Route: ~/wp-json/rest/v1/services/titles .
	register_rest_route( 'rest/v1', '/services/titles', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\route_services_titles',
	) );

	// Route: ~/wp-json/rest/v1/services/summary .
	register_rest_route( 'rest/v1', '/services/summary', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\route_services_summary',
	) );

	// Route: ~/wp-json/rest/v1/services .
	register_rest_route( 'rest/v1', '/services/', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\route_services',
		'args' => array(
			'search' => array(),
			'slug' => array(),
		),
	) );

	// TODO: add to regex `[\w-]` to allow any character allowed in slugs.
	// Route: ~/wp-json/rest/v1/services/{slug} .
	register_rest_route( 'rest/v1', '/services/(?P<slug>[\w-]+)', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\route_services_slug',
	) );
	// TODO: add more granular routes with subsets of the data (e.g., /services/{slug}/hours).
	// TODO: add REST routes for active campaigns.
	// Route: ~/wp-json/rest/v1/campaigns .
	// Route: ~/wp-json/rest/v1/campaigns/{slug} .
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );


/**
 * Route: ~/wp-json/rest/v1/categories
 *
 * @param WP_REST_Request $request The request to be parsed into WP_Query args.
 * @return An array of IWpCategory objects, to be converted to JSON by WordPress.
 */
function route_categories( $request = null ) {
	if ( null === $request ) { $request = new \WP_REST_Request(); }
	return
		get_categories( array(
			'orderby' => 'name',
			'exclude' => array( 1 ), // Uncategorized.
			'parent' => 0,
			'taxonomy' => 'category',
		) );
}

/**
 * Route: ~/wp-json/rest/v1/services/titles
 *
 * @param WP_REST_Request $request The request to be parsed into WP_Query args.
 * @return An array of strings, to be converted to a simple JSON array by WordPress.
 */
function route_services_titles( $request = null ) {
	if ( null === $request ) { $request = new \WP_REST_Request(); }
	$args = route_services_default_args_for( $request );
	$services = new \WP_Query( $args );
	// Loop through queried posts and get a title for each matching post.
	$retval = null;
	while ( $services->have_posts() ) {
		$services->the_post();
		$retval[] = get_the_title();
	}
	wp_reset_postdata();
	return $retval;
}

/**
 * Route: ~/wp-json/rest/v1/services/{slug}
 *
 * @see https://codex.wordpress.org/Function_Reference/get_page_by_path WP-Codex:get_page_by_path()
 * @return A single IStudentService object, to be converted to JSON by WordPress.
 */
function route_services_slug( $request = null ) {
	if ( null === $request ) { $request = new \WP_REST_Request(); }
	$requested_post = \get_page_by_path( $request->get_param( 'slug' ), OBJECT, 'student_service' );
	return StudentService::get_render_context_from_post( $requested_post );
}

/**
 * Route: ~/wp-json/rest/v1/services/summary
 * Return search results that summarize student services.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_rest_request/ WP-Ref: WP_REST_Request class
 * @see http://codex.wordpress.org/Class_Reference/WP_Query WP-Codex: WP_Query
 * @return An array of IStudentServiceSummary objects, to be converted to JSON by WordPress.
 */
function route_services_summary( $request = null ) {
	if ( null === $request ) { $request = new \WP_REST_Request(); }
	$args = route_services_default_args_for( $request );
	$services = new \WP_Query( $args );
	if ( empty( $services ) ) {
		return null;
	}
	$retval = null;
	global $post;
	while ( $services->have_posts() ) {
		$services->the_post();

		$retval[] = StudentService::get_summary_context_from_post( $post );

	}
	wp_reset_postdata();
	return $retval;
}

/**
 * Route: ~/wp-json/rest/v1/services
 * Handle a JSON request, return an object to be converted to JSON by WordPress.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_rest_request/ WP-Ref: WP_REST_Request class
 * @see http://codex.wordpress.org/Class_Reference/WP_Query WP-Codex: WP_Query
 * @return An array of IStudentService objects, to be converted to JSON by WordPress.
 */
function route_services( $request = null ) {
	if ( null === $request ) { $request = new \WP_REST_Request(); }
	$args = route_services_default_args_for( $request );
	$services = new \WP_Query( $args );
	if ( empty( $services ) ) {
		return null;
	}
	// Loop through queried posts and get a render context for each matching post.
	$retval = null;
	global $post;
	while ( $services->have_posts() ) {
		$services->the_post();

		$retval[] = StudentService::get_render_context_from_post( $post );

	}
	wp_reset_postdata();
	return $retval;
}

/**
 * Return default or overridden WP_Query args for a request searching under ~/wp-json/rest/v1/services/*
 *
 * @param WP_REST_Request $request The request to be parsed into WP_Query args.
 * @return An $args array to be used by WP_Query.
 */
function route_services_default_args_for( $request = null ) {
	if ( ! defined( 'SDES\ServicesTheme\API\DEFAULT_POSTS_PER_PAGE' ) ) {
		define( 'SDES\ServicesTheme\API\DEFAULT_POSTS_PER_PAGE', 7 );
	}
	// Build WP Query based on request.
	$args = array(
		'post_type' => StudentService::NAME,
		'post_status' => array( 'publish' ),
		'posts_per_page' => 50,  // Show a relatively large number unless explicitly paginated with offset, paged, or overriding posts_per_page.
		'orderby' => 'title',
		'order' => 'ASC',
	);

	// TODO: Make and merge multiple WP_Query statements instead of calling $wpdb. $query_search $query_tax $query_meta
	// ?q=&search=&s= // Set to 'q', then 'search', then 's' if multiple are present.
	if ( $request->get_param( 'q' ) || $request->get_param( 'search' ) || $request->get_param( 's' ) ) {
		$search_term = $request->get_param( 'q' ) ?: $request->get_param( 'search' ) ?: $request->get_param( 's' );
		$args = array_merge( $args, array(
			'ucf_search_filter' => UCF_SEARCH_FILTER::SERVICES,
			'ucf_query_services' => $search_term,
		) );
	}
	// ?name=&slug=  // Set to 'name' if both are present.
	if ( $request->get_param( 'name' ) || $request->get_param( 'slug' ) ) {
		$name = $request->get_param( 'name' ) ?: $request->get_param( 'slug' );
		$args = array_merge( $args, array(
			'name' => $name,
		) );
	}
	// ?id=
	if ( $request->get_param( 'id' ) ) {
		$args = array_merge( $args, array(
			'p' => $request->get_param( 'id' ),
		) );
	}
	// ?posts_per_page=&limit=  // Set to 'posts_per_page' if both are present.
	if ( $request->get_param( 'posts_per_page' ) || $request->get_param( 'limit' ) ) {
		$posts_per_page = $request->get_param( 'posts_per_page' ) ?: $request->get_param( 'limit' );
		$args = array_merge( $args, array(
			'posts_per_page' => $posts_per_page,
		) );
	}
	// ?offset=
	if ( $request->get_param( 'offset' ) ||  '0' === $request->get_param( 'offset' ) ) {
		$args = array_merge( $args, array(
			'offset' => $request->get_param( 'offset' ),
		) );
		if ( -1 === $args['posts_per_page'] ) {
			$args = array_merge( $args, array( 'posts_per_page' => DEFAULT_POSTS_PER_PAGE ) );
		}
	}
	// ?paged=
	if ( $request->get_param( 'paged' ) ||  '0' === $request->get_param( 'paged' ) ) {
		$args = array_merge( $args, array(
			'paged' => $request->get_param( 'paged' ),
		) );
		if ( -1 === $args['posts_per_page'] ) {
			$args = array_merge( $args, array( 'posts_per_page' => DEFAULT_POSTS_PER_PAGE ) );
		}
	}
	return $args;
}


/**
 * Enable querying student_service terms and metadata.
 * Filter applies to any WP_Query when the parameter 'ucf_search_filter' is set to constant UCF_SEARCH_FILTER::SERVICES.
 */
function ucf_search_filter_services( $search, &$wp_query ) {
	if (
		isset( $wp_query->query_vars['ucf_search_filter'] )
		&& $wp_query->query_vars['ucf_search_filter'] === UCF_SEARCH_FILTER::SERVICES
		&& isset( $wp_query->query_vars['ucf_query_services'] )
		&& ! empty( $wp_query->query_vars['ucf_query_services'] )
		&& isset( $wp_query->query_vars['post_type'] )
	) {
		global $wpdb;
		if ( empty( $search ) ) {
			return $search;
		}
		$meta_key1 = StudentService::NAME . '_short_description';
		$meta_key2 = StudentService::NAME . '_heading_text';
		$search_term = '%'.$wpdb->esc_like( $wp_query->query_vars['ucf_query_services'] ).'%';
		$search .= $wpdb->prepare( " AND (
			($wpdb->posts.post_title LIKE %s) /* Title (query post object itself) */
			OR EXISTS
			(	/* Categories and Tags (query related terms in these taxonomies). */
				SELECT * FROM $wpdb->terms
				INNER JOIN $wpdb->term_taxonomy
					ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
				INNER JOIN $wpdb->term_relationships
					ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
				WHERE object_id = $wpdb->posts.ID
					AND ( /* Search associated taxonomies: */
						taxonomy = 'category' 
						OR taxonomy = 'post_tag'
						OR taxonomy = 'curation_groups'
						OR taxonomy = 'service_cost'
						OR taxonomy = 'service_type'
					)
					AND $wpdb->terms.name LIKE %s
			)
			OR EXISTS
			(	/* short_descr (query related metadata) */
				SELECT * FROM $wpdb->postmeta
				WHERE post_id = $wpdb->posts.ID
					AND ( /* Search associated meta keys: */
						meta_key = '%s'
						OR meta_key = '%s'
					)
					AND meta_value LIKE %s
			)
			OR ($wpdb->posts.post_content LIKE %s) /* Long_descr */
		)",	$search_term, $search_term, $meta_key1, $meta_key2,	$search_term, $search_term );
		$search = strtr( $search, array( "\r\n" => ' ', "\t" => ' ' ) ); // Prettier print.
	}
	return $search;
}
add_filter( 'posts_where', __NAMESPACE__ . '\ucf_search_filter_services', 500, 2 );

