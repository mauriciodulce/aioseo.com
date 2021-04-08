<?php
namespace AIOSEO\Plugin\Pro\Social;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Social as CommonSocial;

/**
 * Handles our social meta.
 *
 * @since 4.0.0
 */
class Output extends CommonSocial\Output {
	/**
	 * Returns the social meta for the current page.
	 *
	 * @since 4.0.0
	 *
	 * @return array The social meta.
	 */
	public function getMeta() {
		if (
			! is_category() &&
			! is_tag() &&
			! is_tax()
		) {
			return parent::getMeta();
		}

		return $this->getMetaHelper();
	}
}