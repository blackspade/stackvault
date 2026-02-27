# StackVault

**Self-hosted DevOps client and infrastructure manager.**

StackVault is a secure, dual-admin web application for managing clients, servers, domains, credentials, databases, DNS records, email accounts, bookmarks, and files — all in one place. Credentials are encrypted with AES-256-GCM using a per-user vault key that never touches the database unencrypted.

---

## Features

- **Vault encryption** — Credentials are unreadable without the vault password.
- **Client profiles** — Track clients, contacts, and export full reports to PDF.
- **Servers** — IP, hostname, provider, OS, SSH port, firewall notes, installed stacks.
- **Domains** — Expiry tracking, SSL monitoring, 30-day dashboard alerts.
- **Credentials** — SSH, cPanel, API keys, registrar logins — all vault-encrypted at rest.
- **Databases** — Host, port, credentials (encrypted), linked to servers and clients.
- **DNS Records** — Full record management with template system and bulk apply.
- **Email Accounts** — Mail host, SMTP/IMAP ports, vault-encrypted passwords.
- **Applications** — Track deployments with optional App Catalog (374 apps).
- **File Manager** — Upload and store client files (extension-whitelisted).
- **Bookmarks** — Organise links in nested folders per collection.
- **Host Files** — Manage `/etc/hosts`-style machine files.
- **Reminders** — Due-date alerts with dashboard widget.
- **M365 Licenses** — Track Microsoft 365 plans per client with recurring billing, stacked unpaid period alerts, and dashboard integration.
- **Activity Log** — Full audit trail of every action.
- **2FA** — TOTP-based two-factor authentication.
- **IP Whitelist** — Restrict login to trusted IP addresses.
- **Export** — Client profile PDF export; full data export/import.

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.1 or higher |
| MySQL / MariaDB | 5.7 / 10.4 or higher |
| Apache | 2.4+ with `mod_rewrite` |
| PHP Extensions | `pdo`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `fileinfo` |

> **Nginx users:** You will need to translate the root `.htaccess` rewrite rules into an `nginx.conf` location block. See the Nginx section below.

---

## Installation

### 1. Download & place files

Copy the project folder to your web server's document root (e.g. `/var/www/html/stackvault` or `C:/wamp64/www/stackvault`).

### 2. Set directory permissions

```bash
chmod -R 755 storage/
chmod -R 755 assets/
```

### 3. Run the installer

Navigate to `setup.php` in your browser:

```
http://yourdomain.com/stackvault/setup.php
```

The wizard will:
- Check PHP requirements
- Collect your app URL and base path
- Connect to MySQL and create the database
- Create the admin account with a separate login password and vault password
- Auto-generate a secure `APP_KEY` and write `.env`
- Lock itself out

> After setup completes, `setup.php` automatically redirects away and cannot be re-run while `.env` exists with `APP_INSTALLED=true`.

> **APP_KEY** is generated automatically by `setup.php` using `random_bytes(32)`. You do not need to create it manually. If you ever need to regenerate it outside of setup, run:
> ```bash
> php -r "echo base64_encode(random_bytes(32));"
> # or
> openssl rand -base64 32
> ```
> Then update `APP_KEY=` in your `.env` file. Note: rotating APP_KEY does not affect vault encryption (the vault key uses a separate PBKDF2 derivation).

### 4. Access the app

```
http://yourdomain.com/stackvault/
```

Log in with the admin credentials you set during setup. You will need to **unlock the vault** separately with your vault password to view encrypted credentials.

---

## Nginx Configuration

If running under Nginx, add this to your server block instead of relying on `.htaccess`:

```nginx
location /stackvault {
    # Block sensitive directories
    location ~ ^/stackvault/(app|config|routes|storage|resources|notes) {
        deny all;
    }

    # Block hidden files
    location ~ /\. {
        deny all;
    }

    # Route everything to front controller
    try_files $uri /stackvault/public/index.php?$query_string;
}

location ~ ^/stackvault/public/index\.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## Apache .htaccess Setup

The repository includes a root `.htaccess` that handles URL rewriting and blocks direct access to sensitive directories. Which setup you need depends on how your server is configured.

### Scenario A — Project in a subdirectory (most common)

This covers WAMP/XAMPP locally, VPS with DocumentRoot at `/var/www/html/`, and shared hosting where you upload `stackvault/` into `public_html/`.

```
/var/www/html/            ← DocumentRoot
  stackvault/             ← project root
    .htaccess             ← included, handles everything
    public/
      index.php
    app/
    ...
```

The included `.htaccess` routes all requests to `public/index.php` and blocks `app/`, `config/`, `routes/`, `storage/`, and `resources/` at the URL level. **No additional `.htaccess` file is needed.**

---

### Scenario B — DocumentRoot pointed directly at `public/`

Some hosts (cPanel subdomains, Forge, Ploi) let you set the document root to the `public/` subfolder directly. In this layout:

```
/home/user/stackvault/    ← project root (above web root)
  .htaccess               ← NOT served (above document root)
  public/                 ← DocumentRoot
    index.php
  app/                    ← not web-accessible (above document root)
  .env                    ← not web-accessible (above document root)
```

Sensitive directories and `.env` are already protected because they are above the document root. You only need a minimal `.htaccess` inside `public/` to route requests to `index.php`. Create `public/.htaccess` with:

```apache
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Serve real files directly (assets, CSS, JS, images)
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    # Route everything else to front controller
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

> **cPanel tip:** In cPanel → Subdomains, set the document root to `public_html/stackvault/public` and create the `public/.htaccess` above.

---

### Verifying mod_rewrite is enabled

```bash
# Apache 2.4
sudo a2enmod rewrite
sudo systemctl restart apache2
```

On WAMP, enable `mod_rewrite` via the WAMP tray icon → Apache → Apache modules → rewrite_module.

---

## Security Notes

- **`.env` is never web-accessible** — protected by `.htaccess` and the rewrite rules.
- **Vault key** is stored encrypted in the database. The raw key only exists in the session during an unlocked session.
- **Passwords** use Argon2id (65536 KB memory, 4 iterations).
- **Login lockout** — 5 failed attempts triggers a 15-minute account lock.
- **CSRF** protection on every state-changing request.
- **Session** is httponly, SameSite=Lax, with configurable secure flag for HTTPS.
- **APP_DEBUG** must be `false` in production — never expose stack traces.

---

## Configuration (.env)

The `.env` file is generated automatically by `setup.php`. Key values:

```ini
APP_NAME=StackVault
APP_ENV=production
APP_DEBUG=false          # MUST be false in production
APP_URL=https://yourdomain.com/stackvault
APP_BASE_PATH=/stackvault
APP_INSTALLED=true

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=stackvault
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass

SESSION_LIFETIME=120     # Minutes of inactivity before logout
SESSION_SECURE=true      # Set true when running HTTPS
```

---

## Terminal Setup

The Terminal view embeds a live shell session alongside client reference data (docs, IP tables, servers, DNS records). It uses [ttyd](https://github.com/tsl0922/ttyd) as a WebSocket terminal backend and [xterm.js](https://xtermjs.org/) (stored locally in `assets/xterm/`) as the browser client.

The terminal is **disabled by default**. Set these values in `.env` to enable it:

```ini
TERMINAL_ENABLED=true
TERMINAL_WS_URL=wss://yourdomain.com/terminal/ws
```

### 1. Install ttyd on Ubuntu

```bash
# Option A — apt (Ubuntu 22.04+)
sudo apt update && sudo apt install -y ttyd

# Option B — download prebuilt binary
sudo wget -O /usr/local/bin/ttyd \
  https://github.com/tsl0922/ttyd/releases/latest/download/ttyd.x86_64
sudo chmod +x /usr/local/bin/ttyd
```

### 2. Run ttyd as a systemd service

Create `/etc/systemd/system/ttyd.service`:

```ini
[Unit]
Description=ttyd WebSocket terminal
After=network.target

[Service]
ExecStart=/usr/bin/ttyd --port 7681 --interface 127.0.0.1 bash
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ttyd
sudo systemctl restart ttyd
```

> `--interface 127.0.0.1` binds ttyd to localhost only. It is never exposed directly to the internet — traffic goes through the web server proxy below.

---

### 3. WebSocket proxy — Apache

Enable the required modules:

```bash
sudo a2enmod proxy proxy_http proxy_wstunnel
sudo systemctl restart apache2
```

Add to your VirtualHost (or to `.htaccess` if `AllowOverride All` is set):

```apache
# Proxy WebSocket requests to ttyd
ProxyPass        /stackvault/terminal/ws  ws://127.0.0.1:7681
ProxyPassReverse /stackvault/terminal/ws  ws://127.0.0.1:7681
```

Then set in `.env`:

```ini
TERMINAL_WS_URL=wss://yourdomain.com/stackvault/terminal/ws
```

---

### 3. WebSocket proxy — Nginx

Add to your `server {}` block:

```nginx
location /stackvault/terminal/ws {
    proxy_pass         http://127.0.0.1:7681;
    proxy_http_version 1.1;
    proxy_set_header   Upgrade    $http_upgrade;
    proxy_set_header   Connection "upgrade";
    proxy_set_header   Host       $host;
    proxy_read_timeout 3600s;
}
```

Then set in `.env`:

```ini
TERMINAL_WS_URL=wss://yourdomain.com/stackvault/terminal/ws
```

---

### Security notes

- ttyd is bound to `127.0.0.1` — only reachable via the web server proxy.
- Access to `/terminal` is protected by the app's `AuthMiddleware` (requires login).
- The WebSocket URL should always use `wss://` (TLS) in production.
- The shell runs as the `www-data` user (or whichever user you configure in the systemd unit). Limit permissions accordingly.

---

### Local development (WAMP / Windows)

ttyd is a Linux binary and does not run natively on Windows. Leave `TERMINAL_ENABLED=false` during local development — the terminal pane shows a configuration placeholder while the client info panel (docs, IP tables, servers, DNS) remains fully functional.

---

## M365 License Tracker

The M365 module tracks Microsoft 365 subscriptions per client, automates recurring billing period generation, and surfaces overdue payments on the dashboard.

### Features

- **License catalog** — Record plan name, license source (Vendor-provided / Self-registered), seat count, billing amount, and billing interval (Monthly / Quarterly / Custom days).
- **Recurring billing** — Billing periods are generated automatically each time the M365 index is loaded. Missed periods stack up so nothing is silently skipped.
- **Unpaid alerts** — Each unpaid period shows on the license detail page and in the dashboard widget until marked as paid or dismissed.
- **Dismiss / restore** — Dismiss a billing record to hide it from the dashboard; restore it if dismissed by mistake.
- **Dashboard widget** — Shows all due and overdue unpaid periods across every client. Mark paid or dismiss directly from the dashboard without navigating away.
- **Expiry tracking** — Optional license expiry date with colour-coded urgency (red = expired / within 7 days, orange = within 30 days).

### How billing periods work

When you create a license you set:

| Field | Description |
|---|---|
| **Billing interval** | `monthly`, `quarterly`, or `custom` |
| **Custom days** | (custom only) number of days per period |
| **Next billing date** | First date a period becomes due |
| **Remind days** | How many days before the due date it appears on the dashboard |

Each page load of `/m365` calls `autoGenerateDue()`, which:
1. Checks every active license whose `next_billing_date` is today or in the past.
2. Creates a billing record for the period (labeled e.g. "January 2026" or "Q1 2026").
3. Advances `next_billing_date` to the following period.
4. Repeats until `next_billing_date` is in the future (up to 36 iterations per license).

This means no cron job is required. Periods stack automatically if the app is not visited for several months.

### No database setup required

The two tables (`m365_licenses`, `m365_billing_records`) are created automatically via `ensureSchema()` on first visit to `/m365`. No manual SQL or migrations needed.

---

## Updating

StackVault has no migration system yet. For updates:

1. Back up your database.
2. Replace application files (keep your `.env` and `storage/` intact).
3. Visit any page — new tables are created automatically via `ensureSchema()` calls.

---

## License

See [LICENSE](LICENSE) for terms. Personal and non-commercial use only.
