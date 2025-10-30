<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\PickBasket;
use App\Models\PickBasketTransfer;
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
        View::composer('admin.*', function ($view) {
            $user = Auth::user();

            $counts = [
                'pendingTransfers' => 0,
                'myOpenBaskets'    => 0,
            ];

            if ($user) {
                $uid = (int) $user->id;

                // Transferencias pendientes hacia mí
                $counts['pendingTransfers'] = PickBasketTransfer::query()
                    ->where('to_user_id', $uid)
                    ->where('status', 'pending')
                    ->count();

                // Mis canastas abiertas / en progreso
                $counts['myOpenBaskets'] = PickBasket::query()
                    ->where('responsible_user_id', $uid)
                    ->whereIn('status', ['open', 'in_progress'])
                    ->count();
            }

            $view->with('basketMenuCounts', $counts);
        });
    }
}
