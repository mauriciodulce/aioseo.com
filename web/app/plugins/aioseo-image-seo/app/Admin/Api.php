<?php
namespace AIOSEO\Plugin\Extend\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API class for the admin.
 *
 * @since 1.0.0
 */
class Api {
	/**
	 * Class contructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'hooks' ] );
	}

	/**
	 * Registers our hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'rest_pre_serve_request', [ $this, 'allowHeaders' ] );
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Sets headers that are allowed for our API routes.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function allowHeaders() {
		aioseo()->api->allowHeaders();
	}

	/**
	 * Registers the API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerRoutes() {
		register_rest_route(
			aioseo()->api->namespace,
			'image-meta',
			[
				'methods'             => 'POST',
				'permission_callback' => [ aioseo()->api, 'validRequest' ],
				'callback'            => [ $this, 'saveImageMeta' ]
			]
		);
	}

	/**
	 * Save the image meta.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public function saveImageMeta( $request ) {
		$body       = $request->get_json_params();
		$postId     = ! empty( $body['postId'] ) ? intval( $body['postId'] ) : null;
		$columnName = ! empty( $body['key'] ) ? sanitize_text_field( $body['key'] ) : null;
		$value      = ! empty( $body['value'] ) ? sanitize_text_field( $body['value'] ) : null;

		if ( ! $postId || ! $columnName || ! $value ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Post ID, column name or value is missing.'
			], 400 );
		}

		$columnTitles = [
			'aioseo-image-title',
			'aioseo-image-alt-tag'
		];

		if ( ! current_user_can( 'edit_post', $postId ) && ! current_user_can( 'aioseo_manage_seo' ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 401 );
		}

		if ( ! in_array( $columnName, $columnTitles, true ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Column name could not be identified.'
			], 400 );
		}

		switch ( $columnName ) {
			case 'aioseo-image-title':
				wp_update_post(
					[
						'ID'         => $postId,
						'post_title' => $value,
					]
				);
				break;
			case 'aioseo-image-alt-tag':
				update_post_meta( $postId, '_wp_attachment_image_alt', $value );
				break;
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}
}