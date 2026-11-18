// Isolated local demo. Passwords are chosen through hidden terminal prompts, never stored in this script.
const {execFileSync,spawn}=require('node:child_process');
const {resolve,join}=require('node:path');const {existsSync,writeFileSync,mkdirSync}=require('node:fs');
const root=resolve(__dirname,'..'),backend=join(root,'backend'),php=process.env.PHP_BINARY||'php';
const dir=join(backend,'storage/app/private/demo');mkdirSync(dir,{recursive:true});
const db=join(dir,'demo.sqlite');if(!existsSync(db))writeFileSync(db,'');
const env={...process.env,APP_ENV:'local',APP_DEBUG:'false',APP_URL:'http://127.0.0.1:8001',DB_CONNECTION:'sqlite',DB_DATABASE:db,SHOP_DEMO:'true',SHOP_CHECKOUT_MODE:'test',SHOP_CURRENCY:'EUR',SHOP_TEST_SHIPPING_CENTS:'0',SHOP_TEST_TAX_BASIS_POINTS:'0',SESSION_COOKIE:'frekvens17_demo',SESSION_DRIVER:'database',CACHE_STORE:'array'};
function run(args){execFileSync(php,args,{cwd:backend,env,stdio:'inherit'});}
try{
 run(['artisan','migrate','--seed','--force']);
 run(['artisan','shop:demo']);
 execFileSync(process.execPath,[join(__dirname,'prepare.cjs')],{env,stdio:'inherit'});
 console.log('DEMO ONLY: http://127.0.0.1:8001/account. No payments, external email or real deliveries. Ctrl+C stops it.');
 const server=spawn(php,['-d','upload_max_filesize=5M','-d','post_max_size=8M','-S','127.0.0.1:8001',join(backend,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:join(backend,'public'),env,stdio:'inherit'});
 for(const signal of ['SIGINT','SIGTERM'])process.on(signal,()=>server.kill(signal));
 server.on('close',code=>{process.exitCode=code||0;});
}catch(e){console.error('Demo stopped. No live shop data was changed.');process.exitCode=1;}
