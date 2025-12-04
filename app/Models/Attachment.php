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
        return asset('storage/' . $this->file_path);
    }
}
