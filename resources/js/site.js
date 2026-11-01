const menuBtn=document.getElementById('menuBtn');
const navLinks=document.getElementById('navLinks');
if(menuBtn&&navLinks){
    menuBtn.addEventListener('click',()=>{const open=navLinks.classList.toggle('open');menuBtn.setAttribute('aria-expanded',String(open));menuBtn.textContent=open?'×':'☰'});
    navLinks.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{navLinks.classList.remove('open');menuBtn.setAttribute('aria-expanded','false');menuBtn.textContent='☰'}));
}
const target=document.body.dataset.scrollTarget;
const reduceMotion=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if(target) document.getElementById(target)?.scrollIntoView({behavior:reduceMotion?'auto':'smooth',block:'start'});
const upBtn=document.getElementById('upBtn');
if(upBtn){window.addEventListener('scroll',()=>upBtn.classList.toggle('show',window.scrollY>500));upBtn.addEventListener('click',()=>window.scrollTo({top:0,behavior:reduceMotion?'auto':'smooth'}));}

// Keep all academic entries in the primary Akademik section. The management
// screen already separates entries by type; this only changes their public
// presentation into compact image-and-description rows.
const academicSection=document.getElementById('program');
const academicDetails=document.getElementById('akademik-detail');
if(academicSection){
    academicSection.querySelector('.program-list')?.remove();
    academicSection.querySelector('.section-head h2')?.remove();
    academicSection.querySelector('.section-head p')?.remove();

    if(!academicDetails){academicSection.remove();}
}
if(academicSection&&academicDetails){

    const list=document.createElement('div');
    list.className='academic-content-list';
    let groupTitle='';
    const mediaIndexes={};
    [...academicDetails.querySelector('.container').children].forEach(item=>{
        if(item.classList.contains('section-head'))return;
        if(item.matches('h3')){groupTitle=item.textContent.trim();return;}
        const entries=item.matches('.clubs,.program-list')?[...item.children]:[item];
        entries.forEach(entry=>{
            const row=document.createElement('article');
            row.className='academic-content-entry';
            if(groupTitle){const category=document.createElement('span');category.className='academic-entry-category';category.textContent=groupTitle;row.append(category);}
            const mediaIndex=mediaIndexes[groupTitle]||0;
            mediaIndexes[groupTitle]=mediaIndex+1;
            const media=window.academicEntryMedia?.[groupTitle]?.[mediaIndex];
            const image=entry.querySelector('img')||(media?.image?Object.assign(document.createElement('img'),{src:media.image,alt:media.alt||groupTitle}):null);
            if(image){image.classList.add('academic-entry-image');image.loading='lazy';image.decoding='async';row.append(image);}
            entry.querySelector('h3')?.remove();
            const copy=document.createElement('div');
            copy.className='academic-entry-copy';
            [...entry.childNodes].forEach(node=>{if(node!==image)copy.append(node);});
            const emptyDescription=[...copy.querySelectorAll('p')].find(paragraph=>!paragraph.textContent.trim());
            if(media?.description&&emptyDescription)emptyDescription.textContent=media.description;
            else if(media?.description&&!copy.querySelector('p')){const description=document.createElement('p');description.textContent=media.description;copy.append(description);}
            row.append(copy);list.append(row);
        });
    });
    academicSection.querySelector('.container').append(list);
    academicDetails.remove();
}


