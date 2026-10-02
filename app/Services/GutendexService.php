<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GutendexService
{
    private string $url = 'https://gutendex.com/books';

    public function list(string $query = ''): array
    {
        $params = ['page' => 1];
        if ($query !== '') $params['search'] = $query;
        $response = Http::acceptJson()->timeout(15)->retry(2, 300)->get($this->url, $params);
        if ($response->failed()) throw new RuntimeException('Gutendex gagal merespons (HTTP ' . $response->status() . ').');

        return collect($response->json('results', []))->map(fn(array $book) => $this->normalize($book))->values()->all();
    }

    public function search(string $query): array
    {
        return $this->list(trim($query));
    }

    private function normalize(array $book): array
    {
        $formats = $book['formats'] ?? [];
        $readUrl = $formats['text/html'] ?? $formats['text/html; charset=utf-8'] ?? $formats['text/plain'] ?? $formats['application/epub+zip'] ?? '';
        $year = collect($book['copyright'] ? [$book['copyright']] : [])->map(fn($value) => preg_match('/\b(18|19|20)\d{2}\b/', (string) $value, $m) ? $m[0] : '')->first() ?? '';

        return [
            'id' => 'gutendex-' . $book['id'],
            'source' => 'ebook',
            'source_label' => 'Gutendex / Project Gutenberg',
            'title' => $book['title'] ?? 'Tanpa judul',
            'author' => collect($book['authors'] ?? [])->pluck('name')->implode(', ') ?: 'Penulis tidak tercantum',
            'publisher' => 'Project Gutenberg',
            'year' => $year,
            'category' => 'lainnya',
            'subject' => collect($book['subjects'] ?? [])->implode(' • '),
            'description' => collect($book['summaries'] ?? [])->implode(' '),
            'cover' => $formats['image/jpeg'] ?? '',
            'read_url' => $readUrl,
        ];
    }
}
