<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server Error</title>
    <style>
        body { background: #1e1e2e; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; font-family: system-ui, sans-serif; }
        .error-box { text-align: center; padding: 2rem; max-width: 480px; color: #cdd6f4; }
        .error-code  { font-size: 6rem; font-weight: 800; color: #f38ba8; line-height: 1; }
        .error-title { font-size: 1.5rem; font-weight: 600; margin: .5rem 0; }
        .error-desc  { color: #9399b2; margin-bottom: 1.5rem; line-height: 1.6; }
        a { color: #89b4fa; }
        a:hover { color: #cba6f7; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">500</div>
        <div class="error-title">Internal Server Error</div>
        <div class="error-desc">
            Something went wrong on our end. The error has been logged.
            <?php if (!empty($message)): ?>
            <br><small style="font-size:.8rem;opacity:.7"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </div>
        <a href="javascript:history.back()">← Go Back</a>
    </div>
</body>
</html>
