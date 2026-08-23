<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasAiTranslations;

    protected $with = ['contentTranslations'];

    protected $fillable = ['key', 'value', 'type', 'group'];

    public const TRANSLATABLE_KEYS = [
        'site_name',
        'site_tagline',
        'site_address',
        'footer_text',
    ];

    public function translatableFields(): array
    {
        return ['value'];
    }

    public function shouldTranslateAutomatically(): bool
    {
        return in_array($this->key, self::TRANSLATABLE_KEYS, true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function set(
        string $key,
        mixed $value,
        ?string $type = null,
        ?string $group = null
    ): void {
        $setting = static::query()->firstOrNew(['key' => $key]);
        $setting->value = $value;

        if ($type !== null) {
            $setting->type = $type;
        } elseif (! $setting->exists && blank($setting->type)) {
            $setting->type = 'text';
        }

        if ($group !== null) {
            $setting->group = $group;
        }

        $setting->save();
    }
}
