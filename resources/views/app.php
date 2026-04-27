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
        ])->toHtml();
    ?>

    <?php
        // Inertia head + app root rendering (without Blade directives).
        // Mirrors inertia-laravel's @inertiaHead and @inertia directives.
        if (! isset($__inertiaSsrDispatched)) {
            $__inertiaSsrDispatched = true;
            $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page);
        }

        if ($__inertiaSsrResponse) {
            echo $__inertiaSsrResponse->head;
        }
    ?>
</head>
<body class="h-full font-sans antialiased">
    <?php
        if ($__inertiaSsrResponse) {
            echo $__inertiaSsrResponse->body;
        } elseif (config('inertia.use_script_element_for_initial_page')) {
            ?>
            <script data-page="app" type="application/json"><?php echo json_encode($page, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
            <div id="app"></div>
            <?php
        } else {
            ?>
            <div id="app" data-page="<?php echo htmlspecialchars(json_encode($page, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></div>
            <?php
        }
    ?>
</body>
</html>

