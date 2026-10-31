import { escape, mountainDate, stampTime, stampLabel, stampISO, imageURL, pictureHTML, validDate } from './lib/ui.js?v=20261031-premium2';
import { request } from './lib/api.js?v=20261031-premium2';

const $ = selector => document.querySelector(selector);
const $$ = selector => [...document.querySelectorAll(selector)];
const icons = new Map([...$('#ui-icons').content.querySelectorAll('[data-icon]')].map(node => [node.dataset.icon, node.innerHTML]));
const icon = name => icons.get(name) ?? '';
const mobile = matchMedia('(max-width: 760px)');
const state = { catalog: [], snapshots: {}, csrf: '', selected: null, view: 'overview', filter: 'all', items: [], viewerIndex: 0, retrieving: false };
let archiveController;
let viewerTrigger;

function status(message) { $('#status').textContent = message; }
function readURL() {
  const parameters = new URLSearchParams(location.search);
  state.view = ['archive', 'map'].includes(parameters.get('view')) ? parameters.get('view') : 'overview';
  state.selected = parameters.get('camera');
  state.filter = ['edmonton', 'highways', 'unavailable'].includes(parameters.get('filter')) ? parameters.get('filter') : 'all';
  $('#date').value = validDate(parameters.get('date')) ? parameters.get('date') : mountainDate();
  $('#hour').value = /^(all|[01]\d|2[0-3])$/.test(parameters.get('hour') ?? '') ? parameters.get('hour') : 'all';
  $('#search').value = parameters.get('q') ?? '';
}
function writeURL(mode = 'replace') {
  const url = new URL('/awc/', location.origin);
  if (state.view !== 'overview') url.searchParams.set('view', state.view);
  if (state.selected) url.searchParams.set('camera', state.selected);
  if (state.view === 'archive') {
    url.searchParams.set('date', $('#date').value);
    url.searchParams.set('hour', $('#hour').value);
  } else if (state.view === 'overview') {
    if (state.filter !== 'all') url.searchParams.set('filter', state.filter);
    if ($('#search').value) url.searchParams.set('q', $('#search').value);
  }
  if (url.href !== location.href) history[mode === 'push' ? 'pushState' : 'replaceState'](null, '', url);
}
function chooseCamera(id) {
  state.selected = id;
  state.view = 'archive';
  closeRail();
  render('push');
  if (mobile.matches) $('#main').focus({ preventScroll: true });
}
function cameraList() {
  $('#camera-count').textContent = state.catalog.length;
  $('#camera-list').innerHTML = ['Edmonton', 'Highways'].map(group => {
    const cameras = state.catalog.filter(camera => (camera.region.startsWith('Edmonton') ? 'Edmonton' : 'Highways') === group);
    return `<h2 class="group-title">${group}</h2>${cameras.map(camera => `<a class="camera-link" href="/awc/?view=archive&amp;camera=${escape(camera.id)}" data-camera="${escape(camera.id)}" ${state.selected === camera.id ? 'aria-current="page"' : ''}><span>${escape(camera.region)}</span><small>${escape(camera.title)}</small></a>`).join('')}`;
  }).join('');
  $('#all-cameras').classList.toggle('selected', state.view === 'overview');
  $('#stat-cameras').textContent = state.catalog.length;
  $('#stat-available').textContent = state.catalog.filter(camera => state.snapshots.cameras?.[camera.id]?.status === 'ok').length;
}
function card(item, index) {
  const { camera, stamp } = item;
  const available = state.snapshots.cameras?.[camera.id]?.status === 'ok';
  const visual = stamp
    ? `<a class="image-button" href="${escape(imageURL(camera.id, stamp))}" data-index="${index}" aria-label="Enlarge ${escape(camera.title)} at ${escape(stampTime(stamp))}">${pictureHTML(camera, stamp, index === 0)}<span class="image-expand">${icon('expand')}</span></a>`
    : `<div class="unavailable">${icon('camera')}<strong>Camera unavailable</strong><p>The source has no image right now.</p></div>`;
  return `<article class="camera-card"><figure>${visual}<figcaption class="card-caption"><div class="card-topline"><span class="region">${escape(camera.region)}</span><span class="feed-state"><i class="feed-dot${available ? '' : ' offline'}"></i>${available ? 'Snapshot' : stamp ? 'Previous image' : 'Unavailable'}</span></div><h2>${escape(camera.title)}</h2><div class="caption-meta"><time${stamp ? ` datetime="${stampISO(stamp)}"` : ''}>${icon('clock')}<span>${escape(stampTime(stamp))}<small>${stamp ? stamp.slice(0, 10) : ''}</small></span></time><a class="archive-link" href="/awc/?view=archive&amp;camera=${escape(camera.id)}" data-camera="${escape(camera.id)}">Archive ${icon('arrow')}</a></div></figcaption></figure></article>`;
}
function showGallery() { $('#gallery').innerHTML = state.items.map(card).join(''); }
function empty(title, message, retrieve = true) {
  $('#gallery').innerHTML = `<div class="empty">${icon('camera')}<h2>${escape(title)}</h2><p>${escape(message)}</p>${retrieve ? '<button data-action="retrieve">Retrieve images</button>' : '<button data-action="reset">Show all cameras</button>'}</div>`;
}
async function render(historyMode = 'replace') {
  archiveController?.abort();
  archiveController = new AbortController();
  cameraList();
  const camera = state.catalog.find(camera => camera.id === state.selected);
  $$('[data-view]').forEach(link => {
    if (link.dataset.view === state.view) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  $$('[data-filter]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.filter === state.filter)));
  $('#page-title').textContent = state.view === 'map' ? 'The wider view.' : state.view === 'archive' ? (camera?.region ?? 'The image archive.') : 'Alberta, in view.';
  $('#eyebrow').textContent = state.view === 'archive' ? 'THE IMAGE ARCHIVE' : state.view === 'map' ? 'EXPLORE THE LOCATIONS' : 'THE CAMERA COLLECTION';
  $('#intro-copy').textContent = state.view === 'archive'
    ? (camera?.title ?? 'Choose a camera and revisit a moment. Explore saved snapshots by date and hour.')
    : state.view === 'map' ? 'A province of perspectives. Choose a location to explore its saved snapshots.'
      : 'A little perspective before you go. Explore highway snapshots across Alberta, or look back through the archive.';
  $('#archive-controls').hidden = state.view !== 'archive';
  $('#grid-toolbar').hidden = state.view !== 'overview';
  $('#gallery').hidden = state.view === 'map';
  $('#map-panel').hidden = state.view !== 'map';
  $('.collection-notes').hidden = state.view !== 'overview';
  writeURL(historyMode);
  if (state.view === 'map') { renderMap(); status(`${state.catalog.length} camera locations. Select a marker to browse its archive.`); return; }
  if (state.view === 'archive') {
    if (!camera && state.selected) { empty('Camera unavailable', 'This historical camera is not in the current catalog. Choose a camera from the sidebar.', false); status('Historical camera not available.'); return; }
    if (!camera) { state.selected = state.catalog[0]?.id; return render(); }
    $('#gallery').setAttribute('aria-busy', 'true');
    status('Loading the image archive…');
    try {
      const data = await request('archive', { signal: archiveController.signal, parameters: { camera: camera.id, date: $('#date').value } });
      state.items = data.frames.filter(frame => $('#hour').value === 'all' || frame.stamp.slice(11, 13) === $('#hour').value).map(frame => ({ camera, stamp: frame.stamp }));
      if (state.items.length) showGallery();
      else empty('No snapshots for this time', 'Try another date or retrieve a new collection. New snapshots are saved under today’s date.');
      status(`${state.items.length} ${state.items.length === 1 ? 'snapshot' : 'snapshots'} · ${$('#date').value} · ${data.dates.length} archived ${data.dates.length === 1 ? 'day' : 'days'}`);
    } catch (error) {
      if (error.name !== 'AbortError') { empty('Archive unavailable', 'Unable to load the archive. Please try again.'); status(error.message); }
    } finally { if (!archiveController.signal.aborted) $('#gallery').removeAttribute('aria-busy'); }
  } else {
    const query = $('#search').value.trim().toLowerCase();
    state.items = state.catalog.filter(camera => {
      const group = camera.region.startsWith('Edmonton') ? 'edmonton' : 'highways';
      const matches = state.filter === 'all' || state.filter === group || (state.filter === 'unavailable' && state.snapshots.cameras?.[camera.id]?.status !== 'ok');
      return matches && `${camera.title} ${camera.region}`.toLowerCase().includes(query);
    }).map(camera => ({ camera, stamp: state.snapshots.cameras?.[camera.id]?.stamp }));
    if (state.items.length) showGallery(); else empty('No cameras found', 'Try another place or clear your filters to see the full collection.', false);
    const last = state.snapshots.lastResult;
    status(last ? `${state.items.length} cameras in view · Last retrieved ${new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Edmonton', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(new Date(last.at))} MT` : 'Select Retrieve images to collect the first snapshots.');
  }
}
async function retrieve() {
  if (state.retrieving) return;
  state.retrieving = true;
  const button = $('#retrieve');
  button.disabled = true;
  button.setAttribute('aria-busy', 'true');
  button.querySelector('span').textContent = 'Retrieving…';
  status('Retrieving your camera collection…');
  try {
    const data = await request('retrieve', { token: state.csrf });
    state.snapshots = data.state;
    await render();
    status(`${data.saved} images saved${data.failed ? ` · ${data.failed} ${data.failed === 1 ? 'camera' : 'cameras'} unavailable` : ''}.`);
  } catch (error) { status(error.message); }
  finally {
    state.retrieving = false;
    button.disabled = false;
    button.removeAttribute('aria-busy');
    button.querySelector('span').textContent = 'Retrieve images';
  }
}
function openViewer(index, trigger) {
  state.viewerIndex = index;
  viewerTrigger = trigger;
  updateViewer();
  $('#viewer').showModal();
}
function updateViewer() {
  const item = state.items[state.viewerIndex];
  if (!item?.stamp) return;
  $('#viewer-title').textContent = item.camera.title;
  $('#viewer-image').width = item.camera.width ?? 840;
  $('#viewer-image').height = item.camera.height ?? 630;
  $('#viewer-image').src = imageURL(item.camera.id, item.stamp);
  $('#viewer-image').alt = `${item.camera.title} camera snapshot`;
  $('#viewer-original').href = imageURL(item.camera.id, item.stamp);
  $('#viewer-time').textContent = `${stampLabel(item.stamp)} · ${state.items.filter(item => item.stamp).indexOf(item) + 1} of ${state.items.filter(item => item.stamp).length}`;
  $('#viewer-prev').disabled = !state.items.slice(0, state.viewerIndex).some(item => item.stamp);
  $('#viewer-next').disabled = !state.items.slice(state.viewerIndex + 1).some(item => item.stamp);
}
function stepViewer(direction) {
  let index = state.viewerIndex + direction;
  while (index >= 0 && index < state.items.length && !state.items[index].stamp) index += direction;
  if (index >= 0 && index < state.items.length) { state.viewerIndex = index; updateViewer(); }
}
function closeRail() {
  document.body.classList.remove('rail-open');
  $('#rail-backdrop').hidden = true;
  $('#main').inert = false;
  $('.views').inert = false;
  $('.header-actions').inert = false;
  $('#rail-toggle').setAttribute('aria-expanded', String(!mobile.matches && !document.body.classList.contains('rail-hidden')));
}
function toggleRail() {
  if (mobile.matches) {
    if (document.body.classList.contains('rail-open')) { closeRail(); return; }
    document.body.classList.add('rail-open');
    $('#rail-backdrop').hidden = false;
    $('#main').inert = true;
    $('.views').inert = true;
    $('.header-actions').inert = true;
    $('#rail-toggle').setAttribute('aria-expanded', 'true');
    $('#rail-close').focus();
  } else {
    document.body.classList.toggle('rail-hidden');
    $('#rail-toggle').setAttribute('aria-expanded', String(!document.body.classList.contains('rail-hidden')));
  }
}
function resetFilters() { state.view = 'overview'; state.selected = null; state.filter = 'all'; $('#search').value = ''; closeRail(); render('push'); }

// One delegated handler keeps server rendered and refreshed cards on the same path.
document.addEventListener('click', event => {
  const target = event.target.closest('a,button');
  if (!target || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
  if (target.dataset.view) { event.preventDefault(); state.view = target.dataset.view; if (state.view === 'overview') state.selected = null; render('push'); }
  else if (target.dataset.camera) { event.preventDefault(); chooseCamera(target.dataset.camera); }
  else if (target.matches('.image-button')) { event.preventDefault(); openViewer(Number(target.dataset.index), target); }
  else if (target.dataset.filter) { state.filter = target.dataset.filter; render('push'); }
  else if (target.id === 'all-cameras' || target.dataset.action === 'reset') resetFilters();
  else if (target.id === 'retrieve' || target.dataset.action === 'retrieve') retrieve();
  else if (target.id === 'rail-toggle') toggleRail();
  else if (['rail-close','rail-backdrop'].includes(target.id)) { closeRail(); $('#rail-toggle').focus(); }
  else if (target.id === 'viewer-close') $('#viewer').close();
  else if (target.id === 'viewer-prev') stepViewer(-1);
  else if (target.id === 'viewer-next') stepViewer(1);
});
$('#viewer').addEventListener('close', () => viewerTrigger?.isConnected && viewerTrigger.focus());
$('#gallery').addEventListener('error', event => {
  if (event.target instanceof HTMLImageElement) {
    const picture = event.target.closest('picture');
    if (picture?.querySelector('source')) { picture.querySelector('source').remove(); return; }
    const link = event.target.closest('.image-button');
    if (link) link.outerHTML = `<div class="unavailable">${icon('camera')}<strong>Image unavailable</strong><p>Please retrieve the collection again.</p></div>`;
  }
}, true);
$('#search').addEventListener('input', () => render());
$('#archive-controls').addEventListener('submit', event => event.preventDefault());
$('#date').max = mountainDate();
for (let hour = 0; hour < 24; hour++) {
  const value = String(hour).padStart(2, '0');
  $('#hour').add(new Option(`${value}:00`, value));
}
$('#date').addEventListener('change', () => validDate($('#date').value) && render('push'));
$('#hour').addEventListener('change', () => render('push'));
for (const [id, direction] of [['prev-day', -1], ['next-day', 1]]) {
  $(`#${id}`).addEventListener('click', () => {
    const date = new Date(`${$('#date').value}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + direction);
    const value = date.toISOString().slice(0, 10);
    if (value <= mountainDate()) { $('#date').value = value; render('push'); }
  });
}
$('#today').addEventListener('click', () => { $('#date').value = mountainDate(); $('#hour').value = 'all'; render('push'); });
function density(value) {
  $('#gallery').classList.toggle('compact', value === 'compact');
  $('#gallery').classList.toggle('large', value === 'large');
  $('#density').value = value;
}
try { const saved = localStorage.getItem('awc.grid.density'); if (['comfortable','compact','large'].includes(saved)) density(saved); } catch { /* Preferences remain usable for this visit. */ }
$('#density').addEventListener('change', () => { density($('#density').value); try { localStorage.setItem('awc.grid.density', $('#density').value); } catch { /* Storage is optional. */ } });
window.addEventListener('popstate', () => { readURL(); render(); });
mobile.addEventListener('change', closeRail);
new ResizeObserver(entries => { document.documentElement.style.setProperty('--header-height', `${Math.ceil(entries[0].target.getBoundingClientRect().height)}px`); }).observe($('.header'));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && document.body.classList.contains('rail-open')) { closeRail(); $('#rail-toggle').focus(); }
  if ($('#viewer').open) {
    if (event.key === 'ArrowLeft') { event.preventDefault(); stepViewer(-1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); stepViewer(1); }
  }
  if (event.key === 'Tab' && mobile.matches && document.body.classList.contains('rail-open')) {
    const elements = [...$('#sidebar').querySelectorAll('a,button')].filter(element => element.getClientRects().length);
    if (event.shiftKey && document.activeElement === elements[0]) { event.preventDefault(); elements.at(-1).focus(); }
    else if (!event.shiftKey && document.activeElement === elements.at(-1)) { event.preventDefault(); elements[0].focus(); }
  }
});
closeRail();
try {
  const data = await request();
  state.catalog = data.cameras;
  state.snapshots = data.state;
  state.csrf = data.csrf;
  readURL();
  await render();
  $('#retrieve').disabled = false;
} catch (error) { status(`${error.message} Saved snapshots are still available below.`); }

function renderMap(){
 const point=c=>({x:80+(c.lng+120)/10*600,y:650-(c.lat-49)/11*600});
 $('#map-panel').innerHTML=`<div class="map-wrap"><svg viewBox="0 0 760 720" role="img" aria-labelledby="map-title map-desc"><title id="map-title">Alberta camera locations</title><desc id="map-desc">A schematic province outline with camera locations from 511 Alberta. The camera buttons below provide the same navigation.</desc><path class="map-region" d="M80 50H680V650H240L230 590 205 550 200 505 160 460 140 400 110 360 80 310Z"/>${[50,200,350,500,650].map(y=>`<path class="map-grid" d="M80 ${y}H680"/>`).join('')}${state.catalog.map(c=>{const p=point(c);return `<a class="map-marker-link" href="?view=archive&amp;camera=${c.id}" data-camera="${c.id}" aria-label="${escape(c.title)}"><circle class="map-marker" cx="${p.x}" cy="${p.y}" r="7"><title>${escape(c.title)}</title></circle></a>`;}).join('')}<text class="map-label" x="470" y="70">ALBERTA</text><text class="map-label" x="480" y="400">Edmonton</text><text class="map-label" x="440" y="550">Calgary</text></svg><p class="map-note">Schematic map · Locations from 511 Alberta. Use the camera list below for individual locations.</p></div><div class="map-legend">${state.catalog.map(c=>`<button data-camera="${c.id}">${escape(c.region)}</button>`).join('')}</div>`;

}
