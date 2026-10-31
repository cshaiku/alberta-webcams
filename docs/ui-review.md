# UI review and redesign

First functional draft: commit 72caf43. Reviewed October 31, 2026 using the installed Apple HIG reference, the original AWC structure and rendered desktop and phone views.

## Findings and changes

The original fixed 200 pixel date rail and camera thumbnail strip consumed image space and did not reflow. The rebuild uses a collapsible camera rail and a compact date and hour strip only in Archive. On phones the rail becomes an explicitly toggled drawer; the image area takes the full width.

The first draft inherited serif typography and generic full borders from the accessibility adapter. The redesigned host uses system sans typography, restrained native controls and borderless navigation. The preferences center retains its controls and persistence. A local data-default-font adapter lets the host select sans without overriding a visitor’s saved font choice.

Overview is a responsive grid with Large and Compact options. Every card retains its retrieval timestamp and camera name outside the image. Source capture timestamps inside images are preserved. Enlarge opens a native dialog with focus containment, Escape and previous or next navigation. The close control remains visible, and no automatic slideshow runs.

Camera names come from current provider metadata. A separate note explains that UI timestamps represent retrieval. Failed retrieval preserves an earlier image and marks it as previous. Empty archive dates show an honest empty state. No generated photographs or substituted scenery are used.

The calendar is now a native date control with previous day, next day and Today controls, plus hour filtering. The original date URL structure is accepted and redirected to the modern view. Historical camera IDs absent from the new catalog show an explicit unavailable state. The map uses provider coordinates in a labelled schematic rather than embedding the obsolete Google Maps implementation or an old API key.

## Review evidence

Playwright exercised Overview, Archive, Map, empty dates, search, viewer keyboard dismissal, rail controls, preferences and 1440, 768, 390 and 320 pixel reflow. A 320 pixel check at 200 percent root text size exposed and then verified a fix for wrapping view buttons and the image size control. The automated axe scan of the desktop Overview reported no violations. These are scoped checks; screen reader and device specific assistive technology verification remain pending.

Foundations loaded: accessibility, branding, color, dark mode, design principles, icons, images, inclusion, layout, materials, motion, privacy, right to left, SF Symbols, typography and writing, plus buttons. Web adaptations use semantic HTML, CSS grid, native form controls and dialogs, system fonts and readable feedback. No Apple font files or SF Symbols artwork are distributed.
