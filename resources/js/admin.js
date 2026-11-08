const sidebarToggle=document.querySelector('.sidebar-toggle,.site-admin-toggle'),sidebarCloses=document.querySelectorAll('[data-sidebar-close]');
document.querySelectorAll('[data-password-toggle]').forEach(toggle=>{
    const input=document.getElementById(toggle.getAttribute('aria-controls'));
    if(!input)return;
    toggle.addEventListener('click',()=>{
        const showPassword=input.type==='password';
        input.type=showPassword?'text':'password';
        toggle.setAttribute('aria-pressed',String(showPassword));
        toggle.setAttribute('aria-label',showPassword?'Sembunyikan kata sandi':'Tampilkan kata sandi');
        toggle.querySelector('[data-eye-open]').hidden=showPassword;
        toggle.querySelector('[data-eye-closed]').hidden=!showPassword;
    });
});
const setSidebar=open=>{document.body.classList.toggle('sidebar-open',open);sidebarToggle?.setAttribute('aria-expanded',String(open));sidebarToggle?.setAttribute('aria-label',open?'Tutup menu pengelolaan':'Buka menu pengelolaan');};
sidebarToggle?.addEventListener('click',()=>setSidebar(!document.body.classList.contains('sidebar-open')));
sidebarCloses.forEach(button=>button.addEventListener('click',()=>{setSidebar(false);sidebarToggle?.focus()}));
document.querySelectorAll('.sidebar a').forEach(link=>link.addEventListener('click',()=>setSidebar(false)));
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.body.classList.contains('sidebar-open')){setSidebar(false);sidebarToggle?.focus()}});
const logoutDialog=document.getElementById('logoutConfirmation'),logoutForm=document.getElementById('logoutForm'),logoutOpen=document.querySelector('[data-logout-open]'),logoutCancel=document.querySelector('[data-logout-cancel]'),logoutConfirm=document.querySelector('[data-logout-confirm]');
logoutOpen?.addEventListener('click',()=>{if(!logoutDialog?.open)logoutDialog?.showModal();logoutCancel?.focus()});
logoutCancel?.addEventListener('click',()=>logoutDialog?.close());
logoutConfirm?.addEventListener('click',()=>{if(logoutForm&&!logoutConfirm.disabled){logoutConfirm.disabled=true;logoutConfirm.textContent='Keluar…';logoutForm.requestSubmit()}});
logoutDialog?.addEventListener('close',()=>logoutOpen?.focus());
const actionDialog=document.getElementById('actionConfirmDialog'),actionTitle=document.getElementById('actionConfirmTitle'),actionDescription=document.getElementById('actionConfirmDescription'),actionCancel=document.querySelector('[data-action-cancel]'),actionConfirm=document.querySelector('[data-action-confirm]');
let pendingActionForm=null,pendingActionButton=null;
document.addEventListener('click',event=>{
    const trigger=event.target.closest('[data-confirm-action]');
    if(!trigger||!actionDialog)return;
    event.preventDefault();
    if(actionDialog.open)return;
    pendingActionForm=trigger.form;
    pendingActionButton=trigger;
    actionTitle.textContent=trigger.dataset.confirmTitle||'Konfirmasi penghapusan';
    actionDescription.textContent=trigger.dataset.confirmDescription||'Tindakan ini tidak dapat dibatalkan.';
    actionConfirm.textContent=trigger.dataset.confirmLabel||'Ya, Hapus';
    actionConfirm.disabled=false;
    actionDialog.showModal();
    actionCancel?.focus();
});
actionCancel?.addEventListener('click',()=>actionDialog?.close());
actionConfirm?.addEventListener('click',()=>{
    if(!pendingActionForm||actionConfirm.disabled)return;
    actionConfirm.disabled=true;
    actionConfirm.textContent='Memproses…';
    pendingActionForm.requestSubmit();
});
actionDialog?.addEventListener('close',()=>{
    if(!actionConfirm?.disabled)pendingActionButton?.focus();
    pendingActionForm=null;
    pendingActionButton=null;
});
if(document.body.classList.contains('admin-preview')){
    const academicManagementUrl=document.querySelector('#admin-sidebar a[href*="section=academic"]')?.href;
    const contextualLinks={profil:['/admin/settings?section=profile&edit=1','Edit profil'],akademik:[academicManagementUrl,'Edit akademik'],pengumuman:['/admin/content/announcement/create','Tambah pengumuman'],ekstrakurikuler:['/admin/content/extracurricular/create','Tambah ekstrakurikuler'],prestasi:['/admin/content/achievement/create','Tambah prestasi'],kontak:['/admin/settings?section=contact','Edit informasi kontak']};
    Object.entries(contextualLinks).forEach(([id,[href,label]])=>{const head=document.querySelector(`#${id} .section-head`);if(!head||!href)return;const link=document.createElement('a');link.className='admin-context-edit';link.href=href;link.textContent=label;head.append(link);});
    [...document.querySelectorAll('.section-head')].find(head=>head.querySelector('h2')?.textContent.trim()==='Galeri Sekolah')?.append(Object.assign(document.createElement('a'),{className:'admin-context-edit',href:'/admin/content/gallery',textContent:'Kelola Galeri'}));
}
const photoForm=document.getElementById('photoEditForm');
if(photoForm){
    const fileInput=document.getElementById('photoFile'),fileName=document.getElementById('photoFileName'),preview=document.getElementById('photoPreview'),emptyPreview=document.getElementById('photoPreviewEmpty');
    let previewUrl=null;
    fileInput?.addEventListener('change',()=>{const file=fileInput.files?.[0];if(!file)return;fileName.textContent=file.name;previewUrl&&URL.revokeObjectURL(previewUrl);previewUrl=URL.createObjectURL(file);preview.src=previewUrl;preview.hidden=false;if(emptyPreview)emptyPreview.hidden=true;});
    photoForm.addEventListener('submit',()=>{const button=photoForm.querySelector('[type="submit"]');if(button){button.disabled=true;button.textContent='Menyimpan foto…';}});
}
const spmbPosterInput=document.getElementById('spmbPoster');
if(spmbPosterInput){
    const previewWrap=document.getElementById('spmbPosterPreview'),previewImage=previewWrap?.querySelector('img');
    let previewUrl=null;
    spmbPosterInput.addEventListener('change',()=>{
        const file=spmbPosterInput.files?.[0];
        if(!file){if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;if(previewWrap)previewWrap.hidden=true;return;}
        if(previewUrl)URL.revokeObjectURL(previewUrl);
        previewUrl=URL.createObjectURL(file);
        if(previewImage)previewImage.src=previewUrl;
        if(previewWrap)previewWrap.hidden=false;
    });
    window.addEventListener('pagehide',()=>{if(previewUrl)URL.revokeObjectURL(previewUrl)});
}
const extracurricularImage=document.getElementById('extracurricularImage');
if(extracurricularImage){
    const preview=document.getElementById('extracurricularPreview'),placeholder=document.getElementById('extracurricularPlaceholder');
    let previewUrl=null;
    extracurricularImage.addEventListener('change',()=>{
        const file=extracurricularImage.files?.[0];
        if(!file)return;
        if(previewUrl)URL.revokeObjectURL(previewUrl);
        previewUrl=URL.createObjectURL(file);
        preview.src=previewUrl;
        preview.hidden=false;
        if(placeholder)placeholder.hidden=true;
    });
}
const galleryImage=document.getElementById('galleryImage');
if(galleryImage){
    const zone=document.getElementById('galleryUploadZone'),preview=document.getElementById('galleryUploadPreview'),placeholder=document.getElementById('galleryUploadPlaceholder');
    let previewUrl=null;
    const showFile=file=>{
        if(!file)return;
        if(previewUrl)URL.revokeObjectURL(previewUrl);
        previewUrl=URL.createObjectURL(file);
        preview.src=previewUrl;
        preview.hidden=false;
        if(placeholder)placeholder.hidden=true;
    };
    galleryImage.addEventListener('change',()=>showFile(galleryImage.files?.[0]));
    zone?.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();galleryImage.click();}});
    ['dragenter','dragover'].forEach(name=>zone?.addEventListener(name,event=>{event.preventDefault();zone.classList.add('is-dragging');}));
    ['dragleave','drop'].forEach(name=>zone?.addEventListener(name,event=>{event.preventDefault();zone.classList.remove('is-dragging');}));
    zone?.addEventListener('drop',event=>{
        const file=event.dataTransfer?.files?.[0];
        if(!file)return;
        const transfer=new DataTransfer();transfer.items.add(file);galleryImage.files=transfer.files;showFile(file);
    });
}
const galleryAdmin=document.getElementById('galleryAdmin'),galleryGrid=document.getElementById('galleryAdminGrid');
if(galleryAdmin&&galleryGrid){
    const status=document.getElementById('galleryOrderStatus'),csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
    let dragged=null,dragStartOrder=null,dropCommitted=false;
    const cards=()=>[...galleryGrid.querySelectorAll('[data-gallery-card]')];
    const ids=()=>cards().map(card=>Number(card.dataset.entryId));
    const paintOrder=()=>cards().forEach((card,index)=>{const number=card.querySelector('[data-order-number]');if(number)number.textContent=String(index+1);});
    const restoreOrder=order=>{const byId=new Map(cards().map(card=>[Number(card.dataset.entryId),card]));order.forEach(id=>{const card=byId.get(id);if(card)galleryGrid.append(card);});paintOrder();};
    const persistOrder=async oldOrder=>{
        const orderedIds=ids();
        paintOrder();
        if(status)status.textContent='Menyimpan urutan…';
        galleryAdmin.classList.add('is-saving-order');
        try{
            const response=await fetch(galleryAdmin.dataset.orderUrl,{method:'PATCH',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({ordered_ids:orderedIds})});
            const result=await response.json();
            if(!response.ok)throw new Error(result.message||'Urutan foto gagal disimpan.');
            if(status)status.textContent=result.message||'Urutan galeri tersimpan.';
        }catch(error){
            if(oldOrder)restoreOrder(oldOrder);
            if(status)status.textContent=`${error.message} Urutan sebelumnya dipulihkan.`;
        }finally{galleryAdmin.classList.remove('is-saving-order');}
    };
    galleryGrid.addEventListener('dragstart',event=>{
        const card=event.target.closest('[data-gallery-card]');
        if(!card||event.target.closest('a,button,form')){event.preventDefault();return;}
        dragged=card;dragStartOrder=ids();dropCommitted=false;card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain',card.dataset.entryId);
    });
    galleryGrid.addEventListener('dragover',event=>{
        if(!dragged)return;
        event.preventDefault();
        const target=event.target.closest('[data-gallery-card]');
        if(!target||target===dragged)return;
        const box=target.getBoundingClientRect(),after=event.clientY>box.top+box.height/2;
        galleryGrid.insertBefore(dragged,after?target.nextElementSibling:target);
    });
    galleryGrid.addEventListener('drop',event=>{if(!dragged)return;event.preventDefault();dropCommitted=true;persistOrder(dragStartOrder);});
    galleryGrid.addEventListener('dragend',()=>{if(dragged&&!dropCommitted&&dragStartOrder)restoreOrder(dragStartOrder);dragged?.classList.remove('is-dragging');dragged=null;dragStartOrder=null;});
    galleryGrid.addEventListener('click',event=>{
        const button=event.target.closest('[data-order-move]');if(!button)return;
        const card=button.closest('[data-gallery-card]'),oldOrder=ids();
        if(button.dataset.orderMove==='up'&&card.previousElementSibling)galleryGrid.insertBefore(card,card.previousElementSibling);
        if(button.dataset.orderMove==='down'&&card.nextElementSibling)galleryGrid.insertBefore(card.nextElementSibling,card);
        if(ids().some((id,index)=>id!==oldOrder[index]))persistOrder(oldOrder);
    });
}
const editorForm=document.getElementById('articleForm');
if(editorForm){
    const editor=document.getElementById('editorBody'),bodyField=document.getElementById('articleBody'),csrf=document.querySelector('meta[name="csrf-token"]')?.content||'',slugPreview=document.getElementById('slugPreview');
    let range=null,selectedImage=null;
    const safeHtml=html=>{const doc=new DOMParser().parseFromString(String(html||''),'text/html'),tags=new Set(['P','BR','H1','H2','H3','H4','STRONG','B','EM','I','U','S','UL','OL','LI','A','BLOCKQUOTE','IMG','FIGURE','FIGCAPTION','HR']);for(const node of [...doc.body.querySelectorAll('*')].reverse()){if(!tags.has(node.tagName)){if(['SCRIPT','STYLE','IFRAME','OBJECT','EMBED','SVG','MATH'].includes(node.tagName))node.remove();else node.replaceWith(...node.childNodes);continue}for(const attr of [...node.attributes]){const okay=node.tagName==='A'&&['href','target','rel'].includes(attr.name)||node.tagName==='IMG'&&['src','alt','title','width','height'].includes(attr.name);if(!okay)node.removeAttribute(attr.name)}if(node.tagName==='A'){const href=node.getAttribute('href')||'';if(!/^https?:\/\//i.test(href)&&!/^mailto:/i.test(href))node.removeAttribute('href');node.setAttribute('target','_blank');node.setAttribute('rel','noopener noreferrer')}if(node.tagName==='IMG'&&!/^\/storage\/media\/[a-f0-9-]+\.(?:png|jpe?g|webp|gif)$/i.test(node.getAttribute('src')||''))node.removeAttribute('src')}return doc.body.innerHTML};
    const selectionSave=()=>{const s=getSelection();if(s?.rangeCount&&editor.contains(s.anchorNode))range=s.getRangeAt(0).cloneRange()};
    const selectionRestore=()=>{editor.focus();const s=getSelection();s.removeAllRanges();if(range)s.addRange(range);else{const r=document.createRange();r.selectNodeContents(editor);r.collapse(false);s.addRange(r)}};
    editor.addEventListener('keyup',selectionSave);editor.addEventListener('mouseup',selectionSave);editor.addEventListener('click',event=>{if(event.target.matches('img')){selectedImage=event.target;selectedImage.style.outline='3px solid #166534';selectionSave()}else if(selectedImage){selectedImage.style.outline='';selectedImage=null}});
    editor.addEventListener('paste',event=>{event.preventDefault();const html=event.clipboardData.getData('text/html'),plain=event.clipboardData.getData('text/plain');document.execCommand('insertHTML',false,html?safeHtml(html):`<p>${plain.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])).replace(/\r?\n/g,'<br>')}</p>`)});
    document.querySelectorAll('.toolbar button').forEach(button=>button.addEventListener('mousedown',event=>event.preventDefault()));
    document.querySelectorAll('[data-command]').forEach(button=>button.onclick=()=>{editor.focus();document.execCommand(button.dataset.command,false,null)});
    document.querySelectorAll('[data-block]').forEach(button=>button.onclick=()=>{editor.focus();document.execCommand('formatBlock',false,button.dataset.block)});
    document.querySelector('[data-link]')?.addEventListener('click',()=>{selectionSave();const url=prompt('Alamat tautan (https://…):');if(url){if(!/^https?:\/\//i.test(url))return alert('Tautan harus memakai http:// atau https://.');selectionRestore();document.execCommand('createLink',false,url)}});
    const upload=async(file,alt='',caption='')=>{const data=new FormData();data.append('file',file);data.append('alt',alt);data.append('caption',caption);const response=await fetch(editorForm.dataset.imageUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:data,credentials:'same-origin'});const result=await response.json();if(!response.ok)throw new Error(result.message||'Gagal mengunggah gambar.');return result};
    document.getElementById('insertImage')?.addEventListener('click',()=>{selectedImage=null;selectionSave();document.getElementById('imageFile').click()});
    document.getElementById('replaceImage')?.addEventListener('click',()=>selectedImage?document.getElementById('imageFile').click():alert('Pilih gambar di dalam artikel terlebih dahulu.'));
    document.getElementById('removeImage')?.addEventListener('click',()=>{if(!selectedImage)return alert('Pilih gambar di dalam artikel terlebih dahulu.');(selectedImage.closest('figure')||selectedImage).remove();selectedImage=null});
    document.getElementById('imageFile')?.addEventListener('change',async event=>{const file=event.target.files[0];event.target.value='';if(!file)return;try{if(selectedImage){const image=await upload(file,prompt('Teks alternatif:',selectedImage.alt)||'',prompt('Keterangan:',selectedImage.title||'')||'');selectedImage.src=image.url;selectedImage.alt=image.alt||'';selectedImage.title=image.caption||'';const fig=selectedImage.closest('figure'),caption=fig?.querySelector('figcaption');if(image.caption){if(caption)caption.textContent=image.caption;else{const item=document.createElement('figcaption');item.textContent=image.caption;fig?.append(item)}}else caption?.remove();selectedImage.style.outline='';selectedImage=null;return}const alt=prompt('Teks alternatif gambar:','')||'',caption=prompt('Keterangan gambar (boleh kosong):','')||'',image=await upload(file,alt,caption),figure=document.createElement('figure'),img=document.createElement('img');img.src=image.url;img.alt=alt;img.title=caption;figure.append(img);if(caption){const label=document.createElement('figcaption');label.textContent=caption;figure.append(label)}selectionRestore();const s=getSelection();if(s.rangeCount){const r=s.getRangeAt(0);r.deleteContents();r.insertNode(figure);r.setStartAfter(figure);r.collapse(true);s.removeAllRanges();s.addRange(r)}else editor.append(figure)}catch(error){alert(error.message)}});
    document.getElementById('importWord')?.addEventListener('click',()=>document.getElementById('docxFile').click());
    document.getElementById('docxFile')?.addEventListener('change',async event=>{const file=event.target.files[0];event.target.value='';if(!file)return;const data=new FormData();data.append('file',file);try{const response=await fetch(editorForm.dataset.docxUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:data,credentials:'same-origin'});const result=await response.json();if(!response.ok)throw new Error(result.message||'Dokumen Word tidak dapat diproses.');editor.innerHTML=result.html;const notice=document.getElementById('importNotice');notice.textContent=result.warnings?.length?`Dokumen diimpor dengan catatan: ${result.warnings.join(' · ')}`:'Teks, format dasar, dan gambar tertanam telah dimuat. Periksa hasilnya sebelum menyimpan.';notice.hidden=false}catch(error){alert(error.message)}});
    editorForm.querySelectorAll('[data-intent]').forEach(button=>button.addEventListener('click',()=>document.getElementById('intent').value=button.dataset.intent));
    editorForm.addEventListener('submit',()=>bodyField.value=editor.innerHTML);
    let slugWasEdited=false;
    slugPreview?.addEventListener('input',()=>{slugWasEdited=true});
    editorForm.elements.title.addEventListener('input',()=>{if(!slugWasEdited&&slugPreview)slugPreview.value=editorForm.elements.title.value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^\w\s-]/g,'').trim().replace(/[\s_-]+/g,'-').replace(/^-|-$/g,'')});
    document.getElementById('previewBtn')?.addEventListener('click',()=>{const preview=document.getElementById('preview');if(preview.hidden){preview.innerHTML=`<h2>${editorForm.elements.title.value.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))||'Pratinjau artikel'}</h2>${safeHtml(editor.innerHTML)}`;preview.hidden=false}else preview.hidden=true});
}
