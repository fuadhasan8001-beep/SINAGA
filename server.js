require('dotenv').config();
const express = require('express');
const session = require('express-session');
const Database = require('better-sqlite3');
const multer = require('multer');
const sanitizeHtml = require('sanitize-html');
const mammoth = require('mammoth');
const JSZip = require('jszip');
const rateLimit = require('express-rate-limit');
const helmet = require('helmet');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const { promisify } = require('node:util');

const app = express();
const PORT = Number(process.env.PORT || 3000);
const ROOT = __dirname;
const DATA_DIR = path.resolve(process.env.DATA_DIR || path.join(ROOT, 'data'));
const MEDIA_DIR = path.join(DATA_DIR, 'uploads');
fs.mkdirSync(MEDIA_DIR, { recursive: true });
const db = new Database(path.join(DATA_DIR, 'sinaga.sqlite'));
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');
db.exec(`
CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('admin','teacher')), active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS sessions(sid TEXT PRIMARY KEY, expired INTEGER NOT NULL, sess TEXT NOT NULL);
CREATE INDEX IF NOT EXISTS sessions_expired_idx ON sessions(expired);
CREATE TABLE IF NOT EXISTS categories(id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE, slug TEXT NOT NULL UNIQUE);
CREATE TABLE IF NOT EXISTS articles(id INTEGER PRIMARY KEY, author_id INTEGER NOT NULL REFERENCES users(id), category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL, title TEXT NOT NULL, slug TEXT NOT NULL UNIQUE, excerpt TEXT NOT NULL DEFAULT '', body TEXT NOT NULL DEFAULT '', cover_image TEXT, status TEXT NOT NULL CHECK(status IN ('draft','pending','revision','published','rejected')) DEFAULT 'draft', revision_note TEXT, published_at TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS activity(id INTEGER PRIMARY KEY, actor_id INTEGER REFERENCES users(id) ON DELETE SET NULL, article_id INTEGER REFERENCES articles(id) ON DELETE SET NULL, action TEXT NOT NULL, detail TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE INDEX IF NOT EXISTS article_status_idx ON articles(status);
CREATE INDEX IF NOT EXISTS article_author_idx ON articles(author_id);
CREATE INDEX IF NOT EXISTS activity_created_idx ON activity(created_at);
`);

const pbkdf2 = promisify(crypto.pbkdf2);
async function hashPassword(password) {
  const salt = crypto.randomBytes(16);
  const hash = await pbkdf2(password, salt, 310000, 32, 'sha256');
  return `pbkdf2$310000$${salt.toString('hex')}$${hash.toString('hex')}`;
}
async function verifyPassword(password, stored) {
  const [scheme, rounds, saltHex, hashHex] = String(stored).split('$');
  if (scheme !== 'pbkdf2' || !/^\d+$/.test(rounds) || !/^[a-f0-9]+$/i.test(saltHex) || !/^[a-f0-9]+$/i.test(hashHex)) return false;
  const actual = await pbkdf2(password, Buffer.from(saltHex, 'hex'), Number(rounds), hashHex.length / 2, 'sha256');
  return crypto.timingSafeEqual(actual, Buffer.from(hashHex, 'hex'));
}

const sessionSecret = process.env.SESSION_SECRET;
if (!sessionSecret || sessionSecret.length < 32) {
  if (process.env.NODE_ENV === 'production') throw new Error('SESSION_SECRET wajib diisi dengan nilai acak minimal 32 karakter.');
  process.env.SESSION_SECRET = crypto.randomBytes(32).toString('hex');
}
app.disable('x-powered-by');
app.use(helmet({ contentSecurityPolicy: { directives: { defaultSrc: ["'self'"], styleSrc: ["'self'", "'unsafe-inline'"], scriptSrc: ["'self'"], imgSrc: ["'self'", 'data:'], connectSrc: ["'self'"], objectSrc: ["'none'"], frameAncestors: ["'none'"] } } }));
if (process.env.NODE_ENV === 'production') app.set('trust proxy', 1);
app.use(express.json({ limit: '2mb' }));
class SQLiteSessionStore extends session.Store {
  get(sid, callback) { try { const row=db.prepare('SELECT sess,expired FROM sessions WHERE sid=?').get(sid); if(!row || row.expired<Date.now()) { if(row) db.prepare('DELETE FROM sessions WHERE sid=?').run(sid); return callback(null,null); } callback(null,JSON.parse(row.sess)); } catch(e) { callback(e); } }
  set(sid, sess, callback=()=>{}) { try { const expired=sess.cookie?.expires?new Date(sess.cookie.expires).getTime():Date.now()+(sess.cookie?.maxAge||86400000); db.prepare('INSERT INTO sessions(sid,expired,sess) VALUES(?,?,?) ON CONFLICT(sid) DO UPDATE SET expired=excluded.expired,sess=excluded.sess').run(sid,expired,JSON.stringify(sess)); callback(null); } catch(e) { callback(e); } }
  destroy(sid, callback=()=>{}) { try { db.prepare('DELETE FROM sessions WHERE sid=?').run(sid); callback(null); } catch(e) { callback(e); } }
  touch(sid, sess, callback=()=>{}) { this.set(sid,sess,callback); }
}
const sessionStore=new SQLiteSessionStore();
const cleanupSessions=setInterval(()=>db.prepare('DELETE FROM sessions WHERE expired<?').run(Date.now()),60*60*1000); cleanupSessions.unref();
app.use(session({
  name: 'sinaga.sid', secret: process.env.SESSION_SECRET, resave: false, saveUninitialized: false,
  store: sessionStore,
  cookie: { httpOnly: true, sameSite: 'lax', secure: process.env.NODE_ENV === 'production', maxAge: 8 * 60 * 60 * 1000 }
}));
app.use('/uploads', express.static(MEDIA_DIR, { fallthrough: false, index: false, maxAge: '1d', setHeaders: res => res.setHeader('X-Content-Type-Options', 'nosniff') }));
app.use('/admin', express.static(path.join(ROOT, 'public', 'admin'), { index: 'index.html', fallthrough: true }));
app.use(express.static(ROOT, { index: 'index.html', dotfiles: 'ignore', setHeaders(res, file) { if (file.endsWith('.html')) res.setHeader('Cache-Control', 'no-cache'); } }));
app.use('/api/public/articles', (req,res,next) => {
  if(process.env.PUBLIC_ORIGIN && req.get('Origin')===process.env.PUBLIC_ORIGIN) { res.setHeader('Access-Control-Allow-Origin',process.env.PUBLIC_ORIGIN); res.vary('Origin'); }
  next();
});

const STATUS = { draft: 'Draft', pending: 'Menunggu Persetujuan', revision: 'Perlu Revisi', published: 'Diterbitkan', rejected: 'Ditolak' };
const fail = (res, code, message) => res.status(code).json({ error: message });
const cleanText = (value, max) => String(value || '').trim().slice(0, max);
const publicUser = row => ({ id: row.id, name: row.name, email: row.email, role: row.role, active: !!row.active });
const currentUser = req => req.session.user;
function requireAuth(req, res, next) {
  if (!currentUser(req)) return fail(res, 401, 'Silakan masuk terlebih dahulu.');
  const fresh=db.prepare('SELECT id,name,email,role,active FROM users WHERE id=?').get(currentUser(req).id);
  if (!fresh || !fresh.active) { req.session.destroy(()=>{}); return fail(res, 401, 'Akun tidak aktif. Hubungi Admin.'); }
  req.session.user=publicUser(fresh); next();
}
function requireAdmin(req, res, next) { requireAuth(req,res,()=>currentUser(req).role !== 'admin' ? fail(res,403,'Akses khusus Admin.') : next()); }
function requireCsrf(req, res, next) {
  if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(req.method)) return next();
  const sent = req.get('x-csrf-token');
  if (!req.session.csrf || !sent || sent.length !== req.session.csrf.length || !crypto.timingSafeEqual(Buffer.from(req.session.csrf), Buffer.from(sent))) return fail(res, 403, 'Token keamanan tidak valid. Muat ulang halaman.');
  next();
}
app.use('/api', requireCsrf);
app.get('/api/csrf', (req, res) => { req.session.csrf ||= crypto.randomBytes(32).toString('hex'); res.json({ token: req.session.csrf }); });
const loginLimiter = rateLimit({ windowMs: 15 * 60 * 1000, limit: 10, standardHeaders: 'draft-8', legacyHeaders: false, message: { error: 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.' } });

function logActivity(actorId, action, detail = '', articleId = null) {
  db.prepare('INSERT INTO activity(actor_id,article_id,action,detail) VALUES(?,?,?,?)').run(actorId, articleId, action, detail.slice(0, 300));
}
function articleRow(id) {
  return db.prepare(`SELECT a.*,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id WHERE a.id=?`).get(id);
}
function articleJson(row) {
  return { id: row.id, title: row.title, slug: row.slug, excerpt: row.excerpt, body: row.body, coverImage: row.cover_image, categoryId: row.category_id, category: row.category, status: row.status, statusLabel: STATUS[row.status], revisionNote: row.revision_note, authorId: row.author_id, author: row.author, publishedAt: row.published_at, createdAt: row.created_at, updatedAt: row.updated_at };
}
function uniqueSlug(title, exceptId = null) {
  let base = title.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 90) || 'artikel';
  let candidate = base, i = 2;
  while (db.prepare('SELECT id FROM articles WHERE slug=? AND id != COALESCE(?, -1)').get(candidate, exceptId)) candidate = `${base}-${i++}`;
  return candidate;
}
const allowedTags = ['p','br','h1','h2','h3','h4','strong','b','em','i','u','s','ul','ol','li','a','blockquote','img','figure','figcaption','hr'];
function cleanBody(html) {
  return sanitizeHtml(String(html || ''), { allowedTags, allowedAttributes: { a: ['href','target','rel'], img: ['src','alt','title','width','height'], figure: [], figcaption: [] }, allowedSchemes: ['http','https','mailto'], allowedSchemesByTag: { img: ['http','https'] }, transformTags: { a: sanitizeHtml.simpleTransform('a', { rel: 'noopener noreferrer', target: '_blank' }, true), img: (tag, attrs) => { const safe={...attrs}; if(!/^\/uploads\/[a-f0-9-]+\.(?:png|jpe?g|webp|gif)$/i.test(safe.src||'')) delete safe.src; return {tagName:tag,attribs:safe}; } }, enforceHtmlBoundary: true });
}
function mediaPath(value) { return typeof value==='string' && /^\/uploads\/[a-f0-9-]+\.(?:png|jpe?g|webp|gif)$/i.test(value) ? value : null; }

app.post('/api/auth/login', loginLimiter, async (req, res) => {
  const email = cleanText(req.body?.email, 254).toLowerCase();
  const password = String(req.body?.password || '');
  if (!email || password.length < 1 || password.length > 200) return fail(res, 400, 'Email dan kata sandi wajib diisi.');
  const user = db.prepare('SELECT * FROM users WHERE email=? AND active=1').get(email);
  if (!user || !(await verifyPassword(password, user.password_hash))) return fail(res, 401, 'Email atau kata sandi salah.');
  await new Promise((resolve, reject) => req.session.regenerate(err => err ? reject(err) : resolve()));
  req.session.user = publicUser(user); req.session.csrf = crypto.randomBytes(32).toString('hex');
  logActivity(user.id, 'login', 'Masuk ke panel');
  res.json({ user: req.session.user, csrf: req.session.csrf });
});
app.post('/api/auth/logout', requireAuth, (req, res) => { logActivity(currentUser(req).id, 'logout', 'Keluar dari panel'); req.session.destroy(() => { res.clearCookie('sinaga.sid'); res.json({ ok: true }); }); });
app.get('/api/auth/me', (req, res) => {
  if(!currentUser(req)) return res.json({user:null});
  const fresh=db.prepare('SELECT id,name,email,role,active FROM users WHERE id=?').get(currentUser(req).id);
  if(!fresh||!fresh.active){req.session.destroy(()=>{});return res.json({user:null});}
  req.session.user=publicUser(fresh); res.json({user:req.session.user});
});

const imageUpload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 5 * 1024 * 1024, files: 1 } });
const docxUpload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10 * 1024 * 1024, files: 1 } });
function imageType(buffer) {
  if (buffer.length >= 8 && buffer.subarray(0,8).equals(Buffer.from([137,80,78,71,13,10,26,10]))) return ['png','image/png'];
  if (buffer.length >= 3 && buffer[0]===255 && buffer[1]===216 && buffer[2]===255) return ['jpg','image/jpeg'];
  if (buffer.length >= 12 && buffer.toString('ascii',0,4)==='RIFF' && buffer.toString('ascii',8,12)==='WEBP') return ['webp','image/webp'];
  if (buffer.length >= 6 && /^GIF8[79]a$/.test(buffer.toString('ascii',0,6))) return ['gif','image/gif'];
  return null;
}
function saveImage(buffer) {
  const type = imageType(buffer); if (!type) throw new Error('Format gambar tidak didukung. Gunakan JPEG, PNG, WebP, atau GIF.');
  const name = `${crypto.randomUUID()}.${type[0]}`; fs.writeFileSync(path.join(MEDIA_DIR, name), buffer, { flag: 'wx' }); return `/uploads/${name}`;
}
app.post('/api/media/image', requireAuth, imageUpload.single('file'), (req,res) => {
  if (!req.file) return fail(res,400,'Pilih gambar terlebih dahulu.');
  try { const url=saveImage(req.file.buffer); logActivity(currentUser(req).id,'unggah gambar',url); res.json({url}); }
  catch(e) { fail(res,400,e.message); }
});

function requireEditor(req,res,next) { if (!currentUser(req)) return fail(res,401,'Silakan masuk terlebih dahulu.'); next(); }
app.post('/api/articles/import-docx', requireEditor, docxUpload.single('file'), async (req,res) => {
  try {
    if (!req.file) return fail(res,400,'Pilih dokumen .docx terlebih dahulu.');
    if (path.extname(req.file.originalname).toLowerCase() !== '.docx' || req.file.buffer.subarray(0,4).toString('hex') !== '504b0304') return fail(res,400,'File tidak valid. Pilih dokumen Microsoft Word .docx yang sah.');
    const archive=await JSZip.loadAsync(req.file.buffer,{checkCRC32:false}), entries=Object.values(archive.files).filter(entry=>!entry.dir);
    const unpacked=entries.reduce((sum,entry)=>sum+(entry._data?.uncompressedSize||0),0);
    if(entries.length>500 || unpacked>30*1024*1024) return fail(res,400,'Dokumen terlalu kompleks atau terlalu besar untuk diimpor.');
    const result = await mammoth.convertToHtml({ buffer: req.file.buffer }, { convertImage: mammoth.images.imgElement(async image => {
      const buffer = await image.read('base64').then(value => Buffer.from(value,'base64'));
      const src = saveImage(buffer); return { src, alt: '' };
    }) });
    const body = cleanBody(result.value);
    if (!body.trim()) return fail(res,400,'Dokumen tidak berisi konten yang dapat diimpor.');
    res.json({ html: body, warnings: result.messages.map(item => item.message).slice(0,10) });
  } catch(e) { fail(res,400,`Dokumen Word tidak dapat diproses: ${e.message.slice(0,180)}`); }
});

app.get('/api/categories', requireAuth, (req,res) => res.json(db.prepare('SELECT id,name,slug FROM categories ORDER BY name').all()));
app.post('/api/categories', requireAdmin, (req,res) => {
  const name=cleanText(req.body?.name,80); if(name.length<2) return fail(res,400,'Nama kategori minimal 2 karakter.');
  try { const slug=name.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); const result=db.prepare('INSERT INTO categories(name,slug) VALUES(?,?)').run(name,slug); res.status(201).json({id:result.lastInsertRowid,name,slug}); }
  catch(e) { fail(res,409,'Kategori sudah ada atau tidak valid.'); }
});
app.put('/api/categories/:id', requireAdmin, (req,res) => {
  const id=Number(req.params.id),name=cleanText(req.body?.name,80); if(name.length<2) return fail(res,400,'Nama kategori minimal 2 karakter.');
  try { const slug=name.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''); const result=db.prepare('UPDATE categories SET name=?,slug=? WHERE id=?').run(name,slug,id); if(!result.changes) return fail(res,404,'Kategori tidak ditemukan.'); res.json({id,name,slug}); }
  catch(e) { fail(res,409,'Kategori sudah ada atau tidak valid.'); }
});
app.delete('/api/categories/:id', requireAdmin, (req,res) => { db.prepare('DELETE FROM categories WHERE id=?').run(Number(req.params.id)); res.json({ok:true}); });

app.get('/api/dashboard', requireAuth, (req,res) => {
  const user=currentUser(req), admin=user.role==='admin';
  const where=admin?'':' WHERE author_id=?', params=admin?[]:[user.id];
  const countStatus=status=>db.prepare(`SELECT COUNT(*) n FROM articles${where}${where?' AND':' WHERE'} status=?`).get(...params,status).n;
  const articles=db.prepare(`SELECT a.*,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id${admin?'':' WHERE a.author_id=?'} ORDER BY a.updated_at DESC LIMIT 30`).all(...params).map(articleJson);
  const activity=admin?db.prepare('SELECT x.*,u.name actor FROM activity x LEFT JOIN users u ON u.id=x.actor_id ORDER BY x.id DESC LIMIT 12').all():db.prepare('SELECT x.*,u.name actor FROM activity x LEFT JOIN users u ON u.id=x.actor_id WHERE x.actor_id=? ORDER BY x.id DESC LIMIT 12').all(user.id);
  res.json({counts:{total:countStatus('draft')+countStatus('pending')+countStatus('revision')+countStatus('published')+countStatus('rejected'),draft:countStatus('draft'),pending:countStatus('pending'),revision:countStatus('revision'),published:countStatus('published'),teachers:admin?db.prepare("SELECT COUNT(*) n FROM users WHERE role='teacher' AND active=1").get().n:undefined},articles,activity});
});
app.get('/api/articles', requireAuth, (req,res) => {
  const u=currentUser(req); const rows=u.role==='admin'?db.prepare('SELECT a.*,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id ORDER BY a.updated_at DESC').all():db.prepare('SELECT a.*,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id WHERE a.author_id=? ORDER BY a.updated_at DESC').all(u.id);
  res.json(rows.map(articleJson));
});
app.post('/api/articles', requireAuth, (req,res) => {
  const u=currentUser(req), title=cleanText(req.body?.title,180), excerpt=cleanText(req.body?.excerpt,500), body=cleanBody(req.body?.body);
  if(title.length<3 || !body.trim()) return fail(res,400,'Judul minimal 3 karakter dan isi artikel wajib diisi.');
  const cat=Number(req.body?.categoryId)||null; if(cat && !db.prepare('SELECT id FROM categories WHERE id=?').get(cat)) return fail(res,400,'Kategori tidak ditemukan.');
  const requested=req.body?.submit==='review'; const status=requested?(u.role==='admin'?'published':'pending'):'draft';
  const cover=mediaPath(req.body?.coverImage); if(req.body?.coverImage && !cover) return fail(res,400,'Foto sampul harus diunggah melalui pemilih gambar.');
  const slug=uniqueSlug(title); const result=db.prepare('INSERT INTO articles(author_id,category_id,title,slug,excerpt,body,cover_image,status,published_at) VALUES(?,?,?,?,?,?,?,?,?)').run(u.id,cat,title,slug,excerpt,body,cover,status,status==='published'?new Date().toISOString():null);
  logActivity(u.id,requested?'kirim artikel':'buat draf',title,result.lastInsertRowid); res.status(201).json(articleJson(articleRow(result.lastInsertRowid)));
});
app.put('/api/articles/:id', requireAuth, (req,res) => {
  const id=Number(req.params.id), row=articleRow(id), u=currentUser(req); if(!row) return fail(res,404,'Artikel tidak ditemukan.');
  const admin=u.role==='admin'; if(!admin && row.author_id!==u.id) return fail(res,403,'Anda hanya dapat mengelola artikel sendiri.');
  if(!admin && !['draft','revision','rejected'].includes(row.status)) return fail(res,403,'Artikel menunggu persetujuan atau sudah terbit; hubungi Admin untuk perubahan.');
  const title=cleanText(req.body?.title,180), excerpt=cleanText(req.body?.excerpt,500), body=cleanBody(req.body?.body);
  if(title.length<3 || !body.trim()) return fail(res,400,'Judul minimal 3 karakter dan isi artikel wajib diisi.');
  const cat=Number(req.body?.categoryId)||null; if(cat && !db.prepare('SELECT id FROM categories WHERE id=?').get(cat)) return fail(res,400,'Kategori tidak ditemukan.');
  const requested=req.body?.submit==='review'; const status=requested?(admin?'published':'pending'):(admin && req.body?.status==='published'?'published':'draft');
  const cover=mediaPath(req.body?.coverImage); if(req.body?.coverImage && !cover) return fail(res,400,'Foto sampul harus diunggah melalui pemilih gambar.');
  db.prepare('UPDATE articles SET title=?,slug=?,category_id=?,excerpt=?,body=?,cover_image=?,status=?,revision_note=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?').run(title,uniqueSlug(title,id),cat,excerpt,body,cover,status,null,status==='published'?(row.published_at||new Date().toISOString()):null,id);
  logActivity(u.id,requested?(admin?'terbitkan artikel':'kirim ulang artikel'):'perbarui artikel',title,id); res.json(articleJson(articleRow(id)));
});
app.delete('/api/articles/:id', requireAuth, (req,res) => {
  const row=articleRow(Number(req.params.id)),u=currentUser(req); if(!row) return fail(res,404,'Artikel tidak ditemukan.');
  if(u.role!=='admin' && row.author_id!==u.id) return fail(res,403,'Anda hanya dapat menghapus artikel sendiri.');
  if(u.role!=='admin' && !['draft','revision','rejected'].includes(row.status)) return fail(res,403,'Artikel yang sedang ditinjau atau sudah terbit tidak dapat dihapus.');
  db.prepare('DELETE FROM articles WHERE id=?').run(row.id); logActivity(u.id,'hapus artikel',row.title); res.json({ok:true});
});
app.post('/api/articles/:id/review', requireAdmin, (req,res) => {
  const id=Number(req.params.id),row=articleRow(id); if(!row) return fail(res,404,'Artikel tidak ditemukan.');
  const action=req.body?.action, note=cleanText(req.body?.note,1000); if(!['publish','revision','reject','unpublish'].includes(action)) return fail(res,400,'Keputusan tidak dikenal.'); if(action==='revision'&&!note) return fail(res,400,'Catatan revisi wajib diisi.');
  if(action==='unpublish'&&row.status!=='published') return fail(res,400,'Artikel ini belum diterbitkan.');
  const status=action==='publish'?'published':action==='revision'||action==='reject'?action:'draft'; db.prepare('UPDATE articles SET status=?,revision_note=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?').run(status,action==='revision'||action==='reject'?note:null,action==='publish'?new Date().toISOString():null,id);
  logActivity(currentUser(req).id,action==='publish'?'setujui dan terbitkan':action==='revision'?'minta revisi':action==='unpublish'?'batalkan publikasi':'tolak artikel',note||row.title,id); res.json(articleJson(articleRow(id)));
});

app.get('/api/users', requireAdmin, (req,res) => res.json(db.prepare("SELECT id,name,email,role,active,created_at FROM users WHERE role='teacher' ORDER BY name").all()));
app.post('/api/users', requireAdmin, async (req,res) => {
  const name=cleanText(req.body?.name,120), email=cleanText(req.body?.email,254).toLowerCase(), password=String(req.body?.password||'');
  if(name.length<2||!/^\S+@\S+\.\S+$/.test(email)||password.length<12) return fail(res,400,'Nama, email valid, dan kata sandi minimal 12 karakter wajib diisi.');
  try { const result=db.prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'teacher')").run(name,email,await hashPassword(password)); logActivity(currentUser(req).id,'buat akun guru',email); res.status(201).json(publicUser(db.prepare('SELECT * FROM users WHERE id=?').get(result.lastInsertRowid))); }
  catch(e) { fail(res,409,'Email sudah digunakan.'); }
});
app.patch('/api/users/:id', requireAdmin, (req,res) => {
  const id=Number(req.params.id), user=db.prepare("SELECT * FROM users WHERE id=? AND role='teacher'").get(id); if(!user) return fail(res,404,'Akun guru tidak ditemukan.');
  const name=cleanText(req.body?.name,120),email=cleanText(req.body?.email??user.email,254).toLowerCase(),active=req.body?.active?1:0; if(name.length<2||!/^\S+@\S+\.\S+$/.test(email)) return fail(res,400,'Nama dan email valid wajib diisi.');
  try { db.prepare('UPDATE users SET name=?,email=?,active=? WHERE id=?').run(name,email,active,id); } catch(e) { return fail(res,409,'Email sudah digunakan.'); }
  logActivity(currentUser(req).id,active?'perbarui akun guru':'nonaktifkan akun guru',email); res.json(publicUser(db.prepare('SELECT * FROM users WHERE id=?').get(id)));
});
app.put('/api/profile', requireAuth, async (req,res) => {
  const u=currentUser(req),name=cleanText(req.body?.name,120),oldPassword=String(req.body?.oldPassword||''),newPassword=String(req.body?.newPassword||'');
  if(name.length<2) return fail(res,400,'Nama minimal 2 karakter.');
  const dbUser=db.prepare('SELECT * FROM users WHERE id=?').get(u.id);
  if(newPassword && (newPassword.length<12 || !(await verifyPassword(oldPassword,dbUser.password_hash)))) return fail(res,400,'Kata sandi lama tidak cocok atau kata sandi baru kurang dari 12 karakter.');
  if(newPassword) db.prepare('UPDATE users SET name=?,password_hash=? WHERE id=?').run(name,await hashPassword(newPassword),u.id); else db.prepare('UPDATE users SET name=? WHERE id=?').run(name,u.id);
  req.session.user.name=name; logActivity(u.id,'perbarui profil',u.email); res.json({user:req.session.user});
});
app.get('/api/public/articles', (req,res) => res.json(db.prepare("SELECT a.title,a.slug,a.excerpt,a.body,a.cover_image coverImage,a.published_at publishedAt,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id WHERE a.status='published' ORDER BY a.published_at DESC LIMIT 50").all()));
app.get('/artikel/:slug', (req,res) => {
  const row=db.prepare("SELECT a.*,u.name author,c.name category FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN categories c ON c.id=a.category_id WHERE a.slug=? AND a.status='published'").get(req.params.slug);
  if(!row) return res.status(404).sendFile(path.join(ROOT,'public','not-found.html'));
  res.type('html').send(`<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${escapeHtml(row.title)} — SINAGA</title><style>body{font:17px/1.75 system-ui,sans-serif;color:#1f2937;max-width:760px;margin:40px auto;padding:0 20px}a{color:#166534}h1{line-height:1.2}.meta{color:#5f6b63;font-size:.9rem}.cover{width:100%;height:auto}.body img{max-width:100%;height:auto}.body blockquote{border-left:4px solid #166534;padding-left:16px;color:#526057}</style><p><a href="/">← Website sekolah</a></p><article><h1>${escapeHtml(row.title)}</h1><p class="meta">${escapeHtml(row.category||'Berita sekolah')} · ${escapeHtml(row.author)}</p>${row.cover_image?`<img class="cover" src="${escapeHtml(row.cover_image)}" alt="">`:''}<div class="body">${cleanBody(row.body)}</div></article></html>`);
});
function escapeHtml(value) { return String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

app.use((err,req,res,next) => { if(err instanceof multer.MulterError) return fail(res,400,err.code==='LIMIT_FILE_SIZE'?'Ukuran file melewati batas.':'Unggahan file tidak valid.'); console.error(err); if(res.headersSent) return next(err); fail(res,500,'Terjadi kesalahan server.'); });

async function bootstrap() {
  const email=process.env.ADMIN_EMAIL, password=process.env.ADMIN_PASSWORD;
  if(email && password && !db.prepare("SELECT id FROM users WHERE role='admin' LIMIT 1").get()) {
    if(password.length<12) throw new Error('ADMIN_PASSWORD minimal 12 karakter.');
    db.prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'admin')").run(cleanText(process.env.ADMIN_NAME||'Administrator',120),email.toLowerCase(),await hashPassword(password));
    console.log(`Akun Admin awal dibuat untuk ${email}.`);
  }
  if(db.prepare('SELECT COUNT(*) n FROM categories').get().n===0) for(const name of ['Berita','Kegiatan','Prestasi']) db.prepare('INSERT OR IGNORE INTO categories(name,slug) VALUES(?,?)').run(name,name.toLowerCase());
  const server=app.listen(PORT,()=>console.log(`SINAGA berjalan di http://localhost:${server.address().port}`));
}
bootstrap().catch(err=>{console.error(err);process.exit(1);});
