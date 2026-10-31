# Image workspace update

October 31, 2026, owner direction.

The default view is now an edge to edge image wall. Outer content padding, card gaps, visible captions, borders, rounded corners, introductory copy, footer and decorative chrome are removed from the visual workspace. A small header and control strip retain retrieval, navigation, filters, search and image size. The camera drawer starts closed. Camera names and image descriptions remain available to assistive technology; camera details, retrieval times and original image access are available in the enlarged viewer and archive controls.

The wall uses a responsive row grid with identical 4:3 camera slots, including unavailable cameras. Space is reserved before images decode, so different source dimensions cannot move subsequent tiles. Images fill each slot without stretching. Differently proportioned feeds are cropped with bottom alignment to retain the usual timestamp strip; the viewer and original link retain the full source image. WebP derivatives and original JPEG fallback remain unchanged.

The same update extracts the collector into a shared private module and adds a CLI scheduler entry point. See scheduling.md for the exact quarter hour schedule and its activation status. The shared host CLI invocation is tested and the quarter hour job is registered in hPanel.

The desktop collection now has exactly three columns at every density setting. Navigation, filters, search, density and retrieval share a single 44px header. At 760px and below, a hamburger button discloses the controls without moving the grid; Escape closes it and restores focus. Mobile density choices retain one or two columns at phone widths.

The SDOU preferences center is now placed directly in the header, retaining its existing component, storage key and modal behavior. The header hides on downward scrolling beyond its initial height and returns after 8px of upward movement. Keyboard focus reveals it, and open controls, camera drawer or preferences prevent hiding. The header retains its layout space to avoid shifting the image grid. Manual retrieval controls were removed, including empty state actions; the private scheduled collector remains unchanged.

The header is now 48px tall. Header text is increased by 4px. Search follows the filters directly and grows up to 720px, four times its previous 180px basis. The density dropdown and saved density restoration are removed. Phone filters wrap within the disclosed menu.
