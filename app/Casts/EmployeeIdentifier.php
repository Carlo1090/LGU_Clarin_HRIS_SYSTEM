<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/** @implements CastsAttributes<string, string> */
class EmployeeIdentifier implements CastsAttributes
{
    public const PREFIX = 'hris-encrypted:v1:';

    public static function encrypt(string $value): string
    {
        return self::PREFIX.Crypt::encryptString($value);
    }

    public static function decrypt(string $value): string
    {
        return str_starts_with($value, self::PREFIX)
            ? Crypt::decryptString(substr($value, strlen(self::PREFIX)))
            : $value;
    }

    public static function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_strlen($value) <= 4 ? '••••' : '••••'.mb_substr($value, -4);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : self::decrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($model->getConnection()->getSchemaBuilder()->getColumnType($model->getTable(), $key) !== 'text') {
            throw ValidationException::withMessages([$key => 'Identifier storage needs the approved schema upgrade before saving this value.']);
        }

        return self::encrypt($value);
    }
}
