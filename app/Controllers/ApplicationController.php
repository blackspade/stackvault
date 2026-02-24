<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ApplicationModel;
use App\Models\ClientModel;
use App\Models\DatalistPresetModel;
use App\Models\DomainModel;
use App\Models\ServerModel;
use App\Services\AppCatalogService;
use App\Services\AuthService;

class ApplicationController extends Controller
{
    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        ApplicationModel::migrate();
    }

    // ─── GET /applications ────────────────────────────────────────────────────

    public function index(): void
    {
        $search = trim((string) $this->request->get('search', ''));
        $apps   = ApplicationModel::getAll($search);

        $this->view('applications/index', [
            'title'  => 'Applications',
            'apps'   => $apps,
            'search' => $search,
        ]);
    }

    // ─── GET /applications/create ─────────────────────────────────────────────

    public function showCreate(): void
    {
        $catalogId  = trim((string) $this->request->get('catalog_id', ''));
        $catalogApp = $catalogId ? AppCatalogService::getById($catalogId) : null;
        $old        = $this->popOldInput();
        $errors     = get_flash('error');

        DatalistPresetModel::init();

        $this->view('applications/create', [
            'title'       => 'Add Application',
            'breadcrumbs' => [
                ['label' => 'Applications', 'url' => '/applications'],
                ['label' => 'Add Application'],
            ],
            'old'               => $old,
            'errors'            => $errors,
            'catalogApp'        => $catalogApp,
            'clients'           => ClientModel::getForSelect(),
            'servers'           => ServerModel::getForSelect(),
            'domains'           => DomainModel::getForSelect(),
            'deploymentPresets' => DatalistPresetModel::getValues('app_deployment'),
        ]);
    }

    // ─── POST /applications/store ─────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectFormData();
        $errors = $this->validateAppData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $redir = '/applications/create';
            if ($data['catalog_id']) {
                $redir .= '?catalog_id=' . urlencode($data['catalog_id']);
            }
            $this->redirect($redir);
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = ApplicationModel::create($data);

        $this->logActivity('application_created', $id,
            "Application created: {$data['app_name']}");

        $this->flashSuccess("Application \"{$data['app_name']}\" added.");
        $this->redirect("/applications/{$id}");
    }

    // ─── GET /applications/{id} ───────────────────────────────────────────────

    public function show(): void
    {
        $app = $this->resolveApp();

        $catalogApp = null;
        if (!empty($app['catalog_id'])) {
            $catalogApp = AppCatalogService::getById($app['catalog_id']);
        }

        $this->view('applications/show', [
            'title'       => e($app['app_name']),
            'breadcrumbs' => [
                ['label' => 'Applications', 'url' => '/applications'],
                ['label' => $app['app_name']],
            ],
            'app'         => $app,
            'catalogApp'  => $catalogApp,
            'credentials' => ApplicationModel::getCredentials((int) $app['id']),
            'activity'    => ApplicationModel::getActivity((int) $app['id']),
        ]);
    }

    // ─── GET /applications/{id}/edit ──────────────────────────────────────────

    public function showEdit(): void
    {
        $app    = $this->resolveApp();
        $old    = $this->popOldInput();
        $errors = get_flash('error');
        $merged = array_merge($app, $old);

        $catalogApp = null;
        if (!empty($merged['catalog_id'])) {
            $catalogApp = AppCatalogService::getById($merged['catalog_id']);
        }

        // Allow catalog_id override from query param (coming back from catalog browse)
        $qCatalogId = trim((string) $this->request->get('catalog_id', ''));
        if ($qCatalogId && $qCatalogId !== ($merged['catalog_id'] ?? '')) {
            $overrideCatalogApp = AppCatalogService::getById($qCatalogId);
            if ($overrideCatalogApp) {
                $catalogApp           = $overrideCatalogApp;
                $merged['catalog_id'] = $qCatalogId;
                $merged['app_name']   = $overrideCatalogApp['manifest']['title'];
                $merged['version']    = $overrideCatalogApp['manifest']['upstreamVersion']
                                     ?? $overrideCatalogApp['manifest']['version']
                                     ?? '';
                $merged['stack_type'] = AppCatalogService::getAddonString(
                    $overrideCatalogApp['manifest']['addons'] ?? []
                );
            }
        }

        DatalistPresetModel::init();

        $this->view('applications/edit', [
            'title'       => 'Edit Application',
            'breadcrumbs' => [
                ['label' => 'Applications', 'url' => '/applications'],
                ['label' => $app['app_name'], 'url' => "/applications/{$app['id']}"],
                ['label' => 'Edit'],
            ],
            'app'               => $merged,
            'errors'            => $errors,
            'catalogApp'        => $catalogApp,
            'clients'           => ClientModel::getForSelect(),
            'servers'           => ServerModel::getForSelect(),
            'domains'           => DomainModel::getForSelect(),
            'deploymentPresets' => DatalistPresetModel::getValues('app_deployment'),
        ]);
    }

    // ─── POST /applications/{id}/update ──────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $app    = $this->resolveApp();
        $id     = (int) $app['id'];
        $data   = $this->collectFormData();
        $errors = $this->validateAppData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/applications/{$id}/edit");
        }

        ApplicationModel::update($id, $data);

        $this->logActivity('application_updated', $id,
            "Application updated: {$data['app_name']}");

        $this->flashSuccess("Application \"{$data['app_name']}\" updated.");
        $this->redirect("/applications/{$id}");
    }

    // ─── POST /applications/{id}/delete ──────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $app  = $this->resolveApp();
        $id   = (int) $app['id'];
        $name = $app['app_name'];

        ApplicationModel::delete($id);

        $this->logActivity('application_deleted', $id,
            "Application deleted: {$name}");

        $this->flashSuccess("Application \"{$name}\" deleted.");
        $this->redirect('/applications');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveApp(): array
    {
        $id  = (int) $this->request->param('id', 0);
        $app = $id > 0 ? ApplicationModel::getById($id) : null;

        if (!$app) {
            $this->notFound();
        }

        return $app;
    }

    private function collectFormData(): array
    {
        return [
            'app_name'          => trim((string) $this->request->post('app_name',          '')),
            'catalog_id'        => trim((string) $this->request->post('catalog_id',        '')),
            'version'           => trim((string) $this->request->post('version',           '')),
            'stack_type'        => trim((string) $this->request->post('stack_type',        '')),
            'client_id'         => (int)          $this->request->post('client_id',        0),
            'server_id'         => (int)          $this->request->post('server_id',        0),
            'domain_id'         => (int)          $this->request->post('domain_id',        0),
            'install_path'      => trim((string) $this->request->post('install_path',      '')),
            'git_repo'          => trim((string) $this->request->post('git_repo',          '')),
            'deployment_method' => trim((string) $this->request->post('deployment_method', '')),
            'notes'             => trim((string) $this->request->post('notes',             '')),
        ];
    }

    private function validateAppData(array $data): array
    {
        $errors = [];

        if ($data['app_name'] === '') {
            $errors[] = 'Application name is required.';
        } elseif (mb_strlen($data['app_name']) > 255) {
            $errors[] = 'Application name must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function popOldInput(): array
    {
        $raw = get_flash('old')[0] ?? '';
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    private function logActivity(string $action, int $entityId, string $description): void
    {
        AuthService::log(
            (int) $_SESSION['user_id'],
            $action,
            'application',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}
