<?php
/**
 * Hero API Manager — a lean, field-controlled public read API.
 *
 * The core wp/v2 API exposes every registered field on every response. This
 * module serves a separate namespace (hero-api/v1) where each post type is
 * opt-in and ONLY the fields ticked in Structure → post type → "Hero API"
 * are present in the payload. wp/v2 stays untouched: Hero Admin itself (and
 * the block editor) depend on it.
 *
 * Config lives in one option:
 *   hero_admin_api_manager = array(
 *     'post' => array( 'enabled' => true, 'fields' => array( 'id', 'slug', … ) ),
 *   )
 *
 * @package hero-admin
 */

defined( 'ABSPATH' ) || exit;

class Hero_Admin_API_Manager {

	const OPTION    = 'hero_admin_api_manager';
	const REST_BASE = 'hero-api/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_settings_routes' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_public_routes' ) );
	}

	/**
	 * Every field the manager can expose. Key order is the UI order.
	 *
	 * Extensible: adapters/plugins can add entries (with a `value` callback
	 * receiving WP_Post) through the filter.
	 *
	 * @return array<string,array{label:string,desc:string}>
	 */
	public static function field_registry() {
		$fields = array(
			'id'             => array(
				'label' => 'ID',
				'desc'  => 'The numeric post ID.',
			),
			'slug'           => array(
				'label' => 'Slug',
				'desc'  => 'URL-safe name, e.g. hello-world.',
			),
			'title'          => array(
				'label' => 'Title',
				'desc'  => 'Plain-text title.',
			),
			'content'        => array(
				'label' => 'Content',
				'desc'  => 'Rendered HTML body.',
			),
			'excerpt'        => array(
				'label' => 'Excerpt',
				'desc'  => 'Short summary (plain text).',
			),
			'date'           => array(
				'label' => 'Date',
				'desc'  => 'Publish date, ISO 8601 (UTC).',
			),
			'modified'       => array(
				'label' => 'Modified',
				'desc'  => 'Last-edit date, ISO 8601 (UTC).',
			),
			'link'           => array(
				'label' => 'Link',
				'desc'  => 'The permalink URL.',
			),
			'author'         => array(
				'label' => 'Author',
				'desc'  => '{ id, name } of the author.',
			),
			'featured_image' => array(
				'label' => 'Featured image',
				'desc'  => '{ id, url, alt } or null.',
			),
			'categories'     => array(
				'label' => 'Categories',
				'desc'  => 'Array of { id, name, slug }.',
			),
			'tags'           => array(
				'label' => 'Tags',
				'desc'  => 'Array of { id, name, slug }.',
			),
		);
		/**
		 * Filter the exposable field registry.
		 *
		 * @param array $fields key => { label, desc, value?: callable( WP_Post ): mixed }
		 */
		return apply_filters( 'hero_admin_api_fields', $fields );
	}

	/** Fields new configs start with — the lean headless staples. */
	public static function default_fields() {
		return array( 'id', 'slug', 'title', 'excerpt', 'date' );
	}

	/** Post types the manager may expose: public, real content types. */
	public static function exposable_types() {
		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $obj ) {
			if ( 'attachment' === $obj->name ) {
				continue;
			}
			$out[ $obj->name ] = $obj;
		}
		return $out;
	}

	/**
	 * Stored config for one type, normalized against the current registry.
	 *
	 * @param string $type Post type slug.
	 * @return array{enabled:bool,fields:string[]}
	 */
	public static function type_config( $type ) {
		$all   = get_option( self::OPTION, array() );
		$raw   = is_array( $all ) && isset( $all[ $type ] ) && is_array( $all[ $type ] ) ? $all[ $type ] : array();
		$known = array_keys( self::field_registry() );

		$fields = isset( $raw['fields'] ) && is_array( $raw['fields'] )
			? array_values( array_intersect( array_map( 'sanitize_key', $raw['fields'] ), $known ) )
			: self::default_fields();

		return array(
			'enabled' => ! empty( $raw['enabled'] ),
			'fields'  => $fields,
		);
	}

	/* ---------------------------------------------------------------------
	 * Settings routes (Hero Admin UI, admins only)
	 * ------------------------------------------------------------------- */

	public static function register_settings_routes() {
		$perm = function () {
			return current_user_can( 'manage_options' );
		};
		register_rest_route(
			'hero-admin/v1',
			'/api-manager',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_settings' ),
				'permission_callback' => $perm,
			)
		);
		register_rest_route(
			'hero-admin/v1',
			'/api-manager/(?P<type>[a-z0-9_-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_type_settings' ),
				'permission_callback' => $perm,
			)
		);
	}

	public static function get_settings() {
		$fields = array();
		foreach ( self::field_registry() as $key => $def ) {
			$fields[] = array(
				'key'   => $key,
				'label' => $def['label'],
				'desc'  => $def['desc'],
			);
		}
		$types = array();
		foreach ( self::exposable_types() as $slug => $obj ) {
			$types[ $slug ] = self::type_config( $slug );
		}
		return array(
			'fields' => $fields,
			'types'  => $types,
			'base'   => rest_url( self::REST_BASE ),
		);
	}

	public static function save_type_settings( WP_REST_Request $request ) {
		$type = sanitize_key( $request['type'] );
		if ( ! array_key_exists( $type, self::exposable_types() ) ) {
			return new WP_Error( 'hero_api_bad_type', 'Unknown or non-public post type.', array( 'status' => 404 ) );
		}

		$known  = array_keys( self::field_registry() );
		$fields = $request->get_param( 'fields' );
		$fields = is_array( $fields )
			? array_values( array_intersect( array_map( 'sanitize_key', $fields ), $known ) )
			: self::default_fields();

		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		$all[ $type ] = array(
			'enabled' => (bool) $request->get_param( 'enabled' ),
			'fields'  => $fields,
		);
		update_option( self::OPTION, $all );

		return array(
			'saved'  => true,
			'type'   => $type,
			'config' => self::type_config( $type ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Public routes — one collection per ENABLED type
	 * ------------------------------------------------------------------- */

	public static function register_public_routes() {
		foreach ( self::exposable_types() as $slug => $obj ) {
			$config = self::type_config( $slug );
			if ( ! $config['enabled'] ) {
				continue;
			}
			register_rest_route(
				self::REST_BASE,
				'/' . $slug,
				array(
					'methods'             => 'GET',
					// Public read API by design: published content of public
					// post types only, shaped to the admin-chosen fields.
					'permission_callback' => '__return_true',
					'callback'            => function ( WP_REST_Request $request ) use ( $slug ) {
						return self::serve_collection( $slug, $request );
					},
					'args'                => array(
						'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
						'per_page' => array( 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100 ),
						'search'   => array( 'type' => 'string' ),
						'slug'     => array( 'type' => 'string' ),
					),
				)
			);
			register_rest_route(
				self::REST_BASE,
				'/' . $slug . '/(?P<id>\d+)',
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => function ( WP_REST_Request $request ) use ( $slug ) {
						return self::serve_item( $slug, $request );
					},
				)
			);
		}
	}

	public static function serve_collection( $type, WP_REST_Request $request ) {
		$args = array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'paged'          => max( 1, (int) $request['page'] ),
			'posts_per_page' => min( 100, max( 1, (int) $request['per_page'] ) ),
			'no_found_rows'  => false,
		);
		if ( ! empty( $request['search'] ) ) {
			$args['s'] = sanitize_text_field( $request['search'] );
		}
		if ( ! empty( $request['slug'] ) ) {
			$args['name'] = sanitize_title( $request['slug'] );
		}

		$query  = new WP_Query( $args );
		$fields = self::type_config( $type )['fields'];
		$items  = array();
		foreach ( $query->posts as $post ) {
			$items[] = self::shape_post( $post, $fields );
		}

		$response = rest_ensure_response(
			array(
				'items'      => $items,
				'total'      => (int) $query->found_posts,
				'totalPages' => (int) $query->max_num_pages,
				'page'       => $args['paged'],
			)
		);
		// Standard WP pagination headers too, for header-reading clients.
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );
		return $response;
	}

	public static function serve_item( $type, WP_REST_Request $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== $type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'hero_api_not_found', 'Not found.', array( 'status' => 404 ) );
		}
		return self::shape_post( $post, self::type_config( $type )['fields'] );
	}

	/**
	 * Build the response object for one post: ONLY the chosen fields.
	 *
	 * @param WP_Post  $post   The post.
	 * @param string[] $fields Exposed field keys.
	 * @return array<string,mixed>
	 */
	public static function shape_post( WP_Post $post, array $fields ) {
		$registry = self::field_registry();
		$out      = array();
		foreach ( $fields as $key ) {
			if ( ! isset( $registry[ $key ] ) ) {
				continue;
			}
			// Adapter-provided fields bring their own value callback.
			if ( isset( $registry[ $key ]['value'] ) && is_callable( $registry[ $key ]['value'] ) ) {
				$out[ $key ] = call_user_func( $registry[ $key ]['value'], $post );
				continue;
			}
			$out[ $key ] = self::field_value( $post, $key );
		}
		return $out;
	}

	/** Value for one built-in field. */
	private static function field_value( WP_Post $post, $key ) {
		switch ( $key ) {
			case 'id':
				return (int) $post->ID;
			case 'slug':
				return $post->post_name;
			case 'title':
				return wp_specialchars_decode( get_the_title( $post ), ENT_QUOTES );
			case 'content':
				return apply_filters( 'the_content', $post->post_content );
			case 'excerpt':
				return wp_strip_all_tags( get_the_excerpt( $post ) );
			case 'date':
				return mysql2date( 'c', $post->post_date_gmt, false );
			case 'modified':
				return mysql2date( 'c', $post->post_modified_gmt, false );
			case 'link':
				return get_permalink( $post );
			case 'author':
				return array(
					'id'   => (int) $post->post_author,
					'name' => get_the_author_meta( 'display_name', $post->post_author ),
				);
			case 'featured_image':
				$thumb_id = get_post_thumbnail_id( $post );
				if ( ! $thumb_id ) {
					return null;
				}
				return array(
					'id'  => (int) $thumb_id,
					'url' => wp_get_attachment_image_url( $thumb_id, 'full' ),
					'alt' => (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
				);
			case 'categories':
				return self::term_list( $post, 'category' );
			case 'tags':
				return self::term_list( $post, 'post_tag' );
		}
		return null;
	}

	/** Terms of one taxonomy as { id, name, slug } rows ([] when not attached). */
	private static function term_list( WP_Post $post, $taxonomy ) {
		if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
			return array();
		}
		$terms = get_the_terms( $post, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array_values(
			array_map(
				function ( $term ) {
					return array(
						'id'   => (int) $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					);
				},
				$terms
			)
		);
	}
}
