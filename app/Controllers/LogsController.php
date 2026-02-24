<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLogModel;

class LogsController extends Controller
{
    // ─── GET /logs → moved to Settings > Activity Log tab ────────────────────

    public function index(): void
    {
        $this->redirect('/settings?tab=logs');
    }
}
