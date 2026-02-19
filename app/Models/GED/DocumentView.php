<?php


namespace App\Models\GED;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentView extends Model
{
    use HasFactory;

    protected $table = 'ged_document_views';

    protected $fillable = [
        'user_id',
        'document_id',
        'document_version_id',
        'viewed_at',
        'duration_seconds',
        'ip_address',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }
}
