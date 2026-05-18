<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigationActivity extends Model
{
    use HasFactory;

    protected $table = 'investigation_activities';

    protected $fillable = [
        'investigation_id',
        'user_id',
        'action',
        'description',
        'attachment_path',
    ];

    public function investigation()
    {
        return $this->belongsTo(InvestigationLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
