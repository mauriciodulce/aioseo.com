<?php
namespace AIOSEO\Plugin\Pro\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils as CommonUtils;

/**
 * Manages capabilities for our users.
 *
 * @since 4.0.0
 */
class Access extends CommonUtils\Access {
	/**
	 * Class constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->roles = array_merge( $this->roles, [
			'editor'         => 'editor',
			'author'         => 'author',
			'aioseo_manager' => 'seoManager',
			'aioseo_editor'  => 'seoEditor'
		] );
	}

	/**
	 * Checks if the current user has the capability.
	 *
	 * @since 4.0.0
	 *
	 * @param  string      $capability The capability to check against.
	 * @param  string|null $checkRole  A role to check against.
	 * @return bool                    Whether or not the user has this capability.
	 */
	public function hasCapability( $capability, $checkRole = null ) {
		if ( ! $checkRole && $this->isAdmin() ) {
			return true;
		}

		if ( $checkRole ) {
			if ( 'administrator' === $checkRole || 'superadmin' === $checkRole ) {
				return true;
			}

			$role = get_role( $checkRole );
			// Anyone with install plugins can remain.
			if ( $role && $role->has_cap( 'install_plugins' ) ) {
				return true;
			}
		}

		$options = aioseo()->options->noConflict();
		foreach ( $this->roles as $wpRole => $role ) {
			if ( $checkRole ) {
				if ( $role !== $checkRole ) {
					continue;
				}
			} else {
				// We don't need to loop here for superadmins.
				if ( 'superadmin' === $role ) {
					continue;
				}
			}

			$newCapability = str_replace( 'aioseo_', '', $capability );
			if ( $checkRole || current_user_can( $wpRole ) ) {
				if ( 'aioseo_about_us_page' === $capability ) {
					return true;
				}

				$hasRole = $options->accessControl->has( $role );
				if ( $hasRole && $options->accessControl->$role->useDefault ) {
					return $options
						->accessControl
						->{ $role }
						->getDefault( aioseo()->helpers->toCamelCase( $newCapability ) );
				}

				return $hasRole && $options
					->accessControl
					->{ $role }
					->{ aioseo()->helpers->toCamelCase( $newCapability ) };
			}
		}
	}

	/**
	 * Adds capabilities into WordPress for the current user.
	 * Only on activation or settings saved.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function addCapabilities() {
		parent::addCapabilities();

		foreach ( $this->roles as $wpRole => $role ) {
			// Role doesn't exist, let's add it in.
			if ( in_array( $wpRole,
				[
					'aioseo_manager',
					'aioseo_editor'
				], true
			) ) {
				add_role( $wpRole, ucwords( str_replace( 'aioseo_', 'SEO ', $wpRole ) ), [
					'edit_others_posts'    => true,
					'edit_others_pages'    => true,
					'edit_pages'           => true,
					'edit_posts'           => true,
					'edit_posts'           => true,
					'edit_private_pages'   => true,
					'edit_private_posts'   => true,
					'edit_published_pages' => true,
					'edit_published_posts' => true,
					'manage_categories'    => true,
					'read_private_pages'   => true,
					'read_private_posts'   => true,
					'read'                 => true
				] );
			}

			$roleObject = get_role( $wpRole );
			if ( ! is_object( $roleObject ) ) {
				continue;
			}

			if ( 'superadmin' === $role || 'administrator' === $role ) {
				continue;
			}

			if ( $roleObject->has_cap( 'install_plugins' ) ) {
				continue;
			}

			foreach ( $this->getAllCapabilities( $role ) as $capability => $enabled ) {
				if ( $enabled ) {
					$roleObject->add_cap( $capability );
				} else {
					$roleObject->remove_cap( $capability );
				}
			}
		}

		// Let addons add their own Access Control.
		foreach ( aioseo()->addons->getLoadedAddons() as $addon ) {
			if ( isset( $addon->access ) && method_exists( $addon->access, 'addCapabilities' ) ) {
				$addon->access->addCapabilities();
			}
		}
	}

	/**
	 * Checks if the passed in role can manage AIOSEO.
	 *
	 * @since 4.0.0
	 *
	 * @param  string $role The role to check against.
	 * @return bool         Whether or not the user can manage AIOSEO.
	 */
	protected function canManage( $role ) {
		if ( $this->isAdmin( $role ) ) {
			return true;
		}

		if ( ! aioseo()->options->accessControl->has( $role ) ) {
			return false;
		}

		if ( in_array( $role, [ 'editor', 'administrator' ], true ) && aioseo()->options->accessControl->$role->useDefault ) {
			return true;
		}

		foreach ( aioseo()->options->accessControl->$role->all() as $ac => $enabled ) {
			// We are not looking for page settings here.
			if ( ! $enabled || false !== strpos( $ac, 'page' ) ) {
				continue;
			}

			if ( $ac ) {
				return true;
			}
		}

		return false;
	}
}