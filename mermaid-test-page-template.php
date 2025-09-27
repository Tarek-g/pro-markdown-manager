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
                
                <h2>Test 3: More complex diagram</h2>
                <pre><code class="language-mermaid">
graph TD
    %% ✅ المدخلات
    subgraph المدخلات
        A(نموذج مشغل ن8ن - إدخال الموضوع) --> B(ملاحظة لاصقة 1 - قائد البحث 🔬)
    end

    %% ✅ التخطيط والتفويض
    subgraph التخطيط والتفويض
        B --> C(قائد البحث 🔬 - وكيل)
        C -- أداة بحث Perplexity --> C
        C --> D(OpenAI Chat Model1)
        D --> E(محلل المخرجات المهيكلة)
        E --> F(ملاحظة لاصقة 2 - مخطط المشروع 📅)
        F --> G(مخطط المشروع - وكيل)
        G -- أداة بحث Perplexity2 --> G
        G --> H(OpenAI Chat Model2)
        H --> I(مندوب لمساعدي البحث - تقسيم الفصول)
        I --> J(ملاحظة لاصقة 3 - فريق مساعدي البحث ✍️)
    end
</code></pre>
            </div>
        </article>
    </main>
</div>

<?php get_footer(); ?>