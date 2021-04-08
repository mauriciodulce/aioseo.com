<?php
namespace AIOSEO\Plugin\Extend {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Main class.
	 *
	 * @since 1.0.0
	 */
	final class ImageSeo {
		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @since 1.0.0
		 *
		 * @var AIOSEO\Plugin\Extend\ImageSeo
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is retrieved from the version constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Main Addon Instance.
		 *
		 * Insures that only one instance of the addon exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return ImageSeo
		 */
		public static function instance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();
				self::$instance->constants();
				self::$instance->includes();
				self::$instance->load();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function constants() {
			$defaultHeaders = [
				'name'    => 'Plugin Name',
				'version' => 'Version',
			];

			$pluginData = get_file_data( AIOSEO_IMAGE_SEO_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_IMAGE_SEO_VERSION' => $pluginData['version']
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_IMAGE_SEO_VERSION;
		}

		/**
		 * Including the new files with PHP 5.3 style.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			$dependencies = [
				'/vendor/autoload.php'
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( AIOSEO_IMAGE_SEO_DIR . $path ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'aioseo-image-seo' ) );
				}
				require AIOSEO_IMAGE_SEO_DIR . $path;
			}
		}

		/**
		 * Load our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load() {
			require_once( AIOSEO_IMAGE_SEO_DIR . '/extend/updates.php' );
			new \AIOSEOUpdates( 'aioseo-image-seo', $this->version, plugin_basename( AIOSEO_IMAGE_SEO_FILE ) );

			aioseo()->image = new Image\Image();
		}
	}
}

namespace {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * The function which returns the one ImageSeo instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOSEO\Plugin\Extend\ImageSeo
	 */
	function aioseoImageSeo() {
		return AIOSEO\Plugin\Extend\ImageSeo::instance();
	}
}