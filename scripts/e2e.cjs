// Disposable browser test database. Never resets the developer's database.
const {execFileSync,spawn}=require('node:child_process');
const {mkdtempSync,writeFileSync,rmSync,openSync,copyFileSync}=require('node:fs');
const {resolve,join}=require('node:path');const {tmpdir}=require('node:os');
const root=resolve(__dirname,'..');const php=process.env.PHP_BINARY||'php';const dir=mkdtempSync(join(tmpdir(),'f17-e2e-'));const db=join(dir,'test.sqlite');writeFileSync(db,'');
const env={...process.env,APP_ENV:'testing',APP_DEBUG:'false',DB_CONNECTION:'sqlite',DB_DATABASE:db,SHOP_CHECKOUT_MODE:'test',SHOP_CURRENCY:'EUR',SHOP_TEST_SHIPPING_CENTS:'700',SHOP_TEST_TAX_BASIS_POINTS:'2100',CACHE_STORE:'array',SESSION_DRIVER:'file',E2E_BASE_URL:'http://127.0.0.1:8765',E2E_OUTPUT:join(root,'test-results')};
const backend=join(root,'backend');let server;
(async()=>{try {
 execFileSync(php,['artisan','migrate','--seed','--force'],{cwd:backend,env,stdio:'inherit'});
 execFileSync(php,['tests/Support/browser-fixtures.php'],{cwd:backend,env,stdio:'inherit'});
 server=spawn(php,['-d','upload_max_filesize=5M','-d','post_max_size=8M','-S','127.0.0.1:8765',join(backend,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:join(backend,'public'),env,stdio:['ignore',openSync(join(dir,'server.log'),'a'),openSync(join(dir,'server.log'),'a')]});
 let ready=false;for(let i=0;i<80;i++){try{const r=await fetch(env.E2E_BASE_URL+'/up');if(r.ok){ready=true;break;}}catch{}await new Promise(r=>setTimeout(r,100));}
 if(!ready)throw new Error('Test server did not become ready. Check that port 8765 is free.');
 execFileSync(process.execPath,['tests/browser-check.cjs'],{cwd:join(root,'frontend'),env,stdio:'inherit'});
}finally {copyFileSync(join(dir,'server.log'),join(root,'test-results/server.log'));copyFileSync(db,join(root,'test-results/test.sqlite'));if(server)server.kill('SIGTERM');setTimeout(()=>rmSync(dir,{recursive:true,force:true}),500);}})().catch(e=>{console.error(e.message);process.exitCode=1;});
