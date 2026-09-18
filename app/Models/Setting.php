<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const KEY_COURSE_PRICE_FULL = 'course_price_full';
    public const KEY_COURSE_PRICE_DAILY = 'course_price_daily';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, $default = null)
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue(string $key, string $value): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");

        return $setting;
    }

    public static function coursePriceFull(): float
    {
        return (float) static::getValue(self::KEY_COURSE_PRICE_FULL, 100);
    }

    public static function coursePriceDaily(): float
    {
        return (float) static::getValue(self::KEY_COURSE_PRICE_DAILY, 4);
    }
}
