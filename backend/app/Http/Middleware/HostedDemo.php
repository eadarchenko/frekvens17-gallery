<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HostedDemo
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('shop.hosted_demo')) {
            return $next($request);
        }
        // Every browser gets a disposable database. Never use the normal shop DB.
        $workspace = $request->session()->get('demo_workspace');
        if (!is_string($workspace) || !Str::isUuid($workspace)) {
            $workspace = (string) Str::uuid();
            $request->session()->put('demo_workspace', $workspace);
        }
        $dir = storage_path('app/hosted-demo');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $path = $dir.'/'.$workspace.'.sqlite';
        $lock = fopen($dir.'/workspace.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            if (!is_file($path)) {
                foreach (glob($dir.'/*.sqlite') as $old) {
                    if (filemtime($old) < time() - 86400) {
                        @unlink($old);
                    }
                }
                abort_if(count(glob($dir.'/*.sqlite')) >= 200, 503, 'Demo capacity reached. Please try later.');
                copy(database_path('hosted-template.sqlite'), $path);
                chmod($path, 0600);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $path]);
        DB::purge('sqlite');
        if ($request->is('api/register', 'api/login', 'api/forgot-password', 'api/reset-password', 'api/profile', 'api/admin/images', 'api/email/*', 'verify-email/*')) {
            return response()->json(['message' => 'Public demo: use the sample roles. Personal accounts, email and uploads are disabled.'], 403);
        }
        if ($request->is('api/checkout')) {
            // Do not retain submitted personal names, email or delivery details.
            $request->merge(['name' => 'DEMO Collector', 'email' => $request->user()?->email ?? 'guest@demo.test', 'address' => ['line1' => 'DEMO - do not deliver', 'line2' => '', 'city' => 'Demo City', 'postcode' => '00000', 'country' => 'ES']]);
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }
        return $response;
    }
}
