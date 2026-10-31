# Fifteen minute retrieval

The recurring job was registered in Hostinger hPanel for sltd.ca on October 31, 2026 at approximately 02:38 America/Edmonton. hPanel confirmed creation and listed the exact command and schedule below. The collector previously saved 11 images in its CLI test. The automatic 02:45:06 America/Edmonton execution is verified from runtime state and eleven saved JPEG files. Its result recorded 11 saved and one unavailable. The public page and image URLs served that collection successfully. This runs on shared hosting.

In Hostinger hPanel, select sltd.ca, open Cron Jobs, choose Custom, and use:

```text
/opt/alt/php85/usr/bin/php /home/u394197407/domains/sltd.ca/private/awc/retrieve.php
```

Schedule:

```cron
*/15 * * * *
```

That is minute 0, 15, 30 and 45 of every hour. Keep all other schedule fields at `*`. Hostinger schedules use UTC; a quarter hour interval works the same in Alberta. Save the new entry, then check its output after a scheduled boundary. Do not replace unrelated jobs.

The stable private entry point loads `private/awc/current/tools/retrieve.php`. The `current` symlink selects the deployed private release. Future app releases must update this symlink along with the public payload. The original archive and runtime state stay in private/awc/runtime.

Manual and scheduled retrieval share an exclusive lock, the one minute cooldown, the same source allowlist and image validation. Concurrent or too recent requests are reported as skipped. The CLI emits a compact JSON summary, records `trigger: scheduled` in the runtime result and exits nonzero when every source fails. It has no public URL and needs no browser session or exposed scheduler token. The manual button retains its existing CSRF protection.

Validate configuration without retrieving:

```sh
/opt/alt/php85/usr/bin/php /home/u394197407/domains/sltd.ca/private/awc/retrieve.php --check
```

Primary setup reference: https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/
