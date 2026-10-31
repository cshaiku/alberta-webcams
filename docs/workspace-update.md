# Image workspace update

October 31, 2026, owner direction.

The default view is now an edge to edge image wall. Outer content padding, card gaps, visible captions, borders, rounded corners, introductory copy, footer and decorative chrome are removed from the visual workspace. A small header and control strip retain retrieval, navigation, filters, search and image size. The camera drawer starts closed. Camera names and image descriptions remain available to assistive technology; camera details, retrieval times and original image access are available in the enlarged viewer and archive controls.

The wall uses natural source image proportions, without cropping embedded timestamps or stretching images. Column flow fills the available space without artificial letterboxing. Explicit responsive picture sizes replace lazy auto containment for this natural ratio layout. WebP derivatives and original JPEG fallback remain unchanged.

The same update extracts the collector into a shared private module and adds a CLI scheduler entry point. See scheduling.md for the exact quarter hour schedule and its activation status. The shared host CLI invocation is tested, but hPanel registration remains pending because direct crontab access is unavailable and Work browser access is disabled.
