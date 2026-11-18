const {spawn,execFileSync}=require('node:child_process');const {resolve,join}=require('node:path');
const root=resolve(__dirname,'..');const backend=join(root,'backend');
execFileSync(process.execPath,[join(__dirname,'prepare.cjs')],{stdio:'inherit'});
console.log('Local preview: http://127.0.0.1:8000. Do not expose this development server to the internet.');
const server=spawn(process.env.PHP_BINARY||'php',['-d','upload_max_filesize=5M','-d','post_max_size=8M','-S','127.0.0.1:8000',join(backend,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:join(backend,'public'),stdio:'inherit'});
for(const signal of ['SIGINT','SIGTERM'])process.on(signal,()=>server.kill(signal));
server.on('close',code=>{process.exitCode=code||0;});
