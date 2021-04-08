<?php
namespace AIOSEO\Plugin\Pro\Social;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Social as CommonSocial;

/**
 * Handles the Open Graph meta.
 *
 * @since 4.0.0
 */
class Facebook extends CommonSocial\Facebook {
	/**
	 * Returns the Open Graph image URL.
	 *
	 * @since 4.0.0
	 *
	 * @return string The Open Graph URL.
	 */
	public function getImage() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getImage();
		}

		$term     = get_queried_object();
		$metaData = aioseo()->meta->metaData->getMetaData( $term );

		$image = '';
		if ( ! empty( $metaData ) ) {
			$imageSource = ! empty( $metaData->og_image_type ) && 'default' !== $metaData->og_image_type
				? $metaData->og_image_type
				: aioseo()->options->social->facebook->general->defaultImageSourceTerms;

			$image = aioseo()->social->image->getImage( 'facebook', $imageSource, $term );
		}
		return $image ? $image : aioseo()->helpers->getSiteLogoUrl();
	}

	/**
	 * Returns the width of the Open Graph image.
	 *
	 * @since 4.0.0
	 *
	 * @return string The image width.
	 */
	public function getImageWidth() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getImageWidth();
		}

		$image = $this->getImage();
		if ( is_array( $image ) ) {
			return $image[1];
		}

		return aioseo()->options->social->facebook->general->defaultImageTermsWidth;
	}

	/**
	 * Returns the height of the Open Graph image.
	 *
	 * @since 4.0.0
	 *
	 * @return string The image height.
	 */
	public function getImageHeight() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getImageHeight();
		}

		$image = $this->getImage();
		if ( is_array( $image ) ) {
			return $image[2];
		}
		return aioseo()->options->social->facebook->general->defaultImageTermsHeight;
	}

	/**
	 * Returns the Open Graph title for the current page.
	 *
	 * @since 4.0.0
	 *
	 * @param  WP_Post|integer $post The post object or ID (optional).
	 * @return string                The Open Graph title.
	 */
	public function getTitle( $post = null ) {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getTitle( $post );
		}

		$term     = get_queried_object();
		$metaData = aioseo()->meta->metaData->getMetaData( $term );

		$title = '';
		if ( ! empty( $metaData->og_title ) ) {
			$title = aioseo()->meta->title->prepareTitle( $metaData->og_title );
		}
		return $title ? $title : aioseo()->meta->title->getTermTitle( $term );
	}

	/**
	 * Returns the Open Graph description for the current page.
	 *
	 * @since 4.0.0
	 *
	 * @param  WP_Post|integer $post The post object or ID (optional).
	 * @return string                The Open Graph description.
	 */
	public function getDescription( $post = null ) {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getDescription( $post );
		}

		$term     = get_queried_object();
		$metaData = aioseo()->meta->metaData->getMetaData( $term );

		$description = '';
		if ( ! empty( $metaData->og_description ) ) {
			$description = aioseo()->meta->description->prepareDescription( $metaData->og_description );
		}
		return $description ? $description : aioseo()->meta->description->getTermDescription( $term );
	}

	/**
	 * Returns the Open Graph object type.
	 *
	 * @since 4.0.0
	 *
	 * @return string The object type.
	 */
	public function getObjectType() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return parent::getObjectType();
		}

		$term     = get_queried_object();
		$metaData = aioseo()->meta->metaData->getMetaData( $term );
		if ( ! empty( $metaData->og_object_type ) && 'default' !== $metaData->og_object_type ) {
			return $metaData->og_object_type;
		}

		$options           = aioseo()->options->noConflict();
		$defaultObjectType = $options->social->facebook->general->dynamic->taxonomies->has( $term->taxonomy )
			? $options->social->facebook->general->dynamic->taxonomies->{$term->taxonomy}->objectType
			: null;

		return ! empty( $defaultObjectType ) ? $defaultObjectType : 'article';
	}
}