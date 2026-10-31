# Accessibility center

A complete gear panel with reading, appearance, motion and focus preferences.

Import `index.js` as a module and include `../../shared/content.css` for the target adapter. Component CSS is loaded by the element from its own folder and `../../shared/ui.css`. No framework or build step is required.

```html
<script type="module" src="accessibility/components/preferences-center/index.js"></script>
<sdou-preferences-center target="#article" storage-key="my-project.reading.v1"></sdou-preferences-center>
```

The `target` attribute selects the content to adapt. Omit it to use the first `[data-sdou-a11y-content]` region. The optional `storage-key` isolates preferences by project. Components on the same target share a validated controller and stay in sync. IDs inside each component are isolated in its Shadow DOM.

Use `shared/content.css` and `[data-sdou-a11y-prose]` for scoped text styling. `sdou:a11y-change` is dispatched on the target after preference changes. The event includes `preferences`, `palette` and `reducedMotion`. App owned JavaScript motion should respond to this event.

Native labels, ranges, switches and select elements expose state directly. Dialog components use native modal focus containment, Escape, an explicit close button and focus return. No global letter shortcut is installed. Errors and polite status updates have separate regions.

Demos and reusable modules use external scripts and styles under a strict CSP. These files send no analytics and require no external service. Browser speech and fullscreen capabilities vary. Use `../../README.md` for the full scope and manual assistive technology checklist.

## Reference patterns

- https://evanswiki.com/
- https://docs.fluidproject.org/infusion/development/tutorial-userInterfaceOptions/WorkingWithUserInterfaceOptions
- https://github.com/Azkhar/accessibility-preference-widget

## Host default appearance

Use `data-default-theme="dark"` on the target content region for the SiteCraft
default. The initial document theme should also be dark to avoid a light flash.
Saved visitor choices take precedence, and Reset all preferences returns to the
host default. Omitting this attribute retains the reusable component’s device
appearance default for other integrations.

## Canonical SiteCraft presentation

The full center now uses the existing sltd.ca gear and panel presentation. The
32 step Page shade slider is first, and the Appearance preset dropdown is removed.
Contrast, typography, focus, motion and native media controls remain. The compact
gear opens an adaptive panel with a one pixel page preview gap and a transparent
backdrop. Its colours track the selected shade. `sdou:pause-media` lets a host
stop an external video player when Pause page media or reduced motion requests it.
