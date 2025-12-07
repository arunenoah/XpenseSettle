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
    ];

    private const MAX_UPLOAD_SIZE = 5 * 1024 * 1024; // 5MB (upload limit)
    private const MAX_STORED_SIZE = 50 * 1024; // 50KB (target compressed size)

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

        // Compress image to reduce file size
        $compressedContent = $this->compressImage($file);

        // Generate unique filename with timestamp and random component to handle duplicates
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = time();
        $randomPart = substr(md5(random_bytes(16)), 0, 8);
        $filename = $originalName . '_' . $timestamp . '_' . $randomPart . '.jpg';
        $path = $directory . '/' . $filename;

        // Store compressed image
        Storage::disk('local')->put($path, $compressedContent);

        // Create attachment record using the polymorphic relationship
        return $model->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($compressedContent),
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
            throw new \Exception('File type not allowed. Please upload PNG or JPEG images only.');
        }

        if ($file->getSize() > self::MAX_UPLOAD_SIZE) {
            throw new \Exception('File size exceeds maximum upload size of 5MB. Please use a smaller image.');
        }
    }

    /**
     * Compress image to reduce file size to approximately 50KB.
     *
     * @param UploadedFile $file
     * @return string Compressed image binary content
     * @throws \Exception
     */
    private function compressImage(UploadedFile $file): string
    {
        // Get temporary file path
        $tempPath = $file->getPathname();

        // Determine image type
        $imageInfo = @getimagesize($tempPath);
        if ($imageInfo === false) {
            throw new \Exception('Unable to process image. Please ensure it is a valid PNG or JPEG file.');
        }

        // Create image resource from file
        if ($file->getMimeType() === 'image/jpeg') {
            $image = @imagecreatefromjpeg($tempPath);
        } elseif ($file->getMimeType() === 'image/png') {
            $image = @imagecreatefrompng($tempPath);
        } else {
            throw new \Exception('Unsupported image format.');
        }

        if ($image === false) {
            throw new \Exception('Unable to read image file.');
        }

        // Start with 80% quality and reduce if needed
        $quality = 80;
        $compressed = ob_get_clean() ?: '';

        // Compress iteratively until we reach target size
        do {
            ob_start();
            imagejpeg($image, null, $quality);
            $compressed = ob_get_clean();

            // If file is still too large and quality is above 20%, reduce quality
            if (strlen($compressed) > self::MAX_STORED_SIZE && $quality > 20) {
                $quality -= 5;
            } else {
                break;
            }
        } while (strlen($compressed) > self::MAX_STORED_SIZE);

        // Destroy image resource
        imagedestroy($image);

        return $compressed;
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
