<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Upload and attach a file to a model.
     *
     * @param UploadedFile $file
     * @param Model $model
     * @param string $directory
     * @return Attachment
     * @throws \Exception
     */
    public function uploadAttachment(UploadedFile $file, Model $model, string $directory = 'attachments'): Attachment
    {
        // Validate file
        $this->validateFile($file);

        // Store file
        $path = $file->store($directory, 'local');

        // Create attachment record using the polymorphic relationship
        return $model->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Validate file before upload.
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('File type not allowed. Allowed types: JPG, PNG, GIF, PDF');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of 10MB');
        }
    }

    /**
     * Delete an attachment.
     *
     * @param Attachment $attachment
     * @return bool
     */
    public function deleteAttachment(Attachment $attachment): bool
    {
        // Delete file from storage
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        // Delete database record
        return $attachment->delete();
    }

    /**
     * Get attachment download URL.
     *
     * @param Attachment $attachment
     * @return string
     */
    public function getDownloadUrl(Attachment $attachment): string
    {
        return route('attachments.download', ['attachment' => $attachment->id]);
    }

    /**
     * Get file from storage.
     *
     * @param Attachment $attachment
     * @return \Illuminate\Http\Response
     */
    public function downloadFile(Attachment $attachment)
    {
        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->file_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }
}
