# Verification

October 31, 2026.

* PHP 8.5 local lint and contract tests pass. Production targets PHP 8.1.
* JavaScript syntax check passes.
* Live source retrieval locally saved 11 of 12 official camera JPEGs. Feed ab_295 returned the provider’s PNG placeholder stating that no live camera feed was available. Failures remain visible and do not discard earlier images.
* Saved image serving returns valid JPEG bytes. Invalid camera IDs and traversal attempts return 400 or 404. Retrieval by GET returns 405. POST without a session CSRF token returns 403.
* Playwright covers Overview, Archive, Map, search, empty dates, image dialog, Escape, camera rail, preferences and desktop/tablet/phone layouts. No JavaScript page errors occurred.
* Overview axe scan reports zero automated violations. 320 pixel reflow at 200 percent text size passes after a wrapping fix.
* Gitleaks found no secrets in the project directory before deployment.
* Historical source images have not been recovered. Current feeds replace obsolete URLs and are labelled using provider metadata. Capture timestamps are not inferred from retrieval time.
* No cron jobs are installed. No repository visibility change or licensing decision is automated.

Run the browser checks with Playwright installed and `NODE_PATH` pointing to its node_modules directory:

```sh
node tests/browser.cjs
```

Set AWC_TEST_URL to the deployed /awc/ URL for live verification. Browser tests expect at least one manually retrieved snapshot, and twelve configured cameras. Production checks and deployment digests are recorded separately in deployment.json.

Production verification: PHP 8.1 lint passes. HTTPS returns 200, the live header button saves 11 images and reports one confirmed provider outage. All 14 public source file digests match the local payload, excluding the intentional production bootstrap wrapper. Live Playwright checks pass at 1440, 768, 390 and 320 pixels with no JavaScript page errors.
