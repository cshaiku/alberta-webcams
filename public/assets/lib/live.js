import {settings} from './config.js?v=20261031-config1';
// Refresh data in place, at most once per fifteen minutes. Catch up after a hidden tab returns.
export function liveRefresh(refresh) {
  const interval=settings.retrieval.intervalMinutes*60*1000;
  let last=Date.now(),busy=false;
  async function check(){
    if(busy||document.hidden||Date.now()-last<interval)return;
    busy=true;
    try{if(await refresh()!==false)last=Date.now();}catch{/* Keep the last successful images. Retry on the next check. */}
    finally{busy=false;}
  }
  const timer=setInterval(check,interval);
  document.addEventListener('visibilitychange',check);
  window.addEventListener('pagehide',()=>clearInterval(timer),{once:true});
}
