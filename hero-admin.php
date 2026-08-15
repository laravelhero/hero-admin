<?php
/**
 * Plugin Name:       Hero Admin
 * Plugin URI:        https://fb.com/iamwphero
 * Description:       A reimagined WordPress admin experience. Fast, focused and beautiful. Served at /hero-admin/.
 * Version:           0.29.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Abu Taher Hero
 * Author URI:        https://fb.com/iamwphero
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       hero-admin
 */

defined( 'ABSPATH' ) || exit;

define( 'HERO_ADMIN_VERSION', '0.29.0' );
define( 'HERO_ADMIN_FILE', __FILE__ );
define( 'HERO_ADMIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HERO_ADMIN_URL', plugin_dir_url( __FILE__ ) );

require_once HERO_ADMIN_DIR . 'includes/class-hero-admin.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-rest.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-surfaces.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-cpt.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-notices.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-logs.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-db.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-api-manager.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-sections.php';
require_once HERO_ADMIN_DIR . 'includes/class-hero-admin-updater.php';

// Bundled adapters for third-party plugins (each guards on its plugin).
require_once HERO_ADMIN_DIR . 'includes/adapters/jetpack-tiled-gallery.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/gravity-forms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/fluent-forms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/ninja-forms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/forminator.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/formidable.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/everest-forms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/sureforms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wpforms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/cf7-flamingo.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/cfdb7.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/elementor-forms.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/gravity-smtp.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/fluent-smtp.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-mail-smtp.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/post-smtp.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-mail-logging.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/suremails.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/site-mailer.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/ottokit.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/acf.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/meta-box.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/pods.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/seriously-simple-podcasting.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/powerpress.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/the-events-calendar.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-job-manager.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/safe-svg.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/koko-analytics.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-statistics.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/burst-statistics.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/independent-analytics.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/analyticswp.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/matomo.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/site-kit.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/jetpack-stats.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/simple-history.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-activity-log.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/aryo-activity-log.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/stream.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wordfence.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/limit-login-attempts.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/solid-security.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/all-in-one-security.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/code-snippets.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wpcode.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/fluent-snippets.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/custom-css-js.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/hfcm.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/redirection.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/safe-redirect-manager.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/simple-301-redirects.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/eps-301-redirects.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/query-monitor.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/scrutoscope.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wp-crontrol.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/transients-manager.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/rewrite-rules-inspector.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/cache-purge.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/updraftplus.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/disembark.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/duplicator.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wpvivid.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/backwpup.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/ai1wm.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/page-builders.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/seo.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/media-localize.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/stackable.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/kadence.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/generateblocks.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/otter.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/essential-blocks.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/spam.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/site-status.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/user-switching.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/one-time-login.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/public-post-preview.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/regenerate-thumbnails.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/enable-media-replace.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/media-folders.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/wcpdf.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/licenses.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/perfmatters.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/autoptimize.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/asset-cleanup.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/performance-lab.php';
require_once HERO_ADMIN_DIR . 'includes/adapters/network.php';

Hero_Admin::init();
Hero_Admin_REST::init();
Hero_Admin_DB::init();
Hero_Admin_Notices::init();
Hero_Admin_CPT::init();
Hero_Admin_API_Manager::init();
Hero_Admin_Sections::init();
// Self-updater disabled: it polls the GitHub repo in Hero_Admin_Updater::MANIFEST_URL,
// which does not exist yet. Publish your own repo + manifest.json, update that
// constant, then re-enable this line.
// new Hero_Admin_Updater();

register_activation_hook( __FILE__, function ( $network_wide ) {
	Hero_Admin::register_route();
	flush_rewrite_rules();
	if ( $network_wide ) {
		Hero_Admin::invalidate_network_rewrites();
	}
} );

register_deactivation_hook( __FILE__, function ( $network_wide ) {
	// Not flush_rewrite_rules(): init already registered the route this
	// request, so a flush here would regenerate the rules WITH it and leave
	// /hero-admin/ serving the homepage after deactivation. Dropping the
	// option makes the site rebuild on its next request, route omitted.
	delete_option( 'rewrite_rules' );
	if ( $network_wide ) {
		Hero_Admin::invalidate_network_rewrites();
	}
} );
