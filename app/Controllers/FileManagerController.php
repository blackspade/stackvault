<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ClientFileModel;
use App\Models\ClientModel;
use App\Services\AuthService;

class FileManagerController extends Controller
{
    // ─── GET /files ───────────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search',    ''));
        $clientId = (int)          $this->request->get('client_id', 0);
        $ext      = trim((string) $this->request->get('ext',        ''));

        $files   = ClientFileModel::getAll($search, $clientId, $ext);
        $clients = ClientModel::getForSelect();

        $this->view('files/index', [
            'title'        => 'File Manager',
            'files'        => $files,
            'clients'      => $clients,
            'search'       => $search,
            'filterClient' => $clientId,
            'filterExt'    => $ext,
        ]);
    }

    // ─── POST /files/upload ───────────────────────────────────────────────────

    public function upload(): void
    {
        $this->validateCsrf();

        $clientId   = (int)          $this->request->post('client_id',   0);
        $description = trim((string) $this->request->post('description', ''));

        // ── Client required ──────────────────────────────────────────────────
        if ($clientId <= 0) {
            $this->flashError('Please select a client for this file.');
            $this->redirect('/files');
        }

        $client = ClientModel::getById($clientId);
        if (!$client) {
            $this->flashError('Selected client not found.');
            $this->redirect('/files');
        }

        // ── File presence ────────────────────────────────────────────────────
        if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->flashError('Please choose a file to upload.');
            $this->redirect('/files');
        }

        // ── Upload error codes ───────────────────────────────────────────────
        $uploadError = $_FILES['file']['error'];
        if ($uploadError !== UPLOAD_ERR_OK) {
            $msg = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'File exceeds the maximum allowed size (512 MB). Check your php.ini upload_max_filesize and post_max_size.',
                UPLOAD_ERR_PARTIAL   => 'File upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not write the file. Check storage permissions.',
                default => 'File upload failed (error code ' . $uploadError . ').',
            };
            $this->flashError($msg);
            $this->redirect('/files');
        }

        if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->flashError('Invalid file upload.');
            $this->redirect('/files');
        }

        // ── File size ────────────────────────────────────────────────────────
        $fileSize = (int) $_FILES['file']['size'];
        if ($fileSize > ClientFileModel::MAX_BYTES) {
            $this->flashError('File is too large. Maximum allowed size is 512 MB.');
            $this->redirect('/files');
        }

        if ($fileSize === 0) {
            $this->flashError('Uploaded file is empty.');
            $this->redirect('/files');
        }

        // ── Extension validation ─────────────────────────────────────────────
        $originalName = $_FILES['file']['name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, ClientFileModel::ALLOWED_EXTENSIONS, true)) {
            $allowed = implode(', ', ClientFileModel::ALLOWED_EXTENSIONS);
            $this->flashError("File type \".{$ext}\" is not allowed. Allowed types: {$allowed}.");
            $this->redirect('/files');
        }

        // ── Store file on disk ───────────────────────────────────────────────
        $storedName = uniqid('', true) . '.' . $ext;
        $storageDir = ClientFileModel::ensureStorageDir($clientId);
        $destPath   = $storageDir . '/' . $storedName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
            $this->flashError('Failed to save the file. Check server storage permissions.');
            $this->redirect('/files');
        }

        // ── Insert DB record ─────────────────────────────────────────────────
        $id = ClientFileModel::create([
            'client_id'   => $clientId,
            'filename'    => $originalName,
            'stored_name' => $storedName,
            'file_size'   => $fileSize,
            'extension'   => $ext,
            'description' => $description,
            'uploaded_by' => $_SESSION['user_id'],
        ]);

        $this->logActivity('file_uploaded', $id,
            "Uploaded: {$originalName} (" . ClientFileModel::formatBytes($fileSize) . ") for client: {$client['name']}");

        $this->flashSuccess("\"{$originalName}\" uploaded successfully.");
        $this->redirect('/files');
    }

    // ─── POST /files/{id}/update ──────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $file        = $this->resolveFile();
        $id          = (int) $file['id'];
        $description = trim((string) $this->request->post('description', ''));

        ClientFileModel::updateDescription($id, $description);

        $this->flashSuccess('Description updated.');
        $this->redirect('/files');
    }

    // ─── GET /files/{id}/download ─────────────────────────────────────────────

    public function download(): void
    {
        $file   = $this->resolveFile();
        $id     = (int) $file['id'];
        $path   = ClientFileModel::storagePath((int) $file['client_id'], $file['stored_name']);

        if (!is_file($path)) {
            $this->flashError('File not found on disk. It may have been removed.');
            $this->redirect('/files');
        }

        $this->logActivity('file_downloaded', $id,
            "Downloaded: {$file['filename']}");

        $mime     = ClientFileModel::mimeType($file['extension']);
        $filename = rawurlencode($file['filename']);
        $size     = filesize($path);

        // Stream file to browser
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"; filename*=UTF-8\'\'' . $filename);
        header('Content-Length: ' . $size);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Disable output buffering for large files
        while (ob_get_level()) {
            ob_end_clean();
        }

        set_time_limit(0);
        ignore_user_abort(false);

        readfile($path);
        exit;
    }

    // ─── POST /files/{id}/delete ──────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $file = $this->resolveFile();
        $id   = (int) $file['id'];
        $name = $file['filename'];
        $path = ClientFileModel::storagePath((int) $file['client_id'], $file['stored_name']);

        // Delete from disk first
        if (is_file($path)) {
            unlink($path);
        }

        ClientFileModel::delete($id);

        $this->logActivity('file_deleted', $id, "Deleted: {$name}");

        $this->flashSuccess("\"{$name}\" deleted.");
        $this->redirect('/files');
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveFile(): array
    {
        $id   = (int) $this->request->param('id', 0);
        $file = $id > 0 ? ClientFileModel::getById($id) : null;

        if (!$file) {
            $this->notFound();
        }

        return $file;
    }

    private function logActivity(string $action, int $entityId, string $description): void
    {
        AuthService::log(
            (int) $_SESSION['user_id'],
            $action,
            'client_file',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}
