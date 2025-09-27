<?php
// Script to create a test page with Mermaid content
require_once '../../../wp-config.php';
require_once '../../../wp-includes/wp-db.php';

global $wpdb;

// Create a test page with Mermaid content
$page_data = array(
    'post_title'    => 'Mermaid Test Page',
    'post_content'  => '<h2>Mermaid Diagram Test</h2>
<pre><code class="language-mermaid">graph TD
    A[Christmas] -->|Get money| B(Go shopping)
    B --> C{Let me think}
    C -->|One| D[Laptop]
    C -->|Two| E[iPhone]
    C -->|Three| F[Car]</code></pre>
    
<h2>Another Mermaid Diagram</h2>
<code class="language-mermaid">graph TD
    A[Start] --> B[Process]
    B --> C[End]</code>',
    'post_status'   => 'publish',
    'post_type'     => 'page',
    'post_author'   => 1,
    'post_date'     => date('Y-m-d H:i:s'),
    'post_date_gmt' => gmdate('Y-m-d H:i:s')
);

$page_id = wp_insert_post($page_data);

if ($page_id) {
    echo "Test page created successfully with ID: " . $page_id . "\n";
    echo "You can view it at: " . get_permalink($page_id) . "\n";
} else {
    echo "Failed to create test page\n";
}