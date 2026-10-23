<div align="center">

# ✨ Summarize with AI

**Let readers send any WordPress post to ChatGPT, Perplexity, Claude, Gemini and more, with one click.**

Prompt modes · Privacy-first analytics · `llms.txt` · Floating button · Gutenberg block · Elementor widget

[![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-21759b?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)](https://php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](LICENSE)
[![CI](https://github.com/Alireza-Fakhari/summarize-with-ai/actions/workflows/ci.yml/badge.svg)](https://github.com/Alireza-Fakhari/summarize-with-ai/actions/workflows/ci.yml)
[![GitHub stars](https://img.shields.io/github/stars/Alireza-Fakhari/summarize-with-ai?style=social)](https://github.com/Alireza-Fakhari/summarize-with-ai/stargazers)

[English](README.md) · [فارسی](README.fa.md)

<!-- Add a screenshot or GIF here: docs/hero.gif (buttons on a post + the analytics dashboard) -->

</div>

---

## Why?

Readers already paste your articles into AI assistants. **Summarize with AI** makes that one click, keeps your link in the prompt (so AI answers cite *your* page), and shows you exactly how readers use it.

## Features

| | |
|---|---|
| 🤖 **10 AI assistants built in** | ChatGPT, Perplexity, Claude, Gemini, Grok, Google AI Mode, Copilot, DeepSeek, Le Chat, You.com, plus unlimited custom ones |
| 🎛️ **Prompt modes** | Summary · Key points · Explain simply · Translate · Ask questions. Every prompt is editable, with `{url}` `{title}` `{site}` `{excerpt}` variables |
| 📋 **Copy & paste fallback** | Assistants without a prompt URL (Gemini, DeepSeek) get the prompt copied to the clipboard automatically |
| 📊 **Analytics dashboard** | Clicks, impressions, CTR, top assistants, modes, devices, top posts, insights and CSV export |
| 🔒 **Privacy-first** | No cookies, no IP addresses, no personal data. Only anonymous daily totals are stored |
| 🧭 **`llms.txt` generator** | Serves a cached Markdown index of your content for AI crawlers ([llmstxt.org](https://llmstxt.org)) |
| 🔗 **UTM tagging** | AI-referred visits show up in Google Analytics as `utm_source=ai-assistant` |
| 🎨 **6 styles + dark mode** | Outline, Filled, Pill, Minimal, Icons-only, Card, with light, dark or auto scheme and a live preview |
| 📍 **Smart placement** | Before/after content, per post type, minimum word count, excluded categories, per-post off switch and custom prompt |
| 🫧 **Floating button** | A thumb-friendly floating panel, per device |
| 🧱 **Block, Elementor, shortcode, PHP** | Use it anywhere |
| 🌍 **Translation-ready & RTL** | Ships with Persian (fa_IR); all CSS uses logical properties |
| ⚡ **Zero bloat** | No jQuery, no chart library, no external requests. Assets load only on pages that show the buttons, with `defer` |

## Installation

1. Download the latest `summarize-with-ai.zip` from [Releases](https://github.com/Alireza-Fakhari/summarize-with-ai/releases).
2. In WordPress go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate it and open **Summarize AI → Settings**.

## Usage

The buttons are added to posts automatically. For manual placement:

```text
[summarize_with_ai services="chatgpt,claude,perplexity" modes="summary,keypoints" template="pill" theme="auto"]
```

```php
<?php echo swai_render( array( 'template' => 'card', 'services' => array( 'chatgpt', 'claude' ) ) ); ?>
```

Or add the **Summarize with AI** block or Elementor widget.

## Developer hooks

```php
// Add or change built-in assistants.
add_filter( 'swai_builtin_services', function ( $services ) {
	$services['kagi'] = array(
		'name'  => 'Kagi',
		'url'   => 'https://kagi.com/assistant?q={q}',
		'color' => '#ffb319',
		'icon'  => '',
		'copy'  => false,
	);
	return $services;
} );

// Customize prompt modes.
add_filter( 'swai_modes', function ( $modes ) {
	$modes['tweet'] = array( 'label' => 'Tweet it', 'prompt' => 'Write a tweet about {url}', 'enabled' => 1 );
	return $modes;
} );

// Decide per post.
add_filter( 'swai_is_eligible', function ( $show, $post ) {
	return $show && ! has_tag( 'no-ai', $post );
}, 10, 2 );

// Edit the final HTML or llms.txt.
add_filter( 'swai_render_html', fn( $html, $args ) => $html, 10, 2 );
add_filter( 'swai_llms_txt', fn( $markdown ) => $markdown );
```

## Project structure

```
summarize-with-ai/
├── summarize-with-ai.php   # Bootstrap + PSR-4 autoloader
├── uninstall.php           # Full cleanup on delete
├── src/                    # Namespaced classes (SummarizeWithAI\…)
│   ├── Plugin.php  Settings.php  Services.php  Renderer.php  Frontend.php  Llms.php  Block.php  Icons.php
│   ├── Analytics/          # Store (aggregated table) + Rest (beacon endpoint)
│   ├── Admin/              # Dashboard, SettingsPage, MetaBox, Tools, UiIcons
│   └── Integrations/       # Elementor
├── blocks/summarize/       # block.json, editor script, server render
├── assets/{css,js}/        # Front end + admin, no build step
└── languages/              # .pot + fa_IR
```

## Privacy & security

- Analytics rows are aggregated per **day/post/assistant/mode/device**. Nothing identifies a visitor.
- The tracking endpoint validates every field against an allow-list and is rate-limited by a salted hash. The IP itself is never stored.
- Every admin action checks capabilities and nonces, all output is escaped, and CSV export guards against formula injection.

Found a vulnerability? See [SECURITY.md](SECURITY.md).

## Contributing

PRs are welcome! Read [CONTRIBUTING.md](CONTRIBUTING.md). New AI assistants, translations and design ideas are especially appreciated.

If this plugin helps you, **please ⭐ star the repo**. It really helps others find it.

## License

[GPL-2.0-or-later](LICENSE) © Alireza Fakhari
