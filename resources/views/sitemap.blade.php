<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ url('/') }}</loc>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>{{ url('/trucks') }}</loc>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>{{ url('/about') }}</loc>
    <priority>0.3</priority>
  </url>
  <url>
    <loc>{{ url('/trucks/create') }}</loc>
    <priority>0.5</priority>
  </url>
@foreach ($trucks as $truck)
  <url>
    <loc>{{ url("/trucks/{$truck->id}") }}</loc>
    <lastmod>{{ $truck->updated_at->toAtomString() }}</lastmod>
    <priority>0.8</priority>
  </url>
@endforeach
</urlset>
