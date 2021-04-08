<?php
namespace AIOSEO\Plugin\Pro\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Main as CommonMain;
use AIOSEO\Plugin\Common\Models as CommonModels;
use AIOSEO\Plugin\Pro\Models as ProModels;

/**
 * Updater class.
 *
 * @since 4.0.0
 */
class Updates extends CommonMain\Updates {
	/**
	 * Class constructor.
	 *
	 * @since 4.0.5
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'aioseo_v4_migrate_post_og_image', [ $this, 'migratePostOgImage' ] );
		add_action( 'aioseo_v4_migrate_term_og_image', [ $this, 'migrateTermOgImage' ] );
	}

	/**
	 * Runs our migrations.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function runUpdates() {
		parent::runUpdates();

		$lastActiveVersion = aioseo()->internalOptions->internal->lastActiveVersion;
		if ( version_compare( $lastActiveVersion, '4.0.0', '>=' ) && version_compare( $lastActiveVersion, '4.0.5', '<' ) ) {
			try {
				aioseo()->transients->update( 'v4_migrate_post_og_image', time(), WEEK_IN_SECONDS );
				aioseo()->transients->update( 'v4_migrate_term_og_image', time(), WEEK_IN_SECONDS );

				as_schedule_single_action( time() + 10, 'aioseo_v4_migrate_post_og_image', [], 'aioseo' );
				as_schedule_single_action( time() + 10, 'aioseo_v4_migrate_term_og_image', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		}

		if ( version_compare( $lastActiveVersion, '4.0.0', '>=' ) && version_compare( $lastActiveVersion, '4.0.6', '<' ) ) {
			$this->migratedGoogleAnalyticsToDeprecated();
		}

		if ( version_compare( $lastActiveVersion, '4.0.6', '<' ) ) {
			$this->disableTwitterUseOgDefault();
			$this->updateMaxImagePreviewDefault();
		}
	}

	/**
	 * Adds custom tables for V4.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function addInitialCustomTablesForV4() {
		parent::addInitialCustomTablesForV4();

		$db             = aioseo()->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		if ( ! aioseo()->db->tableExists( 'aioseo_terms' ) ) {
			$tableName = $db->prefix . 'aioseo_terms';

			aioseo()->db->execute(
				"CREATE TABLE {$tableName} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					term_id bigint(20) unsigned NOT NULL,
					title text DEFAULT NULL,
					description text DEFAULT NULL,
					keywords mediumtext DEFAULT NULL,
					keyphrases longtext DEFAULT NULL,
					page_analysis longtext DEFAULT NULL,
					canonical_url text DEFAULT NULL,
					og_title text DEFAULT NULL,
					og_description text DEFAULT NULL,
					og_object_type varchar(64) DEFAULT 'default',
					og_image_type varchar(64) DEFAULT 'default',
					og_image_custom_url text DEFAULT NULL,
					og_image_custom_fields text DEFAULT NULL,
					og_custom_image_width int(11) DEFAULT NULL,
					og_custom_image_height int(11) DEFAULT NULL,
					og_video varchar(255) DEFAULT NULL,
					og_custom_url text DEFAULT NULL,
					og_article_section text DEFAULT NULL,
					og_article_tags text DEFAULT NULL,
					twitter_use_og tinyint(1) DEFAULT 1,
					twitter_card varchar(64) DEFAULT 'default',
					twitter_image_type varchar(64) DEFAULT 'default',
					twitter_image_custom_url text DEFAULT NULL,
					twitter_image_custom_fields text DEFAULT NULL,
					twitter_title text DEFAULT NULL,
					twitter_description text DEFAULT NULL,
					seo_score int(11) DEFAULT 0 NOT NULL,
					pillar_content tinyint(1) DEFAULT NULL,
					robots_default tinyint(1) DEFAULT 1 NOT NULL,
					robots_noindex tinyint(1) DEFAULT 0 NOT NULL,
					robots_noarchive tinyint(1) DEFAULT 0 NOT NULL,
					robots_nosnippet tinyint(1) DEFAULT 0 NOT NULL,
					robots_nofollow tinyint(1) DEFAULT 0 NOT NULL,
					robots_noimageindex tinyint(1) DEFAULT 0 NOT NULL,
					robots_noodp tinyint(1) DEFAULT 0 NOT NULL,
					robots_notranslate tinyint(1) DEFAULT 0 NOT NULL,
					robots_max_snippet int(11) DEFAULT NULL,
					robots_max_videopreview int(11) DEFAULT NULL,
					robots_max_imagepreview varchar(20) DEFAULT 'none',
					priority tinytext DEFAULT NULL,
					frequency tinytext DEFAULT NULL,
					images longtext DEFAULT NULL,
					videos longtext DEFAULT NULL,
					video_scan_date datetime DEFAULT NULL,
					tabs mediumtext DEFAULT NULL,
					local_seo longtext DEFAULT NULL,
					created datetime NOT NULL,
					updated datetime NOT NULL,
					PRIMARY KEY (id),
					KEY ndx_aioseo_terms_term_id (term_id)
				) {$charsetCollate};"
			);
		}
	}

	/**
	 * Sets the default social images.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function setDefaultSocialImages() {
		parent::setDefaultSocialImages();

		$siteLogo = aioseo()->helpers->getSiteLogoUrl();
		if ( $siteLogo ) {
			if ( ! aioseo()->options->social->facebook->general->defaultImageTerms ) {
				aioseo()->options->social->facebook->general->defaultImageTerms = $siteLogo;
			}
			if ( ! aioseo()->options->social->twitter->general->defaultImageTerms ) {
				aioseo()->options->social->twitter->general->defaultImageTerms = $siteLogo;
			}
		}
	}

	/**
	 * Migrates the post OG images for users between 4.0.0 and 4.0.4 again to fix a bug.
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function migratePostOgImage() {
		// If the v3 migration is still running, postpone this.
		if ( aioseo()->transients->get( 'v3_migration_in_progress_posts' ) ) {
			try {
				as_schedule_single_action( time() + 300, 'aioseo_v4_migrate_post_og_image', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
			return;
		}

		$postsPerAction  = 200;
		$publicPostTypes = implode( "', '", aioseo()->helpers->getPublicPostTypes( true ) );
		$timeStarted     = gmdate( 'Y-m-d H:i:s', aioseo()->transients->get( 'v4_migrate_post_og_image' ) );

		$postsToMigrate = aioseo()->db
			->start( 'posts' . ' as p' )
			->select( 'p.ID' )
			->leftJoin( 'aioseo_posts as ap', '`p`.`ID` = `ap`.`post_id`' )
			->whereRaw( "( ap.post_id IS NULL OR ap.updated < '$timeStarted' )" )
			->whereRaw( "( p.post_type IN ( '$publicPostTypes' ) )" )
			->orderBy( 'p.ID DESC' )
			->limit( $postsPerAction )
			->run()
			->result();

		if ( ! $postsToMigrate || ! count( $postsToMigrate ) ) {
			aioseo()->transients->delete( 'v4_migrate_post_og_image' );
			return;
		}

		foreach ( $postsToMigrate as $post ) {
			$postMeta = aioseo()->db
				->start( 'postmeta' . ' as pm' )
				->select( 'pm.meta_key, pm.meta_value' )
				->where( 'pm.post_id', $post->ID )
				->whereRaw( "`pm`.`meta_key` LIKE '_aioseop_opengraph_settings'" )
				->run()
				->result();

			$aioseoPost = CommonModels\Post::getPost( $post->ID );
			$meta       = [
				'post_id' => $post->ID,
			];

			if ( ! $postMeta || ! count( $postMeta ) ) {
				$aioseoPost->set( $meta );
				$aioseoPost->save();
				continue;
			}

			foreach ( $postMeta as $record ) {
				$name  = $record->meta_key;
				$value = $record->meta_value;

				if ( '_aioseop_opengraph_settings' !== $name ) {
					continue;
				}

				$ogMeta = maybe_unserialize( $value );
				if ( ! is_array( $ogMeta ) ) {
					continue;
				}

				foreach ( $ogMeta as $name => $value ) {
					if (
						'aioseop_opengraph_settings_image' !== $name ||
						(
							in_array( 'aioseop_opengraph_settings_customimg', array_keys( $ogMeta ), true ) &&
							! empty( $post->og_image_custom_url ) &&
							$post->og_image_custom_url !== $ogMeta['aioseop_opengraph_settings_customimg']
						)
					) {
						continue;
					}

					$meta['og_image_type']       = 'custom_image';
					$meta['og_image_custom_url'] = esc_url( $value );
					break;
				}
			}

			$aioseoPost->set( $meta );
			$aioseoPost->save();
		}

		if ( count( $postsToMigrate ) === $postsPerAction ) {
			try {
				as_schedule_single_action( time() + 10, 'aioseo_v4_migrate_post_og_image', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		} else {
			aioseo()->transients->delete( 'v4_migrate_post_og_image' );
		}
	}

	/**
	 * Migrates the term OG images for users between 4.0.0 and 4.0.4 again to fix a bug.
	 *
	 * @since 4.0.5
	 *
	 * @return void
	 */
	public function migrateTermOgImage() {
		// If the v3 migration is still running, postpone this.
		if ( aioseo()->transients->get( 'v3_migration_in_progress_terms' ) ) {
			try {
				as_schedule_single_action( time() + 300, 'aioseo_v4_migrate_term_og_image', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
			return;
		}

		$termsPerAction   = 200;
		$publicTaxonomies = implode( "', '", aioseo()->helpers->getPublicTaxonomies( true ) );
		$timeStarted      = gmdate( 'Y-m-d H:i:s', aioseo()->transients->get( 'v4_migrate_term_og_image' ) );

		$termsToMigrate = aioseo()->db
			->start( 'terms' . ' as t' )
			->select( 't.term_id' )
			->leftJoin( 'aioseo_terms as at', '`t`.`term_id` = `at`.`term_id`' )
			->leftJoin( 'term_taxonomy as tt', '`t`.`term_id` = `tt`.`term_id`' )
			->whereRaw( "( at.term_id IS NULL OR at.updated < '$timeStarted' )" )
			->whereRaw( "( tt.taxonomy IN ( '$publicTaxonomies' ) )" )
			->orderBy( 't.term_id DESC' )
			->limit( $termsPerAction )
			->run()
			->result();

		if ( ! $termsToMigrate || ! count( $termsToMigrate ) ) {
			aioseo()->transients->delete( 'v4_migrate_term_og_image' );
			return;
		}

		foreach ( $termsToMigrate as $term ) {
			$termMeta = aioseo()->db
				->start( 'termmeta' . ' as tm' )
				->select( '`tm`.`meta_key`, `tm`.`meta_value`' )
				->where( 'tm.term_id', $term->term_id )
				->whereRaw( "`tm`.`meta_key` LIKE '_aioseop_opengraph_settings%'" )
				->run()
				->result();

			$aioseoTerm = ProModels\Term::getTerm( $term->term_id );
			$meta       = [
				'term_id' => $term->term_id
			];

			if ( ! $termMeta || ! count( $termMeta ) ) {
				$aioseoTerm->set( $meta );
				$aioseoTerm->save();
				continue;
			}

			foreach ( $termMeta as $record ) {
				$name  = $record->meta_key;
				$value = $record->meta_value;

				if ( '_aioseop_opengraph_settings' !== $name ) {
					continue;
				}

				$ogMeta = maybe_unserialize( $value );
				if ( ! is_array( $ogMeta ) ) {
					continue;
				}

				foreach ( $ogMeta as $name => $value ) {
					if (
						'aioseop_opengraph_settings_image' !== $name ||
						(
							in_array( 'aioseop_opengraph_settings_customimg', array_keys( $ogMeta ), true ) &&
							! empty( $term->og_image_custom_url ) &&
							$term->og_image_custom_url !== $ogMeta['aioseop_opengraph_settings_customimg']
						)
					) {
						continue;
					}

					$meta['og_image_type']       = 'custom_image';
					$meta['og_image_custom_url'] = esc_url( $value );
					break;
				}
			}

			$aioseoTerm->set( $meta );
			$aioseoTerm->save();
		}

		if ( count( $termsToMigrate ) === $termsPerAction ) {
			try {
				as_schedule_single_action( time() + 10, 'aioseo_v4_migrate_term_og_image', [], 'aioseo' );
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		} else {
			aioseo()->transients->delete( 'v4_migrate_term_og_image' );
		}
	}


	/**
	 * Deprecate already migrated googleAnalytics.
	 *
	 * @since 4.0.6
	 *
	 * @return void
	 */
	public function migratedGoogleAnalyticsToDeprecated() {
		$options = $this->getRawOptions();
		if (
			! empty( $options['webmasterTools'] ) &&
			! empty( $options['webmasterTools']['googleAnalytics'] )
		) {
			foreach ( $options['webmasterTools']['googleAnalytics'] as $name => $value ) {
				aioseo()->options->deprecated->webmasterTools->googleAnalytics->$name = $value;
			}
		}
	}

	/**
	 * Retrieve the raw options from the database for migration.
	 *
	 * @since 4.0.6
	 *
	 * @return array An array of options.
	 */
	private function getRawOptions() {
		// Options from the DB.
		$commonOptions = json_decode( get_option( aioseo()->options->optionsName ), true );
		if ( empty( $commonOptions ) ) {
			$commonOptions = [];
		}

		$proOptions = json_decode( get_option( aioseo()->options->optionsName . '_pro' ), true );
		if ( empty( $proOptions ) ) {
			$proOptions = [];
		}

		return array_merge_recursive( $commonOptions, $proOptions );
	}

	/**
	 * Modifes the default value of the twitter_use_og column.
	 *
	 * @since 4.0.6
	 *
	 * @return void
	 */
	public function disableTwitterUseOgDefault() {
		parent::disableTwitterUseOgDefault();

		if ( aioseo()->db->tableExists( 'aioseo_terms' ) ) {
			$tableName = aioseo()->db->db->prefix . 'aioseo_terms';
			aioseo()->db->execute(
				"ALTER TABLE {$tableName}
				MODIFY twitter_use_og tinyint(1) DEFAULT 0"
			);
		}
	}

	/**
	 * Modifes the default value of the robots_max_imagepreview column.
	 *
	 * @since 4.0.6
	 *
	 * @return void
	 */
	public function updateMaxImagePreviewDefault() {
		parent::updateMaxImagePreviewDefault();

		if ( aioseo()->db->tableExists( 'aioseo_terms' ) ) {
			$tableName = aioseo()->db->db->prefix . 'aioseo_terms';
			aioseo()->db->execute(
				"ALTER TABLE {$tableName}
				MODIFY robots_max_imagepreview varchar(20) DEFAULT 'large'"
			);
		}
	}

	/**
	 * Deletes duplicate records in our custom tables.
	 *
	 * @since 4.0.13
	 *
	 * @return void
	 */
	public function removeDuplicateRecords() {
		parent::removeDuplicateRecords();

		$duplicates = aioseo()->db->start( 'aioseo_terms' )
			->select( 'term_id, min(id) as id' )
			->groupBy( 'term_id having count(term_id) > 1' )
			->orderBy( 'count(term_id) DESC' )
			->run()
			->result();

		if ( empty( $duplicates ) ) {
			return;
		}

		foreach ( $duplicates as $duplicate ) {
			$termId        = $duplicate->term_id;
			$firstRecordId = $duplicate->id;

			aioseo()->db->delete( 'aioseo_terms' )
				->whereRaw( "( id > $firstRecordId AND term_id = $termId )" )
				->run();
		}
	}
}