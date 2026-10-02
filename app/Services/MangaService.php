<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MangaService
{
    public function list(): array
    {
        return $this->parseListing('/');
    }

    public function search(string $query): array
    {
        return trim($query) === '' ? $this->list() : $this->parseListing('/?s=' . rawurlencode($query) . '&post_type=manga');
    }

    public function detail(string $slug): array
    {
        $html = $this->get('/manga/' . rawurlencode($slug) . '/');
        $dom = $this->document($html);
        $xpath = new \DOMXPath($dom);
        $title = $this->firstText($xpath, '//h1');
        $cover = $this->firstAttribute($xpath, "//section[contains(@id,'Informasi')]//img | //meta[@itemprop='image']", ['src', 'content']);
        $description = $this->firstText($xpath, "//p[contains(@class,'desc')] | //section[contains(@id,'Sinopsis')]//p");
        $chapters = [];
        foreach ($xpath->query("//a[contains(@href, 'chapter')]") as $anchor) {
            $href = $anchor->getAttribute('href');
            $chapters[] = ['title' => trim($anchor->textContent), 'read_url' => $this->absolute($href), 'chapter' => $this->chapterNumber($href)];
        }
        return ['id' => 'komiku-' . $slug, 'source' => 'manga', 'source_label' => 'Komiku', 'title' => $title, 'author' => '', 'cover' => $this->absolute($cover), 'description' => $description, 'category' => 'lainnya', 'genre' => '', 'year' => '', 'publisher' => 'Komiku', 'read_url' => $chapters[0]['read_url'] ?? '', 'detail_url' => $this->absolute('/manga/' . $slug . '/'), 'chapters' => $chapters];
    }

    public function chapter(string $slug, string $chapter): array
    {
        $url = '/' . $slug . '-chapter-' . $chapter . '/';
        $dom = $this->document($this->get($url));
        $xpath = new \DOMXPath($dom);
        $images = [];
        foreach ($xpath->query('//img') as $image) {
            $src = $image->getAttribute('src') ?: $image->getAttribute('data-src');
            if ($src !== '') $images[] = ['src' => $this->absolute($src), 'alt' => trim($image->getAttribute('alt'))];
        }
        return ['title' => $this->firstText($xpath, '//h1'), 'manga_slug' => $slug, 'chapter' => $chapter, 'images' => $images, 'source_url' => $this->absolute($url)];
    }

    private function parseListing(string $path): array
    {
        $dom = $this->document($this->get($path));
        $xpath = new \DOMXPath($dom);
        $items = [];
        foreach ($xpath->query("//a[contains(@href, '/manga/')]") as $anchor) {
            $href = $anchor->getAttribute('href');
            $slug = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');
            $slug = preg_replace('#^manga/#', '', $slug);
            if ($slug === '' || isset($items[$slug])) continue;
            $image = $xpath->query('.//img', $anchor)->item(0);
            $title = trim($anchor->textContent);
            $cover = $image ? ($image->getAttribute('src') ?: $image->getAttribute('data-src')) : '';
            if ($title === '' && $image) $title = trim($image->getAttribute('alt'));
            if ($title === '') continue;
            $items[$slug] = ['id' => 'komiku-' . $slug, 'source' => 'manga', 'source_label' => 'Komiku', 'title' => $title, 'author' => '', 'cover' => $this->absolute($cover), 'description' => '', 'category' => 'lainnya', 'genre' => '', 'year' => '', 'publisher' => 'Komiku', 'read_url' => '', 'detail_url' => $this->absolute('/manga/' . $slug . '/')];
            if (count($items) >= 24) break;
        }
        if (!$items) throw new RuntimeException('Komiku mengembalikan katalog kosong atau struktur halaman berubah.');
        return array_values($items);
    }

    private function get(string $path): string
    {
        $response = Http::withHeaders(['User-Agent' => 'E-Library-Demo/1.0'])->timeout(15)->retry(2, 300)->get($this->absolute($path));
        if ($response->failed()) throw new RuntimeException('Komiku gagal merespons (HTTP ' . $response->status() . ').');
        return $response->body();
    }
    private function document(string $html): \DOMDocument
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        return $dom;
    }
    private function firstText(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);
        return $node ? trim(preg_replace('/\s+/', ' ', $node->textContent)) : '';
    }
    private function firstAttribute(\DOMXPath $xpath, string $query, array $attributes): string
    {
        $node = $xpath->query($query)->item(0);
        if (!$node) return '';
        foreach ($attributes as $attribute) if ($node->hasAttribute($attribute)) return $node->getAttribute($attribute);
        return '';
    }
    private function absolute(string $url): string
    {
        $baseUrl = rtrim(config('services.komiku.url'), '/');
        return $url === '' ? '' : (str_starts_with($url, 'http') ? $url : $baseUrl . '/' . ltrim($url, '/'));
    }
    private function chapterNumber(string $url): string
    {
        return preg_match('#chapter[-/]([\d.-]+)#i', $url, $match) ? $match[1] : '';
    }
}
