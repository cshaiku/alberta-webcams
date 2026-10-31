.PHONY: serve test
serve:
	@mkdir -p /tmp/alberta-webcams-preview
	@ln -sfn "$(CURDIR)/public" /tmp/alberta-webcams-preview/awc
	php -S 127.0.0.1:8768 -t /tmp/alberta-webcams-preview

test:
	find app public tests -name '*.php' -exec php -l {} \;
	php tests/contracts.php
	php tests/media.php
	php tests/retrieval.php
	node --check public/assets/app.js
	node --check public/assets/map.js
	node --check public/assets/timeline.js
	node --check public/assets/compare.js
	node --check public/assets/lib/ui.js
	node --check public/assets/lib/api.js
	node --check public/assets/lib/live.js
