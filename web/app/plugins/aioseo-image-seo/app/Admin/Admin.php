<?php
namespace AIOSEO\Plugin\Extend\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models;

/**
 * Adds our Image SEO columns to the Media Library.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'hooks' ], 2 );
	}

	/**
	 * Registers our hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hooks() {
		global $pagenow;

		if ( is_admin() && ! empty( $pagenow ) && ( 'upload.php' === $pagenow ) ) {
			remove_action( 'manage_media_custom_column', [ aioseo()->admin, 'renderColumn' ] );
			add_action( 'manage_media_custom_column', [ $this, 'renderColumn' ], 10, 2 );
		}
	}

	/**
	 * Renders the column in the Media Library.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $columnName The column name.
	 * @param  int    $postId     The current rows, post id.
	 * @return void
	 */
	public function renderColumn( $columnName, $postId ) {
		$supportedMimeTypes = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/gif' ];
		$mimeType           = get_post_mime_type( $postId );

		if ( ( ! current_user_can( 'edit_post', $postId ) && ! current_user_can( 'aioseo_manage_seo' ) ) || ! in_array( $mimeType, $supportedMimeTypes, true ) || 'aioseo-details' !== $columnName ) {
			return;
		}

		// Add this column/post to the localized array.
		global $wp_scripts;

		$data = $wp_scripts->get_data( 'aioseo-posts-table', 'data' );

		if ( ! is_array( $data ) ) {
			$data = json_decode( str_replace( 'var aioseo = ', '', substr( $data, 0, -1 ) ), true );
		}

		$nonce   = wp_create_nonce( "aioseo_meta_{$columnName}_{$postId}" );
		$posts   = $data['posts'];
		$thePost = Models\Post::getPost( $postId );
		$posts[] = [
			'id'          => $postId,
			'columnName'  => $columnName,
			'nonce'       => $nonce,
			'title'       => $thePost->title,
			'description' => $thePost->description,
			'imageTitle'  => get_the_title( $postId ),
			'imageAltTag' => get_post_meta( $postId, '_wp_attachment_image_alt', true ),
			'showMedia'   => true
		];

		$data['posts'] = $posts;

		$wp_scripts->add_data( 'aioseo-posts-table', 'data', '' );
		wp_localize_script( 'aioseo-posts-table', 'aioseo', $data );

		require( AIOSEO_DIR . '/app/Common/Views/admin/posts/columns.php' );
	}
}