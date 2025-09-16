<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\URL;

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
        Vite::prefetch(concurrency: 3);

        Inertia::share([
            'auth' => [
                'user' => fn () => Auth::user(),
            ],
        ]);

        // Paksa semua URL jadi https jika bukan di local
        if (str_contains(config('app.url'), 'ngrok-free.app')) {
            URL::forceScheme('https');
        }
    
    }
}
