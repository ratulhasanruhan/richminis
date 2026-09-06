<?php
/**
 * ONE-TIME MAINTENANCE SCRIPT - DELETE THIS FILE WHEN YOU ARE DONE.
 *
 * Put this in the SAME folder as index.php (the document root), then open it in a browser.
 *
 *   Report only, changes nothing:
 *     https://richminis.com/opcache-reset.php?token=TOKEN
 *
 *   Reset OPcache so newly uploaded .php files are actually used:
 *     https://richminis.com/opcache-reset.php?token=TOKEN&reset=1
 *
 *   Also clear Laravel's compiled Blade views and cached settings/translations:
 *     https://richminis.com/opcache-reset.php?token=TOKEN&reset=1&clear=1
 *
 * Deployment by copying files leaves PHP serving the OLD bytecode until OPcache is reset, which is
 * why an uploaded fix can look like it "did nothing".
 */

// Random per-deploy token. Delete this whole file once you are done with it.
const RESET_TOKEN = 'gOMRcBAR9fKb4MvYmGMcthmP';

if (!hash_equals(RESET_TOKEN, $_GET['token'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

// Past the token check, so this is a maintenance operator. Show errors rather than serving a
// blank 500 that gives them nothing to act on.
@ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$doReset = ($_GET['reset'] ?? '') === '1';
$doClear = ($_GET['clear'] ?? '') === '1';

// Walk up until the Laravel root is found, so this works whether the file was dropped at the
// document root or inside public/.
$appBase = __DIR__;
while (!is_file($appBase . '/vendor/autoload.php') && dirname($appBase) !== $appBase) {
    $appBase = dirname($appBase);
}

echo "PHP CONFIGURATION (what the server is actually using right now)\n";
echo str_repeat('-', 62) . "\n";
printf("%-26s %s\n", 'PHP version',         PHP_VERSION);
printf("%-26s %s\n", 'memory_limit',        ini_get('memory_limit'));
printf("%-26s %s\n", 'max_execution_time',  ini_get('max_execution_time'));
printf("%-26s %s\n", 'upload_max_filesize', ini_get('upload_max_filesize'));
printf("%-26s %s\n", 'post_max_size',       ini_get('post_max_size'));
printf("%-26s %s\n", 'max_file_uploads',    ini_get('max_file_uploads'));
printf("%-26s %s\n", 'GD extension',        extension_loaded('gd') ? 'loaded' : 'MISSING');
printf("%-26s %s\n", 'php.ini used',        php_ini_loaded_file() ?: 'none');

echo "\nOPCACHE\n";
echo str_repeat('-', 62) . "\n";

if (!function_exists('opcache_get_status')) {
    echo "OPcache is NOT available on this server.\n";
    echo "Nothing to reset - uploaded files take effect immediately.\n";
    exit;
}

$status = @opcache_get_status(false);

if ($status === false || empty($status['opcache_enabled'])) {
    echo "OPcache is installed but NOT enabled for this request.\n";
    echo "Uploaded files should take effect immediately.\n";
    exit;
}

$mem = $status['memory_usage'];
printf("%-26s %s\n", 'enabled', 'yes');
printf("%-26s %s\n", 'cached files', number_format($status['opcache_statistics']['num_cached_scripts']));
printf("%-26s %s MB used / %s MB free\n", 'memory',
    round($mem['used_memory'] / 1048576, 1),
    round($mem['free_memory'] / 1048576, 1)
);
printf("%-26s %s\n", 'validate_timestamps',
    ini_get('opcache.validate_timestamps') ? 'on (picks up changes by itself)' : 'OFF (a reset is required after every upload)'
);

if (!$doReset) {
    echo "\nAdd &reset=1 to the URL to actually reset it.\n";
    exit;
}

echo "\n" . (opcache_reset() ? "OPcache reset OK - the new code is now live.\n" : "opcache_reset() returned false.\n");

if (!$doClear) {
    echo "\n(Add &clear=1 as well to also clear Laravel's compiled views and cached settings.)\n";
    exit;
}

echo "\nLARAVEL CACHES\n";
echo str_repeat('-', 62) . "\n";

/**
 * Deletes files under one directory. Every path is resolved and re-checked against the directory
 * it must live in, so a surprising symlink cannot make this delete something elsewhere.
 */
function clear_directory($dir, $pattern = '*')
{
    $base = realpath($dir);
    if ($base === false) {
        return "not found";
    }

    $deleted = 0;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $real = realpath($item->getPathname());
        if ($real === false || strpos($real, $base) !== 0) {
            continue;
        }
        if ($item->isFile() && fnmatch($pattern, $item->getFilename())) {
            $deleted += @unlink($real) ? 1 : 0;
        }
    }

    return $deleted . " file(s) deleted";
}

// Compiled Blade templates - safe to delete, Laravel rebuilds them on the next request.
printf("%-34s %s\n", 'compiled views', clear_directory($appBase . '/storage/framework/views', '*.php'));

// Cached business settings and translations - rebuilt from the database on next use.
printf("%-34s %s\n", 'cached settings/translations', clear_directory($appBase . '/storage/framework/cache/data'));

echo "\nDone. Reload your site, check it looks right, then DELETE this file.\n";
