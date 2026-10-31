let serial=0;
export function syncPills(root){
 for(const select of root.querySelectorAll('select')){
  const options=[...select.options];let group=select.nextElementSibling;
  if(!group?.classList.contains('settings-pills'))group=null;
  if(!options.length||options.length>=5){select.hidden=false;select.removeAttribute('data-pill-source');group?.remove();continue;}
  if(select.parentElement.tagName==='LABEL'){
   const label=select.parentElement,wrapper=document.createElement('div');wrapper.className=label.className+' setting-field';const title=document.createElement('span');title.className='setting-label';title.textContent=[...label.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).map(node=>node.textContent).join('').trim();title.id='setting-label-'+(++serial);wrapper.append(title);while(label.firstChild){if(label.firstChild.nodeType===Node.TEXT_NODE)label.firstChild.remove();else wrapper.append(label.firstChild);}label.replaceWith(wrapper);select.dataset.pillLabel=title.id;select.setAttribute('aria-labelledby',title.id);
  }
  const label=select.dataset.pillLabel?root.querySelector('#'+select.dataset.pillLabel):root.querySelector(`label[for="${select.id}"]`);
  const title=label?.textContent.trim()||select.getAttribute('aria-label')||'Choose an option';
  if(!group){group=document.createElement('fieldset');group.className='settings-pills';const legend=document.createElement('legend');legend.className='sr-only';legend.textContent=title;group.append(legend);select.after(group);}
  const signature=JSON.stringify(options.map(option=>[option.value,option.textContent,option.disabled]));
  if(group.dataset.signature!==signature){group.replaceChildren();const legend=document.createElement('legend');legend.className='sr-only';legend.textContent=title;group.append(legend);const name='settings-choice-'+(++serial);for(const option of options){const label=document.createElement('label'),radio=document.createElement('input'),text=document.createElement('span');radio.type='radio';radio.name=name;radio.value=option.value;text.textContent=option.textContent;label.append(radio,text);group.append(label);radio.addEventListener('change',()=>{if(!radio.checked)return;select.value=radio.value;select.dispatchEvent(new Event('input',{bubbles:true}));select.dispatchEvent(new Event('change',{bubbles:true}));});}group.dataset.signature=signature;}
  const hint=select.getAttribute('aria-describedby');if(hint)group.setAttribute('aria-describedby',hint);
  select.hidden=true;select.dataset.pillSource='true';
  for(const radio of group.querySelectorAll('input')){radio.checked=radio.value===select.value;radio.disabled=select.disabled||options.find(option=>option.value===radio.value)?.disabled;}
 }
}
export function installPills(root){syncPills(root);const observer=new MutationObserver(()=>syncPills(root));observer.observe(root,{childList:true,subtree:true});root.addEventListener('change',()=>syncPills(root));window.addEventListener('awc:personal',()=>syncPills(root));return observer;}
