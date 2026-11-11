<?php

namespace App\Providers;

use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('anggota.partials.header', function ($view): void {
            $anggota = Auth::guard('anggota')->user();

            if (! $anggota || ! Schema::hasTable('notifikasi')) {
                $view->with([
                    'notifikasiAnggotaTerbaru' => collect(),
                    'jumlahNotifikasiBelumDibaca' => 0,
                ]);

                return;
            }

            $query = Notifikasi::query()
                ->where('id_anggota', $anggota->getAuthIdentifier())
                ->latest('created_at');

            $view->with([
                'notifikasiAnggotaTerbaru' => (clone $query)->limit(5)->get(),
                'jumlahNotifikasiBelumDibaca' => (clone $query)->where('is_dibaca', false)->count(),
            ]);
        });
    }
}
