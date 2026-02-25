<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ClientModel;
use App\Models\DatalistPresetModel;
use App\Models\ReminderModel;
use App\Services\AuthService;

class ReminderController extends Controller
{
    public function __construct($request)
    {
        parent::__construct($request);
        ReminderModel::ensureSchema();
    }

    // ─── GET /reminders ───────────────────────────────────────────────────────

    public function index(): void
    {
        $status   = trim((string) $this->request->get('status', ''));
        $type     = trim((string) $this->request->get('type',   ''));
        $clientId = (int) $this->request->get('client_id', 0);

        if (!in_array($status, ['', 'pending', 'done', 'overdue'], true)) {
            $status = '';
        }

        $reminders    = ReminderModel::getAll($status, $type, $clientId);
        $overdueCount = ReminderModel::countOverdue();

        \App\Core\View::share('pageActions',
            '<a href="' . url('/reminders/create') . '" class="btn btn-primary">'
            . '<i class="ti ti-plus me-1"></i>Add Reminder</a>'
        );

        $this->view('reminders/index', [
            'title'        => 'Reminders',
            'breadcrumbs'  => [['label' => 'Reminders']],
            'reminders'    => $reminders,
            'overdueCount' => $overdueCount,
            'filters'      => compact('status', 'type', 'clientId'),
            'types'        => $this->buildTypes(),
            'typeIcons'    => ReminderModel::TYPE_ICONS,
            'typeColors'   => ReminderModel::TYPE_COLORS,
            'clients'      => ClientModel::getForSelect(),
        ]);
    }

    // ─── GET /reminders/create ────────────────────────────────────────────────

    public function showCreate(): void
    {
        $this->view('reminders/create', [
            'title'       => 'Add Reminder',
            'breadcrumbs' => [
                ['label' => 'Reminders', 'url' => '/reminders'],
                ['label' => 'New Reminder'],
            ],
            'types'   => $this->buildTypes(),
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /reminders/store ────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data = $this->collectPost();
        $errors = $this->validate($data);

        if ($errors) {
            flash('error', implode(' ', $errors));
            flash('old', json_encode($data));
            $this->redirect('/reminders/create');
        }

        $id = ReminderModel::create($data);

        AuthService::log(
            (int) $_SESSION['user_id'],
            'reminder_created',
            'reminder', $id,
            "Reminder created: {$data['title']}",
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'Reminder added.');
        $this->redirect('/reminders');
    }

    // ─── GET /reminders/{id}/edit ─────────────────────────────────────────────

    public function showEdit(): void
    {
        $reminder = $this->findOr404();

        $this->view('reminders/edit', [
            'title'       => 'Edit Reminder',
            'breadcrumbs' => [
                ['label' => 'Reminders', 'url' => '/reminders'],
                ['label' => e($reminder['title'])],
            ],
            'reminder' => $reminder,
            'types'    => $this->buildTypes(),
            'clients'  => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /reminders/{id}/update ──────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $reminder = $this->findOr404();
        $data     = $this->collectPost();
        $errors   = $this->validate($data);

        if ($errors) {
            flash('error', implode(' ', $errors));
            flash('old', json_encode($data));
            $this->redirect('/reminders/' . $reminder['id'] . '/edit');
        }

        ReminderModel::update((int) $reminder['id'], $data);

        AuthService::log(
            (int) $_SESSION['user_id'],
            'reminder_updated',
            'reminder', (int) $reminder['id'],
            "Reminder updated: {$data['title']}",
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'Reminder updated.');
        $this->redirect('/reminders');
    }

    // ─── POST /reminders/{id}/done ────────────────────────────────────────────

    public function markDone(): void
    {
        $this->validateCsrf();
        $reminder = $this->findOr404();
        ReminderModel::markDone((int) $reminder['id']);
        flash('success', 'Marked as done.');
        $this->redirect('/reminders');
    }

    // ─── POST /reminders/{id}/undone ──────────────────────────────────────────

    public function markUndone(): void
    {
        $this->validateCsrf();
        $reminder = $this->findOr404();
        ReminderModel::markUndone((int) $reminder['id']);
        flash('success', 'Reminder reopened.');
        $this->redirect('/reminders');
    }

    // ─── POST /reminders/{id}/delete ──────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();
        $reminder = $this->findOr404();

        ReminderModel::delete((int) $reminder['id']);

        AuthService::log(
            (int) $_SESSION['user_id'],
            'reminder_deleted',
            'reminder', (int) $reminder['id'],
            "Reminder deleted: {$reminder['title']}",
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'Reminder deleted.');
        $this->redirect('/reminders');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findOr404(): array
    {
        $id       = (int) $this->request->param('id', 0);
        $reminder = $id > 0 ? ReminderModel::getById($id) : null;

        if (!$reminder) {
            $this->notFound();
        }

        return $reminder;
    }

    /**
     * Merge built-in reminder types with any user-defined types from DB.
     * Built-ins use their slug as key; custom DB types use their label as key.
     */
    private function buildTypes(): array
    {
        DatalistPresetModel::init();
        $types         = ReminderModel::TYPES;
        $builtinLabels = array_values(ReminderModel::TYPES);

        foreach (DatalistPresetModel::getValues('reminder_type') as $label) {
            if (!in_array($label, $builtinLabels, true)) {
                $types[$label] = $label;
            }
        }

        return $types;
    }

    private function collectPost(): array
    {
        $type       = trim((string) $this->request->post('type', 'custom'));
        $validTypes = $this->buildTypes();
        if (!array_key_exists($type, $validTypes)) {
            $type = 'custom';
        }

        return [
            'title'     => trim((string) $this->request->post('title',     '')),
            'type'      => $type,
            'client_id' => (int) $this->request->post('client_id', 0),
            'due_date'  => trim((string) $this->request->post('due_date',  '')),
            'notes'     => trim((string) $this->request->post('notes',     '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors[] = 'Title must be 255 characters or fewer.';
        }

        if ($data['due_date'] === '' || strtotime($data['due_date']) === false) {
            $errors[] = 'A valid due date is required.';
        }

        return $errors;
    }
}
