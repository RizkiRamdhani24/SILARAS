<?php

use App\Http\Controllers\Api\EbookController;
use App\Http\Controllers\Api\IndonesiaBookController;
use App\Http\Controllers\Api\MangaController;
use Illuminate\Support\Facades\Route;

Route::get('/ebooks', [EbookController::class, 'index']);
Route::get('/ebooks/search', [EbookController::class, 'search']);
Route::get('/indonesia', [IndonesiaBookController::class, 'index']);
Route::get('/indonesia/search', [IndonesiaBookController::class, 'search']);
Route::get('/manga', [MangaController::class, 'index']);
Route::get('/manga/search', [MangaController::class, 'search']);
Route::get('/manga/{slug}/chapter/{chapter}', [MangaController::class, 'chapter']);
Route::get('/manga/{slug}', [MangaController::class, 'detail']);
Route::get('/search', function (\Illuminate\Http\Request $request) {
    $query = trim((string) $request->query('q', ''));

    if ($query === '') {
        return response()->json(['data' => [], 'errors' => ['Query pencarian kosong.']], 422);
    }

    $services = [
        'ebooks' => app(\App\Services\GutendexService::class),
        'indonesia' => app(\App\Services\IndonesiaBookService::class),
        'manga' => app(\App\Services\MangaService::class),
    ];
    $data = [];
    $errors = [];

    foreach ($services as $source => $service) {
        try {
            $data = array_merge($data, $service->search($query));
        } catch (\Throwable $exception) {
            $errors[$source] = $exception->getMessage();
        }
    }

    return response()->json(['data' => $data, 'errors' => $errors]);
});
