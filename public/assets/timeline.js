import { request } from './lib/api.js?v=20261031-workspace2';
import { imageURL, stampLabel, escape } from './lib/ui.js?v=20261031-workspace2';
const $ = s => document.querySelector(s);
const camera = document.body.dataset.camera;
let frames = [], index = 0, playing = false, timer, controller, generation = 0, selection = 0;
function pause() { playing = false; clearTimeout(timer); $('#timeline-play').textContent = 'Play'; $('#timeline-play').setAttribute('aria-label','Play snapshots'); }
function updateURL() { const url = new URL(location.href); url.searchParams.set('date',$('#timeline-day').value); if(frames[index]) url.searchParams.set('stamp',frames[index].stamp); else url.searchParams.delete('stamp'); history.replaceState(null,'',url); }
function controls() { const disabled = !frames.length; $('#timeline-play').disabled=disabled || frames.length<2; $('#timeline-prev').disabled=disabled || index===0; $('#timeline-next').disabled=disabled || index===frames.length-1; }
async function selectFrame(next, { user = false, scroll = true } = {}) {
  if(user) pause();
  if(!frames.length) return;
  index=Math.max(0,Math.min(frames.length-1,next));
  const current=index, frame=frames[current], token=++selection, run=generation;
  controls();
  const preview=new Image(); preview.src=imageURL(camera,frame.stamp,840);
  try { await preview.decode(); } catch {
    if(token===selection && run===generation) { pause(); $('#timeline-status').textContent='This snapshot could not load. Choose another image or try again.'; }
    return;
  }
  if(token!==selection || run!==generation) return;
  const image=$('#timeline-image'); image.src=preview.src; image.alt=`Camera snapshot retrieved ${stampLabel(frame.stamp)}`; image.hidden=false; $('#timeline-empty').hidden=true;
  $('#timeline-original').href=imageURL(camera,frame.stamp); $('#timeline-original').hidden=false;
  document.body.dataset.frame=String(current);
  document.querySelectorAll('.timeline-thumb').forEach((button,i)=>{if(i===current)button.setAttribute('aria-current','true');else button.removeAttribute('aria-current');});
  const thumb=$(`.timeline-thumb[data-index="${current}"]`);
  if(scroll && thumb) { const strip=$('#timeline-filmstrip'); strip.scrollLeft=thumb.offsetLeft-strip.offsetLeft-(strip.clientWidth-thumb.offsetWidth)/2; }
  if(user) $('#timeline-status').textContent=stampLabel(frame.stamp);
  updateURL();
  if(frames[current+1]) { const nextImage=new Image(); nextImage.src=imageURL(camera,frames[current+1].stamp,840); }
}
async function tick() { if(!playing)return; if(index>=frames.length-1){pause();return;} await selectFrame(index+1); if(playing)timer=setTimeout(tick,Number($('#timeline-speed').value)); }
function filmstrip() {
  $('#timeline-filmstrip').innerHTML=`<div class="timeline-frames">${frames.map((frame,i)=>`<button class="timeline-thumb" data-index="${i}" aria-label="Show ${escape(stampLabel(frame.stamp))}"><img src="${escape(imageURL(camera,frame.stamp,400))}" width="180" height="135" alt="" loading="lazy" decoding="async"></button>`).join('')}</div>`;
}
async function loadDay() {
  pause();controller?.abort();controller=new AbortController();const run=++generation;++selection;frames=[];controls();
  $('#timeline-filmstrip').replaceChildren();$('#timeline-image').hidden=true;$('#timeline-original').hidden=true;$('#timeline-empty').hidden=false;$('#timeline-empty').textContent='Loading snapshots…';delete document.body.dataset.frame;
  try {
    const data=await request('archive',{signal:controller.signal,parameters:{camera,date:$('#timeline-day').value}});
    if(run!==generation)return;
    frames=data.frames.sort((a,b)=>a.stamp.localeCompare(b.stamp));index=0;
    if(!frames.length){$('#timeline-empty').textContent='No snapshots saved for this day. Choose another day.';$('#timeline-status').textContent='New images are collected every 15 minutes when the camera is available.';updateURL();return;}
    filmstrip();
    const stamp=new URL(location.href).searchParams.get('stamp');const saved=frames.findIndex(frame=>frame.stamp===stamp);await selectFrame(saved>=0?saved:0);controls();
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

let hoverTimer;
$('#timeline-filmstrip').addEventListener('pointerover',event=>{
  if(event.pointerType!=='mouse')return;
  const button=event.target.closest('[data-index]');
  clearTimeout(hoverTimer);
  if(button)hoverTimer=setTimeout(()=>selectFrame(Number(button.dataset.index),{user:true,scroll:false}),180);
});
$('#timeline-filmstrip').addEventListener('pointerleave',()=>clearTimeout(hoverTimer));

$('#timeline-filmstrip').addEventListener('keydown',event=>{
  if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;
  event.preventDefault();
  const next=event.key==='Home'?0:event.key==='End'?frames.length-1:index+(event.key==='ArrowRight'?1:-1);
  selectFrame(next,{user:true});
  $(`.timeline-thumb[data-index="${Math.max(0,Math.min(frames.length-1,next))}"]`)?.focus();
});
