<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = ['rfid_user_id', 'uid', 'status', 'ip_address'];

    public function rfidUser(): BelongsTo
    {
        return $this->belongsTo(RfidUser::class);
    }
}
