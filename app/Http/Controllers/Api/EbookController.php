<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GutendexService;
use Illuminate\Http\Request;

class EbookController extends Controller
{
    public function __construct(private GutendexService $service) {}

    public function index()
    {
        try {
            return response()->json(['success' => true, 'message' => 'Katalog ebook Gutendex dimuat.', 'data' => $this->service->list()]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber ebook Gutendex sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }

    public function search(Request $request)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Pencarian ebook selesai.', 'data' => $this->service->search((string) $request->query('q', ''))]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber ebook Gutendex sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }
}
