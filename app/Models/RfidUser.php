<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfidUser extends Model
{
    protected $fillable = ['name', 'position', 'uid', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    // "92 fd 36 b9" -> "92FD36B9" (selalu disimpan seragam)
    public static function normalizeUid(string $uid): string
    {
        return strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $uid));
    }

    protected function uid(): Attribute
    {
        return Attribute::make(set: fn (string $v) => self::normalizeUid($v));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }
}