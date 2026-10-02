<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IndonesiaBookService;
use Illuminate\Http\Request;

class IndonesiaBookController extends Controller
{
    public function __construct(private IndonesiaBookService $service) {}

    public function index(Request $request)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Katalog buku Indonesia dimuat.', 'data' => $this->service->list((string) $request->query('category', 'all'))]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber buku Indonesia sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }

    public function search(Request $request)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Pencarian buku Indonesia selesai.', 'data' => $this->service->search((string) $request->query('q', ''))]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber buku Indonesia sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }
}
