# Modernization verification

Verified October 31, 2026 at https://sltd.ca/awc/.

* PHP syntax and camera contract checks pass. New image delivery tests verify WebP output, proportional resize, original preservation, no upscaling and rejection of arbitrary variant sizes and traversal.
* Browser tests pass locally and live for collection filtering, search, three image densities, archive dates and empty states, map, enlarged image dialog, Escape, camera rail and accessibility center. Desktop, tablet, 390 and 320 pixel layouts reflow. A 320 pixel viewport at 200 percent root text size also reflows.
* Chromium and WebKit render twelve camera cards without page overflow. The Firefox executable was not installed, so no Firefox result is claimed.
* The live Overview axe scan reports zero violations. Text contrast checks pass across all 32 page shades. High contrast endpoint checks also exercise the open preferences panel. These are scoped automated checks. Diagnostic axe injections can produce CSP console messages under the host’s strict policy; the live page itself is free of runtime console errors before diagnostic injection.
* The page’s initial HTML contains twelve camera cards, eleven picture elements, descriptive metadata and valid WebSite and WebPage JSON-LD. The one absent image is a source outage. A live manual retrieval again saved eleven images and reported one unavailable camera.
* The SiteCraft SEO audit reports zero issues after deployment, compared with missing canonical, sharing metadata, structured data, sitemap and indexing policy gaps in the baseline. Query views return noindex,follow in both HTML and HTTP. The collection returns index,follow. The sitemap contains only the canonical collection, and the actual host robots file advertises it.
* Responsive picture selection uses WebP, with real JPEG fallback and intrinsic dimensions. One live sample decreased from 36,087 JPEG bytes to 12,528 bytes for its 400 pixel WebP derivative, a 65.3 percent reduction. Source watermarks and timestamps are retained.
* One synthetic Chromium lab run observed LCP 740 ms and CLS 0.0024 on desktop, and LCP 612 ms and CLS 0.0132 at 390 pixels with 1.6 Mbps download, 100 ms latency and 4x CPU throttling. These are single run observations, not Lighthouse scores or field Core Web Vitals. INP, Search Console, indexing and ranking results are not claimed.
* All twenty two deployed public source file digests match local source, excluding the intentional private production bootstrap wrapper. Existing runtime records remain outside the public root and were not replaced. Private before images include the full previous AWC directory and the host robots file.
* Gitleaks found no secrets. Source, editable artwork and reports are retained in the private cshaiku/alberta-webcams repository. Camera photographs, runtime state and legacy recovery archives remain excluded from Git.

Machine readable receipt: modernization-deployment.json. Public file fingerprints: modernization-sha256.txt. Raw audit, browser, contrast, performance and screenshot evidence remains under the ignored output/modernization-20261031 folder.
