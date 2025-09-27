<?php
/**
 * Loader for the Markdown library.
 *
 * This file loads in a couple specific things from the markdown dir.
 *
 * @package automattic/jetpack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

$base_dir = dirname( __FILE__ );

if ( ! class_exists( 'MarkdownExtra_Parser' ) ) {
	require_once $base_dir . '/markdown/extra.php';
}

require_once $base_dir . '/markdown/gfm.php';
