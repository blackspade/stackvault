<?php
declare(strict_types=1);
/**
 * StackVault — Installation Wizard
 *
 * Run this file once to configure your installation.
 * After setup completes it will auto-redirect and lock itself out.
 *
 * NOTE: This installer uses Tabler CSS/JS from CDN for simplicity.
 *       The application itself uses LOCAL assets from ./assets/tabler/
 *       See the success screen for download instructions.
 */

session_start();

define('SV_ROOT',    __DIR__);
define('SV_VERSION', '1.0.0');

// ─── Already-installed guard ──────────────────────────────────────────────────
$envPath = SV_ROOT . '/.env';
if (file_exists($envPath)) {
    $envParsed = @parse_ini_file($envPath);
    if (!empty($envParsed['APP_INSTALLED']) && $envParsed['APP_INSTALLED'] === 'true') {
        $bp = rtrim($envParsed['APP_BASE_PATH'] ?? '', '/');
        header('Location: ' . $bp . '/public/');
        exit;
    }
}

// ─── HTML escape helper ───────────────────────────────────────────────────────
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
if (empty($_SESSION['sv_csrf'])) {
    $_SESSION['sv_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['sv_csrf'];

function sv_csrf_check(): void
{
    if (!hash_equals($_SESSION['sv_csrf'] ?? '', $_POST['_token'] ?? '')) {
        http_response_code(403);
        die('<p style="font-family:sans-serif">Security token mismatch. <a href="setup.php">Start over</a>.</p>');
    }
}

// ─── State ────────────────────────────────────────────────────────────────────
$step   = (int)($_SESSION['sv_step'] ?? 1);
$data   = $_SESSION['sv_data'] ?? [];
$errors = [];

// ─── AJAX: Test DB connection ─────────────────────────────────────────────────
if (
    isset($_GET['action'], $_SERVER['REQUEST_METHOD'])
    && $_GET['action'] === 'testdb'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    sv_csrf_check();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;charset=utf8mb4',
                trim($_POST['db_host'] ?? 'localhost'),
                (int)($_POST['db_port'] ?? 3306)
            ),
            trim($_POST['db_user'] ?? ''),
            (string)($_POST['db_pass'] ?? ''),
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo json_encode(['ok' => true, 'message' => 'Connected — MySQL/MariaDB ' . $ver]);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── POST step handlers ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sv_step'])) {
    sv_csrf_check();
    $posted = (int)$_POST['sv_step'];

    // Step 1 → 2  (Requirements — just advance)
    if ($posted === 1) {
        $_SESSION['sv_step'] = $step = 2;
    }

    // Step 2 → 3  (App settings)
    elseif ($posted === 2) {
        $v_name = trim($_POST['app_name'] ?? 'StackVault');
        $v_url  = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $v_base = trim($_POST['base_path'] ?? '');
        $v_base = ($v_base === '' || $v_base === '/') ? '' : '/' . trim($v_base, '/');

        if ($v_name === '')                                          $errors[] = 'App name is required.';
        if ($v_url  === '')                                          $errors[] = 'App URL is required.';
        elseif (!filter_var($v_url, FILTER_VALIDATE_URL))            $errors[] = 'App URL must be valid (include http:// or https://).';

        if (!$errors) {
            $data['appName']  = $v_name;
            $data['appUrl']   = $v_url;
            $data['basePath'] = $v_base;
            $_SESSION['sv_data'] = $data;
            $_SESSION['sv_step'] = $step = 3;
        }
    }

    // Step 3 → 4  (Database)
    elseif ($posted === 3) {
        $v_host = trim($_POST['db_host'] ?? 'localhost');
        $v_port = max(1, min(65535, (int)($_POST['db_port'] ?? 3306)));
        $v_db   = trim($_POST['db_name'] ?? '');
        $v_user = trim($_POST['db_user'] ?? '');
        $v_pass = (string)($_POST['db_pass'] ?? '');

        if ($v_host === '') $errors[] = 'Database host is required.';
        if ($v_db   === '') {
            $errors[] = 'Database name is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $v_db)) {
            $errors[] = 'Database name may only contain letters, numbers, and underscores.';
        }
        if ($v_user === '') $errors[] = 'Database user is required.';

        if (!$errors) {
            try {
                new PDO(
                    "mysql:host={$v_host};port={$v_port};charset=utf8mb4",
                    $v_user, $v_pass,
                    [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $data['dbHost'] = $v_host;
                $data['dbPort'] = $v_port;
                $data['dbName'] = $v_db;
                $data['dbUser'] = $v_user;
                $data['dbPass'] = $v_pass;
                $_SESSION['sv_data'] = $data;
                $_SESSION['sv_step'] = $step = 4;
            } catch (PDOException $e) {
                $errors[] = 'Connection failed: ' . $e->getMessage();
            }
        }
    }

    // Step 4 → 5  (Admin account)
    elseif ($posted === 4) {
        $v_uname  = trim($_POST['admin_username']         ?? '');
        $v_email  = trim($_POST['admin_email']            ?? '');
        $v_pass   = (string)($_POST['admin_password']         ?? '');
        $v_pass_c = (string)($_POST['admin_password_confirm'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $v_uname)) $errors[] = 'Username: 3–32 chars, letters/numbers/underscores only.';
        if (!filter_var($v_email, FILTER_VALIDATE_EMAIL))      $errors[] = 'A valid email address is required.';
        if (strlen($v_pass) < 12)                              $errors[] = 'Password must be at least 12 characters.';
        if ($v_pass !== $v_pass_c)                             $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $data['adminUsername'] = $v_uname;
            $data['adminEmail']    = $v_email;
            $data['adminPass']     = $v_pass;
            $_SESSION['sv_data'] = $data;
            $_SESSION['sv_step'] = $step = 5;
        }
    }

    // Step 5 → 6  (Vault unlock password)
    elseif ($posted === 5) {
        $v_vpass  = (string)($_POST['vault_password']         ?? '');
        $v_vpass_c = (string)($_POST['vault_password_confirm'] ?? '');

        if (strlen($v_vpass) < 12)                                          $errors[] = 'Vault password must be at least 12 characters.';
        if ($v_vpass !== $v_vpass_c)                                        $errors[] = 'Vault passwords do not match.';
        if (!empty($data['adminPass']) && $v_vpass === $data['adminPass'])  $errors[] = 'Vault password must differ from your login password.';

        if (!$errors) {
            $data['vaultPass'] = $v_vpass;
            $_SESSION['sv_data'] = $data;
            $_SESSION['sv_step'] = $step = 6;
        }
    }

    // Step 6 — Install
    elseif ($posted === 6) {
        $result = sv_install($data);
        if ($result['success']) {
            unset($_SESSION['sv_step'], $_SESSION['sv_data']);
            $_SESSION['sv_login_url']  = rtrim($data['appUrl'], '/') . '/public/';
            $_SESSION['sv_admin_user'] = $data['adminUsername'];
            $_SESSION['sv_admin_email'] = $data['adminEmail'];
            $step = 7;
        } else {
            $errors = $result['errors'];
        }
    }
}

// ─── Auto-detect app URL for Step 2 pre-fill ─────────────────────────────────
$detectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$detectedHost   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedDir    = dirname($_SERVER['SCRIPT_NAME'] ?? '/setup.php');
$detectedDir    = ($detectedDir === '/' || $detectedDir === '\\') ? '' : $detectedDir;
$detectedUrl    = $detectedScheme . '://' . $detectedHost . $detectedDir;
$detectedBase   = $detectedDir;

// ─────────────────────────────────────────────────────────────────────────────
// Installation functions
// ─────────────────────────────────────────────────────────────────────────────

function sv_install(array $d): array
{
    try {
        // Connect MySQL (no DB selected yet)
        $pdo = new PDO(
            "mysql:host={$d['dbHost']};port={$d['dbPort']};charset=utf8mb4",
            $d['dbUser'],
            $d['dbPass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
        );

        // Create & select database
        $db = $d['dbName'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");

        // Run schema
        foreach (sv_get_schema() as $sql) {
            $pdo->exec($sql);
        }

        // Generate app key
        $appKey = base64_encode(random_bytes(32));

        // Generate vault key and encrypt with vault unlock password
        $vaultKey    = random_bytes(32);
        $vaultKeyEnc = sv_encrypt_vault_key($vaultKey, $d['vaultPass']);

        // Hash both passwords with Argon2id
        $loginHash = password_hash($d['adminPass'],  PASSWORD_ARGON2ID);
        $vaultHash = password_hash($d['vaultPass'],  PASSWORD_ARGON2ID);

        // Insert admin user
        $pdo->prepare(
            "INSERT INTO users
                (username, email, password_hash, role, vault_key_encrypted, vault_password_hash, created_at, updated_at)
             VALUES (?, ?, ?, 'admin', ?, ?, NOW(), NOW())"
        )->execute([
            $d['adminUsername'],
            $d['adminEmail'],
            $loginHash,
            $vaultKeyEnc,
            $vaultHash,
        ]);
        $userId = (int)$pdo->lastInsertId();

        // Write .env
        if (file_put_contents(SV_ROOT . '/.env', sv_build_env($d, $appKey)) === false) {
            throw new RuntimeException('Cannot write .env — check folder write permissions.');
        }

        // Scaffold directories
        $dirs = [
            'storage/logs', 'storage/cache', 'storage/sessions',
            'app/Controllers', 'app/Models', 'app/Services',
            'app/Repositories', 'app/Middleware', 'app/Helpers', 'app/Core',
            'resources/views/auth', 'resources/views/dashboard',
            'resources/views/clients', 'resources/views/domains',
            'resources/views/servers', 'resources/views/credentials',
            'resources/views/applications', 'resources/views/databases',
            'resources/views/dns', 'resources/views/email',
            'resources/views/logs', 'resources/views/settings',
            'resources/layouts', 'resources/components',
            'config', 'routes', 'public',
            'assets/tabler/css', 'assets/tabler/js', 'assets/tabler/fonts', 'assets/icons',
        ];
        foreach ($dirs as $dir) {
            $p = SV_ROOT . '/' . $dir;
            if (!is_dir($p)) {
                mkdir($p, 0755, true);
            }
        }

        // .gitkeep for empty storage directories
        foreach (['storage/logs', 'storage/cache', 'storage/sessions'] as $dir) {
            $gk = SV_ROOT . '/' . $dir . '/.gitkeep';
            if (!file_exists($gk)) {
                file_put_contents($gk, '');
            }
        }

        // Asset download README
        $assetReadme = SV_ROOT . '/assets/tabler/DOWNLOAD_ASSETS.txt';
        file_put_contents($assetReadme, sv_asset_instructions());

        // Log install
        $pdo->prepare(
            "INSERT INTO activity_logs
                (user_id, action, entity_type, entity_id, description, ip_address, created_at)
             VALUES (?, 'install', 'system', 0, 'StackVault installed successfully', ?, NOW())"
        )->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

        return ['success' => true];

    } catch (PDOException $e) {
        error_log('[StackVault Setup] DB error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['Could not connect to the database. Check your credentials and try again.']];
    } catch (RuntimeException $e) {
        return ['success' => false, 'errors' => [$e->getMessage()]];
    }
}

function sv_encrypt_vault_key(string $vaultKey, string $password): string
{
    $salt = random_bytes(16);
    $iv   = random_bytes(12); // AES-GCM standard: 12 bytes
    $key  = hash_pbkdf2('sha256', $password, $salt, 100_000, 32, true);
    $tag  = '';
    $enc  = openssl_encrypt($vaultKey, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($enc === false) {
        throw new RuntimeException('OpenSSL AES-256-GCM encryption failed.');
    }
    // Stored layout: salt[16] | iv[12] | gcm_tag[16] | ciphertext[32] = 76 bytes → base64
    return base64_encode($salt . $iv . $tag . $enc);
}

function sv_build_env(array $d, string $appKey): string
{
    $ts = date('Y-m-d H:i:s');
    $bp = $d['basePath'];
    $au = $d['appUrl'];
    $an = $d['appName'];
    $dh = $d['dbHost'];
    $dp = $d['dbPort'];
    $dn = $d['dbName'];
    $du = $d['dbUser'];
    $dw = $d['dbPass'];

    return <<<ENV
# StackVault — Environment Configuration
# Generated: {$ts}
# Keep this file private — never commit to version control

APP_NAME={$an}
APP_ENV=production
APP_DEBUG=false
APP_KEY={$appKey}
APP_URL={$au}
APP_BASE_PATH={$bp}
APP_INSTALLED=true

DB_HOST={$dh}
DB_PORT={$dp}
DB_DATABASE={$dn}
DB_USERNAME={$du}
DB_PASSWORD={$dw}

SESSION_LIFETIME=120
SESSION_SECURE=false

LOG_LEVEL=error
LOG_PATH=storage/logs

TERMINAL_ENABLED=false
TERMINAL_WS_URL=
ENV;
}

function sv_asset_instructions(): string
{
    return <<<TXT
StackVault — Tabler Asset Download Instructions
================================================

The app uses LOCAL Tabler assets. Download the following files:

1. Tabler Core CSS
   URL: https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css
   Save to: assets/tabler/css/tabler.min.css

2. Tabler Core JS
   URL: https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js
   Save to: assets/tabler/js/tabler.min.js

3. Tabler Icons CSS
   URL: https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/tabler-icons.min.css
   Save to: assets/tabler/css/tabler-icons.min.css

4. Tabler Icons Fonts (download ALL files from this directory listing):
   URL: https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.0.0/dist/fonts/
   Save all .woff2 / .woff / .ttf files to: assets/tabler/fonts/

After downloading, update the tabler-icons.min.css font-face src paths to point
to ../fonts/ relative to the CSS file location.

This file can be deleted once assets are in place.
TXT;
}

function sv_get_schema(): array
{
    return [

        "CREATE TABLE IF NOT EXISTS `users` (
            `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
            `username`              VARCHAR(32)      NOT NULL,
            `email`                 VARCHAR(255)     NOT NULL,
            `password_hash`         VARCHAR(255)     NOT NULL,
            `role`                  ENUM('admin','manager','viewer') NOT NULL DEFAULT 'viewer',
            `vault_key_encrypted`   TEXT             DEFAULT NULL COMMENT 'AES-256-GCM encrypted vault key: salt|iv|tag|ciphertext (base64)',
            `vault_password_hash`   VARCHAR(255)     DEFAULT NULL,
            `is_active`             TINYINT(1)       NOT NULL DEFAULT 1,
            `last_login_at`         DATETIME         DEFAULT NULL,
            `last_login_ip`         VARCHAR(45)      DEFAULT NULL,
            `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `locked_until`          DATETIME         DEFAULT NULL,
            `created_at`            DATETIME         NOT NULL,
            `updated_at`            DATETIME         NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_users_username` (`username`),
            UNIQUE KEY `uq_users_email`    (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `clients` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`          VARCHAR(255) NOT NULL,
            `contact_name`  VARCHAR(255) DEFAULT NULL,
            `contact_email` VARCHAR(255) DEFAULT NULL,
            `contact_phone` VARCHAR(50)  DEFAULT NULL,
            `website`       VARCHAR(500) DEFAULT NULL,
            `notes`         TEXT         DEFAULT NULL,
            `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
            `created_by`    INT UNSIGNED DEFAULT NULL,
            `created_at`    DATETIME     NOT NULL,
            `updated_at`    DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_clients_name` (`name`(100))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `tags` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`       VARCHAR(50)  NOT NULL,
            `color`      VARCHAR(7)   NOT NULL DEFAULT '#6366f1',
            `created_at` DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_tags_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `taggables` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tag_id`        INT UNSIGNED NOT NULL,
            `taggable_type` VARCHAR(50)  NOT NULL COMMENT 'client|domain|server|application|credential',
            `taggable_id`   INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_taggables_tag`    (`tag_id`),
            KEY `idx_taggables_entity` (`taggable_type`, `taggable_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `domains` (
            `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id`   INT UNSIGNED DEFAULT NULL,
            `root_domain` VARCHAR(255) NOT NULL,
            `registrar`   VARCHAR(100) DEFAULT NULL,
            `expiry_date` DATE         DEFAULT NULL,
            `nameservers` TEXT         DEFAULT NULL,
            `ssl_expiry`  DATE         DEFAULT NULL,
            `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
            `notes`       TEXT         DEFAULT NULL,
            `created_by`  INT UNSIGNED DEFAULT NULL,
            `created_at`  DATETIME     NOT NULL,
            `updated_at`  DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_domains_client` (`client_id`),
            KEY `idx_domains_expiry` (`expiry_date`),
            KEY `idx_domains_ssl`    (`ssl_expiry`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `servers` (
            `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `client_id`         INT UNSIGNED  DEFAULT NULL,
            `label`             VARCHAR(255)  NOT NULL,
            `ip_address`        VARCHAR(45)   DEFAULT NULL,
            `hostname`          VARCHAR(255)  DEFAULT NULL,
            `provider`          VARCHAR(100)  DEFAULT NULL,
            `os_version`        VARCHAR(100)  DEFAULT NULL,
            `ssh_port`          SMALLINT UNSIGNED NOT NULL DEFAULT 22,
            `firewall_notes`    TEXT          DEFAULT NULL,
            `installed_stacks`  TEXT          DEFAULT NULL,
            `monitoring_status` ENUM('unknown','online','offline','degraded') NOT NULL DEFAULT 'unknown',
            `notes`             TEXT          DEFAULT NULL,
            `created_by`        INT UNSIGNED  DEFAULT NULL,
            `created_at`        DATETIME      NOT NULL,
            `updated_at`        DATETIME      NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_servers_client` (`client_id`),
            KEY `idx_servers_ip`     (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `applications` (
            `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id`         INT UNSIGNED DEFAULT NULL,
            `server_id`         INT UNSIGNED DEFAULT NULL,
            `domain_id`         INT UNSIGNED DEFAULT NULL,
            `app_name`          VARCHAR(255) NOT NULL,
            `version`           VARCHAR(50)  DEFAULT NULL,
            `stack_type`        VARCHAR(100) DEFAULT NULL,
            `install_path`      VARCHAR(500) DEFAULT NULL,
            `git_repo`          VARCHAR(500) DEFAULT NULL,
            `deployment_method` VARCHAR(100) DEFAULT NULL,
            `notes`             TEXT         DEFAULT NULL,
            `created_by`        INT UNSIGNED DEFAULT NULL,
            `created_at`        DATETIME     NOT NULL,
            `updated_at`        DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_apps_client` (`client_id`),
            KEY `idx_apps_server` (`server_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `credentials` (
            `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id`             INT UNSIGNED DEFAULT NULL,
            `server_id`             INT UNSIGNED DEFAULT NULL,
            `domain_id`             INT UNSIGNED DEFAULT NULL,
            `app_id`                INT UNSIGNED DEFAULT NULL,
            `label`                 VARCHAR(255) NOT NULL,
            `credential_type`       ENUM('ssh','cpanel','database','email','api_key','registrar','cloud','other') NOT NULL DEFAULT 'other',
            `username`              VARCHAR(255) DEFAULT NULL,
            `password_encrypted`    TEXT         DEFAULT NULL COMMENT 'AES-256-GCM with vault key',
            `port`                  SMALLINT UNSIGNED DEFAULT NULL,
            `totp_secret_encrypted` TEXT         DEFAULT NULL,
            `notes`                 TEXT         DEFAULT NULL,
            `created_by`            INT UNSIGNED DEFAULT NULL,
            `last_viewed_at`        DATETIME     DEFAULT NULL,
            `last_viewed_by`        INT UNSIGNED DEFAULT NULL,
            `created_at`            DATETIME     NOT NULL,
            `updated_at`            DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_creds_client` (`client_id`),
            KEY `idx_creds_type`   (`credential_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `db_instances` (
            `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id`          INT UNSIGNED DEFAULT NULL,
            `server_id`          INT UNSIGNED DEFAULT NULL,
            `app_id`             INT UNSIGNED DEFAULT NULL,
            `db_type`            ENUM('mysql','mariadb','postgresql','sqlite','mssql','other') NOT NULL DEFAULT 'mysql',
            `host`               VARCHAR(255) NOT NULL DEFAULT 'localhost',
            `port`               SMALLINT UNSIGNED NOT NULL DEFAULT 3306,
            `db_name`            VARCHAR(255) NOT NULL,
            `username`           VARCHAR(255) DEFAULT NULL,
            `password_encrypted` TEXT         DEFAULT NULL,
            `notes`              TEXT         DEFAULT NULL,
            `created_by`         INT UNSIGNED DEFAULT NULL,
            `created_at`         DATETIME     NOT NULL,
            `updated_at`         DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_db_client` (`client_id`),
            KEY `idx_db_server` (`server_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `dns_records` (
            `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `domain_id`   INT UNSIGNED NOT NULL,
            `record_type` ENUM('A','AAAA','CNAME','MX','TXT','NS','SRV','CAA','PTR','SOA') NOT NULL DEFAULT 'A',
            `name`        VARCHAR(255) NOT NULL,
            `value`       TEXT         NOT NULL,
            `ttl`         INT UNSIGNED NOT NULL DEFAULT 3600,
            `priority`    SMALLINT UNSIGNED DEFAULT NULL,
            `notes`       TEXT         DEFAULT NULL,
            `created_at`  DATETIME     NOT NULL,
            `updated_at`  DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_dns_domain` (`domain_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `email_accounts` (
            `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_id`          INT UNSIGNED DEFAULT NULL,
            `domain_id`          INT UNSIGNED DEFAULT NULL,
            `email_address`      VARCHAR(255) NOT NULL,
            `mail_host`          VARCHAR(255) DEFAULT NULL,
            `smtp_port`          SMALLINT UNSIGNED DEFAULT 587,
            `imap_port`          SMALLINT UNSIGNED DEFAULT 993,
            `username`           VARCHAR(255) DEFAULT NULL,
            `password_encrypted` TEXT         DEFAULT NULL,
            `webmail_url`        VARCHAR(500) DEFAULT NULL,
            `notes`              TEXT         DEFAULT NULL,
            `created_by`         INT UNSIGNED DEFAULT NULL,
            `created_at`         DATETIME     NOT NULL,
            `updated_at`         DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_email_domain` (`domain_id`),
            KEY `idx_email_client` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `user_id`     INT UNSIGNED     DEFAULT NULL,
            `action`      VARCHAR(100)     NOT NULL,
            `entity_type` VARCHAR(50)      DEFAULT NULL,
            `entity_id`   INT UNSIGNED     DEFAULT NULL,
            `description` TEXT             DEFAULT NULL,
            `ip_address`  VARCHAR(45)      DEFAULT NULL,
            `user_agent`  VARCHAR(500)     DEFAULT NULL,
            `created_at`  DATETIME         NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_log_user`    (`user_id`),
            KEY `idx_log_action`  (`action`),
            KEY `idx_log_entity`  (`entity_type`, `entity_id`),
            KEY `idx_log_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `settings` (
            `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key`   VARCHAR(100) NOT NULL,
            `setting_value` TEXT         DEFAULT NULL,
            `setting_group` VARCHAR(50)  NOT NULL DEFAULT 'general',
            `created_at`    DATETIME     NOT NULL,
            `updated_at`    DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_settings_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    ];
}

function sv_check_requirements(): array
{
    $checks = [];

    $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
    $checks[] = ['label' => 'PHP Version (' . PHP_VERSION . ')', 'pass' => $phpOk,
                 'note'  => $phpOk ? 'OK' : 'PHP 8.1+ required'];

    foreach (['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'json', 'fileinfo'] as $ext) {
        $ok = extension_loaded($ext);
        $checks[] = ['label' => "Extension: {$ext}", 'pass' => $ok,
                     'note'  => $ok ? 'Loaded' : 'MISSING — enable in php.ini'];
    }

    $writable = is_writable(SV_ROOT);
    $checks[] = ['label' => 'Root directory writable', 'pass' => $writable,
                 'note'  => $writable ? 'Writable' : 'Not writable — check permissions'];

    return $checks;
}

$reqChecks  = sv_check_requirements();
$reqAllPass = array_reduce($reqChecks, fn($c, $r) => $c && $r['pass'], true);

// Step labels for the progress indicator
$stepLabels = [1 => 'Requirements', 2 => 'App Settings', 3 => 'Database',
               4 => 'Admin Account', 5 => 'Vault', 6 => 'Review'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>StackVault — Setup</title>
    <!-- Tabler CSS (CDN — installer only; app uses local ./assets/tabler/) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css">
    <style>
        .sv-step-indicator { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 4px; }
        .sv-step { display: flex; align-items: center; }
        .sv-step-circle {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; border: 2px solid #dee2e6;
            background: #fff; color: #6c757d; transition: all .2s;
        }
        .sv-step-circle.done  { background: #2fb344; border-color: #2fb344; color: #fff; }
        .sv-step-circle.active { background: #206bc4; border-color: #206bc4; color: #fff; }
        .sv-step-label { font-size: 11px; color: #6c757d; margin-top: 4px; text-align: center; }
        .sv-step-label.active { color: #206bc4; font-weight: 600; }
        .sv-step-label.done   { color: #2fb344; }
        .sv-step-line { width: 24px; height: 2px; background: #dee2e6; margin: 0 2px; transition: background .2s; }
        .sv-step-line.done { background: #2fb344; }
        .sv-step-wrap { display: flex; flex-direction: column; align-items: center; }
        .req-table td { padding: 6px 10px; vertical-align: middle; }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                     background: none; border: none; cursor: pointer; color: #6c757d; padding: 0; }
        .review-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .review-label { width: 160px; font-weight: 500; color: #6c757d; flex-shrink: 0; }
        .review-value { color: #1a1a2e; }
    </style>
</head>
<body class="antialiased">
<div class="page page-center" style="min-height: 100vh; background: #f4f6fb;">
    <div class="container" style="max-width: 700px; padding: 2rem 1rem;">

        <!-- Branding -->
        <div class="text-center mb-4">
            <div class="mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                     fill="none" stroke="#206bc4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 class="h2 mb-0">StackVault</h1>
            <p class="text-muted">DevOps Client &amp; Infrastructure Manager — Installation</p>
        </div>

        <?php if ($step < 7): ?>
        <!-- Step indicator -->
        <div class="sv-step-indicator mb-4">
            <?php foreach ($stepLabels as $n => $label):
                $stateCircle = $step > $n ? 'done' : ($step === $n ? 'active' : '');
                $stateLabel  = $step > $n ? 'done' : ($step === $n ? 'active' : '');
                $stateLine   = $step > $n ? 'done' : '';
            ?>
            <div class="sv-step-wrap">
                <div class="sv-step-circle <?= $stateCircle ?>">
                    <?= $step > $n ? '✓' : $n ?>
                </div>
                <div class="sv-step-label <?= $stateLabel ?>"><?= e($label) ?></div>
            </div>
            <?php if ($n < count($stepLabels)): ?>
            <div class="sv-step-line <?= $stateLine ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Error alerts -->
        <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <div class="d-flex">
                <div class="me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 1 — Requirements Check
             ════════════════════════════════════════════════════════════════ -->
        <?php if ($step === 1): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 1 — Requirements Check</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-vcenter card-table mb-0 req-table">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reqChecks as $chk): ?>
                        <tr>
                            <td><?= e($chk['label']) ?></td>
                            <td>
                                <?php if ($chk['pass']): ?>
                                <span class="badge bg-success-lt">Pass</span>
                                <?php else: ?>
                                <span class="badge bg-danger-lt">Fail</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($chk['note']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <?php if ($reqAllPass): ?>
                <form method="post">
                    <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="sv_step" value="1">
                    <button type="submit" class="btn btn-primary">Continue &rarr;</button>
                </form>
                <?php else: ?>
                <div class="text-danger fw-bold">Fix the failing requirements above, then refresh this page.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 2 — App Settings
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 2): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 2 — App Settings</h3>
            </div>
            <form method="post">
                <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="sv_step" value="2">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Application Name</label>
                        <input type="text" class="form-control" name="app_name"
                               value="<?= e($_POST['app_name'] ?? $data['appName'] ?? 'StackVault') ?>"
                               placeholder="StackVault">
                        <small class="form-hint">Displayed in the title bar and header.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">App URL</label>
                        <input type="url" class="form-control" name="app_url"
                               value="<?= e($_POST['app_url'] ?? $data['appUrl'] ?? $detectedUrl) ?>"
                               placeholder="http://localhost/stackvault">
                        <small class="form-hint">
                            Full URL to this app <em>without</em> trailing slash.
                            Auto-detected: <code><?= e($detectedUrl) ?></code>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subdirectory Base Path</label>
                        <input type="text" class="form-control" name="base_path"
                               value="<?= e($_POST['base_path'] ?? $data['basePath'] ?? $detectedBase) ?>"
                               placeholder="/stackvault">
                        <small class="form-hint">
                            The subdirectory path portion of the URL (e.g. <code>/stackvault</code>).
                            Leave blank or enter <code>/</code> if installing at domain root
                            (e.g. <code>www.example.com</code>).
                            Auto-detected: <code><?= e($detectedBase ?: '/') ?></code>
                        </small>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Continue &rarr;</button>
                </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 3 — Database
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 3): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 3 — Database Connection</h3>
            </div>
            <form method="post" id="db-form">
                <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="sv_step" value="3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-8">
                            <label class="form-label required">Database Host</label>
                            <input type="text" class="form-control" name="db_host" id="db_host"
                                   value="<?= e($_POST['db_host'] ?? $data['dbHost'] ?? 'localhost') ?>"
                                   placeholder="localhost">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label required">Port</label>
                            <input type="number" class="form-control" name="db_port" id="db_port"
                                   value="<?= e((string)($_POST['db_port'] ?? $data['dbPort'] ?? 3306)) ?>"
                                   min="1" max="65535">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Database Name</label>
                            <input type="text" class="form-control" name="db_name"
                                   value="<?= e($_POST['db_name'] ?? $data['dbName'] ?? 'stackvault') ?>"
                                   placeholder="stackvault">
                            <small class="form-hint">Will be created if it doesn't exist.</small>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label required">MySQL User</label>
                            <input type="text" class="form-control" name="db_user" id="db_user"
                                   value="<?= e($_POST['db_user'] ?? $data['dbUser'] ?? 'root') ?>"
                                   autocomplete="username">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">MySQL Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="db_pass" id="db_pass"
                                       value="<?= e($_POST['db_pass'] ?? $data['dbPass'] ?? '') ?>"
                                       autocomplete="current-password">
                                <button type="button" class="toggle-pw" data-target="db_pass" tabindex="-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="btn-test-db" class="btn btn-outline-secondary btn-sm">
                                    Test Connection
                                </button>
                                <span id="db-test-result" class="small"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="setup.php?back=2" class="btn btn-ghost-secondary" onclick="history.back();return false;">&larr; Back</a>
                    <button type="submit" class="btn btn-primary">Continue &rarr;</button>
                </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 4 — Admin Account
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 4): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 4 — Admin Account</h3>
            </div>
            <form method="post">
                <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="sv_step" value="4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Username</label>
                        <input type="text" class="form-control" name="admin_username"
                               value="<?= e($_POST['admin_username'] ?? $data['adminUsername'] ?? '') ?>"
                               placeholder="admin" autocomplete="username"
                               pattern="[a-zA-Z0-9_]{3,32}">
                        <small class="form-hint">3–32 characters: letters, numbers, underscores.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Email Address</label>
                        <input type="email" class="form-control" name="admin_email"
                               value="<?= e($_POST['admin_email'] ?? $data['adminEmail'] ?? '') ?>"
                               placeholder="admin@example.com" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Login Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="admin_password" id="pw1"
                                   autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="pw1" tabindex="-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <small class="form-hint">Minimum 12 characters.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="admin_password_confirm" id="pw2"
                                   autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="pw2" tabindex="-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="#" class="btn btn-ghost-secondary" onclick="history.back();return false;">&larr; Back</a>
                    <button type="submit" class="btn btn-primary">Continue &rarr;</button>
                </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 5 — Vault Unlock Password
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 5): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 5 — Vault Unlock Password</h3>
            </div>
            <div class="card-body pb-0">
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                        </div>
                        <div>
                            <strong>What is the vault password?</strong><br>
                            All stored credentials (SSH keys, API tokens, database passwords) are encrypted
                            with a vault key. That key is locked behind this second password.<br>
                            You will enter it once at each login. It <strong>must differ</strong> from your login password.
                            <strong>Do not lose it</strong> — there is no recovery without it.
                        </div>
                    </div>
                </div>
            </div>
            <form method="post">
                <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="sv_step" value="5">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Vault Unlock Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="vault_password" id="vp1"
                                   autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="vp1" tabindex="-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <small class="form-hint">Minimum 12 characters. Must differ from login password.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Confirm Vault Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="vault_password_confirm" id="vp2"
                                   autocomplete="new-password">
                            <button type="button" class="toggle-pw" data-target="vp2" tabindex="-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="#" class="btn btn-ghost-secondary" onclick="history.back();return false;">&larr; Back</a>
                    <button type="submit" class="btn btn-primary">Continue &rarr;</button>
                </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 6 — Review & Install
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 6): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 6 — Review &amp; Install</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Review your configuration. Click <strong>Install Now</strong> to proceed.</p>

                <div class="mb-3">
                    <h6 class="text-uppercase text-muted small fw-bold mb-2">Application</h6>
                    <div class="review-row"><span class="review-label">Name</span><span class="review-value"><?= e($data['appName'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">URL</span><span class="review-value"><?= e($data['appUrl'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Base Path</span><span class="review-value"><?= e($data['basePath'] ?: '/') ?></span></div>
                </div>

                <div class="mb-3">
                    <h6 class="text-uppercase text-muted small fw-bold mb-2">Database</h6>
                    <div class="review-row"><span class="review-label">Host</span><span class="review-value"><?= e($data['dbHost'] ?? '') ?>:<?= e((string)($data['dbPort'] ?? 3306)) ?></span></div>
                    <div class="review-row"><span class="review-label">Database</span><span class="review-value"><?= e($data['dbName'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">User</span><span class="review-value"><?= e($data['dbUser'] ?? '') ?></span></div>
                </div>

                <div class="mb-3">
                    <h6 class="text-uppercase text-muted small fw-bold mb-2">Admin Account</h6>
                    <div class="review-row"><span class="review-label">Username</span><span class="review-value"><?= e($data['adminUsername'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Email</span><span class="review-value"><?= e($data['adminEmail'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Login Password</span><span class="review-value text-muted">[set]</span></div>
                    <div class="review-row"><span class="review-label">Vault Password</span><span class="review-value text-muted">[set]</span></div>
                </div>

                <div class="alert alert-warning mb-0">
                    <strong>What will be installed:</strong>
                    All 13 database tables will be created, your admin account configured, vault key generated and encrypted,
                    <code>.env</code> written, and all app directories scaffolded.
                    This page will auto-redirect after installation.
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="#" class="btn btn-ghost-secondary" onclick="history.back();return false;">&larr; Back</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="sv_step" value="6">
                    <button type="submit" class="btn btn-success btn-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Install Now
                    </button>
                </form>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             STEP 7 — Success
             ════════════════════════════════════════════════════════════════ -->
        <?php elseif ($step === 7): ?>
        <div class="card border-success">
            <div class="card-header bg-success-lt">
                <h3 class="card-title text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    StackVault Installed Successfully!
                </h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="fw-bold mb-2">Your Login Credentials</h6>
                    <div class="review-row"><span class="review-label">Username</span><span class="review-value"><strong><?= e($_SESSION['sv_admin_user'] ?? '') ?></strong></span></div>
                    <div class="review-row"><span class="review-label">Email</span><span class="review-value"><?= e($_SESSION['sv_admin_email'] ?? '') ?></span></div>
                    <div class="review-row"><span class="review-label">Login URL</span>
                        <span class="review-value">
                            <a href="<?= e($_SESSION['sv_login_url'] ?? '#') ?>"><?= e($_SESSION['sv_login_url'] ?? '') ?></a>
                        </span>
                    </div>
                </div>

                <div class="alert alert-warning mb-3">
                    <strong>Next step: Download Tabler assets locally.</strong><br>
                    The app uses local assets from <code>./assets/tabler/</code>. Download these files:
                    <ul class="mt-2 mb-0 small">
                        <li><code>assets/tabler/css/tabler.min.css</code> — from jsDelivr: <code>@tabler/core@1.0.0/dist/css/tabler.min.css</code></li>
                        <li><code>assets/tabler/js/tabler.min.js</code> — from jsDelivr: <code>@tabler/core@1.0.0/dist/js/tabler.min.js</code></li>
                        <li><code>assets/tabler/css/tabler-icons.min.css</code> + fonts — from jsDelivr: <code>@tabler/icons-webfont@3.0.0</code></li>
                    </ul>
                    Full instructions saved to <code>assets/tabler/DOWNLOAD_ASSETS.txt</code>.
                </div>

                <div class="alert alert-info mb-0">
                    <strong>Stage 2 (MVC Foundation) is next.</strong><br>
                    <code>public/index.php</code> does not exist yet — the link above will 404 until Stage 2 is built.
                    Run this setup wizard again and it will redirect automatically once the app is installed.
                </div>
            </div>
            <div class="card-footer">
                <a href="<?= e($_SESSION['sv_login_url'] ?? '#') ?>" class="btn btn-primary">
                    Go to Login &rarr;
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center text-muted mt-3" style="font-size: 12px;">
            StackVault v<?= SV_VERSION ?> &mdash; Installer uses Tabler CDN &mdash; App uses local assets
        </div>

    </div>
</div>

<!-- Tabler JS (CDN — installer only) -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js"></script>
<script>
// Toggle password visibility
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.dataset.target);
        if (target) {
            target.type = target.type === 'password' ? 'text' : 'password';
        }
    });
});

// DB connection test
var testBtn = document.getElementById('btn-test-db');
if (testBtn) {
    testBtn.addEventListener('click', function() {
        var result = document.getElementById('db-test-result');
        result.textContent = 'Testing\u2026';
        result.className = 'small text-muted';
        testBtn.disabled = true;

        var body = new FormData();
        body.append('_token',  '<?= e($csrf) ?>');
        body.append('db_host', document.getElementById('db_host').value);
        body.append('db_port', document.getElementById('db_port').value);
        body.append('db_user', document.getElementById('db_user').value);
        body.append('db_pass', document.getElementById('db_pass').value);

        fetch('setup.php?action=testdb', { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                result.textContent = d.message || (d.ok ? 'Connected!' : 'Failed');
                result.className = 'small ' + (d.ok ? 'text-success' : 'text-danger');
            })
            .catch(function(err) {
                result.textContent = 'Error: ' + err.message;
                result.className = 'small text-danger';
            })
            .finally(function() { testBtn.disabled = false; });
    });
}
</script>
</body>
</html>
