const menuBtn=document.getElementById('menuBtn');
const navLinks=document.getElementById('navLinks');
if(menuBtn&&navLinks){
    menuBtn.addEventListener('click',()=>{const open=navLinks.classList.toggle('open');menuBtn.setAttribute('aria-expanded',String(open));menuBtn.textContent=open?'×':'☰'});
    navLinks.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{navLinks.classList.remove('open');menuBtn.setAttribute('aria-expanded','false');menuBtn.textContent='☰'}));
}
const target=document.body.dataset.scrollTarget;
if(target) document.getElementById(target)?.scrollIntoView({behavior:'smooth',block:'start'});
const upBtn=document.getElementById('upBtn');
if(upBtn){window.addEventListener('scroll',()=>upBtn.classList.toggle('show',window.scrollY>500));upBtn.addEventListener('click',()=>window.scrollTo({top:0,behavior:'smooth'}));}
