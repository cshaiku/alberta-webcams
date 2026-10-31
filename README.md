# Alberta Webcams

Restoration of Chris Simmons’s AWC site, previously at awc.cshaiku.com.

Repository visibility: **private**. The owner may make it public later. Do not change visibility automatically. Review source provenance, image reuse terms and private history before public release. No license has been granted to third parties yet.

The working example is intended for https://sltd.ca/awc/. Codex project folder: `/private/var/www/starlit-digital/alberta-webcams`. Adding this folder to the Codex sidebar is an owner step because the available app tools cannot create a project.

## Behaviour

* Overview displays the latest collected snapshot from every camera.
* The shared hosting scheduler saves a new set of JPEG images every 15 minutes, with previous images preserved when an individual camera fails. The manual retrieval button is removed.
* Front page image clicks open that camera’s full archive, newest first. The left rail groups images by month, day, hour and individual timestamp. Period controls filter the grid; individual timestamps and archive tiles open the enlarged viewer.
* Map shows a schematic province outline using official camera coordinates. Camera buttons provide an accessible alternative.
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

The original historical sources are largely obsolete. The first draft uses twelve current 511 Alberta camera feeds around the original highway areas and Edmonton. Their exact names and coordinates come from the provider. These are current replacements, not claims that the original urban cameras survived. The camera catalog is operator controlled in config/cameras.json. Remote URLs cannot be supplied through the browser.

DYWS is not integrated because this viewer has no ordering, booking or payment workflow. The design follows the recovered AWC Overview, Archive and Map structure instead of replacing it with a marketing template. The canonical Starlit preferences center is copied with its shared runtime. Local adaptations: default system sans typography and host colour tokens. The UI is reviewed using the installed Apple HIG skill’s accessibility, layout, typography, color, dark mode and buttons guidance, translated to native web controls. This is a web design review, not an Apple or accessibility certification.


## Premium modernization

The grid now uses the SiteCraft Studio compositions gallery reference, with a compact header, collapsible camera rail, regional filters, uniform image tiles, responsive WebP delivery and a native image viewer. The canonical collection has server rendered camera content, SEO metadata, structured data, sharing artwork and a sitemap. Archive and filtered URLs remain excluded from indexing. See [modernization details](docs/modernization.md) and [verification](docs/modernization-verification.md).

PHP GD with WebP support is required for optimized derivatives. Original JPEGs remain available. The private runtime persists across releases. The repository is still private, and any future public release remains an owner decision.

## Dense image workspace and scheduler

The owner requested an application style image wall. The default view now has no visible card labels, gaps, borders or outer padding. The camera drawer starts closed; compact controls and the enlarged viewer retain navigation and metadata. See [workspace update](docs/workspace-update.md).

A shared private CLI collector is deployed and registered in Hostinger hPanel on a 15 minute schedule. See [scheduling setup](docs/scheduling.md). Camera tiles use uniform 4:3 slots to prevent layout shifts while retaining complete source images.

The existing SDOU accessibility gear lives at the top right of the control bar. The bar hides while scrolling down and returns while scrolling up. Open menus, keyboard focus and the preferences dialog keep it visible. Reduced motion disables the transition.

## Camera timelines

Each camera has a separate page at `/awc/timeline.php?camera=<camera-id>`, linked from its archive grid. The page defaults to the latest archived day. Its large preview preserves the complete image, with responsive WebP delivery and original JPEG access. A centered chronological filmstrip with larger adjacent thumbnails, centered previous, play and next buttons, and user initiated play/pause offer day by day exploration. The strip supports Arrow keys, Home and End. Speed choices are 1500ms, 750ms and 300ms between decoded images. Playback stops at the end, when the tab is hidden, or when accessibility preferences gain focus. Hover previews on desktop and touch scrolling on phones are supported.

The URL preserves the selected date and snapshot. The slider, gap markers, external timestamp labels and visible footer status copy are removed. Embedded camera timestamps remain visible; accessible image descriptions retain retrieval times. The existing private archive API is reused. Invalid cameras return 404, missing days show an empty state, and date changes cancel stale requests. Timeline pages have camera specific titles and descriptions and remain noindex, follow, outside the collection sitemap. No new collector or runtime storage is introduced.

Validation: `tests/timeline.cjs` covers archive links, decoded previews, thumbnail selection, keyboard control, playback completion, selection on reload, empty dates, invalid cameras, the preferences dialog and responsive widths. Local tests can read live image and archive responses without writing production data. See `docs/timeline-deployment.json` for the scoped deployment receipt.

Timeline hover switches have no deliberate delay. A bounded LRU cache retains up to 24 decoded preview nodes and preloads nearby snapshots with three concurrent workers. Cached nodes are reused directly for immediate display. Existing immutable image responses supply the browser disk cache; archive metadata stays fresh. Date changes invalidate stale preview work. The main header places navigation, a camera icon for the title, and the day picker on the left. The camera name remains in the icon tooltip, accessible name and page heading. Playback and speed share the center, and accessibility stays on the right. Desktop uses one 48px row; phones at 650px or below use a second centered playback row.
