<?php
namespace AIOSEO\Plugin\Pro\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils as CommonUtils;

/**
 * Contains helper methods specific to the addons.
 *
 * @since 4.0.0
 */
class Addons extends CommonUtils\Addons {
	/**
	 * The licensing URL.
	 *
	 * @since 4.0.13
	 *
	 * @var string
	 */
	protected $licensingUrl = 'https://licensing.aioseo.com/v1/';

	/**
	 * Returns our addons.
	 *
	 * @since 4.0.0
	 *
	 * @param  boolean $flushCache Whether or not to flush the cache.
	 * @return array               An array of addon data.
	 */
	public function getAddons( $flushCache = false ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$addons = aioseo()->transients->get( 'addons' );
		if ( false === $addons || $flushCache ) {
			$addons = aioseo()->helpers->sendRequest( $this->getLicensingUrl() . 'addons/', $this->getAddonPayload() );
			aioseo()->transients->update( 'addons', $addons, 4 * HOUR_IN_SECONDS );
		}

		if ( ! $addons || ! empty( $addons->error ) ) {
			$addons = $this->getDefaultAddons();
			aioseo()->transients->update( 'addons', $addons, 10 * MINUTE_IN_SECONDS );
		}

		// The API request will tell us if we can activate a plugin, but let's check if its already active.
		$installedPlugins = array_keys( get_plugins() );
		foreach ( $addons as $key => $addon ) {
			$addons[ $key ]->basename   = $this->getAddonBasename( $addon->sku );
			$addons[ $key ]->installed  = in_array( $this->getAddonBasename( $addon->sku ), $installedPlugins, true );
			$addons[ $key ]->isActive   = is_plugin_active( $addons[ $key ]->basename );
			$addons[ $key ]->canInstall = $this->canInstall();
		}

		return $addons;
	}

	/**
	 * Gets the payload to send in the request.
	 *
	 * @since 4.1.0
	 *
	 * @param  string $sku The sku to use in the request.
	 * @return array       A payload array.
	 */
	protected function getAddonPayload( $sku = 'all-in-one-seo-pack-pro' ) {
		$payload            = parent::getAddonPayload( $sku );
		$payload['license'] = aioseo()->options->general->licenseKey;
		return $payload;
	}
}