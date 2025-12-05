<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    protected $fillable = ['file_path', 'file_name', 'mime_type', 'file_size'];

    /**
     * Get the owning attachable model.
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full file URL.
     */
    public function getUrlAttribute()
    {
        return route('attachments.download', ['attachment' => $this->id]);
    }

    /**
     * Get file size in KB.
     */
    public function getFileSizeKbAttribute()
    {
        return round($this->file_size / 1024, 2);
    }
}
