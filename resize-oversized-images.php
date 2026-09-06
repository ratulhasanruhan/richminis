<?php
/**
 * ONE-TIME MAINTENANCE SCRIPT - DELETE THIS FILE WHEN YOU ARE DONE.
 *
 * Downscales images that were uploaded before AizUploadController learned to resize webp, which
 * left banners on disk at their original resolution (6000x3125 and similar). Those files are the
 * dominant cost of the homepage load: both the bytes on the wire and the decode time, which on a
 * mid-range phone is seconds per image regardless of connection speed.
 *
 * The file on disk is replaced in place, so every existing reference to it (settings that store an
 * upload id, product galleries, category banners) keeps working untouched. Only uploads.file_size
 * needs updating, and this does that.
 *
 * Usage - dry run first, it changes nothing and just reports:
 *   https://richminis.com/resize-oversized-images.php?token=TOKEN
 * Then, to actually rewrite the files:
 *   https://richminis.com/resize-oversized-images.php?token=TOKEN&apply=1
 *
 * Originals are copied to public/uploads/_original_backup/ before being overwritten, so this is
 * reversible: copy them back over public/uploads/all/ if a result looks wrong.
 */

// Random per-deploy token. Delete this whole file once you are done with it.
const RESIZE_TOKEN = 'gOMRcBAR9fKb4MvYmGMcthmP';

const MAX_DIMENSION = 1500;
const QUALITY       = 82;   // Slightly above the uploader's 75: these are large marketing banners.
const BATCH_SIZE    = 20;   // Keeps each run inside the host's max_execution_time.

/**
 * Bytes per pixel above which a lossy image is considered badly compressed and worth re-encoding
 * even if its dimensions are already fine.
 *
 * A webp or jpeg saved at a sane quality lands around 0.05-0.15 bytes/pixel. The banners on this
 * site measure 0.42-0.45, i.e. a near-lossless export: the 1344x844 mobile hero is 477KB when it
 * should be well under 120KB. Dimensions alone would not catch those, and that file is the mobile
 * LCP element, so it is the one that matters most.
 */
const MAX_BYTES_PER_PIXEL = 0.20;

// Only lossy formats are re-encoded on density alone. PNG is lossless, so a high bytes/pixel there
// is often legitimate (sharp edges, transparency) and re-encoding it saves little.
const DENSITY_FORMATS = ['jpg', 'jpeg', 'webp'];

if (!hash_equals(RESIZE_TOKEN, $_GET['token'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

$apply = ($_GET['apply'] ?? '') === '1';

// Past the token check, so this is a maintenance operator. Show errors rather than serving a
// blank 500 that gives them nothing to act on.
@ini_set('display_errors', '1');
error_reporting(E_ALL);

@ini_set('memory_limit', '512M');
@set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');

// Walk up until the Laravel root is found, so this works whether the file was dropped at the
// document root or inside public/.
$appBase = __DIR__;
while (!is_file($appBase . '/vendor/autoload.php') && dirname($appBase) !== $appBase) {
    $appBase = dirname($appBase);
}

if (!is_file($appBase . '/vendor/autoload.php')) {
    exit("Could not locate the Laravel installation from " . __DIR__ . "\n");
}

require $appBase . '/vendor/autoload.php';
$app = require_once $appBase . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Upload;
use Intervention\Image\Facades\Image;

/** Animated webp keeps an ANIM chunk in its RIFF header; GD would flatten it to one frame. */
function is_animated_webp($path)
{
    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return true;
    }
    $header = fread($handle, 64);
    fclose($handle);

    return $header === false || strpos($header, 'ANIM') !== false;
}

$backupDir = $appBase . '/public/uploads/_original_backup';
if ($apply && !is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

echo "Laravel root detected at: " . $appBase . "\n";
echo $apply
    ? "APPLYING changes (originals backed up to public/uploads/_original_backup/)\n\n"
    : "DRY RUN - nothing is being modified. Add &apply=1 to actually resize.\n\n";

$candidates = Upload::whereIn('extension', ['jpg', 'jpeg', 'png', 'webp'])->orderBy('id')->get();

$done = $skipped = 0;
$savedBytes = 0;
$remaining = 0;

foreach ($candidates as $upload) {
    $absolute = $appBase . '/public/' . $upload->file_name;

    if (!is_file($absolute)) {
        continue;
    }

    $info = @getimagesize($absolute);
    if ($info === false) {
        continue;
    }

    $bytes = filesize($absolute);
    $oversized = $info[0] > MAX_DIMENSION || $info[1] > MAX_DIMENSION;

    // Badly compressed but correctly sized: re-encode in place without changing dimensions.
    $pixels = max(1, $info[0] * $info[1]);
    $density = $bytes / $pixels;
    $bloated = in_array($upload->extension, DENSITY_FORMATS, true)
        && $density > MAX_BYTES_PER_PIXEL;

    if (!$oversized && !$bloated) {
        continue;
    }

    // A backup means this file was already processed on an earlier run. Skip it: re-encoding an
    // image that is already re-encoded only softens it further, and copying over the backup would
    // destroy the pristine original that makes this reversible. This matters because a re-encoded
    // photo can still sit above MAX_BYTES_PER_PIXEL and would otherwise be picked up forever.
    if (is_file($backupDir . '/' . basename($absolute))) {
        continue;
    }

    if ($upload->extension === 'webp' && is_animated_webp($absolute)) {
        echo sprintf("SKIP  (animated webp)  %s\n", $upload->file_name);
        $skipped++;
        continue;
    }

    // Stop queuing work once the batch is full, but keep counting so the report says how many runs
    // are still needed.
    if ($done >= BATCH_SIZE) {
        $remaining++;
        continue;
    }

    $before = $bytes;
    $reason = $oversized ? 'oversized' : 'bloated';

    if (!$apply) {
        echo sprintf(
            "WOULD FIX  %-52s  %5dx%-5d %6sKB  %.2f B/px  (%s)\n",
            $upload->file_name, $info[0], $info[1],
            round($before / 1024), $density, $reason
        );
        $done++;
        continue;
    }

    try {
        // Copy the original aside BEFORE the destructive write, so a bad result can be restored.
        if (!copy($absolute, $backupDir . '/' . basename($absolute))) {
            throw new RuntimeException('could not back up the original');
        }

        $img = Image::make($absolute);

        // Only scale down when it is actually too big. A merely bloated file keeps its dimensions
        // and is simply re-encoded, so nothing gets softened for no reason.
        if ($oversized) {
            if ($img->width() > $img->height()) {
                $img->resize(MAX_DIMENSION, null, function ($c) { $c->aspectRatio(); });
            } else {
                $img->resize(null, MAX_DIMENSION, function ($c) { $c->aspectRatio(); });
            }
        }

        $img->encode($upload->extension, QUALITY);
        $img->save($absolute);

        $newWidth = $img->width();
        $newHeight = $img->height();
        $img->destroy();

        clearstatcache(true, $absolute);
        $after = filesize($absolute);

        if ($after === 0) {
            throw new RuntimeException('wrote an empty file');
        }

        // Re-encoding is only ever worth it if the file actually got smaller. If it did not, put
        // the original back rather than trading image quality for nothing.
        if ($after >= $before) {
            copy($backupDir . '/' . basename($absolute), $absolute);
            clearstatcache(true, $absolute);
            echo sprintf("KEEP  %-52s  re-encode was no smaller, original restored\n", $upload->file_name);
            $skipped++;
            continue;
        }

        $upload->file_size = $after;
        $upload->save();

        $savedBytes += ($before - $after);
        $done++;

        echo sprintf(
            "OK    %-52s  %5dx%-5d %6sKB -> %-6sKB  (%s)\n",
            $upload->file_name,
            $newWidth, $newHeight,
            round($before / 1024), round($after / 1024), $reason
        );
    } catch (\Throwable $e) {
        // Put the original back so a failure never leaves a half-written image live on the site.
        $backup = $backupDir . '/' . basename($absolute);
        if (is_file($backup)) {
            @copy($backup, $absolute);
        }
        echo sprintf("FAIL  %s  (%s)\n", $upload->file_name, $e->getMessage());
        $skipped++;
    }
}

echo "\n----------------------------------------\n";
echo ($apply ? "Resized: " : "Would resize: ") . $done . "\n";
echo "Skipped/failed: " . $skipped . "\n";
if ($apply) {
    echo "Saved: " . round($savedBytes / 1048576, 2) . " MB\n";
}
if ($remaining > 0) {
    echo "Still oversized after this batch: " . $remaining . " - run this URL again to continue.\n";
} else {
    echo "Nothing left oversized. Delete this file now.\n";
}
