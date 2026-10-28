document.querySelectorAll('[data-scroll]').forEach(b=>b.addEventListener('click',()=>{
  const t=document.getElementById('track');if(t)t.scrollBy({left:b.dataset.scroll*380,behavior:'smooth'});}));
const f=document.getElementById('filtros');
if(f)f.querySelectorAll('input[type=checkbox],select').forEach(i=>i.addEventListener('change',()=>f.submit()));

const tr=document.getElementById('track');
if(tr){const sync=()=>document.querySelectorAll('[data-scroll]').forEach(b=>b.hidden=tr.scrollWidth<=tr.clientWidth+4);
sync();addEventListener('resize',sync);}
