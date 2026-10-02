<?php

use App\Models\Anggota;
use App\Models\Petugas;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'anggota'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'anggota'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'anggota' => [
            'driver' => 'session',
            'provider' => 'anggota',
        ],
        'petugas' => [
            'driver' => 'session',
            'provider' => 'petugas',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'anggota' => [
            'driver' => 'eloquent',
            'model' => Anggota::class,
        ],
        'petugas' => [
            'driver' => 'eloquent',
            'model' => Petugas::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    | Tidak dipakai saat ini, tapi dibiarkan agar konfigurasi tidak error.
    */

    'passwords' => [
        'anggota' => [
            'provider' => 'anggota',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
