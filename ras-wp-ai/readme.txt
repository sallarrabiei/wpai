=== RAS WP AI ===
Contributors: ras
Tags: ai, chatgpt, openai, chatbot
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure, scope-aware ChatGPT integration for WordPress via shortcode and REST API. Server-only API calls with privacy controls.

== Description ==
RAS WP AI adds a minimal chat UI via shortcode and calls OpenAI Chat Completions on your server using `wp_remote_post()` to keep your API key secure. Includes:

- Shortcode `[raswpai_chat]` with attributes
- System prompt and scope control with out-of-scope refusal
- Multi-turn conversations
- Logging modes (none/anonymized/full) and retention
- Accessibility, RTL, and Tailwind styling

== Installation ==
1. Upload `ras-wp-ai` to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to Settings → RAS WP AI and enter your API key and defaults.

== Frequently Asked Questions ==
= Does this expose my API key? =
No. All API calls are made on the server.

= Can I change the model? =
Yes, via settings or shortcode attributes.

= Does it support multi-turn? =
Yes. Enable it in settings.

== Shortcode ==
`[raswpai_chat model="gpt-4o-mini" temperature="0.5" max_tokens="600" placeholder="Ask me…" theme="auto" title="Ask AI"]`

== Privacy ==
- Logging modes: none/anonymized/full
- Retention (days) and daily cleanup
- Optional data removal on uninstall

== Changelog ==
= 1.0.0 =
Initial release.