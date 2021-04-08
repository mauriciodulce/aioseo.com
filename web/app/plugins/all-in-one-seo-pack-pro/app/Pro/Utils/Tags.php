<?php
namespace AIOSEO\Plugin\Pro\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils;

/**
 * Class to replace tag values with their data counterparts.
 *
 * @since 4.0.0
 */
class Tags extends Utils\Tags {
	/**
	 * An array of contexts to separate tags.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	private $proContext = [];

	/**
	 * Class constructor.
	 *
	 * @since 4.0.6
	 */
	public function __construct() {
		parent::__construct();

		$this->tags = array_merge( $this->tags, [
			[
				'id'          => 'woocommerce_sku',
				'name'        => __( 'WooCommerce SKU', 'all-in-one-seo-pack' ),
				'description' => __( 'The SKU of the WooCommerce product.', 'all-in-one-seo-pack' )
			],
			[
				'id'          => 'woocommerce_price',
				'name'        => __( 'WooCommerce Price', 'all-in-one-seo-pack' ),
				'description' => __( 'The price of the WooCommerce product.', 'all-in-one-seo-pack' )
			],
			[
				'id'          => 'woocommerce_brand',
				'name'        => __( 'WooCommerce Brand', 'all-in-one-seo-pack' ),
				'description' => __( 'The brand of the WooCommerce product (compatible with WooCommerce Brands and Perfect WooCommerce Brands plugins).', 'all-in-one-seo-pack' )
			]
		] );
	}

	/**
	 * Add the context for all the post/page types.
	 *
	 * @since 4.0.0
	 *
	 * @return array An array of contextual data.
	 */
	public function getContext() {
		$context = parent::getContext() + $this->proContext;

		$wooCommerceTags = [
			'woocommerce_sku',
			'woocommerce_brand',
			'woocommerce_price'
		];

		if ( isset( $context['productTitle'] ) ) {
			$context['productTitle'] = array_merge( $context['productTitle'], $wooCommerceTags );
		}

		if ( isset( $context['productDescription'] ) ) {
			$context['productDescription'] = array_merge( $context['productDescription'], $wooCommerceTags );
		}

		// Taxonomies including from CPT's.
		foreach ( aioseo()->helpers->getPublicTaxonomies() as $taxonomy ) {
			if ( 'category' === $taxonomy['name'] ) {
				continue;
			}

			$context[ $taxonomy['name'] . 'Title' ]       = $context['taxonomyTitle'];
			$context[ $taxonomy['name'] . 'Description' ] = $context['taxonomyDescription'];
		}

		return $context;
	}

	/**
	 * Get the default tags for the current term.
	 *
	 * @since 4.0.0
	 *
	 * @param  integer $termId The Term ID.
	 * @return array           An array of tags.
	 */
	public function getDefaultTermTags( $termId ) {
		$term = get_term( $termId );
		return [
			'title'       => aioseo()->meta->title->getTermTitle( $term, true ),
			'description' => aioseo()->meta->description->getTermDescription( $term, true )
		];
	}

	/**
	 * Get the value of the tag to replace.
	 *
	 * @since 4.0.6
	 *
	 * @param  string $tag        The tag to look for.
	 * @param  int    $id         The post ID.
	 * @param  bool   $sampleData Whether or not to fill empty values with sample data.
	 * @return string             The value of the tag.
	 */
	public function getTagValue( $tag, $id, $sampleData = false ) {
		$post     = aioseo()->helpers->getPost( $id );
		$postId   = null;
		$product  = null;
		if ( $post ) {
			$postId = empty( $id ) ? $post->ID : $id;
			if ( 'product' === $post->post_type && aioseo()->helpers->isWooCommerceActive() ) {
				$product = wc_get_product( $postId );
			}
		}

		switch ( $tag['id'] ) {
			case 'woocommerce_sku':
				if ( ! is_object( $product ) ) {
					return $sampleData ? __( 'Sample SKU', 'all-in-one-seo-pack' ) : '';
				}
				return $product ? $product->get_sku() : '';
			case 'woocommerce_price':
				if ( ! is_object( $product ) ) {
					return $sampleData ? __( '$5.99', 'all-in-one-seo-pack' ) : '';
				}
				$locale       = get_locale();
				$productPrice = $product->get_price() ? $product->get_price() : 0;
				if ( false !== strpos( $locale, 'en', 0 ) ) {
					$productPrice = number_format( $productPrice, 2, '.', ',' );
				} else {
					$productPrice = number_format( $productPrice, 2, ',', '.' );
				}
				return get_woocommerce_currency_symbol() . $productPrice;
			case 'woocommerce_brand':
				if ( ! is_object( $product ) ) {
					return $sampleData ? __( 'Sample Brand', 'all-in-one-seo-pack' ) : '';
				}
				return aioseo()->helpers->getWooCommerceBrand( $product->get_id() );
			default:
				return parent::getTagValue( $tag, $id, $sampleData );
		}
	}
}