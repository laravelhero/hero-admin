<?php
/**
 * Hero Sections — structured website building blocks for a headless frontend.
 *
 * A Section is one block of a website page (Hero banner, Testimonials, Team,
 * FAQ…) stored as a `hero_section` post: the template id and its structured
 * data live in post meta. The admin edits them in Hero's Sections view; the
 * frontend (e.g. Next.js) reads clean JSON from hero-api/v1/sections.
 *
 * Not a wp_block / pattern: those are editor markup. Sections are DATA.
 *
 * @package hero-admin
 */

defined( 'ABSPATH' ) || exit;

class Hero_Admin_Sections {

	const POST_TYPE   = 'hero_section';
	const META_TPL    = '_hero_section_template';
	const META_DATA   = '_hero_section_data';
	/** Ordered section ids attached to a regular post/page/CPT item. */
	const META_ATTACH = '_hero_sections';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_admin_routes' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_attach_routes' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_public_routes' ) );
		// Expose attached sections as a tickable field in the Hero API
		// manager — any post type can then serve its sections, in order.
		add_filter( 'hero_admin_api_fields', array( __CLASS__, 'register_api_field' ) );
	}

	/** The 'sections' field for hero-api payloads: attached sections in saved order. */
	public static function register_api_field( $fields ) {
		$fields['sections'] = array(
			'label' => 'Sections',
			'desc'  => 'Attached sections (published only), in their drag-and-drop order.',
			'value' => array( __CLASS__, 'attached_public' ),
		);
		return $fields;
	}

	/** Published attached sections of a post, shaped for the public API. */
	public static function attached_public( WP_Post $post ) {
		$out = array();
		foreach ( self::attached_ids( $post->ID ) as $sid ) {
			$section = get_post( $sid );
			if ( $section && self::POST_TYPE === $section->post_type && 'publish' === $section->post_status ) {
				$out[] = self::public_shape( $section );
			}
		}
		return $out;
	}

	/** Sanitized ordered id list from meta. */
	public static function attached_ids( $post_id ) {
		$raw = get_post_meta( $post_id, self::META_ATTACH, true );
		return is_array( $raw ) ? array_values( array_unique( array_map( 'absint', $raw ) ) ) : array();
	}

	/** Hidden storage type: no wp-admin UI, no public URLs — Hero owns the surface. */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => 'Sections',
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * Section templates: what kinds of sections exist and which fields each
	 * carries. Field types: text, textarea, image (URL), repeat (rows of
	 * sub-fields). Extensible via the `hero_admin_section_templates` filter.
	 *
	 * @return array<string,array>
	 */
	public static function templates() {
		$templates = array(
			'hero'         => array(
				'label'  => 'Hero',
				'desc'   => 'Big opening banner — headline, supporting text, image, call-to-action buttons.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text' ),
					array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
					array( 'key' => 'image', 'label' => 'Image URL', 'type' => 'image' ),
					array(
						'key'    => 'buttons',
						'label'  => 'Buttons',
						'type'   => 'repeat',
						'item'   => 'button',
						'fields' => array(
							array( 'key' => 'label', 'label' => 'Label', 'type' => 'text' ),
							array( 'key' => 'url', 'label' => 'URL', 'type' => 'text' ),
						),
					),
				),
			),
			'features'     => array(
				'label'  => 'Features',
				'desc'   => 'A grid of product or service highlights.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
					array(
						'key'    => 'items',
						'label'  => 'Features',
						'type'   => 'repeat',
						'item'   => 'feature',
						'fields' => array(
							array( 'key' => 'icon', 'label' => 'Icon (emoji or name)', 'type' => 'text' ),
							array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
							array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
						),
					),
				),
			),
			'testimonials' => array(
				'label'  => 'Testimonials',
				'desc'   => 'Quotes from happy customers.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array(
						'key'    => 'items',
						'label'  => 'Testimonials',
						'type'   => 'repeat',
						'item'   => 'testimonial',
						'fields' => array(
							array( 'key' => 'quote', 'label' => 'Quote', 'type' => 'textarea' ),
							array( 'key' => 'name', 'label' => 'Name', 'type' => 'text' ),
							array( 'key' => 'role', 'label' => 'Role / company', 'type' => 'text' ),
							array( 'key' => 'avatar', 'label' => 'Avatar URL', 'type' => 'image' ),
						),
					),
				),
			),
			'team'         => array(
				'label'  => 'Team',
				'desc'   => 'The people behind the site.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
					array(
						'key'    => 'items',
						'label'  => 'Members',
						'type'   => 'repeat',
						'item'   => 'member',
						'fields' => array(
							array( 'key' => 'name', 'label' => 'Name', 'type' => 'text' ),
							array( 'key' => 'role', 'label' => 'Role', 'type' => 'text' ),
							array( 'key' => 'photo', 'label' => 'Photo URL', 'type' => 'image' ),
							array( 'key' => 'bio', 'label' => 'Short bio', 'type' => 'textarea' ),
						),
					),
				),
			),
			'cta'          => array(
				'label'  => 'Call to action',
				'desc'   => 'A focused prompt with buttons.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
					array(
						'key'    => 'buttons',
						'label'  => 'Buttons',
						'type'   => 'repeat',
						'item'   => 'button',
						'fields' => array(
							array( 'key' => 'label', 'label' => 'Label', 'type' => 'text' ),
							array( 'key' => 'url', 'label' => 'URL', 'type' => 'text' ),
						),
					),
				),
			),
			'faq'          => array(
				'label'  => 'FAQ',
				'desc'   => 'Questions and answers.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array(
						'key'    => 'items',
						'label'  => 'Questions',
						'type'   => 'repeat',
						'item'   => 'question',
						'fields' => array(
							array( 'key' => 'question', 'label' => 'Question', 'type' => 'text' ),
							array( 'key' => 'answer', 'label' => 'Answer', 'type' => 'textarea' ),
						),
					),
				),
			),
			'stats'        => array(
				'label'  => 'Stats',
				'desc'   => 'Numbers that impress — customers served, years active…',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array(
						'key'    => 'items',
						'label'  => 'Stats',
						'type'   => 'repeat',
						'item'   => 'stat',
						'fields' => array(
							array( 'key' => 'value', 'label' => 'Value (e.g. 4,200+)', 'type' => 'text' ),
							array( 'key' => 'label', 'label' => 'Label', 'type' => 'text' ),
						),
					),
				),
			),
			'gallery'      => array(
				'label'  => 'Gallery',
				'desc'   => 'A set of images.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array(
						'key'    => 'images',
						'label'  => 'Images',
						'type'   => 'repeat',
						'item'   => 'image',
						'fields' => array(
							array( 'key' => 'url', 'label' => 'Image URL', 'type' => 'image' ),
							array( 'key' => 'alt', 'label' => 'Alt text', 'type' => 'text' ),
						),
					),
				),
			),
			'contact'      => array(
				'label'  => 'Contact',
				'desc'   => 'How to reach you.',
				'fields' => array(
					array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
					array( 'key' => 'description', 'label' => 'Description', 'type' => 'textarea' ),
					array( 'key' => 'email', 'label' => 'Email', 'type' => 'text' ),
					array( 'key' => 'phone', 'label' => 'Phone', 'type' => 'text' ),
					array( 'key' => 'address', 'label' => 'Address', 'type' => 'textarea' ),
				),
			),
		);
		/**
		 * Add or adjust section templates.
		 *
		 * @param array $templates id => { label, desc, fields[] }
		 */
		return apply_filters( 'hero_admin_section_templates', $templates );
	}

	/* ---------------------------------------------------------------------
	 * Sanitization
	 * ------------------------------------------------------------------- */

	/** Sanitize submitted data against one template's field schema. */
	public static function sanitize_data( $template_id, $raw ) {
		$templates = self::templates();
		if ( ! isset( $templates[ $template_id ] ) || ! is_array( $raw ) ) {
			return array();
		}
		return self::sanitize_fields( $templates[ $template_id ]['fields'], $raw );
	}

	private static function sanitize_fields( array $schema, array $raw ) {
		$out = array();
		foreach ( $schema as $field ) {
			$key   = $field['key'];
			$value = isset( $raw[ $key ] ) ? $raw[ $key ] : null;
			switch ( $field['type'] ) {
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( (string) $value );
					break;
				case 'image':
					$out[ $key ] = esc_url_raw( (string) $value );
					break;
				case 'repeat':
					$rows = array();
					foreach ( is_array( $value ) ? $value : array() as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}
						$clean = self::sanitize_fields( $field['fields'], $row );
						// Keep the row when at least one sub-field has content.
						if ( array_filter( $clean, 'strlen' ) ) {
							$rows[] = $clean;
						}
					}
					$out[ $key ] = $rows;
					break;
				default: // text
					$out[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Admin routes (Hero UI)
	 * ------------------------------------------------------------------- */

	public static function register_admin_routes() {
		// Editors design the site's sections; edit_pages matches that station.
		$perm = function () {
			return current_user_can( 'edit_pages' );
		};
		register_rest_route(
			'hero-admin/v1',
			'/sections',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'admin_list' ),
					'permission_callback' => $perm,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'admin_create' ),
					'permission_callback' => $perm,
				),
			)
		);
		register_rest_route(
			'hero-admin/v1',
			'/sections/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'admin_get' ),
					'permission_callback' => $perm,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'admin_update' ),
					'permission_callback' => $perm,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'admin_delete' ),
					'permission_callback' => $perm,
				),
			)
		);
	}

	/** Attach panel routes: what's on this post + the pickable catalog. */
	public static function register_attach_routes() {
		register_rest_route(
			'hero-admin/v1',
			'/sections/attached/(?P<post>\\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'attached_get' ),
					'permission_callback' => array( __CLASS__, 'can_edit_target' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'attached_save' ),
					'permission_callback' => array( __CLASS__, 'can_edit_target' ),
				),
			)
		);
	}

	public static function can_edit_target( WP_REST_Request $request ) {
		return current_user_can( 'edit_post', (int) $request['post'] );
	}

	public static function attached_get( WP_REST_Request $request ) {
		$catalog = array();
		foreach ( get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) as $section ) {
			$catalog[] = array(
				'id'       => (int) $section->ID,
				'title'    => $section->post_title,
				'template' => (string) get_post_meta( $section->ID, self::META_TPL, true ),
				'status'   => $section->post_status,
			);
		}
		return array(
			'attached' => self::attached_ids( (int) $request['post'] ),
			'catalog'  => $catalog,
		);
	}

	public static function attached_save( WP_REST_Request $request ) {
		$ids   = array();
		foreach ( (array) $request->get_param( 'sections' ) as $sid ) {
			$section = get_post( absint( $sid ) );
			if ( $section && self::POST_TYPE === $section->post_type && ! in_array( (int) $section->ID, $ids, true ) ) {
				$ids[] = (int) $section->ID;
			}
		}
		update_post_meta( (int) $request['post'], self::META_ATTACH, $ids );
		return array( 'saved' => true, 'attached' => $ids );
	}

	private static function template_index() {
		$out = array();
		foreach ( self::templates() as $id => $tpl ) {
			$out[] = array(
				'id'     => $id,
				'label'  => $tpl['label'],
				'desc'   => $tpl['desc'],
				'fields' => $tpl['fields'],
			);
		}
		return $out;
	}

	private static function admin_shape( WP_Post $post ) {
		return array(
			'id'       => (int) $post->ID,
			'title'    => $post->post_title,
			'slug'     => $post->post_name,
			'status'   => $post->post_status,
			'template' => (string) get_post_meta( $post->ID, self::META_TPL, true ),
			'data'     => (array) get_post_meta( $post->ID, self::META_DATA, true ),
			'modified' => mysql2date( 'c', $post->post_modified_gmt, false ),
		);
	}

	public static function admin_list() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 200,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		return array(
			'templates' => self::template_index(),
			'base'      => rest_url( Hero_Admin_API_Manager::REST_BASE . '/sections' ),
			'sections'  => array_map( array( __CLASS__, 'admin_shape' ), $posts ),
		);
	}

	public static function admin_create( WP_REST_Request $request ) {
		$templates = self::templates();
		$template  = sanitize_key( (string) $request->get_param( 'template' ) );
		if ( ! isset( $templates[ $template ] ) ) {
			return new WP_Error( 'hero_section_bad_template', 'Unknown section template.', array( 'status' => 400 ) );
		}
		$title = sanitize_text_field( (string) $request->get_param( 'title' ) );
		$id    = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'draft',
				'post_title'  => $title ?: $templates[ $template ]['label'],
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, self::META_TPL, $template );
		update_post_meta( $id, self::META_DATA, self::sanitize_data( $template, (array) $request->get_param( 'data' ) ) );
		return self::admin_shape( get_post( $id ) );
	}

	private static function find( $id ) {
		$post = get_post( (int) $id );
		return ( $post && self::POST_TYPE === $post->post_type ) ? $post : null;
	}

	public static function admin_get( WP_REST_Request $request ) {
		$post = self::find( $request['id'] );
		if ( ! $post ) {
			return new WP_Error( 'hero_section_not_found', 'Section not found.', array( 'status' => 404 ) );
		}
		return self::admin_shape( $post );
	}

	public static function admin_update( WP_REST_Request $request ) {
		$post = self::find( $request['id'] );
		if ( ! $post ) {
			return new WP_Error( 'hero_section_not_found', 'Section not found.', array( 'status' => 404 ) );
		}
		$update = array( 'ID' => $post->ID );
		if ( null !== $request->get_param( 'title' ) ) {
			$update['post_title'] = sanitize_text_field( (string) $request->get_param( 'title' ) );
		}
		if ( null !== $request->get_param( 'slug' ) ) {
			$update['post_name'] = sanitize_title( (string) $request->get_param( 'slug' ) );
		}
		$status = $request->get_param( 'status' );
		if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$update['post_status'] = $status;
		}
		$result = wp_update_post( $update, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( null !== $request->get_param( 'data' ) ) {
			$template = (string) get_post_meta( $post->ID, self::META_TPL, true );
			update_post_meta( $post->ID, self::META_DATA, self::sanitize_data( $template, (array) $request->get_param( 'data' ) ) );
		}
		return self::admin_shape( get_post( $post->ID ) );
	}

	public static function admin_delete( WP_REST_Request $request ) {
		$post = self::find( $request['id'] );
		if ( ! $post ) {
			return new WP_Error( 'hero_section_not_found', 'Section not found.', array( 'status' => 404 ) );
		}
		wp_delete_post( $post->ID, true );
		return array( 'deleted' => true );
	}

	/* ---------------------------------------------------------------------
	 * Public routes — what the Next.js frontend consumes
	 * ------------------------------------------------------------------- */

	public static function register_public_routes() {
		register_rest_route(
			Hero_Admin_API_Manager::REST_BASE,
			'/sections',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'public_list' ),
				'args'                => array(
					'template' => array( 'type' => 'string' ),
				),
			)
		);
		register_rest_route(
			Hero_Admin_API_Manager::REST_BASE,
			'/sections/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'public_get' ),
			)
		);
	}

	private static function public_shape( WP_Post $post ) {
		return array(
			'id'       => (int) $post->ID,
			'slug'     => $post->post_name,
			'title'    => $post->post_title,
			'template' => (string) get_post_meta( $post->ID, self::META_TPL, true ),
			'data'     => (object) (array) get_post_meta( $post->ID, self::META_DATA, true ),
			'modified' => mysql2date( 'c', $post->post_modified_gmt, false ),
		);
	}

	public static function public_list( WP_REST_Request $request ) {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);
		if ( ! empty( $request['template'] ) ) {
			$args['meta_key']   = self::META_TPL; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['meta_value'] = sanitize_key( $request['template'] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		return array(
			'items' => array_map( array( __CLASS__, 'public_shape' ), get_posts( $args ) ),
		);
	}

	public static function public_get( WP_REST_Request $request ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'name'           => sanitize_title( $request['slug'] ),
				'posts_per_page' => 1,
			)
		);
		if ( ! $posts ) {
			return new WP_Error( 'hero_section_not_found', 'Section not found.', array( 'status' => 404 ) );
		}
		return self::public_shape( $posts[0] );
	}
}
