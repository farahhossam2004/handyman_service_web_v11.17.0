<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLock extends Model
{
    protected $table = 'admin_action_locks';

    protected $fillable = [
        'lock_key', 'admin_id', 'action_type',
        'lockable_type', 'lockable_id',
        'reason', 'ip_address', 'user_agent', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function lockable()
    {
        return $this->morphTo();
    }
}
