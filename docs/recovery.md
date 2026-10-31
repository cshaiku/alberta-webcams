# Recovery inventory

Confirmed on October 31, 2026 from the owner’s mounted Google Drive.

`#Documents/Dropbox.zip` contains:

* `wwwphpfiles.zi_`, with the original AWC homepage, header, footer, config, styles and map PHP.
* `wwwallfiles.zi_`, with AWC JavaScript, favicon and bundled timeline dependencies.
* `awcarchivecode.zi_`, with two identical copies of `archive/index.php` (11,664 bytes), `cal.php` (1,826 bytes), `.htaccess`, and an earlier `cams-archive-index.php` (11,370 bytes).
* `awc-map.zip`, with the map entry point and camera marker data.
* `awc-timeline.zip`, with an experimental timeline that had an empty body.

`CS IT Consulting/csvps.com/cshaiku.com.zip` and `scripts.zip` contain retrieval, movie generation and archive maintenance scripts. Selected relevant originals are preserved outside Git in the private recovery folder named in the README. Full server archives contain unrelated code and potentially private material; none are imported wholesale.

## Original interface

Overview was a four column, three row table of twelve cameras. Archive parsed `/archive/camera/year/month/day/hour:minute`, built year, month, calendar and hour controls, and read JPEG files from dated directories. Header tabs were Overview, Archive and Map. The archive had a fixed 200 pixel date rail and a horizontal camera thumbnail strip. Images refreshed through full page reloads every minute.

The recovered code referenced years 2009 through 2015, obsolete PHP `eregi_replace`, absolute server paths, an old Lightbox bundle and Google Maps API, and legacy analytics. These are replaced with current PHP, native dialogs, CSS grid, JavaScript modules and a local schematic map. No legacy analytics, external scripts, credentials, private cameras or obsolete API keys are carried into the rebuild.

## Historical cameras

The twelve original homepage cameras were Highway 43 near Two Creeks; Highway 43 and 37 near Onoway; Highway 16 west and east; EAS Tory Building; Jasper Avenue; Churchill Square; River Valley; Highway 2 and 2A near Leduc; Highway 2 and 11A near Red Deer; Highway 2 and 566 near Calgary; and Highway 9 near Drumheller.

The restored draft uses current official feeds with their actual provider titles. The precise old camera identities, imagery, weather feed and historic photograph archive have not been recovered. The schematic map is reconstructed; the experimental timeline was not functional in the recovered entry point and is not presented as a working original feature.
