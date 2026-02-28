<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate the sitemap XML.
     */
    public function __invoke(): Response
    {
        $challenges = Challenge::query()
            ->publicVisibility()
            ->active()
            ->select(['id', 'updated_at'])
            ->get();

        return response()
            ->view('sitemap.index', [
                'challenges' => $challenges,
            ])
            ->header('Content-Type', 'application/xml');
    }
}
