const {execFileSync}=require('node:child_process');
const {existsSync,copyFileSync,writeFileSync}=require('node:fs');
const {resolve,join}=require('node:path');
const root=resolve(__dirname,'..');const backend=join(root,'backend');
function run(bin,args,cwd){execFileSync(bin,args,{cwd,stdio:'inherit',shell:process.platform==='win32'});}
if(!existsSync(join(backend,'.env')))copyFileSync(join(backend,'.env.example'),join(backend,'.env'));
if(!existsSync(join(backend,'database/database.sqlite')))writeFileSync(join(backend,'database/database.sqlite'),'');
run(process.env.COMPOSER_BINARY||'composer',['install','--no-interaction'],backend);
const {readFileSync}=require('node:fs');
if(/^APP_KEY=\s*$/m.test(readFileSync(join(backend,'.env'),'utf8')))run(process.env.PHP_BINARY||'php',['artisan','key:generate'],backend);
run(process.env.PHP_BINARY||'php',['artisan','migrate','--seed'],backend);
run(process.env.PHP_BINARY||'php',['artisan','shop:protect-images'],backend);
run(process.platform==='win32'?'npm.cmd':'npm',['ci','--no-audit','--no-fund'],join(root,'frontend'));
console.log('Local setup complete. Checkout is disabled unless explicitly changed in backend/.env. See INICIO.md.');
