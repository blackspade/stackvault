# StackVault

**Self-hosted DevOps client and infrastructure manager.**

StackVault is a secure, single-admin web application for managing clients, servers, domains, credentials, databases, DNS records, email accounts, bookmarks, and files — all in one place. Credentials are encrypted with AES-256-GCM using a per-user vault key that never touches the database unencrypted.

---

## Features

- **Vault encryption** — AES-256-GCM with PBKDF2-derived keys. Credentials are unreadable without the vault password.
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

## Updating

StackVault has no migration system yet. For updates:

1. Back up your database.
2. Replace application files (keep your `.env` and `storage/` intact).
3. Visit any page — new tables are created automatically via `ensureSchema()` calls.

---

## License

See [LICENSE](LICENSE) for terms. Personal and non-commercial use only.
