<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MangaService;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    public function __construct(private MangaService $service) {}

    public function index()
    {
        try {
            return response()->json(['success' => true, 'message' => 'Katalog manga Komiku dimuat.', 'data' => $this->service->list()]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber manga Komiku sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }

    public function search(Request $request)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Pencarian manga selesai.', 'data' => $this->service->search((string) $request->query('q', ''))]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => 'Sumber manga Komiku sedang tidak tersedia: ' . $exception->getMessage(), 'data' => []], 502);
        }
    }

    public function detail(string $slug)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Detail manga dimuat.', 'data' => $this->service->detail($slug)]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'data' => []], 502);
        }
    }

    public function chapter(string $slug, string $chapter)
    {
        try {
            return response()->json(['success' => true, 'message' => 'Chapter dimuat.', 'data' => $this->service->chapter($slug, $chapter)]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'data' => []], 502);
        }
    }
}
