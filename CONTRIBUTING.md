# Contributing

Thanks for helping! Bug reports, new AI assistants, translations and design ideas are all welcome.

## Development setup

```bash
git clone https://github.com/Alireza-Fakhari/summarize-with-ai.git
cd summarize-with-ai
composer install          # dev tools only (PHPCS + WordPress Coding Standards)
npx @wp-playground/cli server --auto-mount   # instant WordPress with the plugin mounted
```

There is no build step: edit files in `src/`, `assets/` and `blocks/` directly.

## Standards

- PHP follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). Run `composer lint` before pushing.
- Classes live in `src/` under the `SummarizeWithAI\` namespace (PSR-4).
- Escape all output, sanitize all input, check capabilities and nonces.
- Front-end JS stays dependency-free and small.
- Every user-facing string is translatable with the `summarize-with-ai` text domain.

## Adding an AI assistant

Add it to `Services::builtin()` in `src/Services.php` (name, URL with `{q}`, brand color) and, optionally, an SVG to `Icons::svg()`.

## Pull requests

1. Fork and create a branch: `feat/my-change` or `fix/issue-123`.
2. Keep PRs focused and describe how you tested them.
3. Update `CHANGELOG.md` under *Unreleased*.
