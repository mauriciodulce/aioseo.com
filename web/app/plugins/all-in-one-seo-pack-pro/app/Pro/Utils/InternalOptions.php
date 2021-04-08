<?php
namespace AIOSEO\Plugin\Pro\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils as CommonUtils;

/**
 * Class that holds all internal options for AIOSEO.
 *
 * @since 4.0.0
 */
class InternalOptions extends CommonUtils\InternalOptions {
	/**
	 * Defaults options for Pro.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	private $proDefaults = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'internal' => [
			'activated'      => [ 'type' => 'number', 'default' => 0 ],
			'firstActivated' => [ 'type' => 'number', 'default' => 0 ],
			'installed'      => [ 'type' => 'number', 'default' => 0 ],
			'license'        => [
				'expires'          => [ 'type' => 'number', 'default' => 0 ],
				'expired'          => [ 'type' => 'boolean', 'default' => false ],
				'invalid'          => [ 'type' => 'boolean', 'default' => false ],
				'disabled'         => [ 'type' => 'boolean', 'default' => false ],
				'connectionError'  => [ 'type' => 'boolean', 'default' => false ],
				'activationsError' => [ 'type' => 'boolean', 'default' => false ],
				'requestError'     => [ 'type' => 'boolean', 'default' => false ],
				'lastChecked'      => [ 'type' => 'number', 'default' => 0 ],
				'level'            => [ 'type' => 'string' ],
				'addons'           => [ 'type' => 'array', 'default' => [] ]
			]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * Class constructor
	 *
	 * @since 4.0.0
	 */
	public function __construct( $optionsName = 'aioseo_options_internal' ) {
		parent::__construct( $optionsName );

		$this->init();
	}

	/**
	 * Initializes the options.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		$dbOptions = json_decode( get_option( $this->optionsName . '_pro' ), true );
		if ( empty( $dbOptions ) ) {
			$dbOptions = [];
		}

		// Refactor options.
		$this->defaultsMerged = array_replace_recursive( $this->defaults, $this->proDefaults );

		$options = array_replace_recursive(
			$this->options,
			$this->addValueToValuesArray( $this->options, $dbOptions )
		);

		$this->options = apply_filters( 'aioseo_get_options_internal_pro', $options );
	}

	/**
	 * Updates the options in the database.
	 *
	 * @since 4.0.0
	 *
	 * @param  array|null $options An optional options array.
	 * @return void
	 */
	public function update( $options = null ) {
		$optionsBefore = $this->options;
		parent::update( $options );
		$this->options = $optionsBefore;

		// First, we need to filter our options.
		$options = $this->filterOptions( $this->proDefaults );

		// Refactor options.
		$refactored = $this->convertOptionsToValues( $options );

		$this->resetGroups();

		update_option( $this->optionsName . '_pro', wp_json_encode( $refactored ) );

		$this->init();
	}
}