# ElevenLabs Reader Headless GraphQL

**Version:** 1.3.0 | **Minimum PHP:** 7.4

A WordPress plugin that converts published posts and pages into spoken audio using ElevenLabs. Audio is generated server-side, cached as MP3 files, and played through a customizable reader. Supports conventional WordPress and headless frontends through WPGraphQL.

## Features

- Server-side text-to-speech with configurable voice, model and voice settings.
- Automatic generation on publication or update, plus manual generation from WordPress administration.
- Cached MP3 playback without visitor-triggered ElevenLabs requests.
- Play/pause, seek bar, elapsed time, playback speed and 15-second skip controls.
- Customizable player color, visibility and labels; optional Polylang translation support.
- Shortcode, REST API and WPGraphQL integration.
- Superfunky `funkycommerceThemeStyles.customCss` integration.

## Requirements

- WordPress with PHP 7.4 or newer.
- ElevenLabs account, valid API key, supported voice/model and sufficient quota.
- Writable WordPress uploads directory and outbound HTTPS access to ElevenLabs.
- WPGraphQL for headless GraphQL integration (optional for conventional WordPress).
- Polylang for translated player labels (optional).
- JavaScript-enabled browser.

## Installation

1. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
2. Upload the plugin ZIP, install and activate it. Alternatively, extract the files into `wp-content/plugins/elevenlabs-wp-reader/` and activate the plugin.
3. Open **Settings → ElevenLabs Reader**.
4. Enter your ElevenLabs API key, voice ID and model ID.
5. Configure the player and save changes.
6. Generate audio for a published post or page before testing playback.

## Configuration

| Setting | Default |
| --- | --- |
| API key | Not configured |
| Voice ID | `JBFqnCBsd6RMkjVDRZzb` |
| Model ID | `eleven_multilingual_v2` |
| Stability | 0.5 |
| Similarity boost | 0.75 |
| Style exaggeration | 0 |
| Speaker boost | Enabled |
| Reading mode | `post` |
| Automatic generation | Enabled |
| Player / title / speed / skip | Enabled |
| Status text | Disabled |
| Player color | `#5b9f4d` |
| Default listen label | Listen to this page |

Player labels can be customized in settings and translated through Polylang when installed. Leaving the API key field blank when saving retains the existing key.

## Generating audio

**Automatic:** With automatic generation enabled, publishing or updating a post/page generates audio on the server. This runs during the save operation, not through a separate background queue, and may increase save time.

**Manual:** Open Settings → ElevenLabs Reader, enter a published post/page ID, choose the audio mode and submit the manual generation form. The admin notice reports success or an error.

Reading modes:

- `post`: reads stored post content with shortcodes and HTML removed.
- `page`: applies WordPress content filters and shortcodes before extracting text. It does not include theme chrome or browser-only rendered content.

## Shortcode usage

Insert a Shortcode block in WordPress:

```text
[elevenlabs_reader]
[elevenlabs_reader mode="page"]
[elevenlabs_reader post_id="123" mode="post" title="1" status="1" speed="1" skip="1"]
```

Supported attributes: `post_id`, `mode`, `label`, `title`, `status`, `speed`, `skip`. Use `1` or `0` for visibility switches. Replace `123` with your actual published content ID.

## REST API

### Retrieve cached audio (public)

```http
GET /wp-json/elwr/v1/audio/123?mode=post
```

Example successful response (illustrative URL):

```json
{
  "ready": true,
  "post_id": 123,
  "mode": "post",
  "audio_url": "https://example.com/wp-content/uploads/elevenlabs-wp-reader/elwr-HASH.mp3",
  "mime_type": "audio/mpeg"
}
```

The actual successful response also includes translated `labels`. Missing audio returns HTTP 404 with `ready: false`. Unpublished or unsupported content is rejected.

### Generate audio (authenticated)

```http
POST /wp-json/elwr/v1/generate/123
Content-Type: application/json
X-WP-Nonce: YOUR_WP_REST_NONCE

{"mode":"post"}
```

Requires an authenticated WordPress session, `edit_posts` capability and a valid REST nonce. The public playback endpoint never generates audio.

## Headless WordPress / WPGraphQL

Query reader markup and frontend assets from your WordPress GraphQL endpoint:

```graphql
query ReaderIntegration {
  posts(first: 10) {
    nodes {
      databaseId
      title
      elwrReaderHtml
    }
  }
  elwrHeadlessAssets {
    css
    scriptUrl
    styleUrl
    apiBaseUrl
    labelsJson
  }
}
```

`elwrReaderHtml` is registered on the `Post` type and returns shortcode-generated markup for published posts. The assets object exposes public CSS, JavaScript URL, stylesheet URL, REST base URL and JSON-encoded labels.

### Frontend integration checklist

1. Fetch the post markup and assets using GraphQL.
2. Render the trusted WordPress reader markup in the article container.
3. Load `styleUrl` or inject the returned `css` once. Returned CSS is scoped to `.blog`, `.single-post` or `[data-blog-root]`; ensure the player is inside one of these containers.
4. Optionally initialize `window.ELWR` with `endpoint: apiBaseUrl` and `labels: JSON.parse(labelsJson)` before loading the script.
5. Load `scriptUrl` once in the browser, not during server-side rendering. Its delegated click listener supports dynamically inserted player markup.
6. Verify that the frontend can fetch the REST endpoint and play the returned MP3. For separate origins, configure appropriate CORS and HTTPS access.

The plugin also appends scoped reader CSS to the existing Superfunky `funkycommerceThemeStyles.customCss` GraphQL result when that field exists. It does not create the Superfunky field itself.

## Audio storage and caching

Audio is stored under the configured WordPress uploads directory in `elevenlabs-wp-reader/`. Cache filenames are SHA-256 hashes derived from post ID, reading mode, extracted content and voice settings. Existing valid cache files are reused. Content or voice changes can produce a new cache file; old files are not automatically removed. Long text is split into chunks of up to approximately 4,500 characters and the returned MP3 data is combined.

## Security and operational notes

- The ElevenLabs API key is stored in WordPress options and is not exposed by the frontend markup or public REST/GraphQL responses.
- The manual admin form requires `manage_options` and a nonce; the generation REST route uses the separate permissions described above.
- Cached MP3 URLs are public; do not use this implementation for confidential audio.
- Text from published content is transmitted to ElevenLabs during generation. Generation consumes account quota.
- No automated cache cleanup, bulk generated-audio inventory or dedicated job queue is implemented in this version.

## Troubleshooting

| Issue | What to check |
| --- | --- |
| Audio not ready / REST 404 | Generate audio for the correct published ID and reading mode. |
| ElevenLabs HTTP 400 or other API error | Verify key, voice/model compatibility, quota and account permissions. |
| Player appears without styling | Load CSS and check the required wrapper for scoped CSS. |
| Play button does nothing | Confirm `reader.js` is loaded once and inspect browser console/network errors. |
| Audio ready but playback fails | Check MP3 URL, HTTP response, HTTPS, browser autoplay restrictions and press play again. |
| GraphQL field missing | Confirm plugin and WPGraphQL are active, refresh schema/cache and check PHP logs. |
| MP3 cannot be saved | Verify uploads permissions and available disk space. |
| Slow post updates | Automatic generation runs synchronously; consider manual generation for long content. |

## File structure

```text
elevenlabs-wp-reader/
├── elevenlabs-wp-reader.php  # Settings, shortcode, generation and REST API
├── headless-graphql.php     # WPGraphQL fields and scoped CSS integration
├── assets/
│   ├── reader.css           # Player styling
│   └── reader.js            # Browser playback and REST fetching
└── README.md                # Documentation
```

## Version information

Version 1.3.0 is the version declared in the supplied plugin source. No license, author or support URL is declared in that source; confirm these details with the plugin owner before public distribution.
