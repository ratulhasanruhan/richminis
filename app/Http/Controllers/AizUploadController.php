<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Response;
use Auth;
use Storage;
use Image;
use Log;
use enshrined\svgSanitize\Sanitizer;
use Str;

class AizUploadController extends Controller
{
    public function index(Request $request)
    {

        $all_uploads = (auth()->user()->user_type == 'seller') ? Upload::where('user_id', auth()->user()->id) : Upload::query();
        $search = null;
        $sort_by = null;

        if ($request->search != null) {
            $search = $request->search;
            $all_uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }

        $sort_by = $request->sort;
        switch ($request->sort) {
            case 'newest':
                $all_uploads->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $all_uploads->orderBy('created_at', 'asc');
                break;
            case 'smallest':
                $all_uploads->orderBy('file_size', 'asc');
                break;
            case 'largest':
                $all_uploads->orderBy('file_size', 'desc');
                break;
            default:
                $all_uploads->orderBy('created_at', 'desc');
                break;
        }

        $all_uploads = $all_uploads->paginate(60)->appends(request()->query());


        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.index', compact('all_uploads', 'search', 'sort_by'))
            : view('backend.uploaded_files.index', compact('all_uploads', 'search', 'sort_by'));
    }

    public function create()
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Data can not change in demo mode.'))->info();
            return back();
        }

        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.create')
            : view('backend.uploaded_files.create');
    }


    public function show_uploader(Request $request)
    {
        return view('uploader.aiz-uploader');
    }

    public function upload(Request $request)
    {
        $type = array(
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "webp" => "image",
            "gif" => "image",
            "mp4" => "video",
            "mpg" => "video",
            "mpeg" => "video",
            "webm" => "video",
            "ogg" => "video",
            "avi" => "video",
            "mov" => "video",
            "flv" => "video",
            "swf" => "video",
            "mkv" => "video",
            "wmv" => "video",
            "wma" => "audio",
            "aac" => "audio",
            "wav" => "audio",
            "mp3" => "audio",
            "zip" => "archive",
            "rar" => "archive",
            "7z" => "archive",
            "doc" => "document",
            "txt" => "document",
            "docx" => "document",
            "pdf" => "document",
            "csv" => "document",
            "xml" => "document",
            "ods" => "document",
            "xlr" => "document",
            "xls" => "document",
            "xlsx" => "document"
        );

        // A file bigger than upload_max_filesize/post_max_size never reaches hasFile(), and an
        // interrupted one arrives invalid. Both used to fall through and return an empty body,
        // which the uploader reported as a success that produced nothing.
        if (!$request->hasFile('aiz_file')) {
            return $this->upload_failed(translate('No file was received. It is most likely bigger than the upload limit of the server.'));
        }

        $file = $request->file('aiz_file');

        if (!$file->isValid()) {
            return $this->upload_failed($file->getErrorMessage());
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (
            env('DEMO_MODE') == 'On' &&
            isset($type[$extension]) &&
            $type[$extension] == 'archive'
        ) {
            return '{}';
        }

        if (!isset($type[$extension])) {
            return $this->upload_failed(translate('Unsupported file type') . ': .' . $extension);
        }

        $original_name = null;
        $arr = explode('.', $file->getClientOriginalName());
        for ($i = 0; $i < count($arr) - 1; $i++) {
            if ($i == 0) {
                $original_name .= $arr[$i];
            } else {
                $original_name .= "." . $arr[$i];
            }
        }

        if ($extension == 'svg') {
            $sanitizer = new Sanitizer();
            // Load the dirty svg
            $dirtySVG = file_get_contents($file->getRealPath());

            // Pass it to the sanitizer and get it back clean
            $cleanSVG = $sanitizer->sanitize($dirtySVG);

            // Load the clean svg
            file_put_contents($file->getRealPath(), $cleanSVG);
        }

        // Held onto because process_image() may swap the extension for the configured output format.
        $file_type = $type[$extension];

        // svg is not a bitmap, and webp/gif are kept as they are so animation and transparency survive.
        $reencode = $file_type == 'image' && !in_array($extension, ['svg', 'webp', 'gif']);

        if ($reencode) {
            try {
                $processed = $this->process_image($file, $extension);
                $path = $processed['path'];
                $size = $processed['size'];
                $extension = $processed['extension'];
            } catch (\Throwable $e) {
                // Re-encoding is an optimisation, not the upload itself. Keep the original file
                // rather than saving a database row that points at a file we never wrote.
                Log::warning('Image processing failed for "' . $original_name . '.' . $extension . '": ' . $e->getMessage());
                $path = $file->store('uploads/all', 'local');
                $size = $file->getSize();
            }
        } else {
            $path = $file->store('uploads/all', 'local');
            $size = $file->getSize();
        }

        if (empty($path)) {
            return $this->upload_failed(translate('The file could not be written. Please check the write permission of the uploads folder.'));
        }

        if (env('FILESYSTEM_DRIVER') != 'local') {
            // Return MIME type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            // Get the MIME type of the file
            $file_mime = finfo_file($finfo, base_path('public/') . $path);

            Storage::disk(env('FILESYSTEM_DRIVER'))->put(
                $path,
                file_get_contents(base_path('public/') . $path),
                [
                    'visibility' => 'public',
                    'ContentType' =>  $extension == 'svg' ? 'image/svg+xml' : $file_mime
                ]
            );

            if ($arr[0] != 'updates') {
                unlink(base_path('public/') . $path);
            }
        } elseif (!file_exists(base_path('public/') . $path)) {
            // Never register a file the media manager would not be able to serve.
            return $this->upload_failed(translate('The file could not be written. Please check the write permission of the uploads folder.'));
        }

        $upload = new Upload;
        $upload->file_original_name = $original_name;
        $upload->extension = $extension;
        $upload->file_name = $path;
        $upload->user_id = Auth::user()->id;
        $upload->type = $file_type;
        $upload->file_size = $size;
        $upload->save();

        return '{}';
    }

    /**
     * Re-encode, watermark and downscale a bitmap image.
     *
     * @throws \Throwable when the image cannot be written; the caller keeps the original file instead.
     */
    private function process_image($file, $extension)
    {
        if (get_setting('uploaded_image_format') != "default") {
            $extension = get_setting('uploaded_image_format');
        }

        $path = 'uploads/all/' . Str::random(40) . '.' . $extension;
        $absolute_path = base_path('public/') . $path;

        if (!is_dir(dirname($absolute_path))) {
            mkdir(dirname($absolute_path), 0755, true);
        }

        // GD holds the whole bitmap in memory. Exhausting memory_limit is a fatal error that no
        // catch block can recover from, so when it does not fit we skip processing altogether.
        if (!$this->image_fits_in_memory($file->getRealPath())) {
            throw new \RuntimeException('Not enough memory to process this image, storing the original instead.');
        }

        @set_time_limit(120);

        $img = Image::make($file->getRealPath())->encode($extension, 75);
        $height = $img->height();
        $width = $img->width();

        // watermark
        if (get_setting('use_image_watermark') == 'on') {
            $watermark_position = get_setting('watermark_position', 'top-left');
            // watermark Image
            if (get_setting('image_watermark_type') == "image") {
                $watermarkImg = Image::make(uploaded_asset(get_setting('watermark_image')));
                if ($width > $height) {
                    $wmarkHeight = $height / 2;
                    $watermarkImg->resize(null, $wmarkHeight, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                } else {
                    $wmarkWidth = $width / 2;
                    $watermarkImg->resize(null, $wmarkWidth, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
                $img->insert($watermarkImg, $watermark_position, 10, 10);

                // watermark Text
            } elseif (get_setting('image_watermark_type') == "text") {
                if ($watermark_position == 'center') {
                    $valign = 'middle';
                    $align = 'center';
                    $x = round($width / 2);
                    $y =  round($height / 2);
                } else {
                    $valign = explode('-', $watermark_position)[0];
                    $align = explode('-', $watermark_position)[1];
                    $x = ($align == 'right') ? ($width - 20) : 20;
                    $y =  ($valign == 'bottom') ? ($height - 20) : 20;
                }
                $img->text(get_setting('watermark_text', 'Watermark Text Here'), $x, $y, function ($font) use ($valign, $align) {
                    $font->file(base_path('public/assets/fonts/robotoMedium.ttf'));
                    $font->size(get_setting('watermark_text_size', 20));
                    $font->color(get_setting('watermark_text_color', '#e1e1e1'));
                    $font->align($align);
                    $font->valign($valign);
                });
            }
        }

        // Image optimization
        if (get_setting('disable_image_optimization') != 1) {
            if ($width > $height && $width > 1500) {
                $img->resize(1500, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } elseif ($height > 1500) {
                $img->resize(null, 1500, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        }

        $img->save($absolute_path);
        $img->destroy();
        clearstatcache(true, $absolute_path);

        if (!file_exists($absolute_path) || filesize($absolute_path) == 0) {
            throw new \RuntimeException('The re-encoded image was not written to ' . $path);
        }

        return [
            'path' => $path,
            'size' => filesize($absolute_path),
            'extension' => $extension,
        ];
    }

    /**
     * Estimate whether GD can decode this image within the remaining memory_limit,
     * raising the limit first when the host allows it.
     */
    private function image_fits_in_memory($absolute_path)
    {
        // Raising memory_limit past this is not worth it: the host would rather kill the process
        // than let a single upload take that much, and storing the original is a fine outcome.
        $ceiling = 768 * 1024 * 1024;

        $info = @getimagesize($absolute_path);

        if ($info === false) {
            return false;
        }

        $channels = isset($info['channels']) ? $info['channels'] : 4;
        $bits = isset($info['bits']) ? $info['bits'] : 8;

        // Source bitmap + the resized copy + encoder buffers, plus headroom for the framework.
        $needed = (int) ($info[0] * $info[1] * ($bits / 8) * $channels * 2.5) + (32 * 1024 * 1024);

        if ($this->memory_headroom() >= $needed) {
            return true;
        }

        $wanted = $needed + memory_get_usage(true);

        if ($wanted > $ceiling) {
            return false;
        }

        @ini_set('memory_limit', ((int) ceil($wanted / 1048576)) . 'M');

        return $this->memory_headroom() >= $needed;
    }

    /**
     * Bytes still available under memory_limit, or PHP_INT_MAX when the limit is unlimited.
     */
    private function memory_headroom()
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || (int) $limit < 0) {
            return PHP_INT_MAX;
        }

        $bytes = (int) $limit;
        switch (strtolower(substr($limit, -1))) {
            case 'g':
                $bytes *= 1024;
                // no break
            case 'm':
                $bytes *= 1024;
                // no break
            case 'k':
                $bytes *= 1024;
        }

        return max(0, $bytes - memory_get_usage(true));
    }

    /**
     * Uppy only surfaces a message to the user when the response is not a 2xx.
     */
    private function upload_failed($message)
    {
        return response()->json(['error' => $message], 422);
    }

    public function get_uploaded_files(Request $request)
    {
        $uploads = Upload::where('user_id', Auth::user()->id);
        if ($request->search != null) {
            $uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }
        if ($request->sort != null) {
            switch ($request->sort) {
                case 'newest':
                    $uploads->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $uploads->orderBy('created_at', 'asc');
                    break;
                case 'smallest':
                    $uploads->orderBy('file_size', 'asc');
                    break;
                case 'largest':
                    $uploads->orderBy('file_size', 'desc');
                    break;
                default:
                    $uploads->orderBy('created_at', 'desc');
                    break;
            }
        }
        return $uploads->paginate(60)->appends(request()->query());
    }

    public function destroy($id)
    {
        $upload = Upload::findOrFail($id);

        if (auth()->user()->user_type == 'seller' && $upload->user_id != auth()->user()->id) {
            flash(translate("You don't have permission for deleting this!"))->error();
            return back();
        }
        try {
            if (env('FILESYSTEM_DRIVER') != 'local') {
                Storage::disk(env('FILESYSTEM_DRIVER'))->delete($upload->file_name);
                if (file_exists(public_path() . '/' . $upload->file_name)) {
                    unlink(public_path() . '/' . $upload->file_name);
                }
            } else {
                unlink(public_path() . '/' . $upload->file_name);
            }
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        } catch (\Exception $e) {
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        }
        return back();
    }

    public function bulk_uploaded_files_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $file_id) {
                $this->destroy($file_id);
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function get_preview_files(Request $request)
    {
        $ids = explode(',', $request->ids);
        $files = Upload::whereIn('id', $ids)
            ->orderByRaw("FIELD(id, " . implode(',', $ids) . ")")
            ->get();
        $new_file_array = [];
        foreach ($files as $file) {
            $file['file_name'] = my_asset($file->file_name);
            if ($file->external_link) {
                $file['file_name'] = $file->external_link;
            }
            $new_file_array[] = $file;
        }
        // dd($new_file_array);
        return $new_file_array;
        // return $files;
    }

    public function all_file()
    {
        $uploads = Upload::all();
        foreach ($uploads as $upload) {
            try {
                if (env('FILESYSTEM_DRIVER') != 'local') {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($upload->file_name);
                    if (file_exists(public_path() . '/' . $upload->file_name)) {
                        unlink(public_path() . '/' . $upload->file_name);
                    }
                } else {
                    unlink(public_path() . '/' . $upload->file_name);
                }
                $upload->delete();
                flash(translate('File deleted successfully'))->success();
            } catch (\Exception $e) {
                $upload->delete();
                flash(translate('File deleted successfully'))->success();
            }
        }

        Upload::query()->truncate();

        return back();
    }

    //Download project attachment
    public function attachment_download($id)
    {
        $project_attachment = Upload::find($id);
        try {
            $file_path = public_path($project_attachment->file_name);
            return Response::download($file_path);
        } catch (\Exception $e) {
            flash(translate('File does not exist!'))->error();
            return back();
        }
    }
    //Download project attachment
    public function file_info(Request $request)
    {
        $file = Upload::findOrFail($request['id']);

        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.info', compact('file'))
            : view('backend.uploaded_files.info', compact('file'));
    }
}
