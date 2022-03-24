<?php
/**
 * Webfonts API class.
 *
 * @package Gutenberg
 */

/**
 * Class WP_Webfonts
 */
class WP_Webfonts {

	/**
	 * An array of registered webfonts.
	 *
	 * @var array[]
	 */
	private $registered_webfonts = array();

	/**
	 * An array of enqueued webfonts.
	 *
	 * @var array[]
	 */
	private $enqueued_webfonts = array();

	/**
	 * An array of registered providers.
	 *
	 * @var array
	 */
	private $providers = array();

	/**
	 * Stylesheet handle.
	 *
	 * @var string
	 */
	private $stylesheet_handle = '';

	/**
	 * Init.
	 */
	public function init() {

		// Register default providers.
		$this->register_provider( 'local', 'WP_Webfonts_Provider_Local' );

		// Register callback to generate and enqueue styles.
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->stylesheet_handle = 'webfonts-footer';
			$hook                    = 'wp_print_footer_scripts';
		} else {
			$this->stylesheet_handle = 'webfonts';
			$hook                    = 'wp_enqueue_scripts';
		}
		add_action( $hook, array( $this, 'generate_and_enqueue_styles' ) );

		// Enqueue webfonts in the block editor.
		add_action( 'admin_init', array( $this, 'generate_and_enqueue_editor_styles' ) );
	}

	/**
	 * Get the list of registered fonts.
	 *
	 * @return array[]
	 */
	public function get_registered_webfonts() {
		return $this->registered_webfonts;
	}

	/**
	 * Get the list of enqueued fonts.
	 *
	 * @return array[]
	 */
	public function get_enqueued_webfonts() {
		return $this->enqueued_webfonts;
	}

	/**
	 * Get the list of all fonts.
	 *
	 * @return array[]
	 */
	public function get_all_webfonts() {
		return array_merge( $this->get_registered_webfonts(), $this->get_enqueued_webfonts() );
	}

	/**
	 * Get the list of providers.
	 *
	 * @return array
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Register a webfont.
	 *
	 * @param array $webfont The font argument.
	 */
	public function register_webfont( $webfont ) {
		$webfont = $this->validate_webfont( $webfont );

		// If not valid, bail out.
		if ( ! $webfont ) {
			return false;
		}

		$slug = $this->get_font_slug( $webfont );

		// Initialize a new font-family collection.
		if ( ! isset( $this->registered_webfonts[ $slug ] ) ) {
			$this->registered_webfonts[ $slug ] = array();
		}

		$this->registered_webfonts[ $slug ][] = $webfont;
	}

	/**
	 * Enqueue a webfont.
	 *
	 * If a string is passed, it enqueues a font-family that has been already registered.
	 * If an array is passed, it registers and enqueues a font-family.
	 *
	 * @param string|array $webfont The webfont to be enqueued.
	 */
	public function enqueue_webfont( $webfont ) {
		$slug = $this->get_font_slug( $webfont );

		if ( ! isset( $this->enqueued_webfonts[ $slug ] ) ) {
			$this->enqueued_webfonts[ $slug ] = array();
		}

		if ( isset( $this->enqueued_webfonts[ $slug ] ) ) {
			$enqueued_font_faces = $this->enqueued_webfonts[ $slug ];
			foreach ( $enqueued_font_faces as $enqueued_font_face ) {
				if (
					$enqueued_font_face['font-style'] === $webfont['font-style'] &&
					$enqueued_font_face['font-display'] === $webfont['font-display']
				) {
					trigger_error(
						sprintf(
							/* translators: %s unique slug to identify the webfont */
							__( 'The "%s" font family is already enqueued.', 'gutenberg' ),
							$slug
						)
					);

					return false;
				}
			}
		}

		if ( ! isset( $this->registered_webfonts[ $slug ] ) ) {
			if ( is_string( $webfont ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( __( 'The "%s" font family is not registered.' ), $slug ), '6.0.0' );
				return false;
			}

			$this->register_webfont( $webfont );
		}

		$this->enqueued_webfonts[ $slug ][] = $webfont;

		// Finds any registered font faces that match the enqueued font faces
		// and removes it from the registered webfonts registry.
		$this->registered_webfonts[ $slug ] = array_filter(
			$this->registered_webfonts[ $slug ],
			function ( $registered_webfont ) use ( $webfont ) {
				if (
					$registered_webfont['font-style'] === $webfont['font-style'] &&
					$registered_webfont['font-display'] === $webfont['font-display']
				) {
					return false;
				}
				return true;
			}
		);

		if ( empty( $this->registered_webfonts[ $slug ] ) ) {
			unset( $this->registered_webfonts[ $slug ] );
		}
	}

	/**
	 * Get the font slug.
	 *
	 * @param array|string $to_convert The value to convert into a slug. Expected as the web font's array or a font-family as a string.
	 */
	public static function get_font_slug( $to_convert ) {
		if ( is_array( $to_convert ) ) {
			if ( isset( $to_convert['font-family'] ) ) {
				$to_convert = $to_convert['font-family'];
			} elseif ( isset( $to_convert['fontFamily'] ) ) {
				$to_convert = $to_convert['fontFamily'];
			} else {
				_doing_it_wrong( __FUNCTION__, __( 'Could not determine the font family name.' ), '6.0.0' );
				return false;
			}
		}

		return sanitize_title( $to_convert );
	}

	/**
	 * Validate a webfont.
	 *
	 * @param array $webfont The webfont arguments.
	 *
	 * @return array|false The validated webfont arguments, or false if the webfont is invalid.
	 */
	public function validate_webfont( $webfont ) {
		$webfont = wp_parse_args(
			$webfont,
			array(
				'provider'     => 'local',
				'font-family'  => '',
				'font-style'   => 'normal',
				'font-weight'  => '400',
				'font-display' => 'fallback',
			)
		);

		// Check the font-family.
		if ( empty( $webfont['font-family'] ) || ! is_string( $webfont['font-family'] ) ) {
			trigger_error( __( 'Webfont font family must be a non-empty string.', 'gutenberg' ) );
			return false;
		}

		// Local fonts need a "src".
		if ( 'local' === $webfont['provider'] ) {
			// Make sure that local fonts have 'src' defined.
			if ( empty( $webfont['src'] ) || ( ! is_string( $webfont['src'] ) && ! is_array( $webfont['src'] ) ) ) {
				trigger_error( __( 'Webfont src must be a non-empty string or an array of strings.', 'gutenberg' ) );
				return false;
			}
		}

		// Validate the 'src' property.
		if ( ! empty( $webfont['src'] ) ) {
			foreach ( (array) $webfont['src'] as $src ) {
				if ( empty( $src ) || ! is_string( $src ) ) {
					trigger_error( __( 'Each webfont src must be a non-empty string.', 'gutenberg' ) );
					return false;
				}
			}
		}

		// Check the font-weight.
		if ( ! is_string( $webfont['font-weight'] ) && ! is_int( $webfont['font-weight'] ) ) {
			trigger_error( __( 'Webfont font weight must be a properly formatted string or integer.', 'gutenberg' ) );
			return false;
		}

		// Check the font-display.
		if ( ! in_array( $webfont['font-display'], array( 'auto', 'block', 'fallback', 'swap' ), true ) ) {
			$webfont['font-display'] = 'fallback';
		}

		$valid_props = array(
			'ascend-override',
			'descend-override',
			'font-display',
			'font-family',
			'font-stretch',
			'font-style',
			'font-weight',
			'font-variant',
			'font-feature-settings',
			'font-variation-settings',
			'line-gap-override',
			'size-adjust',
			'src',
			'unicode-range',

			// Exceptions.
			'provider',
		);

		foreach ( $webfont as $prop => $value ) {
			if ( ! in_array( $prop, $valid_props, true ) ) {
				unset( $webfont[ $prop ] );
			}
		}

		return $webfont;
	}

	/**
	 * Register a provider.
	 *
	 * @param string $provider The provider name.
	 * @param string $class    The provider class name.
	 *
	 * @return bool Whether the provider was registered successfully.
	 */
	public function register_provider( $provider, $class ) {
		if ( empty( $provider ) || empty( $class ) ) {
			return false;
		}
		$this->providers[ $provider ] = $class;
		return true;
	}

	/**
	 * Generate and enqueue webfonts styles.
	 */
	public function generate_and_enqueue_styles() {
		// Generate the styles.
		$styles = $this->generate_styles( $this->get_enqueued_webfonts() );

		// Bail out if there are no styles to enqueue.
		if ( '' === $styles ) {
			return;
		}

		// Enqueue the stylesheet.
		wp_register_style( $this->stylesheet_handle, '' );
		wp_enqueue_style( $this->stylesheet_handle );

		// Add the styles to the stylesheet.
		wp_add_inline_style( $this->stylesheet_handle, $styles );
	}

	/**
	 * Generate and enqueue editor styles.
	 */
	public function generate_and_enqueue_editor_styles() {
		// Generate the styles.
		$styles = $this->generate_styles( $this->get_all_webfonts() );

		// Bail out if there are no styles to enqueue.
		if ( '' === $styles ) {
			return;
		}

		wp_add_inline_style( 'wp-block-library', $styles );
	}

	/**
	 * Generate styles for webfonts.
	 *
	 * @since 6.0.0
	 *
	 * @param array[] $font_families Font families and each of their webfonts.
	 * @return string $styles Generated styles.
	 */
	public function generate_styles( $font_families ) {
		$styles    = '';
		$providers = $this->get_providers();

		$webfonts = array();

		// Grab only the font face declarations from $font_families.
		foreach ( $font_families as $font_family ) {
			foreach ( $font_family as $font_face ) {
				$webfonts[] = $font_face;
			}
		}

		// Group webfonts by provider.
		$webfonts_by_provider = array();
		foreach ( $webfonts as $slug => $webfont ) {
			$provider = $webfont['provider'];
			if ( ! isset( $providers[ $provider ] ) ) {
				/* translators: %s is the provider name. */
				error_log( sprintf( __( 'Webfont provider "%s" is not registered.', 'gutenberg' ), $provider ) );
				continue;
			}
			$webfonts_by_provider[ $provider ]          = isset( $webfonts_by_provider[ $provider ] ) ? $webfonts_by_provider[ $provider ] : array();
			$webfonts_by_provider[ $provider ][ $slug ] = $webfont;
		}

		/*
		 * Loop through each of the providers to get the CSS for their respective webfonts
		 * to incrementally generate the collective styles for all of them.
		 */
		foreach ( $providers as $provider_id => $provider_class ) {

			// Bail out if the provider class does not exist.
			if ( ! class_exists( $provider_class ) ) {
				/* translators: %s is the provider name. */
				error_log( sprintf( __( 'Webfont provider "%s" is not registered.', 'gutenberg' ), $provider_id ) );
				continue;
			}

			$provider_webfonts = isset( $webfonts_by_provider[ $provider_id ] )
				? $webfonts_by_provider[ $provider_id ]
				: array();

			// If there are no registered webfonts for this provider, skip it.
			if ( empty( $provider_webfonts ) ) {
				continue;
			}

			/*
			 * Process the webfonts by first passing them to the provider via `set_webfonts()`
			 * and then getting the CSS from the provider.
			 */
			$provider = new $provider_class();
			$provider->set_webfonts( $provider_webfonts );
			$styles .= $provider->get_css();
		}

		return $styles;
	}
}
