<?php
namespace AIOSEO\Plugin\Pro\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Admin as CommonAdmin;
use AIOSEO\Plugin\Pro\Models;

/**
 * Abstract class that Pro and Lite both extend.
 *
 * @since 4.0.0
 */
class Admin extends CommonAdmin\Admin {
	/**
	 * Class constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		parent::__construct();

		add_action( 'plugins_loaded', [ $this, 'loadUpdates' ] );

		if ( is_admin() ) {
			// Add the columns to page/posts.
			add_action( 'current_screen', [ $this, 'addTaxonomyColumns' ], 1 );
		}
	}

	/**
	 * Actually adds the menu items to the admin bar.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function addAdminBarMenuItems() {
		// Add an upsell to Pro.
		if ( current_user_can( 'update_plugins' ) && ! aioseo()->options->general->licenseKey ) {
			$this->adminBarMenuItems['aioseo-pro-license'] = [
				'parent' => 'aioseo-main',
				'title'  => '<span class="aioseo-menu-highlight red">' . __( 'Add License Key', 'all-in-one-seo-pack' ) . '</span>',
				'id'     => 'aioseo-pro-license',
				'href'   => esc_url( admin_url( 'admin.php?page=aioseo-settings' ) )
			];
		}

		parent::addAdminBarMenuItems();
	}

	/**
	 * Add the menu inside of WordPress.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function addMenu() {
		parent::addMenu();

		// We use the global submenu, because we are adding an external link here.
		if ( current_user_can( 'aioseo_manage_seo' ) && ! aioseo()->options->general->licenseKey ) {
			global $submenu;
			$submenu[ $this->pageSlug ][] = [
				'<span class="aioseo-menu-highlight red">' . esc_html__( 'Add License Key', 'all-in-one-seo-pack' ) . '</span>',
				apply_filters( 'aioseo_manage_seo', 'aioseo_manage_seo' ),
				esc_url( admin_url( 'admin.php?page=aioseo-settings' ) )
			];
		}
	}

	/**
	 * Update checks.
	 * This does user permission checks so we have to run it after plugins loaded.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function loadUpdates() {
		$this->updates = new Updates( [
			'pluginSlug' => 'all-in-one-seo-pack-pro',
			'pluginPath' => plugin_basename( AIOSEO_FILE ),
			'version'    => AIOSEO_VERSION,
			'key'        => aioseo()->options->general->licenseKey
		] );
	}

	/**
	 * Adds All in One SEO to the Admin Bar.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function adminBarMenu() {
		if ( aioseo()->options->advanced->adminBarMenu ) {
			parent::adminBarMenu();
		}
	}

	/**
	 * Retreive data to build the admin bar.
	 * @since 4.0.0
	 *
	 * @param  WP_Post $post The post object.
	 * @return array         An array of data to build a menu link.
	 */
	protected function getAdminBarMenuData( $post ) {
		// Don't show if we're on the home page and the home page is the latest posts.
		if ( ! is_home() || ( ! is_front_page() && ! is_home() ) ) {
			global $wp_the_query;
			$currentObject = $wp_the_query->get_queried_object();

			if ( is_category() || is_tax() || is_tag() ) {
				// SEO for taxonomies are only available in Pro version.
				$editTermLink = get_edit_term_link( $currentObject->term_id, $currentObject->taxonomy );
				return [
					'id'   => $post->ID,
					'link' => $editTermLink . '#aioseo'
				];
			}
		}

		return parent::getAdminBarMenuData( $post );
	}

	/**
	 * Check if the taxonomy should show AIOSEO column.
	 *
	 * @since 4.0.0
	 *
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return bool
	 */
	public function isTaxonomyColumn( $screen, $taxonomy ) {
		if ( 'type' === $taxonomy ) {
			$taxonomy = '_aioseo_type';
		}

		if ( 'edit-tags' === $screen ) {
			if ( aioseo()->options->advanced->taxonomies->all && in_array( $taxonomy, aioseo()->helpers->getPublicTaxonomies( true ), true ) ) {
				return true;
			}

			$taxonomies = aioseo()->options->advanced->taxonomies->included;
			if ( in_array( $taxonomy, $taxonomies, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds the AIOSEO column to taxonomies.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Screen $screen The current screen.
	 *
	 * @return void
	 */
	public function addTaxonomyColumns( $screen ) {
		if ( $this->isTaxonomyColumn( $screen->base, $screen->taxonomy ) ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueuePostsScripts' ] );
			add_filter( "manage_edit-{$screen->taxonomy}_columns", [ $this, 'postColumns' ], 10, 1 );
			add_filter( "manage_{$screen->taxonomy}_custom_column", [ $this, 'renderTaxonomyColumn' ], 10, 3 );
		}
	}

	/**
	 * Renders the column in the taxonomy table.
	 *
	 * @since 4.0.0
	 *
	 * @param  string $out        The output to display.
	 * @param  string $columnName The column name.
	 * @param  int    $termId     The current term id.
	 * @return void
	 */
	public function renderTaxonomyColumn( $out, $columnName, $termId ) {
		if ( 'aioseo-details' === $columnName ) {
			// Add this column/post to the localized array.
			global $wp_scripts, $wp_query;

			$data = $wp_scripts->get_data( 'aioseo-posts-table', 'data' );

			if ( ! is_array( $data ) ) {
				$data = json_decode( str_replace( 'var aioseo = ', '', substr( $data, 0, -1 ) ), true );
			}

			$nonce   = wp_create_nonce( "aioseo_meta_{$columnName}_{$termId}" );
			$terms   = $data['terms'];
			$theTerm = Models\Term::getTerm( $termId );

			// Turn on the tax query so we can get specific tax data.
			$originalTax      = $wp_query->is_tax;
			$wp_query->is_tax = true;

			$terms[] = [
				'id'                => $termId,
				'columnName'        => $columnName,
				'nonce'             => $nonce,
				'title'             => ! empty( $theTerm->title ) ? $theTerm->title : '',
				'titleParsed'       => aioseo()->meta->title->getTermTitle( get_term( $termId ) ),
				'description'       => ! empty( $theTerm->description ) ? $theTerm->description : '',
				'descriptionParsed' => aioseo()->meta->description->getTermDescription( get_term( $termId ) )
			];

			$wp_query->is_tax = $originalTax;
			$data['terms']    = $terms;

			$wp_scripts->add_data( 'aioseo-posts-table', 'data', '' );
			wp_localize_script( 'aioseo-posts-table', 'aioseo', $data );

			require( AIOSEO_DIR . '/app/Common/Views/admin/terms/columns.php' );
		}
	}

	/**
	 * Hooks for loading our pages.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();

		$currentScreen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		global $admin_page_hooks;

		if ( ! is_object( $currentScreen ) || empty( $currentScreen->id ) || empty( $admin_page_hooks ) ) {
			return;
		}

		$addScripts = false;
		if ( 'toplevel_page_aioseo' === $currentScreen->id ) {
			$addScripts = true;
		}

		if ( ! empty( $admin_page_hooks['aioseo'] ) && $currentScreen->id === $admin_page_hooks['aioseo'] ) {
			$addScripts = true;
		}

		if ( strpos( $currentScreen->id, 'aioseo-tools' ) !== false ) {
			$addScripts = true;
		}

		if ( ! $addScripts ) {
			return;
		}

		$this->checkAdminQueryArgs();
	}

	/**
	 * Checks the admin query args to run appropriate tasks.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function checkAdminQueryArgs() {
		parent::checkAdminQueryArgs();

		// Allow users to speed up scanning.
		if ( isset( $_GET['aioseo-video-scan'] ) && 0 < (int) wp_unslash( $_GET['aioseo-video-scan'] ) ) { // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			add_filter( 'aioseo_video_sitemap_posts_per_scan', function( $postsPerScan ) {
				return (int) wp_unslash( $_GET['aioseo-video-scan'] ); // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
			} );
			add_filter( 'aioseo_video_sitemap_terms_per_scan', function( $termsPerScan ) {
				return (int) wp_unslash( $_GET['aioseo-video-scan'] ); // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
			} );
			// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$video = aioseo()->sitemap->addons['video'];
			if ( ! empty( $video ) ) {
				aioseo()->sitemap->addons['video']['sitemap']->video->scan();
			}
		}

		// Allow users to force the plugin to rescan the site.
		if ( isset( $_GET['aioseo-video-rescan'] ) ) {
			$video = aioseo()->sitemap->addons['video'];
			if ( ! empty( $video ) ) {
				aioseo()->sitemap->addons['video']['query']->resetVideos();
			}
		}

		if ( isset( $_GET['aioseo-remove-duplicates'] ) ) {
			aioseo()->updates->removeDuplicateRecords();
		}
	}
}