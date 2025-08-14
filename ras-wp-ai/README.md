# RAS WP AI

A secure, scope-aware ChatGPT integration for WordPress via shortcode and REST API. Server-side calls only, with privacy controls and optional logging.

## Requirements
- WordPress 6.0+
- PHP 7.4+ (tested on 8.1/8.2)

## Installation
1. Upload the `ras-wp-ai` folder to `wp-content/plugins/` or install the `ras-wp-ai.zip` via the Plugins screen.
2. Activate the plugin through the Plugins menu.
3. Go to Settings → RAS WP AI and enter your OpenAI API key and defaults.

## Usage
Place the shortcode where you want the chat UI:

```
[raswpai_chat]
```

### Shortcode attributes
- `model` (string): OpenAI model. Defaults to admin setting.
- `temperature` (float 0–2): Defaults to admin setting.
- `max_tokens` (int 1–4000): Defaults to admin setting.
- `placeholder` (string): Input placeholder text.
- `theme` (light|dark|auto): UI theme. Default from admin.
- `title` (string): Card title.

Example:

```
[raswpai_chat model="gpt-4o-mini" temperature="0.5" max_tokens="600" theme="auto" title="Ask Support"]
```

## Scope control
- Define a system prompt and scope topic/keywords in Settings.
- If a user’s message is out-of-scope, the assistant replies with: “Sorry, I can only answer questions related to {topic}.” The `{topic}` placeholder is replaced with your defined topic.
- You can disable out-of-scope detection.

## Privacy & GDPR
- API key is stored server-side and never exposed to the frontend.
- All API calls are server-side via `wp_remote_post()`.
- Conversation logging modes:
  - None: no logs stored.
  - Anonymized: content redacted; IP hashed with salt.
  - Full: complete content stored.
- Log retention is configurable (days). A daily cleanup runs automatically.
- Optional full data removal on uninstall (options and logs).

## Accessibility & UI
- ARIA roles and live regions for message updates.
- Keyboard/focus friendly.
- RTL layout support.
- Minimal Tailwind-based styling; respects light/dark/auto.

## Troubleshooting
- “API key not configured”: Enter a valid OpenAI key under Settings → RAS WP AI.
- “Invalid security token”: Clear caches and retry; ensure the page loads a fresh nonce.
- No response or error: Check server connectivity to `api.openai.com` and error logs.

## FAQ
- Does this expose my API key? No. All requests happen on the server; the key is never printed in HTML/JS.
- Can I change the model? Yes, in Settings or via shortcode attribute.
- Does it support multi-turn conversations? Yes. Enable it in Settings.
- Can I disable logging? Yes. Set logging to “None”.

## Developer Notes
- All functions, hooks, classes, routes, and option keys are prefixed with `raswpai_`.
- REST: `POST /wp-json/raswpai/v1/chat` with `message`, optional `session_id`, `nonce`, and `attrs`.
- Filter `raswpai_models` to alter the selectable model list.

## Changelog
- 1.0.0: Initial release.