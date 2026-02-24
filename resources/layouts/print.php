<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Export') ?> — <?= e($appName ?? 'StackVault') ?></title>

    <link rel="icon" type="image/x-icon"    href="<?= asset('icons/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('icons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('icons/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180"    href="<?= asset('icons/apple-touch-icon.png') ?>">

    <link rel="stylesheet" href="<?= asset('tabler/css/tabler.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('tabler/css/tabler-icons.min.css') ?>">

    <style>
        :root { --tblr-font-sans-serif: 'Inter', system-ui, -apple-system, sans-serif; }

        body {
            background: #fff;
            padding: 0;
            font-size: 13px;
        }

        .sv-print-header {
            border-bottom: 2px solid #206bc4;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .sv-print-header .brand {
            font-size: 1.1rem;
            font-weight: 700;
            color: #206bc4;
        }
        .sv-print-header .meta {
            font-size: 11px;
            color: #6c757d;
        }

        .sv-section { margin-bottom: 28px; }
        .sv-section-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #6c757d;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        table { font-size: 12px; }
        .font-monospace { font-family: 'SFMono-Regular', Consolas, monospace !important; }

        @media print {
            .sv-no-print { display: none !important; }
            body { padding: 0; }
            .page-break { page-break-before: always; }
            a { color: inherit; text-decoration: none; }
        }

        @media screen {
            body { padding: 24px 32px; max-width: 1100px; margin: 0 auto; }
        }
    </style>
</head>
<body>

<!-- Print toolbar (screen only) -->
<div class="sv-no-print d-flex justify-content-end mb-4 pb-3 border-bottom">
    <button onclick="window.print()" class="btn btn-sm btn-primary">
        <i class="ti ti-printer me-1"></i>Print / Save as PDF
    </button>
</div>

<?= $content ?>

<script src="<?= asset('tabler/js/tabler.min.js') ?>"></script>
</body>
</html>
