<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class IndonesiaBookService
{
    private array $keywords = [
        'sejarah' => ['sejarah', 'kemerdekaan', 'kolonial', 'kerajaan', 'perjuangan', 'historical'],
        'pendidikan' => ['pendidikan', 'sekolah', 'pembelajaran', 'kurikulum', 'guru', 'siswa', 'pedagogi'],
        'budaya' => ['budaya', 'kebudayaan', 'adat', 'tradisi', 'kesenian', 'folklor'],
        'ekonomi' => ['ekonomi', 'bisnis', 'perdagangan', 'keuangan', 'akuntansi'],
        'hukum' => ['hukum', 'undang-undang', 'peraturan', 'pidana', 'perdata'],
        'sastra' => ['sastra', 'novel', 'puisi', 'cerpen', 'literatur'],
        'sosial' => ['sosial', 'masyarakat', 'sosiologi', 'antropologi'],
        'geografi' => ['geografi', 'wilayah', 'lingkungan', 'geologi'],
    ];

    public function list(string $category = 'all', string $query = ''): array
    {
        $params = ['q' => $query !== '' ? $query . ' Indonesia' : 'Indonesia', 'limit' => 24, 'fields' => 'key,title,author_name,first_publish_year,publisher,subject,description,cover_i,ebook_access'];
        $response = Http::acceptJson()->timeout(15)->retry(2, 300)->get(config('services.open_library.url'), $params);
        if ($response->failed()) throw new RuntimeException('Open Library gagal merespons (HTTP ' . $response->status() . ').');
        $items = collect($response->json('docs', []))->map(fn(array $book) => $this->normalize($book))->filter();
        return $items->filter(fn(array $book) => $category === 'all' || $book['category'] === $category)->values()->all();
    }

    public function search(string $query): array
    {
        return $this->list('all', trim($query));
    }

    private function normalize(array $book): array
    {
        $title = trim((string) ($book['title'] ?? ''));
        if ($title === '') return [];
        $subjects = $book['subject'] ?? [];
        $description = is_array($book['description'] ?? null) ? ($book['description']['value'] ?? '') : (string) ($book['description'] ?? '');
        $haystack = mb_strtolower(implode(' ', [$title, implode(' ', $subjects), $description]));
        $category = 'lainnya';
        foreach ($this->keywords as $name => $terms) if (collect($terms)->contains(fn($term) => str_contains($haystack, $term))) {
            $category = $name;
            break;
        }
        $key = (string) ($book['key'] ?? '');
        return [
            'id' => 'openlibrary-' . sha1($key . $title),
            'source' => 'indonesia',
            'source_label' => 'Open Library · katalog topik Indonesia',
            'title' => $title,
            'author' => implode(', ', $book['author_name'] ?? []),
            'cover' => isset($book['cover_i']) ? 'https://covers.openlibrary.org/b/id/' . $book['cover_i'] . '-M.jpg' : '',
            'description' => $description,
            'category' => $category,
            'subject' => implode(' • ', array_slice($subjects, 0, 8)),
            'year' => (string) ($book['first_publish_year'] ?? ''),
            'publisher' => implode(', ', array_slice($book['publisher'] ?? [], 0, 2)),
            'read_url' => '',
            'detail_url' => $key ? 'https://openlibrary.org' . $key : ''
        ];
    }
}
