# Alberta Webcams

Restoration of Chris Simmons’s AWC site, previously at awc.cshaiku.com.

Repository visibility: **private**. The owner may make it public later. Do not change visibility automatically. Review source provenance, image reuse terms and private history before public release. No license has been granted to third parties yet.

The working example is intended for https://sltd.ca/awc/. Codex project folder: `/private/var/www/starlit-digital/alberta-webcams`. Adding this folder to the Codex sidebar is an owner step because the available app tools cannot create a project.

## Behaviour

* Overview displays the latest manually retrieved snapshot from every camera.
* The header’s Retrieve images button saves a new set of JPEG images, with individual failures reported and previous images preserved.
* Archive browses one camera by date and hour, with an enlarged viewer and previous or next image controls.
* Map shows a schematic province outline using official camera coordinates. Camera buttons provide an accessible alternative.
* The camera rail collapses to give images more room. On phones it opens as a drawer. Large and compact image sizes are available.
* All times use America/Edmonton. UI timestamps record retrieval time; a source image may have an older embedded capture timestamp.
* Retrieval requires a session CSRF token and shares a one minute server cooldown and exclusive lock across visitors. No scheduled jobs, automatic polling or background image collection are installed.

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
