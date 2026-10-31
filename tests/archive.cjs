const {chromium}=require('playwright');
const assert=require('node:assert/strict');
(async()=>{
 const browser=await chromium.launch();const page=await browser.newPage({viewport:{width:1440,height:900}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const base=process.env.AWC_TEST_URL||'http://127.0.0.1:8768/awc/';
 await page.goto(base);await page.waitForFunction(()=>document.body.dataset.ready==='true');
 const first=page.locator('.image-button').first();const id=await first.getAttribute('data-camera');assert(id);await first.click();
 await page.waitForSelector('#archive-tree .timeline-image');assert.match(page.url(),/view=archive/);assert(!(await page.locator('#viewer').evaluate(e=>e.open)));
 const data=await page.evaluate(async id=>(await fetch('/awc/api.php?action=archive&date=all&camera='+id)).json(),id);
 assert.equal(await page.locator('.camera-card').count(),data.frames.length);assert.equal(await page.locator('.timeline-image').count(),data.frames.length);
 assert(await page.locator('#sidebar').isVisible());assert(await page.locator('#archive-heading').innerText());
 await page.locator('[data-period]').filter({hasText:'View hour'}).first().click();await page.waitForFunction(()=>!document.querySelector('#gallery').hasAttribute('aria-busy'));
 assert(await page.locator('.camera-card').count()>0);await page.locator('.timeline-image').first().click();assert(await page.locator('#viewer').evaluate(e=>e.open));await page.keyboard.press('Escape');
 await page.locator('#archive-all').click();await page.waitForFunction(()=>!document.querySelector('#gallery').hasAttribute('aria-busy'));assert.equal(await page.locator('.camera-card').count(),data.frames.length);
 await page.reload();await page.waitForFunction(()=>document.body.dataset.ready==='true');assert.equal(await page.locator('.camera-card').count(),data.frames.length);
 for(const width of [1440,768,390,320]){await page.setViewportSize({width,height:900});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));}
 await page.locator('#menu-toggle').click();await page.locator('#rail-toggle').click();assert(await page.locator('#archive-tree').isVisible());await page.keyboard.press('Escape');
 assert.deepEqual(errors,[]);console.log('PASS front page to full camera archive, hierarchy, period filters, minute viewer, reload, mobile rail and reflow');await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
