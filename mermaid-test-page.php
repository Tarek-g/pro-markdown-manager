<?php
/*
Template Name: Mermaid Test Page
*/

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title">Mermaid Test Page</h1>
            </header>
            
            <div class="entry-content">
                <h2>Test 1: Direct Mermaid div</h2>
                <div class="mermaid">
graph TD
    A[Start] --> B[Process]
    B --> C[End]
                </div>
                
                <h2>Test 2: Code block that should be converted</h2>
                <pre><code class="language-mermaid">graph TD
    D[Test] --> E[Conversion]
    E --> F[Result]
</code></pre>
                
                <h2>Test 3: Using shortcode</h2>
                [test_mermaid]
graph TD
    G[Shortcode] --> H[Test]
    H --> I[Works]
[/test_mermaid]
            </div>
        </article>
    </main>
</div>

<?php get_footer(); ?>