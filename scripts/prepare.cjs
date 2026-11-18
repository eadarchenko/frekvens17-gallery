// Build a single-origin local app. This script does not deploy or start a server.
const {execFileSync}=require('node:child_process');
const {readdirSync,rmSync,cpSync,existsSync}=require('node:fs');
const {resolve,join}=require('node:path');
const root=resolve(__dirname,'..');
if(existsSync(join(root,'backend/public/storage')))throw new Error('Remove the legacy public/storage symlink after running php artisan shop:protect-images. Originals must never be linked publicly.');
const legacy=join(root,'backend/storage/app/public/products');
if(existsSync(legacy)&&readdirSync(legacy).some(n=>n!=='.gitignore'))throw new Error('Legacy uploads found. Run php artisan shop:protect-images before building.');
execFileSync(process.platform==='win32'?'npm.cmd':'npm',['run','build'],{cwd:join(root,'frontend'),stdio:'inherit',shell:process.platform==='win32'});
const target=join(root,'backend/public');
for(const entry of readdirSync(target))if(!['index.php','.htaccess','robots.txt','storage'].includes(entry))rmSync(join(target,entry),{recursive:true,force:true});
cpSync(join(root,'frontend/dist/art-gallery'),target,{recursive:true});
console.log('Local build copied to backend/public. Nothing was deployed.');
