<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>
    <url>
        <loc>{{ route('about') }}</loc>
        <priority>0.8</priority>
        <changefreq>monthly</changefreq>
    </url>
    <url>
        <loc>{{ route('challenges.index') }}</loc>
        <priority>0.9</priority>
        <changefreq>daily</changefreq>
    </url>
@foreach($challenges as $challenge)
    <url>
        <loc>{{ route('challenges.show', $challenge) }}</loc>
        @if($challenge->updated_at)
        <lastmod>{{ $challenge->updated_at->toW3cString() }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
    </url>
@endforeach
</urlset>
