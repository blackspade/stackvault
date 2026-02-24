<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AppCatalogService;

class AppCatalogController extends Controller
{
    // ─── GET /app-catalog ─────────────────────────────────────────────────────

    public function index(): void
    {
        $search = trim((string) $this->request->get('q', ''));
        $tag    = trim((string) $this->request->get('tag', ''));

        $apps    = AppCatalogService::search($search, $tag);
        $allTags = AppCatalogService::getAllTags();
        $total   = count(AppCatalogService::getAll());

        $this->view('app_catalog/index', [
            'title'       => 'App Catalog',
            'breadcrumbs' => [
                ['label' => 'Applications', 'url' => '/applications'],
                ['label' => 'App Catalog'],
            ],
            'apps'      => $apps,
            'allTags'   => $allTags,
            'total'     => $total,
            'search'    => $search,
            'filterTag' => $tag,
        ]);
    }

    // ─── GET /app-catalog/{id} ────────────────────────────────────────────────

    public function show(): void
    {
        $id  = $this->request->param('id', '');
        $app = $id ? AppCatalogService::getById($id) : null;

        if (!$app) {
            $this->notFound();
        }

        $manifest = $app['manifest'];

        $this->view('app_catalog/show', [
            'title'       => e($manifest['title']),
            'breadcrumbs' => [
                ['label' => 'Applications', 'url' => '/applications'],
                ['label' => 'App Catalog',  'url' => '/app-catalog'],
                ['label' => $manifest['title']],
            ],
            'app'          => $app,
            'addonBadges'  => AppCatalogService::getAddonBadges($manifest['addons']      ?? []),
            'descHtml'     => AppCatalogService::markdownToHtml($manifest['description']  ?? ''),
            'changeHtml'   => AppCatalogService::markdownToHtml($manifest['changelog']    ?? ''),
        ]);
    }

    // ─── GET /app-icon/{name} ─────────────────────────────────────────────────

    public function icon(): void
    {
        $raw  = $this->request->param('name', '');
        // Allow only safe filename characters — whitelist approach
        $name = basename(preg_replace('/[^a-zA-Z0-9._\- ]/', '', rawurldecode($raw)));

        $path = AppCatalogService::iconPath($name);

        if (!$name || !file_exists($path)) {
            // 1×1 transparent PNG placeholder
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=3600');
            echo base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQ' .
                'AABjkB6QAAAABJRU5ErkJggg=='
            );
            exit;
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
