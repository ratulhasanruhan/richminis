<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use Cache;

class BusinessSetting extends Model
{
    use PreventDemoModeChanges;

    /**
     * The settings collection for the current request.
     *
     * get_setting() is called around 90 times while rendering one storefront page. Without this,
     * each of those calls hits the cache store, and with CACHE_DRIVER=file that means reading a
     * file and unserialising this whole collection of models every time.
     */
    protected static $memo = null;

    /**
     * Settings arranged for lookup by key, built once per request.
     *
     * Scanning the collection with ->where('type', $key)->first() is O(n) over ~200 Eloquent
     * models, and get_setting() does it ~90 times per page. Two plain arrays turn that into a
     * hash lookup.
     */
    protected static $index = null;

    protected static function booted()
    {
        // Nothing invalidated this cache before, and it is stored with a 24 hour TTL, so a setting
        // changed in the admin panel could keep serving its old value on the storefront for the
        // rest of the day. Clearing on write also keeps the per-request memo above honest when a
        // save and a read happen inside the same request.
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache()
    {
        static::$memo = null;
        static::$index = null;
        Cache::forget('business_settings');
    }

    /**
     * ['first' => [type => value], 'by_lang' => [type => [lang => value]]].
     *
     * 'first' mirrors what ->where('type', $key)->first() used to return, so a row whose value is
     * genuinely null still resolves to null rather than falling through to the caller's default.
     */
    public static function indexed()
    {
        if (static::$index === null) {
            $first = [];
            $byLang = [];

            foreach (static::cached() as $setting) {
                $type = $setting->type;

                if (!array_key_exists($type, $first)) {
                    $first[$type] = $setting->value;
                }

                // Keep the earliest row for a given type+lang, matching ->first() on a filtered
                // collection when duplicates exist.
                $lang = (string) $setting->lang;
                if (!isset($byLang[$type]) || !array_key_exists($lang, $byLang[$type])) {
                    $byLang[$type][$lang] = $setting->value;
                }
            }

            static::$index = ['first' => $first, 'by_lang' => $byLang];
        }

        return static::$index;
    }

    /**
     * All settings, resolved at most once per request.
     */
    public static function cached()
    {
        if (static::$memo === null) {
            static::$memo = Cache::remember('business_settings', 86400, function () {
                return static::all();
            });
        }

        return static::$memo;
    }
}
