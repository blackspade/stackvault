<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="<?= isset($_SERVER['HTTP_HOST']) ? '//' . $_SERVER['HTTP_HOST'] . rtrim(\App\Core\Config::get('APP_BASE_PATH', ''), '/') . '/assets/tabler/css/tabler.min.css' : '' ?>">
    <style>
        body { background: #f4f6fb; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; font-family: system-ui, sans-serif; }
        .error-box { text-align: center; padding: 2rem; max-width: 420px; }
        .error-code { font-size: 6rem; font-weight: 800; color: #206bc4; line-height: 1; }
        .error-title { font-size: 1.5rem; font-weight: 600; margin: .5rem 0; color: #1a1a2e; }
        .error-desc  { color: #6c757d; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <div class="error-desc">
            <?= htmlspecialchars($message ?? 'The page you are looking for does not exist.', ENT_QUOTES, 'UTF-8') ?>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-primary me-2">← Go Back</a>
        <a href="<?= rtrim(\App\Core\Config::get('APP_URL', '/'), '/') . '/dashboard' ?>"
           class="btn btn-primary">Dashboard</a>
    </div>
</body>
</html>
