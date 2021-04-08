<?php
namespace AIOSEO\Plugin\Extend\Image;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Extend as Extend;

/**
 * Adds support for Image SEO.
 *
 * @since 1.0.0
 */
class Image {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load();
		$this->hooks();
	}

	/**
	 * Loads our classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load() {
		$this->admin = new Extend\Admin\Admin();
		$this->api   = new Extend\Admin\Api();
		$this->tags  = new Extend\Utils\Tags();
	}

	/**
	 * Registers our hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function hooks() {
		// Filter images embedded into posts.
		add_filter( 'the_content', [ $this, 'filterContent' ] );
		// Filter images embedded in the short description of WooCommerce Products.
		add_filter( 'woocommerce_short_description', [ $this, 'filterContent' ] );
		// Filter attachment pages.
		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'filterImageAttributes' ], 10, 2 );
	}

	/**
	 * Filters the content of the requested post.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $content The post content.
	 * @return string $content The filtered post content.
	 */
	public function filterContent( $content ) {
		if ( is_admin() || ! is_singular() ) {
			return $content;
		}

		return preg_replace_callback( '/<img[^>]+/', [ $this, 'filterEmbeddedImages' ], $content, 20 );
	}

	/**
	 * Filters the attributes of image attachment pages.
	 *
	 * @since 1.0.0
	 *
	 * @param  array   $attributes The image attributes.
	 * @param  WP_Post $post       The post object.
	 * @return array   $attributes The filtered image attributes
	 */
	public function filterImageAttributes( $attributes, $post ) {
		if ( is_admin() || ! is_singular() ) {
			return $attributes;
		}

		$attributes['title'] = $this->getAttribute( 'title', $post->ID );
		$attributes['alt']   = $this->getAttribute( 'altTag', $post->ID );

		return $attributes;
	}

	/**
	 * Filters the attributes of images that are embedded in the post content.
	 *
	 * Helper function for the filterContent() method.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $images The HTML image tag (first match of Regex pattern).
	 * @return string         The filtered HTML image tag.
	 */
	public function filterEmbeddedImages( $images ) {
		$attributes = preg_split( '/(\w+=)/', $images[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$id = $this->imageId( $attributes );
		if ( ! $id ) {
			return $images[0];
		}

		$attributes = $this->insertAttribute(
			$attributes,
			'title',
			$this->getAttribute( 'title', $id )
		);

		$attributes = $this->insertAttribute(
			$attributes,
			'alt',
			$this->getAttribute( 'altTag', $id )
		);

		return implode( '', $attributes ) . ' /';
	}

	/**
	 * Tries to extract the attachment page ID of an image.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $attributes The image attributes.
	 * @return mixed             The ID of the attachment page or false if no ID could be found.
	 */
	private function imageId( $attributes ) {
		if ( ! in_array( 'class=', $attributes, true ) ) {
			return false;
		}

		$index = array_search( 'class=', $attributes, true );
		// Check if class contains an ID.
		preg_match( '#\d+#', $attributes[ $index + 1 ], $matches );

		if ( empty( $matches ) ) {
			return false;
		}
		return intval( $matches[0] );
	}

	/**
	 * Inserts a given value for a given image attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $attributes    The image attributes.
	 * @param  string $attributeName The attribute name.
	 * @param  string $value         The attribute value.
	 * @return array  $attributes    The modified image attributes.
	 */
	private function insertAttribute( $attributes, $attributeName, $value ) {
		$value = sprintf( '"%1$s"', $value );

		if ( in_array( "$attributeName=", $attributes, true ) ) {
			$index                    = array_search( "$attributeName=", $attributes, true );
			$attributes[ $index + 1 ] = $value;
		} else {
			array_push( $attributes, "$attributeName=", $value );
		}
		return $attributes;
	}

	/**
	 * Returns the value of a given image attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $attributeName The attribute name.
	 * @param  int    $id            The attachment page ID.
	 * @return string                The attribute value.
	 */
	private function getAttribute( $attributeName, $id ) {
		$format    = aioseo()->options->image->format->$attributeName;
		$attribute = aioseo()->image->tags->replaceTags( $format, $id, aioseo()->options->image->stripPunctuation->$attributeName );

		$snakeName = aioseo()->helpers->toSnakeCase( $attributeName );
		return apply_filters( "aioseo_image_seo_$snakeName", $attribute, [ $id ] );
	}
}