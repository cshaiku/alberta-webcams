const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const base = process.env.AWC_TEST_URL || 'http://127.0.0.1:8768/awc/';
(async()=>{
 const browser=await chromium.launch({headless:true});const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base);await page.waitForFunction(()=>!document.querySelector('#retrieve').disabled);await page.waitForSelector('.camera-card');assert.equal(await page.locator('.camera-card').count(),12);
 await page.locator('[data-filter=edmonton]').click();assert.equal(await page.locator('.camera-card').count(),4);
 await page.locator('[data-filter=highways]').click();assert.equal(await page.locator('.camera-card').count(),8);
 await page.locator('[data-filter=all]').click();
 await page.locator('#density').selectOption('compact');assert(await page.locator('#gallery').evaluate(e=>e.classList.contains('compact')));
 await page.locator('#density').selectOption('comfortable');
 await page.locator('.image-button img').first().evaluate(e=>e.decode());assert.match(await page.locator('.image-button img').first().evaluate(e=>e.currentSrc),/format=webp/);
 await page.locator('.image-button').first().click();assert(await page.locator('#viewer').evaluate(e=>e.open));await page.keyboard.press('Escape');assert(!(await page.locator('#viewer').evaluate(e=>e.open)));
 await page.locator('#rail-toggle').click();await page.locator('.camera-link[data-camera=ab_128]').click();await page.waitForSelector('.image-button');assert.equal(await page.locator('#archive-controls').isVisible(),true);
 await page.locator('#date').fill('2009-01-01');await page.locator('#date').dispatchEvent('change');await page.waitForSelector('.empty');assert.match(await page.locator('.empty').innerText(),/No snapshots/);
 await page.locator('[data-view=map]').click();await page.waitForSelector('.map-marker-link');assert.equal(await page.locator('.map-marker-link').count(),12);
 await page.locator('[data-view=overview]').click();await page.locator('#search').fill('Drumheller');await page.waitForFunction(()=>document.querySelectorAll('.camera-card').length===1);await page.locator('#search').fill('');
 await page.locator('#rail-toggle').click();assert(await page.locator('body').evaluate(e=>e.classList.contains('rail-hidden')));
 for(const width of [1440,768,390,320]){await page.setViewportSize({width,height:900});await page.screenshot({path:`/tmp/awc-${width}.png`,fullPage:true});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No overflow at ${width}`);}
 await page.setViewportSize({width:390,height:900});await page.locator('#menu-toggle').click();await page.locator('#rail-toggle').click();assert(await page.locator('#sidebar').isVisible());await page.keyboard.press('Escape');assert(!(await page.locator('#sidebar').isVisible()));
 const prefs=page.locator('sdou-preferences-center');await prefs.getByRole('button',{name:/preferences|accessibility/i}).first().click();assert(await prefs.locator('dialog').evaluate(e=>e.open));await page.keyboard.press('Escape');
 await page.setViewportSize({width:320,height:900});await page.evaluate(()=>document.documentElement.style.fontSize='200%');assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'200 percent text reflow');
 assert.deepEqual(errors,[]);console.log('PASS overview, archive, empty date, map, search, viewer, rail, preferences, 1440/768/390/320 reflow and console');await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
