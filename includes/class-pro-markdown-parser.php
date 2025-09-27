<?php
/**
 * Markdown transform helper for Pro Markdown Manager.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pro_Markdown_Manager_Parser' ) ) {
	/**
	 * Provides a unified Markdown transform API using the WordPress.com module when available and
	 * falling back to the bundled parser when that module is inactive.
	 */
	class Pro_Markdown_Manager_Parser {

		/**
		 * Cached fallback parser instance.
		 *
		 * @var WPCom_GHF_Markdown_Parser|false|null
		 */
		private static $fallback_parser = null;

		/**
		 * Converts Markdown text into HTML via the WordPress.com module or a bundled fallback parser.
		 *
		 * @param string     $text    Raw Markdown text.
		 * @param int|string $post_id Optional context identifier.
		 * @param bool       $unslash Whether to unslash the text before processing.
		 * @return string Parsed HTML output.
		 */
		public static function transform( $text, $post_id = 0, $unslash = false ) {
			if ( '' === $text || null === $text ) {
				return '';
			}

			if ( 'gfm' === PRO_MARKDOWN_MANAGER_PARSER_MODE && class_exists( 'WPCom_Markdown' ) ) {
				$markdown = WPCom_Markdown::get_instance();
				$args     = array(
					'unslash' => $unslash,
					'id'      => is_numeric( $post_id ) ? absint( $post_id ) : false,
				);

				return $markdown->transform( $text, $args );
			}

			$parser = self::get_fallback_parser();
			if ( ! $parser ) {
				return $text;
			}

			$is_wpcom_parser = ( $parser instanceof WPCom_GHF_Markdown_Parser );

			if ( $unslash ) {
				$text = wp_unslash( $text );
			}

			if ( function_exists( 'has_blocks' ) && has_blocks( $text ) ) {
				return $text;
			}

			$args = array(
				'id'      => is_numeric( $post_id ) ? absint( $post_id ) : false,
				'parser'  => $is_wpcom_parser ? 'gfm' : 'markdown_extra',
			);

			$text = apply_filters( 'wpcom_markdown_transform_pre', $text, $args ) ?? '';

			if ( $is_wpcom_parser ) {
				$args['unslash']            = false;
				$args['decode_code_blocks'] = ! $parser->use_code_shortcode;
				$text                       = str_replace( array( '</p><p>', "</p>\n<p>" ), "</p>\n\n<p>", $text );
				$text                       = $parser->unp( $text );
				$text                       = preg_replace( '/^&gt;/m', '>', $text );
				$parser->fn_id_prefix        = $args['id'] ? $args['id'] . '-' : '';

				if ( $args['decode_code_blocks'] ) {
					$text = $parser->codeblock_restore( $text );
				}

				$text = $parser->transform( $text );
				$text = preg_replace( '/((id|href)="#?fn(ref)?):/', '$1-', $text );
				$text = rtrim( $text );
			} else {
				$text = $parser->transform( $text );
			}

			$text = apply_filters( 'wpcom_markdown_transform_post', $text, $args );

			return $text;
		}

		/**
		 * Determines if any Markdown parser is available.
		 *
		 * @return bool
		 */
		public static function is_available() {
			if ( class_exists( 'WPCom_Markdown' ) ) {
				return true;
			}

			return (bool) self::get_fallback_parser();
		}

		/**
		 * Loads the WordPress.com Markdown library when the module itself is inactive.
		 *
		 * @return WPCom_GHF_Markdown_Parser|false
		 */
		private static function get_fallback_parser() {
			if ( null !== self::$fallback_parser ) {
				return self::$fallback_parser;
			}

			// If a parser is already present (via Jetpack or manual include), reuse it.
			if ( class_exists( 'WPCom_GHF_Markdown_Parser' ) && 'markdown_extra' !== PRO_MARKDOWN_MANAGER_PARSER_MODE ) {
				self::$fallback_parser = new WPCom_GHF_Markdown_Parser();
				return self::$fallback_parser;
			}

			$vendor_dir    = trailingslashit( dirname( __FILE__ ) ) . 'wpcom';
			$markdown_file = $vendor_dir . '/_inc/lib/markdown.php';

			if ( file_exists( $markdown_file ) ) {
				require_once $markdown_file;

				if ( 'markdown_extra' === PRO_MARKDOWN_MANAGER_PARSER_MODE && class_exists( 'MarkdownExtra_Parser' ) ) {
					self::$fallback_parser = new MarkdownExtra_Parser();
					return self::$fallback_parser;
				}

				if ( class_exists( 'WPCom_GHF_Markdown_Parser' ) ) {
					self::$fallback_parser = new WPCom_GHF_Markdown_Parser();
					return self::$fallback_parser;
				}
			}

			if ( 'markdown_extra' !== PRO_MARKDOWN_MANAGER_PARSER_MODE ) {
				$jetpack_dir   = trailingslashit( WP_PLUGIN_DIR ) . 'jetpack';
				$markdown_file = $jetpack_dir . '/_inc/lib/markdown.php';

				if ( file_exists( $markdown_file ) ) {
					if ( ! defined( 'JETPACK__PLUGIN_DIR' ) ) {
						define( 'JETPACK__PLUGIN_DIR', $jetpack_dir );
					}

					require_once $markdown_file;

					if ( class_exists( 'WPCom_GHF_Markdown_Parser' ) ) {
						self::$fallback_parser = new WPCom_GHF_Markdown_Parser();
						return self::$fallback_parser;
					}
				}
			}

			self::$fallback_parser = class_exists( 'MarkdownExtra_Parser' ) ? new MarkdownExtra_Parser() : false;

			return self::$fallback_parser;
		}
	}
}
