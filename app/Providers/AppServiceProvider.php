<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      RateLimiter::for('login', fn (Request $request) => [
          Limit::perMinute(5)->by('login:' . $request->ip() . '|' . strtolower((string) $request->input('email'))),
          Limit::perMinute(60)->by('login-ip:' . $request->ip()),
      ]);
    
      // sudah login (dipasang setelah auth:sanctum)
      RateLimiter::for('api-limit', function (Request $request) {
          $user = $request->user();
    
          if (! $user) {
              return Limit::perMinute(20)->by('guest:' . $request->ip());
          }
    
          return match ($user->role) {
              'admin'            => Limit::perMinute(120)->by('u:' . $user->id),
              'guru', 'pengurus' => [
                  Limit::perMinute(60)->by('u:' . $user->id),
                  Limit::perMinute(1000)->by('global'),
              ],
              default            => [
                  Limit::perMinute(30)->by('u:' . $user->id),
                  Limit::perMinute(1000)->by('global'),
              ],
          };
      });
    
      // gerbang kode 5 digit: ketat supaya tidak bisa ditebak
      RateLimiter::for('resolve', fn (Request $request) =>
          Limit::perMinute(10)->by('resolve:' . ($request->user()?->id ?: $request->ip()))
      );
    }
}
