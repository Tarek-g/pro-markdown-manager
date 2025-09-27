<?php
// This script checks the current settings for the Pro Markdown Manager plugin

// Load WordPress
require_once '../../../wp-load.php';

// Get the plugin settings
$settings = get_option('pro_markdown_manager_settings', array());

// Display the settings
echo "<h1>Pro Markdown Manager Settings</h1>";
echo "<pre>";
print_r($settings);
echo "</pre>";

// Check if post content rendering is enabled
if (!empty($settings['render_post_content'])) {
    echo "<p><strong>Post content rendering is ENABLED</strong></p>";
} else {
    echo "<p><strong>Post content rendering is DISABLED</strong></p>";
}

// Check which post types are enabled
if (!empty($settings['post_types']) && is_array($settings['post_types'])) {
    echo "<p><strong>Enabled post types:</strong> " . implode(', ', $settings['post_types']) . "</p>";
} else {
    echo "<p><strong>No post types enabled</strong></p>";
}

// Check parser mode
if (!empty($settings['parser_mode'])) {
    echo "<p><strong>Parser mode:</strong> " . $settings['parser_mode'] . "</p>";
} else {
    echo "<p><strong>Parser mode not set</strong></p>";
}

// Check ACF support
if (!empty($settings['acf_support'])) {
    echo "<p><strong>ACF support is ENABLED</strong></p>";
} else {
    echo "<p><strong>ACF support is DISABLED</strong></p>";
}
?>