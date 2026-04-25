<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(str_replace('_', '-', app()->getLocale()), ENT_QUOTES, 'UTF-8'); ?>" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title inertia><?php echo htmlspecialchars(config('app.name', 'AmazePays'), ENT_QUOTES, 'UTF-8'); ?></title>

    <?php
        echo \Illuminate\Support\Facades\Vite::withEntryPoints([
            'resources/css/app.css',
            'resources/js/app.tsx',
        ]);
    ?>

    <?php echo \Inertia\Inertia::head(); ?>
</head>
<body class="h-full font-sans antialiased">
    <?php echo \Inertia\Inertia::app(); ?>
</body>
</html>

