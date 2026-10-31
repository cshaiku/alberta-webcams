// Browser configuration is rendered from the private, authoritative config/site.json.
// The read-only endpoint also supports tabs opened before the configuration migration.
const embedded=document.querySelector('#awc-settings');
export const settings=embedded?JSON.parse(embedded.textContent):await fetch('/awc/api.php?action=settings').then(response=>{if(!response.ok)throw new Error('Unable to load site settings.');return response.json();});
document.documentElement.style.setProperty('--camera-columns',String(settings.ui.desktopColumns));
