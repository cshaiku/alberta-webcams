const $ = s => document.querySelector(s);
const endpoint = '/awc/api.php';
let catalog = [], state = {}, csrf = '', selected = null, view = 'overview', items = [], viewerIndex = 0, activeController;
const escape = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const localDate = () => new Intl.DateTimeFormat('en-CA', {timeZone:'America/Edmonton',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
const stampText = stamp => stamp ? `${stamp.slice(0,10)} · ${stamp.slice(11,13)}:${stamp.slice(13,15)}:${stamp.slice(15,17)} MT` : 'Not retrieved yet';
const imageUrl = (id, stamp) => `/awc/image.php?camera=${encodeURIComponent(id)}&stamp=${encodeURIComponent(stamp)}`;
function setStatus(text) { $('#status').textContent = text; }
function writeUrl() { const u = new URL(location.href); u.pathname='/awc/'; u.search=''; if(view!=='overview') u.searchParams.set('view',view); if(selected)u.searchParams.set('camera',selected); if(view==='archive'){u.searchParams.set('date',$('#date').value);u.searchParams.set('hour',$('#hour').value);} history.replaceState(null,'',u); }
function cameraList() {
 const q = $('#search').value.trim().toLowerCase();
 const filtered = catalog.filter(c => `${c.title} ${c.region}`.toLowerCase().includes(q));
 $('#camera-count').textContent = catalog.length;
 $('#camera-list').replaceChildren();
 for (const group of ['Edmonton','Highways']) {
  const cs = filtered.filter(c => (c.region.startsWith('Edmonton')?'Edmonton':'Highways')===group);
  if(!cs.length) continue;
  const title=document.createElement('p');title.className='group-title';title.textContent=group;$('#camera-list').append(title);
  for(const c of cs){const b=document.createElement('button');b.className=`camera-link ${selected===c.id?'selected':''}`;b.setAttribute('aria-pressed',String(selected===c.id));b.innerHTML=`<span>${escape(c.title)}</span><small>${escape(c.region)}</small>`;b.onclick=()=>{selected=c.id;view='archive';document.body.classList.remove('rail-open');render();};$('#camera-list').append(b);}
 }
 if(!filtered.length)$('#camera-list').textContent='No cameras match your search.';
 $('#all-cameras').classList.toggle('selected',!selected);
}
function card(item, index) {
 const c=item.camera, cs=state.cameras?.[c.id];
 const el=document.createElement('article');el.className='camera-card';
 const visual=item.stamp?`<button class="image-button" data-index="${index}" aria-label="Enlarge ${escape(c.title)} at ${escape(stampText(item.stamp))}"><img src="${imageUrl(c.id,item.stamp)}" width="840" height="630" alt="${escape(c.title)} camera snapshot" loading="lazy" decoding="async"></button>`:`<div class="unavailable"><span class="camera-glyph" aria-hidden="true">▣</span><p>${cs?.status==='unavailable'?'Camera unavailable. Try retrieving again.':'Ready for the first snapshot.'}</p></div>`;
 el.innerHTML=`${visual}<div class="card-caption"><h2>${escape(c.title)}</h2><div class="caption-meta"><time>${escape(stampText(item.stamp))}</time>${view==='overview'?`<button class="archive-link" data-camera="${escape(c.id)}">Archive ↗</button>`:`<span>${escape(c.region)}</span>`}</div>${cs?.status==='unavailable'&&item.stamp&&view==='overview'?'<span class="badge">Last retrieval failed · previous snapshot shown</span>':''}</div>`;
 el.querySelector('.image-button')?.addEventListener('click',()=>openViewer(index));
 el.querySelector('.archive-link')?.addEventListener('click',()=>{selected=c.id;view='archive';render();});
 el.querySelector('img')?.addEventListener('error',e=>{e.target.closest('button').outerHTML='<div class="unavailable"><p>This saved image is unavailable.</p></div>';});
 return el;
}
function showGallery() { $('#gallery').replaceChildren(...items.map(card)); }
function empty(title,text) { $('#gallery').innerHTML=`<div class="empty"><h2>${escape(title)}</h2><p>${escape(text)}</p><button id="empty-retrieve">Retrieve images</button></div>`;$('#empty-retrieve').onclick=retrieve; }
async function render() {
 activeController?.abort();activeController=new AbortController();
 cameraList();writeUrl();
 const c=catalog.find(c=>c.id===selected);
 document.querySelectorAll('[data-view]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.view===view)));
 $('#page-title').textContent=view==='map'?'Camera locations':view==='archive'?(c?.title||'Camera archive'):'All cameras';
 $('#eyebrow').textContent=view==='archive'?'IMAGE ARCHIVE':'ALBERTA, CANADA';
 $('#archive-controls').hidden=view!=='archive';$('#gallery').hidden=view==='map';$('#map-panel').hidden=view!=='map';
 if(view==='map'){renderMap();setStatus(`${catalog.length} camera locations · Select a marker to browse its archive.`);return;}
 if(view==='archive'){
  if(!c && selected){empty('Camera unavailable','This historical camera is not in the current catalog. Choose a camera from the sidebar.');setStatus('Historical camera not available.');return;}
  if(!c){selected=catalog[0]?.id;return render();}
  setStatus('Loading archive…');
  try{
   const response=await fetch(`${endpoint}?action=archive&camera=${encodeURIComponent(c.id)}&date=${$('#date').value}`,{signal:activeController.signal});const data=await response.json();if(!response.ok)throw new Error(data.error);
   items=data.frames.filter(f=>$('#hour').value==='all'||f.stamp.slice(11,13)===$('#hour').value).map(f=>({camera:c,stamp:f.stamp}));
   setStatus(`${items.length} ${items.length===1?'snapshot':'snapshots'} · ${$('#date').value} · ${data.dates.length} archived ${data.dates.length===1?'day':'days'}`);
   if(items.length)showGallery();else empty('No snapshots for this time','Choose another date or retrieve a new set of images. New snapshots are stored under today’s date.');
  }catch(e){if(e.name!=='AbortError')setStatus(e.message||'Unable to load the archive.');}
 } else {
  const q=$('#search').value.toLowerCase();items=catalog.filter(c=>`${c.title} ${c.region}`.toLowerCase().includes(q)).map(c=>({camera:c,stamp:state.cameras?.[c.id]?.stamp}));showGallery();
  const result=state.lastResult;setStatus(result?`Last retrieval: ${result.saved} saved${result.failed?`, ${result.failed} unavailable`:''}.`:'Select Retrieve images to collect the first snapshots.');
  if(!items.length)empty('No matching cameras','Try a different camera name or location.');
 }
}
async function retrieve(){
 const b=$('#retrieve');if(b.disabled)return;b.disabled=true;b.querySelector('span').textContent='Retrieving…';b.setAttribute('aria-busy','true');setStatus('Retrieving camera images…');
 try{const r=await fetch(`${endpoint}?action=retrieve`,{method:'POST',headers:{'X-CSRF-Token':csrf}});const data=await r.json();if(!r.ok)throw new Error(data.error||'Retrieval failed.');state=data.state;await render();setStatus(`${data.saved} images saved${data.failed?`; ${data.failed} ${data.failed===1?'camera':'cameras'} unavailable`:''}.`);}
 catch(e){setStatus(e.message||'Unable to retrieve images. Please try again.');}
 finally{b.disabled=false;b.removeAttribute('aria-busy');b.querySelector('span').textContent='Retrieve images';}
}
function openViewer(index){viewerIndex=index;updateViewer();$('#viewer').showModal();}
function updateViewer(){const i=items[viewerIndex];if(!i?.stamp)return;$('#viewer-title').textContent=i.camera.title;$('#viewer-image').src=imageUrl(i.camera.id,i.stamp);$('#viewer-image').alt=i.camera.title;$('#viewer-time').textContent=`${stampText(i.stamp)} · ${viewerIndex+1} of ${items.length}`;$('#viewer-prev').disabled=!items.slice(0,viewerIndex).some(i=>i.stamp);$('#viewer-next').disabled=!items.slice(viewerIndex+1).some(i=>i.stamp);}
function stepViewer(dir){let n=viewerIndex+dir;while(n>=0&&n<items.length&&!items[n].stamp)n+=dir;if(n>=0&&n<items.length){viewerIndex=n;updateViewer();}}
function renderMap(){
 const point=c=>({x:80+(c.lng+120)/10*600,y:650-(c.lat-49)/11*600});
 $('#map-panel').innerHTML=`<div class="map-wrap"><svg viewBox="0 0 760 720" role="img" aria-labelledby="map-title map-desc"><title id="map-title">Alberta camera locations</title><desc id="map-desc">A schematic province outline with camera locations from 511 Alberta. The camera buttons below provide the same navigation.</desc><path class="map-region" d="M80 50H680V650H240L230 590 205 550 200 505 160 460 140 400 110 360 80 310Z"/>${[50,200,350,500,650].map(y=>`<path class="map-grid" d="M80 ${y}H680"/>`).join('')}${catalog.map(c=>{const p=point(c);return `<a class="map-marker-link" href="?view=archive&amp;camera=${c.id}" data-camera="${c.id}" aria-label="${escape(c.title)}"><circle class="map-marker" cx="${p.x}" cy="${p.y}" r="7"><title>${escape(c.title)}</title></circle></a>`;}).join('')}<text class="map-label" x="470" y="70">ALBERTA</text><text class="map-label" x="480" y="400">Edmonton</text><text class="map-label" x="440" y="550">Calgary</text></svg><p class="map-note">Schematic map · Locations from 511 Alberta. Use the camera list below for individual locations.</p></div><div class="map-legend">${catalog.map(c=>`<button data-camera="${c.id}">${escape(c.region)}</button>`).join('')}</div>`;
 $('#map-panel').querySelectorAll('[data-camera]').forEach(b=>b.onclick=e=>{e.preventDefault();selected=b.dataset.camera;view='archive';render();});
}
$('#retrieve').onclick=retrieve;
document.querySelectorAll('[data-view]').forEach(b=>b.onclick=()=>{view=b.dataset.view;if(view==='overview')selected=null;render();});
$('#all-cameras').onclick=()=>{selected=null;view='overview';render();};
$('#search').oninput=()=>view==='overview'?render():cameraList();
$('#date').value=localDate();$('#date').max=localDate();
for(let h=0;h<24;h++){const o=document.createElement('option');o.value=String(h).padStart(2,'0');o.textContent=`${o.value}:00`;$('#hour').append(o);}
$('#date').onchange=()=>{if($('#date').value)render();};$('#hour').onchange=render;
for(const [id,dir] of [['#prev-day',-1],['#next-day',1]])$(id).onclick=()=>{const d=new Date(`${$('#date').value}T12:00:00Z`);d.setUTCDate(d.getUTCDate()+dir);const next=d.toISOString().slice(0,10);if(next<=localDate()){$('#date').value=next;render();}};
$('#today').onclick=()=>{$('#date').value=localDate();$('#hour').value='all';render();};
$('#density').onchange=()=>$('#gallery').classList.toggle('compact',$('#density').value==='compact');
$('#rail-toggle').onclick=()=>{if(matchMedia('(max-width:720px)').matches){document.body.classList.toggle('rail-open');$('#rail-toggle').setAttribute('aria-expanded',String(document.body.classList.contains('rail-open')));}else{document.body.classList.toggle('rail-hidden');$('#rail-toggle').setAttribute('aria-expanded',String(!document.body.classList.contains('rail-hidden')));}};
if(matchMedia('(max-width:720px)').matches)$('#rail-toggle').setAttribute('aria-expanded','false');
document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.body.classList.remove('rail-open');if(matchMedia('(max-width:720px)').matches)$('#rail-toggle').setAttribute('aria-expanded','false');}if($('#viewer').open){if(e.key==='ArrowLeft')stepViewer(-1);if(e.key==='ArrowRight')stepViewer(1);}});
$('#viewer-close').onclick=()=>$('#viewer').close();$('#viewer-prev').onclick=()=>stepViewer(-1);$('#viewer-next').onclick=()=>stepViewer(1);
try{const r=await fetch(endpoint);if(!r.ok)throw new Error('Unable to load cameras.');const data=await r.json();catalog=data.cameras;state=data.state;csrf=data.csrf;const p=new URLSearchParams(location.search);view=['overview','archive','map'].includes(p.get('view'))?p.get('view'):'overview';selected=p.get('camera')||null;if(/^\d{4}-\d{2}-\d{2}$/.test(p.get('date')||''))$('#date').value=p.get('date');if(p.get('hour')==='all'||/^([01]\d|2[0-3])$/.test(p.get('hour')||''))$('#hour').value=p.get('hour');await render();}catch(e){setStatus(e.message||'Unable to load cameras. Please reload.');}
