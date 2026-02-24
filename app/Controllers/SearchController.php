<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SearchModel;

class SearchController extends Controller
{
    public const MIN_LENGTH = 2;

    // ─── GET /search ──────────────────────────────────────────────────────────

    public function index(): void
    {
        $q       = trim((string) $this->request->get('q', ''));
        $tooShort = $q !== '' && mb_strlen($q) < self::MIN_LENGTH;

        $sections = [];
        $total    = 0;

        if ($q !== '' && !$tooShort) {
            $sections = SearchModel::search($q);
            foreach ($sections as $section) {
                $total += count($section['results']);
            }
        }

        $this->view('search/index', [
            'title'    => $q !== '' ? 'Search: ' . $q : 'Search',
            'q'        => $q,
            'tooShort' => $tooShort,
            'sections' => $sections,
            'total'    => $total,
        ]);
    }
}
