<?php
namespace AIOSEO\Plugin\Pro\Meta;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Meta as CommonMeta;

/**
 * Handles the robots meta tag.
 *
 * @since 4.0.0
 */
class Robots extends CommonMeta\Robots {
	/**
	 * Returns the robots meta tag value.
	 *
	 * @since 4.0.0
	 *
	 * @return mixed The robots meta tag value or false.
	 */
	public function meta() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::meta();
		}

		$options  = aioseo()->options->noConflict();
		$term     = get_queried_object();
		$metaData = aioseo()->meta->metaData->getMetaData( $term );

		if ( ! empty( $metaData ) && ! $metaData->robots_default ) {
			$this->metaValues( $metaData );
			return parent::metaHelper();
		}

		if ( $options->searchAppearance->dynamic->taxonomies->has( $term->taxonomy ) ) {
			$this->globalValues( [ 'dynamic', 'taxonomies', $term->taxonomy ] );
			return parent::metaHelper();
		}

		$this->globalValues();
		return parent::metaHelper();
	}
}