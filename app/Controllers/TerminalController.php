<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\ClientDocModel;
use App\Models\ClientModel;
use App\Models\DnsRecordModel;

class TerminalController extends Controller
{
    public function __construct($request)
    {
        parent::__construct($request);
        ClientDocModel::ensureSchema();
    }

    // ─── GET /terminal ────────────────────────────────────────────────────────

    public function index(): void
    {
        $terminalEnabled = Config::get('TERMINAL_ENABLED', 'false') === 'true';
        $terminalWsUrl   = (string) Config::get('TERMINAL_WS_URL', '');

        $this->view('terminal/index', [
            'title'           => 'Terminal',
            'clients'         => ClientModel::getForSelect(),
            'terminalEnabled' => $terminalEnabled,
            'terminalWsUrl'   => $terminalWsUrl,
        ], 'terminal');
    }

    // ─── GET /terminal/client-data ────────────────────────────────────────────

    public function clientData(): void
    {
        $clientId = (int) $this->request->get('client_id', 0);

        if ($clientId <= 0) {
            $this->json(['error' => 'Invalid client.'], 400);
        }

        $client = ClientModel::getById($clientId);
        if (!$client) {
            $this->json(['error' => 'Client not found.'], 404);
        }

        $doc     = ClientDocModel::getByClient($clientId);
        $servers = ClientModel::getServers($clientId);
        $domains = ClientModel::getDomains($clientId);

        // Collect DNS records for every domain belonging to this client
        $dns = [];
        foreach ($domains as $domain) {
            foreach (DnsRecordModel::getByDomain((int) $domain['id']) as $rec) {
                $rec['root_domain'] = $domain['root_domain'];
                $dns[] = $rec;
            }
        }

        $ipTables = [];
        if ($doc && !empty($doc['ip_tables'])) {
            $decoded  = json_decode((string) $doc['ip_tables'], true);
            $ipTables = is_array($decoded) ? $decoded : [];
        }

        $this->json([
            'client'    => ['id' => $client['id'], 'name' => $client['name']],
            'docs'      => $doc ? (string) $doc['content'] : '',
            'ip_tables' => $ipTables,
            'servers'   => array_values($servers),
            'dns'       => array_values($dns),
        ]);
    }
}
