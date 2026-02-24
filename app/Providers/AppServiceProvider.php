<?php

namespace App\Providers;

use App\Models\ChoreCompletion;
use App\Models\ChoreInstance;
use App\Policies\ChoreCompletionPolicy;
use App\Policies\ChoreInstancePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(ChoreInstance::class, ChoreInstancePolicy::class);
        Gate::policy(ChoreCompletion::class, ChoreCompletionPolicy::class);
    }
}
