# Pro Markdown Manager

Adds project-friendly controls for Markdown-powered content so you can decide where Markdown is stored and rendered. The plugin vendors Automattic's WordPress.com Markdown parser (WPCom_GHF_Markdown_Parser), delivering Jetpack-equivalent Markdown (footnotes, fenced code, shortcodes, etc.) without requiring Jetpack itself.

## Features
- Toggle between GitHub-Flavoured Markdown (Jetpack's `WPCom_Markdown`) and Michel Fortin's Markdown Extra.
- GUI settings to opt individual post types into Markdown storage.
- Optional "Markdown Output" toggle for ACF fields, with the ability to keep post_content raw.
- Automatic sanitisation with `wp_kses_post()` on render.
- Plays nicely with Jetpack when it's active (we automatically use its version of `WPCom_Markdown`).
- Built-in PrismJS syntax highlighting and Mermaid diagram support (version 10.9.4).

## Requirements
- WordPress 6.0+
- Advanced Custom Fields (optional, only for the field-level toggle).

## Installation
1. Copy the `pro-markdown-manager` directory into `wp-content/plugins/`.
2. Activate **Pro Markdown Manager** from **Plugins → Installed Plugins**.
3. (Optional) Zip the folder and upload via **Plugins → Add New → Upload Plugin** if distributing.

## Configuration
1. Navigate to **Settings → Markdown Manager**.
2. Tick the post types that should store Markdown (`post`, `page`, or any custom post type you've registered).
3. Choose your preferred parser (**GitHub Flavoured Markdown** or **Markdown Extra**).
4. (Optional) enable **Render post content** if you want the main editor (`post_content`) to be transformed; leave it unchecked to keep content stored and displayed as raw Markdown.
5. Enable **ACF field option** if you want the *Markdown Output* toggle to appear in ACF fields.

### Working with ACF
- Edit the field group, open any textarea or WYSIWYG field, and enable *Markdown Output*.
- The raw Markdown remains in postmeta; the plugin converts it to HTML during `acf/format_value`.
- Use `the_field()` / `get_field()` directly—avoid wrapping them in `esc_html()` or similar, otherwise Markdown HTML will be escaped.

### Custom Post Types
Every post type checked in the settings gains `pro-markdown` support. When GFM mode is active, the plugin also adds `wpcom-markdown` to ensure full Jetpack compatibility flow; when selecting Markdown Extra, we rely only on the built-in processor during output (post_content is only converted if the **Render post content** option is enabled).

### Templates & Troubleshooting
- Template tags that escape content (`esc_html`, `strip_tags`, etc.) will neutralise Markdown output. Switch to `wp_kses_post()` or print directly.
- Block-based posts (`has_blocks() === true`) are left untouched; create dedicated Markdown templates if you need hybrid pages.
- If Jetpack is installed, its version of `WPCom_Markdown` will be reused automatically.

### Mermaid Diagram Support
To create Mermaid diagrams in your Markdown content, use fenced code blocks with the `mermaid` language identifier:

```mermaid
graph TD
    A[Christmas] -->|Get money| B(Go shopping)
    B --> C{Let me think}
    C -->|One| D[Laptop]
    C -->|Two| E[iPhone]
    C -->|Three| F[Car]
```

Supported diagram types include:
- Flowcharts (graph)
- Sequence diagrams
- Class diagrams
- State diagrams
- Entity Relationship diagrams
- User Journey diagrams
- Gantt charts
- Pie charts
- Requirement diagrams
- Gitgraph diagrams

For more information about Mermaid syntax, visit [mermaid.js.org](https://mermaid.js.org/).

## Development Notes
- Core Markdown sources live in `includes/wpcom/` and originate from Automattic's Jetpack plugin (GPL-compatible).
- `Jetpack_Options` is shimmed only in GFM mode to mimic Jetpack behavior.
- `includes/class-pro-markdown-parser.php` receives the parser mode (`gfm` or `markdown_extra`) and handles fallback/integration with Jetpack when available.

## License & Credits
- Licensed under the GPLv2 (or later) in keeping with WordPress & Jetpack.
- `includes/wpcom/` directory contains code copied from [Automattic/Jetpack](https://github.com/Automattic/jetpack) and retains original headers.