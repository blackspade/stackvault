<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BookmarkSetModel;
use App\Models\BookmarkFolderModel;
use App\Models\BookmarkModel;
use App\Models\ClientModel;
use App\Services\AuthService;
use App\Services\BookmarkImportService;
use App\Services\BookmarkExportService;

class BookmarkController extends Controller
{
    // ─── GET /bookmarks ───────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search',    ''));
        $clientId = (int)          $this->request->get('client_id', 0);

        $sets    = BookmarkSetModel::getAll($search, $clientId);
        $clients = ClientModel::getForSelect();

        $this->view('bookmarks/index', [
            'title'        => 'Bookmarks',
            'sets'         => $sets,
            'clients'      => $clients,
            'search'       => $search,
            'filterClient' => $clientId,
        ]);
    }

    // ─── GET /bookmarks/create ────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('bookmarks/create', [
            'title'       => 'New Bookmark Set',
            'breadcrumbs' => [
                ['label' => 'Bookmarks', 'url' => '/bookmarks'],
                ['label' => 'New Set'],
            ],
            'old'     => $old,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /bookmarks/store ────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectSetFormData();
        $errors = $this->validateSetData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/bookmarks/create');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = BookmarkSetModel::create($data);

        $this->logActivity('bookmark_set_created', $id, "Bookmark set created: {$data['name']}");

        // Optional: import from uploaded file
        $importCount = 0;
        if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            $html        = (string) file_get_contents($_FILES['import_file']['tmp_name']);
            $parsed      = BookmarkImportService::parse($html);
            $importCount = $this->importParsed($id, $parsed);

            if ($importCount > 0) {
                $this->logActivity('bookmarks_imported', $id,
                    "Imported {$importCount} bookmarks into set: {$data['name']}");
            }
        }

        $msg = "Bookmark set \"{$data['name']}\" created.";
        if ($importCount > 0) {
            $msg .= " {$importCount} bookmark(s) imported.";
        }

        $this->flashSuccess($msg);
        $this->redirect("/bookmarks/{$id}");
    }

    // ─── GET /bookmarks/{id} ──────────────────────────────────────────────────

    public function show(): void
    {
        $set     = $this->resolveSet();
        $id      = (int) $set['id'];
        $folders = BookmarkFolderModel::getBySet($id);
        $all     = BookmarkModel::getBySet($id);

        // Group bookmarks by folder_id
        $byFolder = [];
        $root     = [];
        foreach ($all as $bm) {
            if (!empty($bm['folder_id'])) {
                $byFolder[(int) $bm['folder_id']][] = $bm;
            } else {
                $root[] = $bm;
            }
        }

        $this->view('bookmarks/show', [
            'title'       => $set['name'],
            'breadcrumbs' => [
                ['label' => 'Bookmarks', 'url' => '/bookmarks'],
                ['label' => $set['name']],
            ],
            'set'      => $set,
            'folders'  => $folders,
            'byFolder' => $byFolder,
            'root'     => $root,
            'clients'  => ClientModel::getForSelect(),
            'activity' => BookmarkSetModel::getActivity($id),
        ]);
    }

    // ─── GET /bookmarks/{id}/edit ─────────────────────────────────────────────

    public function showEdit(): void
    {
        $set    = $this->resolveSet();
        $old    = $this->popOldInput();
        $errors = get_flash('error');
        $merged = array_merge($set, $old);

        $this->view('bookmarks/edit', [
            'title'       => 'Edit Bookmark Set',
            'breadcrumbs' => [
                ['label' => 'Bookmarks', 'url' => '/bookmarks'],
                ['label' => $set['name'], 'url' => "/bookmarks/{$set['id']}"],
                ['label' => 'Edit'],
            ],
            'set'     => $merged,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /bookmarks/{id}/update ──────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $set    = $this->resolveSet();
        $id     = (int) $set['id'];
        $data   = $this->collectSetFormData();
        $errors = $this->validateSetData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/bookmarks/{$id}/edit");
        }

        BookmarkSetModel::update($id, $data);

        $this->logActivity('bookmark_set_updated', $id, "Bookmark set updated: {$data['name']}");

        $this->flashSuccess("Bookmark set \"{$data['name']}\" updated.");
        $this->redirect("/bookmarks/{$id}");
    }

    // ─── POST /bookmarks/{id}/delete ──────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $set  = $this->resolveSet();
        $id   = (int) $set['id'];
        $name = $set['name'];

        BookmarkSetModel::delete($id);

        $this->logActivity('bookmark_set_deleted', $id, "Bookmark set deleted: {$name}");

        $this->flashSuccess("Bookmark set \"{$name}\" and all its bookmarks deleted.");
        $this->redirect('/bookmarks');
    }

    // ─── GET /bookmarks/{id}/export ───────────────────────────────────────────

    public function export(): void
    {
        $set       = $this->resolveSet();
        $id        = (int) $set['id'];
        $folders   = BookmarkFolderModel::getBySet($id);
        $bookmarks = BookmarkModel::getBySet($id);

        $html = BookmarkExportService::generate($set, $folders, $bookmarks);

        $slug     = preg_replace('/[^a-z0-9]+/', '-', strtolower($set['name']));
        $slug     = trim($slug, '-');
        $filename = 'bookmarks-' . ($slug ?: 'export') . '-' . date('Y-m-d') . '.html';

        $this->logActivity('bookmarks_exported', $id, "Bookmark set exported: {$set['name']}");

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($html));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $html;
        exit;
    }

    // ─── POST /bookmarks/{id}/import ──────────────────────────────────────────

    public function importFile(): void
    {
        $this->validateCsrf();

        $set = $this->resolveSet();
        $id  = (int) $set['id'];

        if (empty($_FILES['import_file']['tmp_name']) || !is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            $this->flashError('Please select an HTML bookmark file to import.');
            $this->redirect("/bookmarks/{$id}");
        }

        $html   = (string) file_get_contents($_FILES['import_file']['tmp_name']);
        $parsed = BookmarkImportService::parse($html);
        $count  = $this->importParsed($id, $parsed);

        $this->logActivity('bookmarks_imported', $id,
            "Imported {$count} bookmarks into set: {$set['name']}");

        $this->flashSuccess("Imported {$count} bookmark(s) successfully.");
        $this->redirect("/bookmarks/{$id}");
    }

    // ─── POST /bookmarks/{id}/folders/store ───────────────────────────────────

    public function addFolder(): void
    {
        $this->validateCsrf();

        $set  = $this->resolveSet();
        $id   = (int) $set['id'];
        $name = trim((string) $this->request->post('folder_name', ''));

        if ($name === '') {
            $this->flashError('Folder name is required.');
            $this->redirect("/bookmarks/{$id}");
        }

        if (mb_strlen($name) > 255) {
            $this->flashError('Folder name must be 255 characters or fewer.');
            $this->redirect("/bookmarks/{$id}");
        }

        $existing = BookmarkFolderModel::getBySet($id);
        BookmarkFolderModel::create($id, $name, count($existing));

        $this->flashSuccess("Folder \"{$name}\" added.");
        $this->redirect("/bookmarks/{$id}#folders");
    }

    // ─── POST /bookmarks/{id}/folders/{fid}/delete ────────────────────────────

    public function deleteFolder(): void
    {
        $this->validateCsrf();

        $set    = $this->resolveSet();
        $id     = (int) $set['id'];
        $folder = $this->resolveFolder($id);
        $name   = $folder['name'];

        BookmarkFolderModel::delete((int) $folder['id']);

        $this->flashSuccess("Folder \"{$name}\" deleted. Its bookmarks are now unfiled.");
        $this->redirect("/bookmarks/{$id}#folders");
    }

    // ─── POST /bookmarks/{id}/bookmarks/store ─────────────────────────────────

    public function addBookmark(): void
    {
        $this->validateCsrf();

        $set      = $this->resolveSet();
        $id       = (int) $set['id'];
        $title    = trim((string) $this->request->post('title',     ''));
        $url      = trim((string) $this->request->post('url',       ''));
        $folderId = (int)          $this->request->post('folder_id', 0);

        if ($url === '') {
            $this->flashError('URL is required.');
            $this->redirect("/bookmarks/{$id}");
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->flashError('Please enter a valid URL (include https://).');
            $this->redirect("/bookmarks/{$id}");
        }

        BookmarkModel::create([
            'set_id'    => $id,
            'folder_id' => $folderId ?: null,
            'title'     => $title !== '' ? $title : $url,
            'url'       => $url,
            'favicon'   => null,
            'add_date'  => time(),
            'sort_order' => 0,
        ]);

        $this->flashSuccess('Bookmark added.');
        $this->redirect("/bookmarks/{$id}");
    }

    // ─── POST /bookmarks/{id}/bookmarks/{bid}/delete ──────────────────────────

    public function deleteBookmark(): void
    {
        $this->validateCsrf();

        $set      = $this->resolveSet();
        $id       = (int) $set['id'];
        $bookmark = $this->resolveBookmark($id);

        BookmarkModel::delete((int) $bookmark['id']);

        $this->flashSuccess('Bookmark deleted.');
        $this->redirect("/bookmarks/{$id}");
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveSet(): array
    {
        $id  = (int) $this->request->param('id', 0);
        $set = $id > 0 ? BookmarkSetModel::getById($id) : null;

        if (!$set) {
            $this->notFound();
        }

        return $set;
    }

    private function resolveFolder(int $setId): array
    {
        $fid    = (int) $this->request->param('fid', 0);
        $folder = $fid > 0 ? BookmarkFolderModel::getById($fid) : null;

        if (!$folder || (int) $folder['set_id'] !== $setId) {
            $this->notFound();
        }

        return $folder;
    }

    private function resolveBookmark(int $setId): array
    {
        $bid      = (int) $this->request->param('bid', 0);
        $bookmark = $bid > 0 ? BookmarkModel::getById($bid) : null;

        if (!$bookmark || (int) $bookmark['set_id'] !== $setId) {
            $this->notFound();
        }

        return $bookmark;
    }

    private function collectSetFormData(): array
    {
        return [
            'name'        => trim((string) $this->request->post('name',        '')),
            'browser'     => trim((string) $this->request->post('browser',     'other')),
            'client_id'   => (int)          $this->request->post('client_id',   0),
            'description' => trim((string) $this->request->post('description', '')),
        ];
    }

    private function validateSetData(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Name is required.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors[] = 'Name must be 255 characters or fewer.';
        }

        if ($data['browser'] !== '' && !array_key_exists($data['browser'], BookmarkSetModel::BROWSERS)) {
            $errors[] = 'Invalid browser selection.';
        }

        return $errors;
    }

    private function popOldInput(): array
    {
        $raw = get_flash('old')[0] ?? '';
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    /**
     * Insert all parsed folders + bookmarks into the DB for a given set.
     * Returns total bookmark count.
     */
    private function importParsed(int $setId, array $parsed): int
    {
        $total = 0;
        $order = 0;

        // Existing folder count (for sort_order offset when appending)
        $existing = BookmarkFolderModel::getBySet($setId);
        $folderOffset = count($existing);

        foreach ($parsed['folders'] as $folderData) {
            $folderId = BookmarkFolderModel::create($setId, $folderData['name'], $folderOffset + $order++);
            $bmOrder  = 0;

            foreach ($folderData['bookmarks'] as $bm) {
                BookmarkModel::create([
                    'set_id'     => $setId,
                    'folder_id'  => $folderId,
                    'title'      => $bm['title'],
                    'url'        => $bm['url'],
                    'favicon'    => $bm['favicon'],
                    'add_date'   => $bm['add_date'],
                    'sort_order' => $bmOrder++,
                ]);
                $total++;
            }
        }

        $bmOrder = 0;
        foreach ($parsed['root'] as $bm) {
            BookmarkModel::create([
                'set_id'     => $setId,
                'folder_id'  => null,
                'title'      => $bm['title'],
                'url'        => $bm['url'],
                'favicon'    => $bm['favicon'],
                'add_date'   => $bm['add_date'],
                'sort_order' => $bmOrder++,
            ]);
            $total++;
        }

        return $total;
    }

    private function logActivity(string $action, int $entityId, string $description): void
    {
        AuthService::log(
            (int) $_SESSION['user_id'],
            $action,
            'bookmark_set',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}
