# Alberta Webcams

My old Alberta webcam site used to live at awc.cshaiku.com. This is its new home.

[Open Alberta Webcams](https://sltd.ca/awc/)

I wanted a simple way to see what is happening around Alberta and look back through the day. The images get the space. The controls stay out of the way.

## Take a look around

* **Network** shows the latest images from 18 cameras across Alberta.
* **Grid** shows a camera's saved images, with a rail for choosing the month, day and hour.
* **Timeline** lets you move through the images by hovering, using the keyboard or pressing play.
* **Compare** puts two moments together with a draggable divider.
* **Map** shows where the cameras are, using OpenStreetMap.

New images are saved every 15 minutes. Open pages check for updates on the same interval without losing your place. Everything is stored as WebP to keep the archive smaller.

The timestamps added by the site use Mountain Time and show when an image was retrieved. A timestamp already inside the camera image comes from the camera itself, so the two can differ.

## Make it yours

The gear opens your settings. Save favourite cameras, arrange them in your own order, make routes, choose your starting view and set your playback speed. Accessibility preferences are there too, including text size, contrast and reduced motion.

Your preferences stay in your browser. No account needed.

## Run it locally

PHP 8.1 or later, with cURL and GD with WebP support. Plain PHP, CSS and JavaScript. No frontend build step.

```sh
make serve
```

Open [localhost:8768/awc/](http://127.0.0.1:8768/awc/).

```sh
make test
```

## Configuration

Site settings and the camera catalog live in `config/site.json`. This includes camera locations, refresh timing, image quality, layout defaults, playback and map settings.

Private deployment overrides go in `config/local.json`, which is excluded from Git. Only `public/` belongs in the web root. Keep the application files, configuration and saved images outside it.

The collector runs through a cron job every 15 minutes. Changing the browser refresh interval in the JSON does not change the cron schedule. If a camera fails, its last saved image stays available.

## Credits

Camera images come from 511 Alberta. Maps use OpenStreetMap, with Leaflet for the map interface. Leaflet's license is included in the repository. Camera imagery and map data remain subject to their providers' terms.

Built with love by [Starlit Digital](https://sltd.ca/).
