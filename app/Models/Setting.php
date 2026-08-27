<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = Cache::get('app_settings');

        if (! is_array($settings)) {
            Cache::forget('app_settings');
            $settings = Cache::remember('app_settings', 60, function () {
                return static::query()->pluck('value', 'key')->all();
            });
        }

        if ($settings instanceof Collection) {
            return $settings->get($key, $default);
        }

        return is_array($settings) ? ($settings[$key] ?? $default) : $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app_settings');
    }
}
