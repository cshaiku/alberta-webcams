import {liveRefresh} from './lib/live.js?v=20261031-live1';
import { request } from './lib/api.js?v=20261031-webp1';
import { imageURL, stampLabel, escape } from './lib/ui.js?v=20261031-webp1';
const $ = s => document.querySelector(s);
const camera = document.body.dataset.camera;
let frames = [], index = 0, playing = false, timer, controller, generation = 0, selection = 0;
function pause() { playing = false; clearTimeout(timer); $('#timeline-play').textContent = 'Play'; $('#timeline-play').setAttribute('aria-label','Play snapshots'); }
function updateURL() { const url = new URL(location.href); url.searchParams.set('date',$('#timeline-day').value); if(frames[index]) url.searchParams.set('stamp',frames[index].stamp); else url.searchParams.delete('stamp'); history.replaceState(null,'',url);
  const compare=$('.comparison-link');if(compare&&frames[index])compare.href=`/awc/compare.php?camera=${encodeURIComponent(camera)}&after=${encodeURIComponent(frames[index].stamp)}`;
  for(const link of document.querySelectorAll('.camera-edge')) { const target=new URL(link.href);target.searchParams.set('date',$('#timeline-day').value);if(frames[index])target.searchParams.set('stamp',frames[index].stamp);else target.searchParams.delete('stamp');link.href=target.href; }
}
function controls() { const disabled = !frames.length; $('#timeline-play').disabled=disabled || frames.length<2; $('#timeline-prev').disabled=disabled || index===0; $('#timeline-next').disabled=disabled || index===frames.length-1; }
// Keep decoded image nodes, so a warm hover needs neither a request nor a decode.
// 24 previews bound decoded memory; immutable snapshot URLs also use browser disk cache.
const previewCache = new Map();
const CACHE_LIMIT = 24;
let warmGeneration = 0;
function cachedPreview(stamp) {
  let entry=previewCache.get(stamp);
  if(entry) { previewCache.delete(stamp); previewCache.set(stamp,entry); return entry; }
  const image=new Image();
  image.decoding='sync';
  image.src=imageURL(camera,stamp,840);
  entry={image,ready:false,promise:null};
  entry.promise=image.decode().then(()=>{entry.ready=true;trimCache();return image;}).catch(error=>{if(previewCache.get(stamp)===entry)previewCache.delete(stamp);throw error;});
  previewCache.set(stamp,entry);
  trimCache();
  return entry;
}
function trimCache() {
  for(const [stamp,entry] of previewCache) {
    if(previewCache.size<=CACHE_LIMIT)break;
    if(!entry.image.isConnected && entry.ready)previewCache.delete(stamp);
  }
}
function warmPreviews(center) {
  const run=++warmGeneration, day=generation;
  const queue=[];
  for(let distance=0;queue.length<Math.min(CACHE_LIMIT,frames.length);distance++) {
    for(const i of distance ? [center+distance,center-distance] : [center])if(frames[i])queue.push(frames[i].stamp);
  }
  async function worker() {
    while(queue.length && run===warmGeneration && day===generation) {
      const stamp=queue.shift();
      try { await cachedPreview(stamp).promise; } catch { /* A failed prefetch can retry on selection. */ }
    }
  }
  for(let i=0;i<3;i++)worker();
}
async function selectFrame(next, { user = false, scroll = true } = {}) {
  if(user) pause();
  if(!frames.length) return;
  index=Math.max(0,Math.min(frames.length-1,next));
  const current=index, frame=frames[current], token=++selection, run=generation;
  controls();
  const cached=cachedPreview(frame.stamp);
  try { if(!cached.ready)await cached.promise; } catch {
    if(token===selection && run===generation) { pause(); $('#timeline-status').textContent='This snapshot could not load. Choose another image or try again.'; }
    return;
  }
  if(token!==selection || run!==generation) return;
  const previous=$('#timeline-image');
  const image=cached.image;
  if(previous!==image){previous.removeAttribute('id');image.id='timeline-image';previous.replaceWith(image);}
  image.alt=`Camera snapshot retrieved ${stampLabel(frame.stamp)}`; image.hidden=false; $('#timeline-empty').hidden=true;
  $('#timeline-original').href=imageURL(camera,frame.stamp); $('#timeline-original').hidden=false;
  document.body.dataset.frame=String(current);
  document.querySelectorAll('.timeline-thumb').forEach((button,i)=>{if(i===current)button.setAttribute('aria-current','true');else button.removeAttribute('aria-current');});
  const thumb=$(`.timeline-thumb[data-index="${current}"]`);
  if(scroll && thumb) { const strip=$('#timeline-filmstrip'); strip.scrollLeft=thumb.offsetLeft-strip.offsetLeft-(strip.clientWidth-thumb.offsetWidth)/2; }
  if(user) $('#timeline-status').textContent=stampLabel(frame.stamp);
  updateURL();
  cameraEdges();
  warmPreviews(current);
}
async function tick() { if(!playing)return; if(index>=frames.length-1){pause();return;} await selectFrame(index+1); if(playing)timer=setTimeout(tick,Number($('#timeline-speed').value)); }
function filmstrip() {
  $('#timeline-filmstrip').innerHTML=`<div class="timeline-frames">${frames.map((frame,i)=>`<button class="timeline-thumb" data-index="${i}" aria-label="Show ${escape(stampLabel(frame.stamp))}"><img src="${escape(imageURL(camera,frame.stamp,400))}" width="180" height="135" alt="" loading="lazy" decoding="async"></button>`).join('')}</div>`;
}
async function loadDay(followLatest=false,quiet=false) {
  pause();controller?.abort();controller=new AbortController();const run=++generation;++selection;++warmGeneration;frames=[];controls();
  if(!quiet){$('#timeline-filmstrip').replaceChildren();$('#timeline-image').hidden=true;$('#timeline-original').hidden=true;$('#timeline-empty').hidden=false;$('#timeline-empty').textContent='Loading snapshots…';delete document.body.dataset.frame;}
  try {
    const data=await request('archive',{signal:controller.signal,parameters:{camera,date:$('#timeline-day').value}});
    if(run!==generation)return;
    frames=data.frames.sort((a,b)=>a.stamp.localeCompare(b.stamp));index=0;
    if(!frames.length){$('#timeline-empty').textContent='No snapshots saved for this day. Choose another day.';$('#timeline-status').textContent='New images are collected every 15 minutes when the camera is available.';updateURL();return;}
    filmstrip();
    const stamp=new URL(location.href).searchParams.get('stamp');const saved=frames.findIndex(frame=>frame.stamp===stamp);await selectFrame(followLatest?frames.length-1:(saved>=0?saved:0));controls();
  } catch(error){if(error.name!=='AbortError' && run===generation){$('#timeline-empty').textContent='Unable to load this day. Choose a day to retry.';$('#timeline-status').textContent=error.message;}}
  finally {if(run===generation)document.body.dataset.ready='true';}
}
$('#timeline-day').addEventListener('change',()=>{const url=new URL(location.href);url.searchParams.delete('stamp');history.replaceState(null,'',url);loadDay();});
$('#timeline-prev').addEventListener('click',()=>selectFrame(index-1,{user:true}));$('#timeline-next').addEventListener('click',()=>selectFrame(index+1,{user:true}));
$('#timeline-play').addEventListener('click',async()=>{if(playing){pause();return;}playing=true;$('#timeline-play').textContent='Pause';$('#timeline-play').setAttribute('aria-label','Pause snapshots');if(index===frames.length-1)await selectFrame(0);if(playing)timer=setTimeout(tick,Number($('#timeline-speed').value));});
$('#timeline-speed').addEventListener('change',()=>{if(playing){clearTimeout(timer);timer=setTimeout(tick,Number($('#timeline-speed').value));}});
$('#timeline-filmstrip').addEventListener('click',event=>{const button=event.target.closest('[data-index]');if(button)selectFrame(Number(button.dataset.index),{user:true,scroll:false});});
document.addEventListener('visibilitychange',()=>{if(document.hidden)pause();});
$('sdou-preferences-center').addEventListener('focusin',pause);
window.addEventListener('pagehide',pause);
loadDay();

$('#timeline-filmstrip').addEventListener('pointerover',event=>{
  if(event.pointerType!=='mouse')return;
  const button=event.target.closest('[data-index]');
  if(button && !button.contains(event.relatedTarget) && Number(button.dataset.index)!==index)selectFrame(Number(button.dataset.index),{user:true,scroll:false});
});

$('#timeline-filmstrip').addEventListener('keydown',event=>{
  if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;
  event.preventDefault();
  const next=event.key==='Home'?0:event.key==='End'?frames.length-1:index+(event.key==='ArrowRight'?1:-1);
  selectFrame(next,{user:true});
  $(`.timeline-thumb[data-index="${Math.max(0,Math.min(frames.length-1,next))}"]`)?.focus();
});

function cameraEdges() {
  const image=$('#timeline-image'),stage=$('.timeline-stage');
  const box=stage.getBoundingClientRect();
  const fittedWidth=image.hidden || !image.naturalWidth ? 0 : Math.min(box.width,box.height*image.naturalWidth/image.naturalHeight);
  const gutter=Math.max(0,(box.width-fittedWidth)/2);
  for(const link of document.querySelectorAll('.camera-edge')) {link.style.width=`${gutter}px`;link.hidden=gutter<1;}
}
new ResizeObserver(cameraEdges).observe($('.timeline-stage'));

liveRefresh(async()=>{
  if(playing||document.querySelector('sdou-preferences-center').shadowRoot?.querySelector('dialog[open]'))return false;
  const follow=frames.length>0&&index===frames.length-1;
  const selectedDay=$('#timeline-day').value;
  const days=[...$('#timeline-day').options].map(option=>option.value);
  const data=await request('archive',{parameters:{camera,date:selectedDay}});
  const latest=data.dates[0];
  const wasLatestDay=selectedDay===days[0];
  for(const day of data.dates)if(!days.includes(day))$('#timeline-day').add(new Option(day,day),0);
  if(follow&&wasLatestDay&&latest)$('#timeline-day').value=latest;
  const changed=JSON.stringify(data.frames.map(frame=>frame.stamp).sort())!==JSON.stringify(frames.map(frame=>frame.stamp).sort());
  if(changed||$('#timeline-day').value!==selectedDay)await loadDay(follow,true);
});
