<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($title ?? 'Sign In') ?> — <?= e($appName) ?></title>

    <link rel="icon" type="image/x-icon"    href="<?= asset('icons/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('icons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('icons/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180"    href="<?= asset('icons/apple-touch-icon.png') ?>">

    <link rel="stylesheet" href="<?= asset('tabler/css/tabler.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('tabler/css/tabler-icons.min.css') ?>">

    <style>
        :root { --tblr-font-sans-serif: 'Inter', system-ui, -apple-system, sans-serif; }
        body   { background: #f4f6fb; }
        .auth-logo { font-size: 1.5rem; font-weight: 700; letter-spacing: -.5px; }
        .auth-logo span { color: var(--tblr-primary); }
        .vault-info {
            background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 1.25rem;
        }
        .vault-info p { color: #90b4d4; margin: 0; font-size: .825rem; line-height: 1.5; }
        .vault-info .title { color: #fff; font-weight: 600; font-size: .875rem; margin-bottom: 4px; }
        .pw-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                     background: none; border: none; cursor: pointer; color: #9aa0ac; padding: 0;
                     line-height: 1; }
        .pw-toggle:hover { color: var(--tblr-primary); }
        .input-group-text-icon { position: relative; }
    </style>
</head>
<body class="d-flex flex-column antialiased">

<div class="page page-center">
    <div class="container-tight py-4" style="max-width: 440px;">

        <!-- Branding -->
        <div class="text-center mb-4">
            <a href="<?= url('/') ?>" class="auth-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                     stroke-linejoin="round" class="me-2" style="color: var(--tblr-primary); vertical-align:middle">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Stack<span>Vault</span>
            </a>
        </div>

        <?= $content ?>

    </div>
</div>

<script src="<?= asset('tabler/js/tabler.min.js') ?>"></script>
<script>
// Toggle password visibility
document.querySelectorAll('[data-pw-toggle]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.dataset.pwToggle);
        if (target) {
            target.type = (target.type === 'password') ? 'text' : 'password';
            var icon = this.querySelector('i');
            if (icon) {
                icon.className = (target.type === 'password')
                    ? 'ti ti-eye' : 'ti ti-eye-off';
            }
        }
    });
});
</script>
</body>
</html>
