# Alberta Webcams

Restoration of Chris Simmons’s AWC site, previously at awc.cshaiku.com.

Repository visibility: **private**. The owner may make it public later. Do not change visibility automatically. Review source provenance, image reuse terms and private history before public release. No license has been granted to third parties yet.

The working example is intended for https://sltd.ca/awc/. Codex project folder: `/private/var/www/starlit-digital/alberta-webcams`. Adding this folder to the Codex sidebar is an owner step because the available app tools cannot create a project.

## Behaviour

* Overview displays the latest collected snapshot from every camera.
* The shared hosting scheduler saves a new set of WebP images every 15 minutes, with previous images preserved when an individual camera fails. The manual retrieval button is removed.
* Front page image clicks open that camera’s full archive, newest first. The left rail groups images by month, day, hour and individual timestamp. The left rail filters the grid; individual timestamps open the enlarged viewer, while archive images open the separate timeline page at that snapshot.
* Map uses OpenStreetMap and Leaflet with interactive camera pins, clustering, search, regional filters and camera preview cards. The camera list provides an accessible alternative.
* The camera rail collapses to give images more room. On phones it opens as a drawer. The desktop collection uses three equal columns.
* All times use America/Edmonton. UI timestamps record retrieval time; a source image may have an older embedded capture timestamp.
* Retrieval requires a session CSRF token and shares a one minute server cooldown and exclusive lock across visitors. Shared hosting retrieves images every 15 minutes through a private CLI cron job.

## Run locally

Requires PHP 8.1 or later with cURL. No frontend build is required.

```sh
make serve
```

Open http://127.0.0.1:8768/awc/. Runtime images and state are excluded from Git. `make test` checks backend validation and frontend syntax. Browser verification is documented in docs/verification.md.

Only `public/` is the document root. Keep `app/`, `config/` and runtime outside it. Production deployment uses a private versioned app directory and a shared private runtime; the public bootstrap points to that release.

## Recovery and source status

The original ZIP files remain unchanged in Google Drive. Selected originals are preserved privately at `~/.local/share/alberta-webcams/recovered`, outside this repository and the web root. See docs/recovery.md for archive paths and known differences. Legacy files must never execute in production.

The original historical sources are largely obsolete. The network now uses eighteen current 511 Alberta camera feeds across the province. Their exact names and coordinates come from the provider. These are current replacements, not claims that the original urban cameras survived. The camera catalog is operator controlled in config/site.json. Remote URLs cannot be supplied through the browser.

DYWS is not integrated because this viewer has no ordering, booking or payment workflow. The design follows the recovered AWC Overview, Archive and Map structure instead of replacing it with a marketing template. The canonical Starlit preferences center is copied with its shared runtime. Local adaptations: default system sans typography and host colour tokens. The UI is reviewed using the installed Apple HIG skill’s accessibility, layout, typography, color, dark mode and buttons guidance, translated to native web controls. This is a web design review, not an Apple or accessibility certification.


## Premium modernization

The grid now uses the SiteCraft Studio compositions gallery reference, with a compact header, collapsible camera rail, regional filters, uniform image tiles, responsive WebP delivery and a native image viewer. The canonical collection has server rendered camera content, SEO metadata, structured data, sharing artwork and a sitemap. Archive and filtered URLs remain excluded from indexing. See [modernization details](docs/modernization.md) and [verification](docs/modernization-verification.md).

PHP GD with WebP support is required for capture and optimized derivatives. Captures and responsive variants are WebP only; no JPEG or PNG capture copies are retained. The private runtime persists across releases. The repository is still private, and any future public release remains an owner decision.

## Dense image workspace and scheduler

The owner requested an application style image wall. The default view now has no visible card labels, gaps, borders or outer padding. The camera drawer starts closed; compact controls and the enlarged viewer retain navigation and metadata. See [workspace update](docs/workspace-update.md).

A shared private CLI collector is deployed and registered in Hostinger hPanel on a 15 minute schedule. See [scheduling setup](docs/scheduling.md). Camera tiles use uniform 4:3 slots to prevent layout shifts while retaining complete source images.

The existing SDOU accessibility gear lives at the top right of the control bar. The bar hides while scrolling down and returns while scrolling up. Open menus, keyboard focus and the preferences dialog keep it visible. Reduced motion disables the transition.

## Camera timelines

Each camera has a separate page at `/awc/timeline.php?camera=<camera-id>`, opened by clicking an image in its archive grid. The page defaults to the latest archived day. Its large preview preserves the complete image, with responsive WebP delivery and full-size WebP access. A centered chronological filmstrip with larger adjacent thumbnails, centered previous, play and next buttons, and user initiated play/pause offer day by day exploration. The strip supports Arrow keys, Home and End. Speed choices are 1500ms, 750ms and 300ms between decoded images. Playback stops at the end, when the tab is hidden, or when accessibility preferences gain focus. Hover previews on desktop and touch scrolling on phones are supported.

The URL preserves the selected date and snapshot. The slider, gap markers, external timestamp labels and visible footer status copy are removed. Embedded camera timestamps remain visible; accessible image descriptions retain retrieval times. The existing private archive API is reused. Invalid cameras return 404, missing days show an empty state, and date changes cancel stale requests. Timeline pages have camera specific titles and descriptions and remain noindex, follow, outside the collection sitemap. No new collector or runtime storage is introduced.

Validation: `tests/timeline.cjs` covers archive links, decoded previews, thumbnail selection, keyboard control, playback completion, selection on reload, empty dates, invalid cameras, the preferences dialog and responsive widths. Local tests can read live image and archive responses without writing production data. See `docs/timeline-deployment.json` for the scoped deployment receipt.

Timeline hover switches have no deliberate delay. A bounded LRU cache retains up to 24 decoded preview nodes and preloads nearby snapshots with three concurrent workers. Cached nodes are reused directly for immediate display. Existing immutable image responses supply the browser disk cache; archive metadata stays fresh. Date changes invalidate stale preview work. The main header places navigation, a camera icon and visible title, and the day picker on the left. The camera name is visible beside the icon, with its full text retained in the tooltip and accessible heading when narrow layouts truncate it. Playback and speed share the center, and accessibility stays on the right. Desktop uses one 48px row; phones at 650px or below use a second centered playback row.

The archive toolbar row is removed from view. Its month, day and hour rail provides navigation; every archive image links directly to its timeline snapshot.

Navigation terminology: Network is the collection of cameras, Grid is one camera’s archived image grid, and Timeline is that camera’s time strip. Timeline links preserve the selected camera. Click the empty left or right side of the large timeline image to move through cameras in catalog order, wrapping at the ends. The selected day and snapshot time are carried in the destination URL. Gutter links are native keyboard accessible links and show directional cues on hover or focus.

## WebP only storage

The provider returns JPEG bytes, which are decoded and converted directly to full-size WebP at quality 84 before saving. No JPEG file is written. A capture is published only after its WebP file decodes and retains the original dimensions. The collector prepares bounded display candidates before updating camera state. Candidates at or above the master width reuse the master, avoiding duplicate files and upscaling. JPEG and PNG fallbacks are intentionally unsupported by owner direction. All display and full-size links explicitly request WebP.

The shared CLI enables its existing installed GD module through a one-time relaunch when needed. The hPanel cron command stays the same. `tools/migrate-webp.php --prune` verifies archived conversions, removes source JPEGs and removes redundant full-size variants, with no recovery image copies retained. The October 31 migration converted 99 hosted captures and removed 141 redundant WebP variants. A direct collector validation saved 11 additional captures with all 33 logical display candidates ready and zero incomplete conversions. The next scheduled run uses the same collector.

## Compare moments and live refresh

Each camera has a Compare page at `/awc/compare.php?camera=<id>`, linked beside Grid and Timeline. Choose two saved moments across archived days, drag the divider or use the keyboard accessible range, swap the images, and copy a link preserving both selections. The preview preserves complete images and reuses bounded WebP image delivery. Camera title, descriptive controls, empty and failure states, and the existing accessibility gear are retained. Comparison pages have camera specific titles, descriptions and canonical URLs, remain noindex, follow and stay outside the Network sitemap.

Network, Grid, Timeline and Compare check for new saved captures every fifteen minutes without a document reload. Filters and historical selections are preserved. Timeline follows a new capture only when already viewing its latest frame; playback and the preferences dialog defer refresh. Compare advances its After selection when it was already the latest snapshot. Hidden tabs catch up on return. The browser reads saved data and never invokes collection; the shared hosting cron remains the only automatic collector. Failed refreshes preserve current images. `tests/live.cjs` advances the browser clock to verify the interval and preserved search, and `tests/compare.cjs` verifies image decode, pointer drag, keyboard range, swapping, selection on reload, preferences and narrow layouts. See `docs/compare-live-deployment.json`.

## Interactive camera map

The owner selected OpenStreetMap instead of configuring Google Maps. The former schematic is replaced with a full viewport interactive map, floating camera browser, nearby marker clusters, fit and zoom controls and selected camera detail cards. Card links lead to that camera’s Grid, Timeline and Compare pages. Mobile results collapse, and the preview becomes a compact bottom sheet. Theme tokens and existing accessibility controls remain in use; motion follows the reduced motion preference. The current eighteen catalog coordinates are used without geolocation or external search requests.

Leaflet 1.9.4 is vendored under `public/assets/vendor/leaflet`, with its license retained. OSM Standard raster tiles are requested directly by browsers over HTTPS, with normal HTTP caching, visible OpenStreetMap contributor attribution and a report issue link. The AWC scoped CSP permits that image host; no external script host is needed. No tile prefetch, scraping, proxy or offline download feature is installed. Automated tests mock tiles, including failure cases; the actual live tile rendering was verified once in the owner’s Chrome browser. Review OSM’s best effort service policy as traffic grows. References: https://leafletjs.com/examples/quick-start/ and https://operations.osmfoundation.org/policies/tiles/ . See `docs/map-deployment.json`.

## JSON configuration

`config/site.json` is the authoritative versioned configuration, with `schemaVersion: 1`. It contains site identity and timezone, retrieval limits and refresh interval, WebP quality and candidate widths, desktop column count and accessibility defaults, timeline cache and playback settings, comparison caching, map tile settings and clustering, and the complete camera catalog including coordinates and provider image view URLs. PHP validates configuration before use. Unsafe source hosts, duplicate IDs, invalid coordinates and invalid settings are rejected. Only the public settings subset is embedded as inert JSON in page HTML; older open tabs can use the read-only `api.php?action=settings` endpoint. Feed URLs, private paths and collector limits are excluded from that subset.

Deployment overrides use private, ignored `config/local.json`, currently just `data_root`. The active `config/local.php` and old separate camera catalog were removed. Runtime state and image archives remain outside Git and outside the public document root. The 15 minute Hostinger cron is configured in hPanel; changing the interval in JSON changes browser refresh timing and does not reconfigure the external hPanel schedule. Keep both intervals consistent if changing them.

The expanded network includes Jasper park gate, the Highway 63 corridor south of Fort McMurray, Medicine Hat, Lethbridge, Waterton and Lake Louise. Bow Trail in west Calgary was deliberately omitted by owner direction to keep eighteen cameras, exactly six desktop rows of three. A collection after deployment saved all eighteen images as WebP with zero failures and all display candidates prepared. Live browser suites cover Network, Grid, Map, Timeline, Compare and 15 minute refresh. `tests/settings.php` verifies configuration propagation and rejects malformed or unsafe configuration.
