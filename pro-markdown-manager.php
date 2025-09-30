<?php
/**
 * Plugin Name: Pro Markdown Manager
 * Description: Adds granular controls for Markdown content, including custom post type support and ACF field integration.
 * Version: 1.0.0
 * Author: Pro Child Theme Team
 * Text Domain: pro-markdown-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PRO_MARKDOWN_MANAGER_WPCOM_DIR' ) ) {
	define( 'PRO_MARKDOWN_MANAGER_WPCOM_DIR', __DIR__ . '/includes/wpcom' );
}

$pro_markdown_manager_boot_settings = get_option( 'pro_markdown_manager_settings', array() );
$pro_markdown_manager_mode = isset( $pro_markdown_manager_boot_settings['parser_mode'] ) && in_array( $pro_markdown_manager_boot_settings['parser_mode'], array( 'gfm', 'markdown_extra' ), true )
	? $pro_markdown_manager_boot_settings['parser_mode']
	: 'gfm';

if ( ! defined( 'PRO_MARKDOWN_MANAGER_PARSER_MODE' ) ) {
	define( 'PRO_MARKDOWN_MANAGER_PARSER_MODE', $pro_markdown_manager_mode );
}

if ( 'gfm' === PRO_MARKDOWN_MANAGER_PARSER_MODE ) {
	if ( ! class_exists( 'Jetpack_Options' ) ) {
		/**
		 * Lightweight Jetpack_Options shim for local environments that do not load
		 * the full Jetpack packages. Implements the methods invoked by the vendored
		 * Markdown module while remaining compatible with WooCommerce's Jetpack
		 * connection package.
		 */
		class Jetpack_Options {
			/**
			 * Defaults applied when Markdown toggles are first accessed.
			 *
			 * @var array
			 */
			private static $markdown_defaults = array(
				'wpcom_publish_posts_with_markdown'    => 1,
				'wpcom_publish_comments_with_markdown' => 1,
			);

			/**
			 * Options that should be stored network-wide when multisite is active.
			 *
			 * @var array
			 */
			private static $network_options = array( 'file_data' );

			/**
			 * Retrieve a Jetpack option (without `jetpack_` prefix).
			 *
			 * @param string $name    Option name without prefix.
			 * @param mixed  $default Default value when the option is missing.
			 *
			 * @return mixed
			 */
			public static function get_option( $name, $default = false ) {
				$option_name = self::normalize_option_name( $name, $is_network );

				$value = $is_network ? get_site_option( $option_name, null ) : get_option( $option_name, null );
				if ( null === $value ) {
					return $default;
				}

				return $value;
			}

			/**
			 * Retrieve a raw option without applying the Jetpack prefix.
			 *
			 * @param string $name    Raw option name.
			 * @param mixed  $default Default value.
			 *
			 * @return mixed
			 */
			public static function get_raw_option( $name, $default = false ) {
				$value = get_option( $name, null );

				return null === $value ? $default : $value;
			}

			/**
			 * Ensure an option exists and is autoloaded, mirroring Jetpack behaviour.
			 *
			 * @param string $name    Raw option name.
			 * @param mixed  $default Default value.
			 *
			 * @return mixed
			 */
			public static function get_option_and_ensure_autoload( $name, $default = false ) {
				$value = get_option( $name, null );

				if ( null === $value ) {
					if ( array_key_exists( $name, self::$markdown_defaults ) ) {
						$value = self::$markdown_defaults[ $name ];
						update_option( $name, $value );
					} else {
						$value = $default;
						if ( false !== $default ) {
							update_option( $name, $default );
						}
					}
				}

				return $value;
			}

			/**
			 * Update a Jetpack option (without `jetpack_` prefix).
			 *
			 * @param string $name  Option name.
			 * @param mixed  $value Value to store.
			 *
			 * @return bool
			 */
			public static function update_option( $name, $value ) {
				$option_name = self::normalize_option_name( $name, $is_network );

				return $is_network ? update_site_option( $option_name, $value ) : update_option( $option_name, $value );
			}

			/**
			 * Update multiple Jetpack options.
			 *
			 * @param array $options Key/value pairs of option names and values.
			 *
			 * @return bool True when all updates succeed.
			 */
			public static function update_options( $options ) {
				$success = true;

				foreach ( (array) $options as $name => $value ) {
					$success = self::update_option( $name, $value ) && $success;
				}

				return $success;
			}

			/**
			 * Delete a Jetpack option (without `jetpack_` prefix).
			 *
			 * @param string $name Option name.
			 *
			 * @return bool
			 */
			public static function delete_option( $name ) {
				$option_name = self::normalize_option_name( $name, $is_network );

				return $is_network ? delete_site_option( $option_name ) : delete_option( $option_name );
			}

			/**
			 * Delete a raw option without prefix handling.
			 *
			 * @param string $name Raw option name.
			 *
			 * @return void
			 */
			public static function delete_raw_option( $name ) {
				delete_option( $name );
				if ( is_multisite() ) {
					delete_site_option( $name );
				}
			}

			/**
			 * Normalize a provided option name.
			 *
			 * @param string $name       Option name without prefix.
			 * @param bool   $is_network Flag set to true when the option should be
			 *                           stored network-wide in multisite installs.
			 *
			 * @return string Prefixed option name.
			 */
			private static function normalize_option_name( $name, &$is_network ) {
				$raw_name   = preg_replace( '/^jetpack_/', '', $name, 1 );
				$is_network = is_multisite() && in_array( $raw_name, self::$network_options, true );

				return 'jetpack_' . $raw_name;
			}
		}
	}

	if ( ! class_exists( 'WPCom_Markdown' ) ) {
		if ( ! defined( 'JETPACK__PLUGIN_DIR' ) ) {
			define( 'JETPACK__PLUGIN_DIR', PRO_MARKDOWN_MANAGER_WPCOM_DIR );
		}

		if ( file_exists( PRO_MARKDOWN_MANAGER_WPCOM_DIR . '/easy-markdown.php' ) ) {
			require_once PRO_MARKDOWN_MANAGER_WPCOM_DIR . '/easy-markdown.php';
		}
	}
} else {
	// Ensure MarkdownExtra parser classes are available when GFM is disabled.
	$markdown_loader = PRO_MARKDOWN_MANAGER_WPCOM_DIR . '/_inc/lib/markdown.php';
	if ( file_exists( $markdown_loader ) ) {
		require_once $markdown_loader;
	}
}

require_once __DIR__ . '/includes/class-pro-markdown-parser.php';

if ( ! class_exists( 'Pro_Markdown_Manager' ) ) {
	/**
		 * Controls Markdown support toggles and ACF integration.
	 */
	class Pro_Markdown_Manager {
		const OPTION_NAME = 'pro_markdown_manager_settings';

		/**
		 * Cached list of post types configured for Markdown support.
		 *
		 * @var array
		 */
		private $cached_post_types = array();

		/**
		 * Tracks whether Mermaid assets were enqueued during the current request.
		 *
		 * @var bool
		 */
		private $mermaid_assets_enqueued = false;

		/**
		 * Bootstraps hooks.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'initialize_markdown_support' ), 20 );
			add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'register_action_links' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
			add_action( 'wp_head', array( $this, 'maybe_enqueue_mermaid_assets' ) );

			add_filter( 'the_content', array( $this, 'filter_post_content' ), 9 );
			add_filter( 'get_the_excerpt', array( $this, 'filter_get_the_excerpt' ), 9, 2 );
			add_filter( 'the_excerpt', array( $this, 'filter_the_excerpt' ), 9 );

			add_action( 'acf/render_field_settings', array( $this, 'add_acf_field_settings' ), 10, 1 );
			add_filter( 'acf/format_value', array( $this, 'format_acf_value' ), 4, 3 );
			
			// Add shortcode for testing Mermaid
			add_shortcode( 'test_mermaid', array( $this, 'test_mermaid_shortcode' ) );
		}

		/**
		 * Sets default options on activation.
		 */
		public static function activate() {
			$instance = new self();
			update_option( self::OPTION_NAME, $instance->get_settings() );
		}

		/**
		 * Ensures selected post types receive Markdown support.
		 */
		public function initialize_markdown_support() {
			$settings    = $this->get_settings();
			$post_types   = $this->get_supported_post_types( $settings['post_types'] );
			$parser_mode = $this->get_parser_mode();

			// Mirror Jetpack behaviour when running in GFM mode.
			if ( 'gfm' === $parser_mode && class_exists( 'WPCom_Markdown' ) ) {
				update_option( WPCom_Markdown::POST_OPTION, 1 );
			}

			foreach ( $post_types as $post_type ) {
				add_post_type_support( $post_type, 'pro-markdown' );
				if ( 'gfm' === $parser_mode && class_exists( 'WPCom_Markdown' ) ) {
					add_post_type_support( $post_type, WPCom_Markdown::POST_TYPE_SUPPORT );
				}
			}
		}

		/**
		 * Converts Markdown post content on the front end when the external module is absent.
		 *
		 * @param string $content Post content.
		 * @return string
		 */
		public function filter_post_content( $content ) {
			$post     = get_post();
			$settings = $this->get_settings();

			if ( empty( $settings['render_post_content'] ) ) {
				return $this->prepare_mermaid_markup( $content );
			}

			if ( ! $post || ! $this->should_process_request( $post, $settings ) ) {
				return $this->prepare_mermaid_markup( $content );
			}

			if ( ! $this->post_type_uses_markdown( $post->post_type ) ) {
				return $this->prepare_mermaid_markup( $content );
			}

			if ( function_exists( 'has_blocks' ) && has_blocks( $post->post_content ) ) {
				return $this->prepare_mermaid_markup( $content );
			}

			if ( false !== strpos( $content, '<p' ) || false !== strpos( $content, '<div' ) ) {
				return $this->prepare_mermaid_markup( $content );
			}

			return $this->transform_markdown_value( $content, $post->ID );
		}

		/**
		 * Applies Markdown conversion to excerpt values (pre-trimming).
		 *
		 * @param string   $excerpt Raw excerpt.
		 * @param \WP_Post $post    Post object.
		 * * @return string
		 */
		public function filter_get_the_excerpt( $excerpt, $post ) {
			if ( ! $post instanceof WP_Post ) {
				return $excerpt;
			}

			$settings = $this->get_settings();

			if ( empty( $settings['render_post_content'] ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			if ( ! $this->should_process_request( $post, $settings ) || ! $this->post_type_uses_markdown( $post->post_type ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			if ( false !== strpos( $excerpt, '<p' ) || false !== strpos( $excerpt, '<div' ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			return $this->transform_markdown_value( $excerpt, $post->ID );
		}

		/**
		 * Applies Markdown conversion to already-trimmed excerpts.
		 *
		 * @param string $excerpt Excerpt content.
		 * @return string
		 */
		public function filter_the_excerpt( $excerpt ) {
			$post = get_post();

			$settings = $this->get_settings();

			if ( empty( $settings['render_post_content'] ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			if ( ! $post || ! $this->should_process_request( $post, $settings ) || ! $this->post_type_uses_markdown( $post->post_type ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			if ( false !== strpos( $excerpt, '<p' ) || false !== strpos( $excerpt, '<div' ) ) {
				return $this->prepare_mermaid_markup( $excerpt );
			}

			return $this->transform_markdown_value( $excerpt, $post->ID );
		}

		/**
		 * Registers the plugin settings page.
		 */
		public function register_settings_page() {
			add_options_page(
				__( 'Markdown Manager', 'pro-markdown-manager' ),
				__( 'Markdown Manager', 'pro-markdown-manager' ),
				'manage_options',
				'pro-markdown-manager',
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Registers settings and fields.
		 */
		public function register_settings() {
			register_setting(
				'pro_markdown_manager_settings',
				self::OPTION_NAME,
				array( $this, 'sanitize_settings' )
			);

			add_settings_section(
				'pro_markdown_manager_general',
				__( 'Markdown Settings', 'pro-markdown-manager' ),
				'__return_false',
				'pro-markdown-manager'
			);

			add_settings_field(
				'post_types',
				__( 'Post types using Markdown', 'pro-markdown-manager' ),
				array( $this, 'render_post_types_field' ),
				'pro-markdown-manager',
				'pro_markdown_manager_general'
			);

			add_settings_field(
				'render_post_content',
				__( 'Render post content', 'pro-markdown-manager' ),
				array( $this, 'render_content_toggle_field' ),
				'pro-markdown-manager',
				'pro_markdown_manager_general'
			);

			add_settings_field(
				'parser_mode',
				__( 'Markdown parser', 'pro-markdown-manager' ),
				array( $this, 'render_parser_mode_field' ),
				'pro-markdown-manager',
				'pro_markdown_manager_general'
			);

			add_settings_field(
				'acf_support',
				__( 'ACF field option', 'pro-markdown-manager' ),
				array( $this, 'render_acf_support_field' ),
				'pro-markdown-manager',
				'pro_markdown_manager_general'
			);
		}

		/**
		 * Sanitizes and normalizes settings before saving.
		 *
		 * @param array $input Raw option input.
		 * @return array
		 */
		public function sanitize_settings( $input ) {
			$defaults = $this->get_default_settings();
			$input    = is_array( $input ) ? $input : array();

			$sanitized = array();
			$sanitized['acf_support']        = ! empty( $input['acf_support'] );
			$sanitized['render_post_content'] = ! empty( $input['render_post_content'] );
			$sanitized['parser_mode']         = in_array( $input['parser_mode'] ?? 'gfm', array( 'gfm', 'markdown_extra' ), true ) ? $input['parser_mode'] : $defaults['parser_mode'];

			$post_types = array();
			if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
				foreach ( $input['post_types'] as $post_type ) {
					$post_type = sanitize_key( $post_type );
					if ( post_type_exists( $post_type ) ) {
						$post_types[] = $post_type;
					}
				}
			}

			$sanitized['post_types'] = $post_types ? array_values( array_unique( $post_types ) ) : $defaults['post_types'];

			return $sanitized;
		}

		/**
		 * Adds plugin link on the plugins screen.
		 *
		 * @param array $links Existing action links.
		 * @return array
		 */
		public function register_action_links( $links ) {
			$settings_url = admin_url( 'options-general.php?page=pro-markdown-manager' );
			$links[]      = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'pro-markdown-manager' ) . '</a>';
			return $links;
		}

		/**
		 * Outputs the plugin settings page.
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = $this->get_settings();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Markdown Manager', 'pro-markdown-manager' ); ?></h1>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'pro_markdown_manager_settings' );
					do_settings_sections( 'pro-markdown-manager' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Renders the multi-checkbox list of post types.
		 */
		public function render_post_types_field() {
			$settings   = $this->get_settings();
			$post_types = $this->get_post_type_options();
			?>
			<fieldset>
				<?php foreach ( $post_types as $post_type => $label ) : ?>
					<label style="display:block;margin-bottom:4px;">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $settings['post_types'], true ) ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			p class="description">
				<?php esc_html_e( 'Selected post types will store Markdown and render HTML through the parser selected below.', 'pro-markdown-manager' ); ?>
			</p>
			</fieldset>
			<?php
		}

		/**
		 * Renders the ACF integration toggle.
		 */
		public function render_acf_support_field() {
			$settings = $this->get_settings();
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[acf_support]" value="1" <?php checked( $settings['acf_support'] ); ?> />
				<?php esc_html_e( 'Expose a Markdown option on ACF textarea and WYSIWYG fields.', 'pro-markdown-manager' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled, field values marked for Markdown will be transformed during output.', 'pro-markdown-manager' ); ?>
			</p>
			<?php
		}

		/**
		 * Renders the post content rendering toggle.
		 */
		public function render_content_toggle_field() {
			$settings = $this->get_settings();
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[render_post_content]" value="1" <?php checked( ! empty( $settings['render_post_content'] ) ); ?> />
				<?php esc_html_e( 'Convert post content with the Markdown parser.', 'pro-markdown-manager' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Disable this if you prefer to keep the main editor content stored and displayed as raw Markdown.', 'pro-markdown-manager' ); ?>
			</p>
			<?php
		}

		/**
		 * Renders the parser selection controls.
		 */
		public function render_parser_mode_field() {
			$settings    = $this->get_settings();
			$current    = $this->get_parser_mode( $settings );
			$options    = array(
				'gfm'            => __( 'GitHub Flavoured Markdown (WP.com parser)', 'pro-markdown-manager' ),
				'markdown_extra' => __( 'Markdown Extra (Michel Fortin)', 'pro-markdown-manager' ),
			);
			?>
			<fieldset>
				<?php foreach ( $options as $value => $label ) : ?>
					<label style="display:block;margin-bottom:4px;">
						<input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[parser_mode]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current, $value ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
				<p class="description">
				<?php esc_html_e( 'GitHub Flavoured Markdown adds fenced code blocks, task lists, and other GitHub styling, while Markdown Extra offers rich tables, footnotes, and definition lists.', 'pro-markdown-manager' ); ?>
				</p>
			</fieldset>
			<?php
		}

		/**
		 * Adds a toggle inside supported ACF field settings.
		 *
		 * @param array $field Field configuration.
		 */
		public function add_acf_field_settings( $field ) {
			$settings = $this->get_settings();

			if ( ! $settings['acf_support'] || ! Pro_Markdown_Manager_Parser::is_available() ) {
				return;
			}

			if ( empty( $field['type'] ) || ! in_array( $field['type'], array( 'textarea', 'wysiwyg' ), true ) ) {
				return;
			}

			if ( ! function_exists( 'acf_render_field_setting' ) ) {
				return;
			}

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Markdown Output', 'pro-markdown-manager' ),
					'instructions' => __( 'Transform this field from Markdown into HTML during render.', 'pro-markdown-manager' ),
					'name'         => 'pro_markdown_enabled',
					'type'         => 'true_false',
					'ui'           => 1,
				)
			);
		}

		/**
		 * Converts enabled ACF values through the Markdown parser when rendering.
		 *
		 * @param mixed  $value   Raw field value.
		 * @param mixed  $post_id Post identifier.
		 * @param array  $field   Field configuration.
		 * @return mixed
		 */
		public function format_acf_value( $value, $post_id, $field ) {
			if ( empty( $value ) ) {
				return $value;
			}

			$settings = $this->get_settings();

			if ( ! $settings['acf_support'] || ! Pro_Markdown_Manager_Parser::is_available() || empty( $field['pro_markdown_enabled'] ) ) {
				return $value;
			}

			$transformed = Pro_Markdown_Manager_Parser::transform( $value, $post_id, true );

			return $this->prepare_mermaid_markup( wp_kses_post( $transformed ) );
		}

		/**
		 * Determines whether Markdown should be processed for the current request.
		 *
		 * @return bool
		 */
		private function should_process_request( $post = null, $settings = null ) {
			if ( is_admin() ) {
				return false;
			}

			if ( ! Pro_Markdown_Manager_Parser::is_available() ) {
				return false;
			}

			if ( ! $post instanceof WP_Post ) {
				return true;
			}

			// Respect block content authored in Gutenberg; WPCom handles blocks itself.
			if ( function_exists( 'has_blocks' ) && has_blocks( $post->post_content ) ) {
				return false;
			}

			$parser_mode = $this->get_parser_mode( $settings );

			if ( 'gfm' === $parser_mode && class_exists( 'WPCom_Markdown' ) ) {
				$markdown = WPCom_Markdown::get_instance();

				if ( $markdown->is_markdown( $post->ID ) ) {
					return false;
				}

				if ( ! empty( $post->post_content_filtered ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Converts Markdown into HTML using the shared parser helper with caching.
		 *
		 * @param string     $value   Raw Markdown.
		 * @param int|string $post_id Post identifier.
		 * @return string
		 */
		private function transform_markdown_value( $value, $post_id ) {
			static $cache = array();

			$hash = md5( $value ) . ':' . $post_id;

			if ( isset( $cache[ $hash ] ) ) {
				return $cache[ $hash ];
			}

			$transformed = Pro_Markdown_Manager_Parser::transform( $value, $post_id, true );
			$transformed = $this->prepare_mermaid_markup( wp_kses_post( $transformed ) );

			$cache[ $hash ] = $transformed;

			return $transformed;
		}

		/**
		 * Determines whether a post type is configured for Markdown.
		 *
		 * @param string $post_type Post type slug.
		 * @return bool
		 */
		private function post_type_uses_markdown( $post_type ) {
			$post_types = $this->get_supported_post_types();
			return in_array( $post_type, $post_types, true );
		}

		/**
		 * Normalizes the configured post type list.
		 *
		 * @param array|null $configured Optional configured list.
		 * @return array
		 */
		private function get_supported_post_types( $configured = null ) {
			if ( null === $configured && ! empty( $this->cached_post_types ) ) {
				return $this->cached_post_types;
			}

			if ( null === $configured ) {
				$settings  = $this->get_settings();
				$configured = isset( $settings['post_types'] ) ? $settings['post_types'] : array();
			}

			$post_types = array();

			if ( is_array( $configured ) ) {
				foreach ( $configured as $post_type ) {
					$post_type = sanitize_key( $post_type );

					if ( post_type_exists( $post_type ) ) {
						$post_types[] = $post_type;
					}
				}
			}

			$post_types = array_values( array_unique( $post_types ) );
			$this->cached_post_types = $post_types;

			return $post_types;
		}

		/**
		 * Retrieves stored settings merged with defaults.
		 *
		 * @return array
		 */
		private function get_settings() {
			$stored = get_option( self::OPTION_NAME, array() );
			$stored = is_array( $stored ) ? $stored : array();

			return wp_parse_args( $stored, $this->get_default_settings() );
		}

		/**
		 * Default settings.
		 *
		 * @return array
		 */
		private function get_default_settings() {
			return array(
				'post_types'           => array( 'post', 'page' ),
				'acf_support'          => true,
				'parser_mode'          => 'gfm',
				'render_post_content'  => false,
			);
		}

		/**
		 * Retrieves the parser mode from settings (or provided array).
		 *
		 * @param array|null $settings Optional pre-fetched settings.
		 * @return string
		 */
		private function get_parser_mode( $settings = null ) {
			if ( null === $settings ) {
				$settings = $this->get_settings();
			}

			return isset( $settings['parser_mode'] ) && in_array( $settings['parser_mode'], array( 'gfm', 'markdown_extra' ), true )
				? $settings['parser_mode']
				: 'gfm';
		}

		/**
		 * Converts Mermaid code blocks into Mermaid containers and flags asset loading.
		 *
		 * @param string $html Converted HTML string.
		 * @return string
		 */
		private function prepare_mermaid_markup( $html ) {
			error_log('prepare_mermaid_markup called');
			// Log the HTML for debugging
			error_log('Mermaid markup processing: ' . substr($html, 0, 1000));
			
			if ( '' === $html ) {
				error_log('Empty HTML, returning as is');
				return $html;
			}

			$contains_language = false !== stripos( $html, 'language-mermaid' );
			$contains_container = false !== stripos( $html, 'class="mermaid' ) || false !== stripos( $html, "class='mermaid" );

			error_log('Contains language-mermaid: ' . ($contains_language ? 'yes' : 'no'));
			error_log('Contains mermaid container: ' . ($contains_container ? 'yes' : 'no'));

			if ( ! $contains_language && ! $contains_container ) {
				error_log('No Mermaid content found, returning as is');
				return $html;
			}

			// Ensure assets are loaded if we have any Mermaid content
			if (($contains_language || $contains_container) && apply_filters( 'pro_markdown_manager_enable_mermaid', true )) {
				error_log('Found Mermaid content, ensuring assets');
				$this->ensure_mermaid_assets();
			}

			if ( ! $contains_language ) {
				error_log('No language-mermaid found, returning as is');
				return $html;
			}

			// Log before regex processing
			error_log('Processing language-mermaid blocks');
			
			// Debug the regex patterns with more flexible patterns
			$pattern1 = '/<pre[^>]*>\s*<code[^>]*class=[\'\"][^\'\"]*\blanguage-mermaid\b[^\'\"]*[\'\"][^>]*>(?P<content>[\s\S]*?)<\/code>\s*<\/pre>/i';
			$pattern2 = '/<code[^>]*class=[\'\"][^\'\"]*\blanguage-mermaid\b[^\'\"]*[\'\"][^>]*>(?P<content>[\s\S]*?)<\/code>/i';
			
			// Add more flexible patterns to catch edge cases
			$pattern3 = '/<pre[^>]*class=[\'\"][^\'\"]*\blanguage-mermaid\b[^\'\"]*[\'\"][^>]*>\s*<code[^>]*>(?P<content>[\s\S]*?)<\/code>\s*<\/pre>/i';
			$pattern4 = '/<pre[^>]*>\s*<code[^>]*>(?P<content>[\s\S]*?)<\/code>\s*<\/pre>/i';
			
			$pattern1_matches = preg_match_all($pattern1, $html);
			$pattern2_matches = preg_match_all($pattern2, $html);
			$pattern3_matches = preg_match_all($pattern3, $html);
			$pattern4_matches = preg_match_all($pattern4, $html);
			
			error_log('Pattern 1 matches: ' . $pattern1_matches);
			error_log('Pattern 2 matches: ' . $pattern2_matches);
			error_log('Pattern 3 matches: ' . $pattern3_matches);
			error_log('Pattern 4 matches: ' . $pattern4_matches);

			$html_before = $html;
			$html = preg_replace_callback(
				$pattern1,
				array( $this, 'convert_mermaid_code_block' ),
				$html
			);
			
			error_log('After pattern 1, HTML changed: ' . ($html_before !== $html ? 'yes' : 'no'));

			$html_before = $html;
			$html = preg_replace_callback(
				$pattern2,
				array( $this, 'convert_mermaid_code_block' ),
				$html
			);
			
			error_log('After pattern 2, HTML changed: ' . ($html_before !== $html ? 'yes' : 'no'));
			
			// Try the additional patterns if the first two didn1't match
			if ($pattern1_matches == 0 && $pattern2_matches == 0) {
				error_log('Trying additional patterns for edge cases');
				
				$html_before = $html;
				$html = preg_replace_callback(
					$pattern3,
					array( $this, 'convert_mermaid_code_block' ),
					$html
				);
				
				error_log('After pattern 3, HTML changed: ' . ($html_before !== $html ? 'yes' : 'no'));
				
				// Only try pattern 4 if we're sure it's a Mermaid block
				if (strpos($html, 'language-mermaid') !== false) {
					$html_before = $html;
					$html = preg_replace_callback(
						$pattern4,
						function($matches) {
							// Additional check to ensure this is actually a Mermaid block
							if (isset($matches[0]) && strpos($matches[0], 'language-mermaid') !== false) {
								return $this->convert_mermaid_code_block($matches);
							}
							return $matches[0];
						},
						$html
					);
					
					error_log('After pattern 4, HTML changed: ' . ($html_before !== $html ? 'yes' : 'no'));
				}
			}
			
			error_log('Final HTML: ' . substr($html, 0, 1000));

			return $html;
		}

		/**
		 * Converts a matched Mermaid code block into an executable container.
		 *
		 * @param array $matches Regex matches.
		 * @return string
		 */
		private function convert_mermaid_code_block( $matches ) {
			// Log the raw matches for debugging
			error_log('Mermaid matches: ' . print_r($matches, true));
			
			if ( empty( $matches['content'] ) || ! apply_filters( 'pro_markdown_manager_enable_mermaid', true ) ) {
				error_log('Mermaid: Empty content or Mermaid disabled');
				return $matches[0];
			}

			$content = $this->normalize_mermaid_content( $matches['content'] );
			
			error_log('Mermaid normalized content: ' . $content);

			if ( '' === $content ) {
				error_log('Mermaid: Normalized content is empty');
				return $matches[0];
			}

			// Additional validation - reject content that looks corrupted
			if (strpos($content, '...') === 0 && strpos($content, ';') !== false) {
				// This content appears corrupted, don't render it
				error_log('Corrupted Mermaid content detected: ' . $content);
				return '<div class="mermaid-error" style="background:#ffeeee; padding:10px; border:1px solid #ff0000;">'
					. '<strong>Mermaid Error:</strong> Corrupted diagram content detected<br>'
					. '<small>Content: ' . esc_html(substr($content, 0, 100)) . (strlen($content) > 100 ? '...' : '') . '</small>'
					. '</div>';
			}
			
			// Additional validation for the specific pattern we're seeing
			if (strpos($content, '; class Z,AA notes') !== false) {
				error_log('Specific corrupted pattern detected: ' . $content);
				return '<div class="mermaid-error" style="background:#ffeeee; padding:10px; border:1px solid #ff0000;">'
					. '<strong>Mermaid Error:</strong> Known corrupted pattern detected<br>'
					. '<small>Content: ' . esc_html(substr($content, 0, 100)) . (strlen($content) > 100 ? '...' : '') . '</small>'
					. '</div>';
			}

			$this->ensure_mermaid_assets();
			
			$result = '<div class="mermaid">' . esc_textarea( $content ) . '</div>';
			error_log('Mermaid conversion result: ' . $result);

			return $result;
		}

		/**
		 * Normalizes Mermaid markup generated from Markdown or WYSIWYG sources.
		 *
		 * @param string $content Raw HTML content inside the code block.
		 * @return string
		 */
		private function normalize_mermaid_content( $content ) {
			// Handle empty content
			if ( empty( $content ) ) {
				return '';
			}
			
			// Store original content for debugging
			$original_content = $content;
			
			// Early validation - check if content is severely corrupted
			if (strpos($content, '...') === 0 && strpos($content, ';') !== false) {
				// This looks like truncated content, try to recover
				error_log('Mermaid content appears truncated: ' . $content);
			}
			
			// Aggressive cleaning for the specific pattern we're seeing
			if (strpos($content, '; class Z,AA notes') !== false) {
				// This is the specific corrupted pattern, remove it
				error_log('Removing specific corrupted pattern from content: ' . $content);
				$content = '';
			}
			
			// Remove any content that starts with ellipsis and semicolon
			if (preg_match('/^\.\.\.[^;\n]*;/', $content)) {
				error_log('Removing corrupted prefix from content: ' . $content);
				$content = preg_replace('/^\.\.\.[^;\n]*;/', '', $content);
			}
			
			// Decode HTML entities that might have been encoded during Markdown processing
			$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			
			// Handle common HTML entities that might break Mermaid syntax
			$content = str_replace( array( '&lt;', '&gt;', '&amp;', '&quot;', '&#039;' ), 
								   array( '<', '>', '&', '"', "'" ), 
								   $content );
			
			// Remove zero-width spaces and other invisible Unicode characters
			$content = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $content );
			
			// Normalize whitespace characters
			$content = str_replace( "\xC2\xA0", ' ', $content ); // Non-breaking space
			$content = preg_replace( '#<br\s*/?>#i', "\n", $content );
			$content = preg_replace( '#</?(?:p|div)[^>]*>#i', "\n", $content );
			$content = preg_replace( '#</?span[^>]*>#i', '', $content );
			
			// Strip all HTML tags but preserve content
			$content = wp_strip_all_tags( $content, true );
			
			// Normalize line endings
			$content = preg_replace( "/\r\n|\r/", "\n", $content );
			$content = preg_replace( "/[ \t]+\n/", "\n", $content );
			$content = preg_replace( "/\n{3,}/", "\n\n", $content );
			
			// Additional normalization for better Mermaid compatibility
			$content = trim( $content );
			
			// Ensure proper line endings
			$content = str_replace( "\r\n", "\n", $content );
			$content = str_replace( "\r", "\n", $content );
			
			// Remove extra whitespace while preserving indentation
			$lines = explode( "\n", $content );
			$normalized_lines = array();
			
			foreach ( $lines as $line ) {
				// Trim trailing whitespace but preserve leading whitespace (indentation)
				$normalized_lines[] = rtrim( $line );
			}
			
			$content = implode( "\n", $normalized_lines );
			
			// Additional validation to ensure content is valid for Mermaid
			if ( empty( $content ) ) {
				return '';
			}
			
			// Check for corrupted content patterns
			if (preg_match('/^\.\.\.[a-zA-Z0-9]/', $content)) {
				// Content appears to be truncated, attempt to clean it
				$content = preg_replace('/^\.\.\.[a-zA-Z0-9]*;\s*/', '', $content);
			}
			
			// Check if the content starts with a valid Mermaid diagram type
			$valid_start_patterns = array(
				'graph', 'flowchart', 'sequenceDiagram', 'classDiagram', 
				'stateDiagram', 'stateDiagram-v2', 'erDiagram', 'gantt', 
				'pie', 'journey', 'requirementDiagram', 'gitGraph', 'c4c', 'mindmap'
			);
			
			$first_line = strtok( $content, "\n" );
			$has_valid_start = false;
			
			foreach ( $valid_start_patterns as $pattern ) {
				if ( stripos( $first_line, $pattern ) !== false ) {
					$has_valid_start = true;
					break;
				}
			}
			
			// If content doesn't start with valid Mermaid syntax, wrap it in a flowchart
			if ( !$has_valid_start && !empty( $content ) ) {
				// Check if content contains valid arrow syntax
				if ( strpos( $content, '-->' ) !== false || strpos( $content, '---' ) !== false ) {
					$content = "graph TD\n" . $content;
				} else {
					// If no arrow syntax, treat as simple nodes
					$lines = explode( "\n", $content );
					$new_content = "graph TD\n";
					foreach ( $lines as $line ) {
						if ( !empty( trim( $line ) ) ) {
							$new_content .= "    " . trim( $line ) . "\n";
						}
					}
					$content = $new_content;
				}
			}
			
			// Final validation - remove any remaining HTML entities that might cause issues
			$content = preg_replace( '/&[a-zA-Z0-9#]*;/', '', $content );
			
			// Remove any JavaScript or CSS comments that might interfere
			$content = preg_replace( '/\/\*.*?\*\//s', '', $content );
			$content = preg_replace( '/\/\/.*$/m', '', $content );
			
			// Ensure proper spacing around operators
			$content = preg_replace( '/(\w)--(\w)/', '$1 --> $2', $content );
			$content = preg_replace( '/(\w)---(\w)/', '$1 --- $2', $content );
			$content = preg_replace( '/(\w)==(\w)/', '$1 == $2', $content );
			$content = preg_replace( '/(\w)===(\w)/', '$1 === $2', $content );
			
			// Remove any remaining corrupted patterns
			$content = preg_replace( '/\.\.\.[a-zA-Z0-9]*;/', '', $content );
			
			// Ensure the content ends with a newline
			if ( substr( $content, -1 ) !== "\n" ) {
				$content .= "\n";
			}
			
			// Final trim
			$content = trim( $content ) . "\n";
			
			// Validate that content doesn't contain corrupted patterns
			if (strpos($content, '...') !== false && strpos($content, ';') !== false) {
				// Try one more cleanup
				$content = preg_replace('/\.\.\.[^;\n]*;/', '', $content);
				$content = trim( $content ) . "\n";
			}
			
			// Final validation - if content is still corrupted, reject it
			if (strpos($content, '...') === 0 || strpos($content, '; class Z,AA notes') !== false) {
				error_log('Content still corrupted after cleaning, rejecting: ' . $content);
				return '';
			}
			
			// Ensure content has proper Mermaid syntax
			if (!empty($content)) {
				// Make sure the first line is a valid diagram declaration
				$lines = explode("\n", $content);
				if (!empty($lines[0])) {
					$first_line = trim($lines[0]);
					$valid_diagrams = ['graph', 'flowchart', 'sequenceDiagram', 'classDiagram', 'stateDiagram', 'stateDiagram-v2', 'erDiagram', 'gantt', 'pie', 'journey', 'requirementDiagram', 'gitGraph', 'c4c', 'mindmap'];
					$valid_start = false;
					
					foreach ($valid_diagrams as $diagram) {
						if (stripos($first_line, $diagram) === 0) {
							$valid_start = true;
							break;
						}
					}
					
					if (!$valid_start && !empty($content)) {
						// Add a default graph declaration
						$content = "graph TD\n" . $content;
					}
				}
			}
			
			return $content;
		}

		/**
		 * Ensures Mermaid assets are enqueued once when needed.
		 */
		private function ensure_mermaid_assets() {
			error_log('ensure_mermaid_assets called');
			if ( $this->mermaid_assets_enqueued ) {
				error_log('Mermaid assets already enqueued');
				return;
			}

			error_log('Enqueuing Mermaid assets');
			$this->mermaid_assets_enqueued = true;

			// Register and enqueue the Mermaid script
			wp_enqueue_script(
				'pro-markdown-mermaid',
				'https://cdn.jsdelivr.net/npm/mermaid@11.12.0/dist/mermaid.min.js',
				array(),
				'11.12.0',
				true
			);
			
			// Add inline CSS to ensure proper Mermaid styling
			$mermaid_css = '
				.mermaid {
					background: transparent;
					padding: 10px;
					border: none;
					font-family: monospace;
					text-align: center;
					display: block;
					margin: 10px 0;
				}
				
				.mermaid:not([data-processed]) {
					background: #f8f8f8;
					border: 1px dashed #ccc;
					min-height: 50px;
				}
				
				.mermaid[data-processed] {
					background: transparent;
					border: none;
				}
				
				.mermaid-error {
					background: #ffeeee;
					padding: 10px;
					border: 1px solid #ff0000;
					color: #333;
					display: block;
					margin: 10px 0;
				}
				
				pre code.language-mermaid,
				code.language-mermaid {
					/* Don\'t hide code blocks - let JavaScript handle conversion */
					display: block;
					background: #f5f5f5;
					padding: 10px;
					border: 1px solid #ddd;
					font-family: monospace;
					white-space: pre;
				}
				
				/* Ensure Mermaid diagrams are visible */
				.mermaid svg {
					max-width: 100%;
					height: auto;
				}
			';
			
			wp_add_inline_style('pro-markdown-mermaid', $mermaid_css);
			
			wp_add_inline_script( 'pro-markdown-mermaid', 'const mermaidStyle = document.createElement("style"); mermaidStyle.textContent = `' . $mermaid_css . '`; document.head.appendChild(mermaidStyle);' );
			
			wp_add_inline_script( 'pro-markdown-mermaid', '
				// Debug function to show what elements we have
				function debugMermaidElements() {
					const preElements = document.querySelectorAll("pre");
					console.log("All pre elements:", preElements.length);
					
					const codeElements = document.querySelectorAll("code");
					console.log("All code elements:", codeElements.length);
					
					const mermaidCodeElements = document.querySelectorAll("code.language-mermaid");
					console.log("Mermaid code elements:", mermaidCodeElements.length);
					
					const mermaidDivElements = document.querySelectorAll("div.mermaid");
					console.log("Mermaid div elements:", mermaidDivElements.length);
					
					mermaidCodeElements.forEach(function(el, index) {
						console.log("Mermaid code element #" + index + ":", el.outerHTML);
					});
					
					mermaidDivElements.forEach(function(el, index) {
						console.log("Mermaid div element #" + index + ":", el.outerHTML);
					});
				}
				
				// Fallback function to manually convert code blocks to Mermaid diagrams
				function convertCodeToMermaid() {
					// Find code blocks with language-mermaid class
					const mermaidCodeBlocks = document.querySelectorAll("pre code.language-mermaid, code.language-mermaid");
					console.log("Found " + mermaidCodeBlocks.length + " Mermaid code blocks to convert");
					
					mermaidCodeBlocks.forEach(function(codeBlock) {
						try {
							const content = codeBlock.textContent;
							console.log("Converting code block to Mermaid:", content);
							
							// Skip if already converted
							if (codeBlock.closest(".mermaid")) {
								console.log("Already converted, skipping");
								return;
							}
							
							// Create a new div for the Mermaid diagram
							const mermaidDiv = document.createElement("div");
							mermaidDiv.className = "mermaid";
							mermaidDiv.textContent = content;
							
							// Replace the pre element with the Mermaid div
							const preElement = codeBlock.closest("pre");
							if (preElement) {
								preElement.parentNode.replaceChild(mermaidDiv, preElement);
							} else {
								codeBlock.parentNode.replaceChild(mermaidDiv, codeBlock);
							}
							
							console.log("Converted code block to Mermaid div");
						} catch (e) {
							console.error("Error converting code block to Mermaid:", e);
						}
					});
				}
				
				// Enhanced function to handle various HTML structures
				function enhancedConvertCodeToMermaid() {
					// Find all pre elements that might contain Mermaid code
					const preElements = document.querySelectorAll("pre");
					console.log("Found " + preElements.length + " pre elements to check for Mermaid");
					
					preElements.forEach(function(preElement, index) {
						try {
							// Check if this pre element has a code child with language-mermaid
							const codeElement = preElement.querySelector("code.language-mermaid");
							if (codeElement) {
								const content = codeElement.textContent;
								console.log("Converting pre element #" + index + " to Mermaid:", content);
								
								// Skip if already converted
								if (preElement.closest(".mermaid")) {
									console.log("Already converted, skipping");
									return;
								}
								
								// Create a new div for the Mermaid diagram
								const mermaidDiv = document.createElement("div");
								mermaidDiv.className = "mermaid";
								mermaidDiv.textContent = content;
								
								// Replace the pre element with the Mermaid div
								preElement.parentNode.replaceChild(mermaidDiv, preElement);
								
								console.log("Converted pre element to Mermaid div");
							}
						} catch (e) {
							console.error("Error converting pre element #" + index + " to Mermaid:", e);
						}
					});
					
					// Also check for standalone code elements with language-mermaid
					const standaloneCodeElements = document.querySelectorAll("code.language-mermaid:not(pre code)");
					console.log("Found " + standaloneCodeElements.length + " standalone Mermaid code elements");
					
					standaloneCodeElements.forEach(function(codeElement, index) {
						try {
							const content = codeElement.textContent;
							console.log("Converting standalone code element #" + index + " to Mermaid:", content);
							
							// Skip if already converted
							if (codeElement.closest(".mermaid")) {
								console.log("Already converted, skipping");
								return;
							}
							
							// Create a new div for the Mermaid diagram
							const mermaidDiv = document.createElement("div");
							mermaidDiv.className = "mermaid";
							mermaidDiv.textContent = content;
							
							// Replace the code element with the Mermaid div
							codeElement.parentNode.replaceChild(mermaidDiv, codeElement);
							
							console.log("Converted standalone code element to Mermaid div");
						} catch (e) {
							console.error("Error converting standalone code element #" + index + " to Mermaid:", e);
						}
					});
				}
				
				// Ensure mermaid is properly initialized
				function initMermaid() {
					if (typeof mermaid !== "undefined") {
						console.log("Initializing Mermaid...");
						debugMermaidElements();
						
						// First, try to convert any remaining code blocks with enhanced conversion
						enhancedConvertCodeToMermaid();
						
						// Configure mermaid
						mermaid.initialize({ 
							startOnLoad: false,
							theme: "default",
							securityLevel: "loose",
							flowchart: {
								useMaxWidth: true
							},
							fontFamily: "monospace",
							logLevel: 1
						});
						
						// Add error handling
						mermaid.parseError = function(err, hash) {
							console.error("Mermaid syntax error:", err);
							console.error("Error hash:", hash);
						};
						
						// Check for mermaid elements
						const mermaidElements = document.querySelectorAll(".mermaid");
						console.log("Found " + mermaidElements.length + " Mermaid elements");
						
						// Process each element
						mermaidElements.forEach(function(element, index) {
							console.log("Processing Mermaid element #" + index + ":", element.textContent.substring(0, 100));
							
							// Check for corrupted content
							if (element.textContent && element.textContent.indexOf("; class Z,AA notes") !== -1) {
								console.warn("Corrupted content detected in element #" + index);
								element.innerHTML = "<div style=\"background:#ffeeee; padding:10px; border:1px solid #ff0000;\"><strong>Mermaid Error:</strong> Known corrupted pattern detected<br><small>Content: " + element.textContent.substring(0, 100).replace(/</g, "&lt;").replace(/>/g, "&gt;") + "</small></div>";
								return;
							}
						});
						
						// Render all mermaid diagrams
						if (mermaidElements.length > 0) {
							try {
								console.log("Rendering Mermaid diagrams...");
								mermaid.run({
									nodes: mermaidElements
								}).then(() => {
									console.log("Mermaid rendering completed successfully");
									// Add data-processed attribute to indicate successful processing
									mermaidElements.forEach(function(element) {
										element.setAttribute("data-processed", "true");
									});
								}).catch((error) => {
									console.error("Mermaid rendering failed:", error);
								});
							} catch (error) {
								console.error("Mermaid init error:", error);
							}
						} else {
							console.log("No Mermaid elements found to render");
						}
					} else {
						console.warn("Mermaid not loaded yet, retrying...");
						// Retry after a short delay
						setTimeout(initMermaid, 100);
					}
				}
				
				// Initialize when DOM is ready
				if (document.readyState === "loading") {
					document.addEventListener("DOMContentLoaded", function() {
						// Small delay to ensure all content is loaded
						setTimeout(initMermaid, 100);
					});
				} else {
					// DOM is already ready
					setTimeout(initMermaid, 100);
				}
				
				// Also initialize after a small delay to catch any late-loading content
				setTimeout(initMermaid, 500);
				setTimeout(debugMermaidElements, 1000);
				setTimeout(enhancedConvertCodeToMermaid, 1500);
				
				// Support for dynamically added content
				if (window.MutationObserver && typeof mermaid !== "undefined") {
					const observer = new MutationObserver(function(mutations) {
						let shouldReinit = false;
						mutations.forEach(function(mutation) {
							if (mutation.type === "childList") {
								mutation.addedNodes.forEach(function(node) {
									if (node.nodeType === 1) {
										// Check if this is a code block that needs conversion
										if (node.tagName === "CODE" && node.classList && node.classList.contains("language-mermaid")) {
											console.log("New Mermaid code block detected, converting...");
											try {
												const content = node.textContent;
												const mermaidDiv = document.createElement("div");
												mermaidDiv.className = "mermaid";
												mermaidDiv.textContent = content;
												const parent = node.parentNode;
												if (parent) {
													parent.replaceChild(mermaidDiv, node);
												}
												shouldReinit = true;
											} catch (e) {
												console.error("Error converting new code block:", e);
											}
										}
										// Check if this is already a Mermaid div
										else if (node.classList && node.classList.contains("mermaid")) {
											console.log("New Mermaid element detected");
											// Check for corrupted content before processing
											if (node.textContent && node.textContent.indexOf("; class Z,AA notes") !== -1) {
												node.innerHTML = "<div style=\"background:#ffeeee; padding:10px; border:1px solid #ff0000;\"><strong>Mermaid Error:</strong> Known corrupted pattern detected<br><small>Content: " + node.textContent.substring(0, 100).replace(/</g, "&lt;").replace(/>/g, "&gt;") + "</small></div>";
												return;
											}
											shouldReinit = true;
										}
										// Check if this is a pre element that might contain Mermaid
										else if (node.tagName === "PRE") {
											const codeElement = node.querySelector("code.language-mermaid");
											if (codeElement) {
												console.log("New pre element with Mermaid code detected, converting...");
												try {
													const content = codeElement.textContent;
													const mermaidDiv = document.createElement("div");
													mermaidDiv.className = "mermaid";
													mermaidDiv.textContent = content;
													node.parentNode.replaceChild(mermaidDiv, node);
													shouldReinit = true;
												} catch (e) {
													console.error("Error converting new pre element:", e);
												}
											}
										}
									}
								});
							}
						});
						
						if (shouldReinit) {
							setTimeout(function() {
								try {
									console.log("Re-initializing Mermaid for dynamic content");
									const mermaidElements = document.querySelectorAll(".mermaid:not([data-processed])");
									if (mermaidElements.length > 0) {
										mermaid.run({
											nodes: mermaidElements
										}).then(() => {
											console.log("Dynamic Mermaid rendering completed successfully");
											// Add data-processed attribute to indicate successful processing
											mermaidElements.forEach(function(element) {
												element.setAttribute("data-processed", "true");
											});
										}).catch((error) => {
											console.error("Dynamic Mermaid rendering failed:", error);
										});
									}
								} catch (e) {
									console.warn("Mermaid re-init failed:", e);
								}
							}, 100);
						}
					});
					
					observer.observe(document.body, {
						childList: true,
						subtree: true
					});
				}
			' );
		}

		/**
		 * Enqueues front-end assets such as PrismJS and Mermaid.
		 */
		public function enqueue_frontend_assets() {
			if ( apply_filters( 'pro_markdown_manager_enable_syntax_highlighting', true ) ) {
				wp_enqueue_style(
					'prismjs-style',
					'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.css',
					array(),
					'1.29.0'
				);

				wp_enqueue_script(
					'prismjs',
					'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js',
					array(),
					'1.29.0',
					true
				);

				wp_enqueue_script(
					'prismjs-autoloader',
					'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js',
					array( 'prismjs' ),
					'1.29.0',
					true
				);
			}

			// Add inline CSS to ensure proper Mermaid styling
			// We'll add this when Mermaid is actually needed
			$mermaid_css = '
				.mermaid {
					background: transparent;
					padding: 10px;
					border: none;
					font-family: monospace;
					text-align: center;
					display: block;
					margin: 10px 0;
				}
				
				.mermaid:not([data-processed]) {
					background: #f8f8f8;
					border: 1px dashed #ccc;
					min-height: 50px;
				}
				
				.mermaid[data-processed] {
					background: transparent;
					border: none;
				}
				
				.mermaid-error {
					background: #ffeeee;
					padding: 10px;
					border: 1px solid #ff0000;
					color: #333;
					display: block;
					margin: 10px 0;
				}
				
				pre code.language-mermaid,
				code.language-mermaid {
					/* Don\'t hide code blocks - let JavaScript handle conversion */
					display: block;
					background: #f5f5f5;
					padding: 10px;
					border: 1px solid #ddd;
					font-family: monospace;
					white-space: pre;
				}
				
				/* Ensure Mermaid diagrams are visible */
				.mermaid svg {
					max-width: 100%;
					height: auto;
				}
			';
			
			// Add the CSS inline
			wp_add_inline_style('pro-markdown-mermaid', $mermaid_css);
		}

		/**
		 * Test shortcode for Mermaid diagrams.
		 */
		public function test_mermaid_shortcode( $atts, $content = null ) {
			$this->ensure_mermaid_assets();
			$atts = shortcode_atts( array(
				'type' => 'flowchart',
			), $atts );
			
			if ( empty( $content ) ) {
				$content = "graph TD\nA[Test] --> B[Mermaid]";
			}
			
			return '<div class="mermaid">' . esc_textarea( $content ) . '</div>';
		}

		/**
		 * Enqueue Mermaid assets on pages that might contain Mermaid diagrams.
		 */
		public function maybe_enqueue_mermaid_assets() {
			// Check if we're on a single post/page
			if (is_singular()) {
				// Get the post content
				$post = get_post();
				if ($post && (false !== stripos($post->post_content, 'language-mermaid') || false !== stripos($post->post_content, '[mermaid'))) {
					// Pre-enqueue Mermaid assets to avoid FOUC
					$this->ensure_mermaid_assets();
				}
			}
		}

		/**
		 * Returns available post types for selection.
		 *
		 * @return array
		 */
		private function get_post_type_options() {
			$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
			$options    = array();

			foreach ( $post_types as $post_type => $object ) {
				if ( 'attachment' === $post_type ) {
					continue;
				}

				$options[ $post_type ] = $object->labels->singular_name;
			}

			natcasesort( $options );

			return $options;
		}
	}

$pro_markdown_manager = new Pro_Markdown_Manager();
register_activation_hook( __FILE__, array( 'Pro_Markdown_Manager', 'activate' ) );
}
