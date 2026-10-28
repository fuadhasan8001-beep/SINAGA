const { test } = require('node:test');
const assert = require('node:assert/strict');
const { spawn } = require('node:child_process');
const { mkdtemp, rm, readFile } = require('node:fs/promises');
const os = require('node:os');
const path = require('node:path');

test('CMS Admin/Guru, persetujuan, gambar, impor DOCX, dan artikel publik', async t => {
  const dataDir=await mkdtemp(path.join(os.tmpdir(),'sinaga-cms-'));
  const server=spawn(process.execPath,[path.join(__dirname,'..','server.js')],{env:{...process.env,NODE_ENV:'test',PORT:'0',DATA_DIR:dataDir,SESSION_SECRET:'test-secret-which-is-longer-than-thirty-two-characters',ADMIN_NAME:'Admin Uji',ADMIN_EMAIL:'admin@test.local',ADMIN_PASSWORD:'password-admin-123',PUBLIC_ORIGIN:'https://school.test'},stdio:['ignore','pipe','pipe']});
  let logs='';server.stdout.on('data',b=>logs+=b.toString());server.stderr.on('data',b=>logs+=b.toString());
  t.after(async()=>{const exited=new Promise(resolve=>server.once('exit',resolve));server.kill('SIGTERM');await exited;await rm(dataDir,{recursive:true,force:true})});
  let base='';
  for(let i=0;i<100;i++){
    const m=logs.match(/http:\/\/localhost:(\d+)/);if(m){base=`http://127.0.0.1:${m[1]}`;break}
    if(server.exitCode!==null)throw new Error(`Server exited early: ${logs}`);
    await new Promise(r=>setTimeout(r,50));
  }
  assert.ok(base,`Server did not start: ${logs}`);
  async function login(email,password){
    const first=await fetch(`${base}/api/csrf`),cookie1=first.headers.get('set-cookie').split(';')[0],token=(await first.json()).token;
    const response=await fetch(`${base}/api/auth/login`,{method:'POST',headers:{'content-type':'application/json','x-csrf-token':token,cookie:cookie1},body:JSON.stringify({email,password})});
    assert.equal(response.status,200,await response.clone().text());
    return {cookie:response.headers.get('set-cookie').split(';')[0],csrf:(await response.json()).csrf};
  }
  async function call(auth,url,method='GET',body){
    const headers={cookie:auth.cookie};if(!['GET','HEAD'].includes(method))headers['x-csrf-token']=auth.csrf;
    let payload=body;if(body && !(body instanceof FormData)){headers['content-type']='application/json';payload=JSON.stringify(body)}
    const response=await fetch(`${base}${url}`,{method,headers,body:payload});let data;try{data=await response.json()}catch{data=await response.text()}return {status:response.status,data,response};
  }
  const admin=await login('admin@test.local','password-admin-123');
  assert.equal((await call(admin,'/api/dashboard')).status,200);
  assert.equal((await fetch(`${base}/api/categories`,{headers:{cookie:admin.cookie}})).status,200);
  const created=await call(admin,'/api/users','POST',{name:'Guru Satu',email:'guru@test.local',password:'password-guru-123'});
  assert.equal(created.status,201,JSON.stringify(created.data));
  const teacher=await login('guru@test.local','password-guru-123');
  assert.equal((await call(teacher,'/api/users')).status,403);
  assert.equal((await call(teacher,'/api/articles/1/review','POST',{action:'publish'})).status,403);

  const invalidDoc=new FormData();invalidDoc.append('file',new Blob(['bukan docx']), 'bad.docx');
  const invalidDocResult=await call(teacher,'/api/articles/import-docx','POST',invalidDoc);
  assert.equal(invalidDocResult.status,400);
  const invalidImage=new FormData();invalidImage.append('file',new Blob(['<svg onload=alert(1)>']), 'bad.png');
  assert.equal((await call(teacher,'/api/media/image','POST',invalidImage)).status,400);

  const docx=new FormData();docx.append('file',new Blob([await readFile(path.join(__dirname,'fixtures','sample-with-image.docx'))]),'sample.docx');
  const imported=await call(teacher,'/api/articles/import-docx','POST',docx);
  assert.equal(imported.status,200,JSON.stringify(imported.data));
  assert.match(imported.data.html,/Judul dari Word/);
  assert.match(imported.data.html,/Paragraf setelah gambar/);
  assert.match(imported.data.html,/src="\/uploads\//);

  const article=await call(teacher,'/api/articles','POST',{title:'Artikel Guru Pertama',excerpt:'Ringkasan',body:`${imported.data.html}<p>Aman <script>alert(1)</script></p><img src="https://evil.test/tracker.png">`,submit:'review'});
  assert.equal(article.status,201,JSON.stringify(article.data));
  assert.equal(article.data.status,'pending');assert.doesNotMatch(article.data.body,/<script/i);
  const other=await call(admin,'/api/articles','POST',{title:'Artikel Admin',body:'<p>Konten admin</p>'});
  assert.equal(other.status,201);
  assert.equal((await call(teacher,'/api/articles')).data.length,1);
  assert.equal((await call(admin,'/api/articles')).data.length,2);
  assert.equal((await call(teacher,`/api/articles/${other.data.id}`,'PUT',{title:'Ambil milik admin',body:'<p>no</p>'})).status,403);
  const revised=await call(admin,`/api/articles/${article.data.id}/review`,'POST',{action:'revision',note:'Tambahkan sumber berita.'});
  assert.equal(revised.status,200);assert.equal(revised.data.status,'revision');
  const edit=await call(teacher,`/api/articles/${article.data.id}`,'PUT',{title:'Artikel Guru Diperbaiki',body:`<h2>Sudah diperbaiki</h2>${imported.data.html}<p>Isi yang benar.</p>`,submit:'review'});
  assert.equal(edit.status,200);assert.equal(edit.data.status,'pending');
  const published=await call(admin,`/api/articles/${article.data.id}/review`,'POST',{action:'publish'});
  assert.equal(published.status,200);assert.equal(published.data.status,'published');
  const publicList=await fetch(`${base}/api/public/articles`).then(r=>r.json());
  assert.ok(publicList.some(a=>a.slug===published.data.slug));
  const cors=await fetch(`${base}/api/public/articles`,{headers:{origin:'https://school.test'}});
  assert.equal(cors.headers.get('access-control-allow-origin'),'https://school.test');
  const articlePage=await fetch(`${base}/artikel/${published.data.slug}`).then(r=>r.text());
  assert.match(articlePage,/Sudah diperbaiki/);
  assert.match(articlePage,/\/uploads\/[a-f0-9-]+\.png/);
  assert.doesNotMatch(articlePage,/evil\.test/);
  const imagePath=articlePage.match(/(\/uploads\/[a-f0-9-]+\.png)/)[1];
  assert.equal((await fetch(`${base}${imagePath}`)).status,200);
  const unpublish=await call(admin,`/api/articles/${article.data.id}/review`,'POST',{action:'unpublish'});
  assert.equal(unpublish.status,200);assert.equal(unpublish.data.status,'draft');
  const afterUnpublish=await fetch(`${base}/api/public/articles`).then(r=>r.json());
  assert.equal(afterUnpublish.some(a=>a.slug===published.data.slug),false);
  assert.equal((await fetch(`${base}/admin/`)).status,200);
});
