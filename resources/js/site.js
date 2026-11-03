const backgroundMusic=document.getElementById('siteBackgroundMusic');
if(backgroundMusic){
    const musicPositionKey='sinaga-background-music-position';
    const musicEnabledKey='sinaga-background-music-enabled';
    let musicStarted=false,musicAttempting=false;
    const clearStartListeners=()=>['pointerdown','touchstart','keydown'].forEach(type=>document.removeEventListener(type,startAfterInteraction,true));
    const startAfterInteraction=()=>tryStartMusic();
    const tryStartMusic=()=>{
        if(musicStarted||musicAttempting)return;
        musicAttempting=true;
        let playResult;
        try{playResult=backgroundMusic.play();}
        catch(error){musicAttempting=false;console.error('[SINAGA] Background music play() failed.',error);return;}
        if(!playResult?.then){musicStarted=true;musicAttempting=false;clearStartListeners();return;}
        playResult.then(()=>{
            musicStarted=true;musicAttempting=false;
            try{sessionStorage.setItem(musicEnabledKey,'1');}catch{}
            clearStartListeners();
        }).catch(error=>{
            musicAttempting=false;
            if(error?.name==='NotAllowedError')console.info('[SINAGA] Autoplay was blocked; playback will retry after the first user interaction.');
            else console.error('[SINAGA] Background music playback was rejected.',error);
        });
    };
    backgroundMusic.addEventListener('loadedmetadata',()=>{
        console.info(`[SINAGA] Background music loaded; duration ${Number.isFinite(backgroundMusic.duration)?backgroundMusic.duration.toFixed(2):'unknown'} seconds.`);
        try{
            const savedPosition=Number(sessionStorage.getItem(musicPositionKey));
            if(Number.isFinite(savedPosition)&&savedPosition>0&&savedPosition<backgroundMusic.duration)backgroundMusic.currentTime=savedPosition;
            if(sessionStorage.getItem(musicEnabledKey)==='1')tryStartMusic();
        }catch{}
    },{once:true});
    backgroundMusic.addEventListener('error',()=>{
        console.error('[SINAGA] Background music failed to load.',{
            url:backgroundMusic.currentSrc||backgroundMusic.querySelector('source')?.src,
            networkState:backgroundMusic.networkState,
            readyState:backgroundMusic.readyState,
            mediaErrorCode:backgroundMusic.error?.code
        });
    },{once:true});
    backgroundMusic.addEventListener('timeupdate',()=>{
        try{sessionStorage.setItem(musicPositionKey,String(backgroundMusic.currentTime));}catch{}
    });
    ['pointerdown','touchstart','keydown'].forEach(type=>document.addEventListener(type,startAfterInteraction,true));
    tryStartMusic();
}

const menuBtn=document.getElementById('menuBtn');
const navLinks=document.getElementById('navLinks');
if(menuBtn&&navLinks){
    const menuIcon=menuBtn.querySelector('span');
    const mobileNav=window.matchMedia('(max-width: 960px)');
    const setMenuOpen=open=>{
        navLinks.classList.toggle('open',open);
        menuBtn.setAttribute('aria-expanded',String(open));
        menuBtn.setAttribute('aria-label',open?'Tutup menu':'Buka menu');
        if(menuIcon)menuIcon.textContent=open?'×':'☰';
    };
    menuBtn.addEventListener('click',()=>setMenuOpen(menuBtn.getAttribute('aria-expanded')!=='true'));
    navLinks.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>setMenuOpen(false)));
    document.addEventListener('keydown',event=>{if(event.key==='Escape')setMenuOpen(false);});
    document.addEventListener('click',event=>{if(!navLinks.contains(event.target)&&!menuBtn.contains(event.target))setMenuOpen(false);});
    mobileNav.addEventListener('change',()=>setMenuOpen(false));
}
const target=document.body.dataset.scrollTarget;
const reduceMotion=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
document.querySelectorAll('[data-activity-carousel]').forEach(carousel=>{
    const viewport=carousel.querySelector('.activity-carousel-viewport');
    let slides=[...carousel.querySelectorAll('.activity-slide')];
    const track=carousel.querySelector('[data-academic-track]');
    const controls=carousel.parentElement.querySelector('[data-academic-carousel-controls]');
    const previous=controls?.querySelector('[data-academic-prev]');
    const next=controls?.querySelector('[data-academic-next]');
    const status=controls?.querySelector('[data-academic-status]');
    if(!viewport||!track||!slides.length)return;
    let currentPage=0,perView=0,pageCount=1;
    const updateControls=()=>{
        slides=[...carousel.querySelectorAll('.activity-slide')];
        if(!slides.length)return;
        const nextPerView=window.matchMedia('(max-width: 650px)').matches?1:2;
        if(nextPerView!==perView){
            const firstVisibleIndex=currentPage*perView;
            perView=nextPerView;
            currentPage=Math.floor(firstVisibleIndex/perView);
        }
        pageCount=Math.max(1,Math.ceil(slides.length/perView));
        currentPage=Math.max(0,Math.min(pageCount-1,currentPage));
        const startIndex=currentPage*perView;
        const visibleCount=Math.min(perView,slides.length-startIndex);
        const centeredLastCard=perView===2&&visibleCount===1&&currentPage===pageCount-1;
        const offset=currentPage*100-(centeredLastCard?25:0);
        track.style.transform=`translate3d(${-offset}%,0,0)`;
        if(previous)previous.disabled=currentPage===0;
        if(next)next.disabled=currentPage===pageCount-1;
        if(status){
            const first=startIndex+1;
            const last=startIndex+visibleCount;
            status.textContent=visibleCount>1?`${first}–${last} / ${slides.length}`:`${first} / ${slides.length}`;
        }
    };
    const goTo=page=>{currentPage=Math.max(0,Math.min(pageCount-1,page));updateControls();};
    previous?.addEventListener('click',()=>goTo(currentPage-1));
    next?.addEventListener('click',()=>goTo(currentPage+1));
    viewport.addEventListener('keydown',event=>{
        if(event.key==='ArrowLeft'){event.preventDefault();goTo(currentPage-1);}
        if(event.key==='ArrowRight'){event.preventDefault();goTo(currentPage+1);}
    });
    window.addEventListener('resize',updateControls,{passive:true});
    updateControls();
});
document.querySelectorAll('[data-extracurricular-carousel]').forEach(carousel=>{
    const viewport=carousel.querySelector('.extracurricular-carousel-viewport');
    const track=carousel.querySelector('.extracurricular-track');
    const cards=[...carousel.querySelectorAll('.extracurricular-card')];
    const controls=carousel.parentElement.querySelector('[data-extracurricular-controls]');
    const previous=controls?.querySelector('[data-extracurricular-prev]');
    const next=controls?.querySelector('[data-extracurricular-next]');
    const status=controls?.querySelector('[data-extracurricular-status]');
    if(!viewport||!track||!cards.length)return;
    let page=0,perView=1,pageCount=1,step=0;
    const update=()=>{
        const trackStyle=getComputedStyle(track);
        const gap=parseFloat(trackStyle.columnGap||trackStyle.gap)||0;
        const cardWidth=cards[0].getBoundingClientRect().width;
        step=cardWidth+gap;
        perView=Math.max(1,Math.floor((viewport.clientWidth+gap+1)/Math.max(1,step)));
        pageCount=Math.max(1,Math.ceil(cards.length/perView));
        page=Math.max(0,Math.min(page,pageCount-1));
        const start=Math.min(page*perView,Math.max(0,cards.length-perView));
        track.style.transform=`translate3d(${-start*step}px,0,0)`;
        if(previous)previous.disabled=page===0;
        if(next)next.disabled=page===pageCount-1;
        if(status)status.textContent=`${page+1} / ${pageCount}`;
    };
    previous?.addEventListener('click',()=>{page--;update();});
    next?.addEventListener('click',()=>{page++;update();});
    viewport.addEventListener('keydown',event=>{
        if(event.key==='ArrowLeft'){event.preventDefault();page--;update();}
        if(event.key==='ArrowRight'){event.preventDefault();page++;update();}
    });
    window.addEventListener('resize',update,{passive:true});
    update();
});
const academicDetailDialog=document.getElementById('academicDetailModal');
if(academicDetailDialog){
    const content=academicDetailDialog.querySelector('[data-academic-detail-content]');
    const closeButton=academicDetailDialog.querySelector('[data-academic-close]');
    let lastAcademicTrigger=null;
    document.querySelectorAll('[data-academic-detail]').forEach(trigger=>trigger.addEventListener('click',()=>{
        const template=document.getElementById(trigger.dataset.academicDetail);
        if(!template||!content)return;
        lastAcademicTrigger=trigger;
        content.replaceChildren(template.content.cloneNode(true));
        academicDetailDialog.setAttribute('aria-labelledby','academicDetailTitle');
        if(typeof academicDetailDialog.showModal==='function')academicDetailDialog.showModal();
        else academicDetailDialog.setAttribute('open','');
        closeButton?.focus({preventScroll:true});
    }));
    closeButton?.addEventListener('click',()=>academicDetailDialog.close());
    academicDetailDialog.addEventListener('click',event=>{if(event.target===academicDetailDialog)academicDetailDialog.close();});
    academicDetailDialog.addEventListener('close',()=>{
        content?.replaceChildren();
        lastAcademicTrigger?.focus({preventScroll:true});
        lastAcademicTrigger=null;
    });
}
if(!reduceMotion&&'IntersectionObserver'in window){
    const revealNodes=[...document.querySelectorAll('main > .section, main .notice-item, main .club-card, main .gallery-item, main .academic-content-entry, .activity-carousel, .extracurricular-carousel')];
    const revealObserver=new IntersectionObserver(entries=>entries.forEach(entry=>{
        if(!entry.isIntersecting)return;
        entry.target.classList.add('is-revealed');
        revealObserver.unobserve(entry.target);
    }),{threshold:.12,rootMargin:'0px 0px -24px 0px'});
    revealNodes.forEach((node,index)=>{
        node.classList.add('scroll-reveal');
        if(node.matches('.notice-item,.club-card,.gallery-item,.academic-content-entry'))node.style.transitionDelay=`${Math.min(index%5,4)*45}ms`;
        revealObserver.observe(node);
    });
}
if(target) document.getElementById(target)?.scrollIntoView({behavior:reduceMotion?'auto':'smooth',block:'start'});
const navAnchors=[...document.querySelectorAll('.nav-links [data-nav-section]')];
const navSections=navAnchors.map(link=>document.getElementById(link.dataset.navSection)).filter(Boolean);
if(navAnchors.length&&navSections.length){
    let ticking=false;
    const updateActiveNavigation=()=>{
        const marker=window.scrollY+(document.querySelector('.nav-wrap')?.offsetHeight||0)+120;
        let activeSection=navSections[0];
        for(const section of navSections){if(section.offsetTop<=marker)activeSection=section;else break;}
        navAnchors.forEach(link=>{
            const active=link.dataset.navSection===activeSection.id;
            link.classList.toggle('active',active);
            if(active)link.setAttribute('aria-current','location');else link.removeAttribute('aria-current');
        });
        ticking=false;
    };
    const requestActiveUpdate=()=>{if(!ticking){ticking=true;window.requestAnimationFrame(updateActiveNavigation);}};
    window.addEventListener('scroll',requestActiveUpdate,{passive:true});
    window.addEventListener('resize',requestActiveUpdate);
    window.addEventListener('hashchange',requestActiveUpdate);
    updateActiveNavigation();
}
const upBtn=document.getElementById('upBtn');
if(upBtn){window.addEventListener('scroll',()=>upBtn.classList.toggle('show',window.scrollY>500));upBtn.addEventListener('click',()=>window.scrollTo({top:0,behavior:reduceMotion?'auto':'smooth'}));}
const contactForm=document.getElementById('contactForm');
contactForm?.addEventListener('submit',()=>{
    const submit=contactForm.querySelector('[type="submit"]');
    if(!submit)return;
    submit.disabled=true;
    submit.textContent='Mengirim pesan…';
});

document.querySelectorAll('[data-gallery-carousel]').forEach(carousel=>{
    const viewport=carousel.querySelector('.gallery-carousel-viewport');
    const track=carousel.querySelector('.gallery-track');
    const previous=carousel.querySelector('[data-gallery-carousel-prev]');
    const next=carousel.querySelector('[data-gallery-carousel-next]');
    const cards=[...carousel.querySelectorAll('[data-gallery-item]')];
    if(!viewport||!track||!cards.length)return;
    const updateButtons=()=>{
        const end=viewport.scrollWidth-viewport.clientWidth;
        if(previous)previous.disabled=viewport.scrollLeft<=2;
        if(next)next.disabled=end<=2||viewport.scrollLeft>=end-2;
    };
    const move=direction=>{
        const card=cards[0];
        if(!card)return;
        const gap=parseFloat(getComputedStyle(track).columnGap)||0;
        viewport.scrollBy({left:direction*(card.getBoundingClientRect().width+gap),behavior:reduceMotion?'auto':'smooth'});
    };
    previous?.addEventListener('click',()=>move(-1));
    next?.addEventListener('click',()=>move(1));
    viewport.addEventListener('scroll',updateButtons,{passive:true});
    viewport.addEventListener('keydown',event=>{
        if(event.key==='ArrowLeft'){event.preventDefault();move(-1);}
        if(event.key==='ArrowRight'){event.preventDefault();move(1);}
    });
    window.addEventListener('resize',updateButtons,{passive:true});
    updateButtons();
});

const galleryLightbox=document.getElementById('galleryLightbox');
if(galleryLightbox){
    const photos=[...document.querySelectorAll('[data-gallery-open]')],image=document.getElementById('galleryLightboxImage'),title=document.getElementById('galleryLightboxTitle');
    let current=-1,touchStartX=null;
    const showPhoto=index=>{
        if(!photos.length)return;
        current=(index+photos.length)%photos.length;
        const photo=photos[current];
        image.src=photo.dataset.photoSrc;
        image.alt=photo.dataset.photoTitle||'';
        title.textContent=photo.dataset.photoTitle||'';
    };
    const openPhoto=index=>{showPhoto(index);if(!galleryLightbox.open)galleryLightbox.showModal();};
    photos.forEach((photo,index)=>photo.addEventListener('click',()=>openPhoto(index)));
    galleryLightbox.querySelector('[data-gallery-close]')?.addEventListener('click',()=>galleryLightbox.close());
    galleryLightbox.querySelector('[data-gallery-prev]')?.addEventListener('click',()=>showPhoto(current-1));
    galleryLightbox.querySelector('[data-gallery-next]')?.addEventListener('click',()=>showPhoto(current+1));
    galleryLightbox.addEventListener('click',event=>{if(event.target===galleryLightbox)galleryLightbox.close();});
    galleryLightbox.addEventListener('keydown',event=>{
        if(event.key==='ArrowLeft'){event.preventDefault();showPhoto(current-1);}
        if(event.key==='ArrowRight'){event.preventDefault();showPhoto(current+1);}
    });
    galleryLightbox.addEventListener('touchstart',event=>{touchStartX=event.changedTouches[0]?.clientX??null;},{passive:true});
    galleryLightbox.addEventListener('touchend',event=>{
        if(touchStartX===null)return;
        const delta=event.changedTouches[0].clientX-touchStartX;touchStartX=null;
        if(Math.abs(delta)>48)showPhoto(current+(delta<0?1:-1));
    },{passive:true});
    galleryLightbox.addEventListener('close',()=>{current=-1;image.removeAttribute('src');});
}


