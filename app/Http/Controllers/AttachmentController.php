<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Comment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    private AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Upload attachment to expense, payment, or comment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'attachable_type' => 'required|in:expense,payment,comment',
            'attachable_id' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            // Get the attachable model
            $attachable = $this->getAttachableModel(
                $validated['attachable_type'],
                $validated['attachable_id']
            );

            // Check authorization
            $this->authorizeAttachment($attachable);

            // Upload attachment
            $attachment = $this->attachmentService->uploadAttachment(
                $request->file('file'),
                $attachable,
                $validated['attachable_type'] . 's'
            );

            // Add description if provided
            if (isset($validated['description'])) {
                $attachment->update(['description' => $validated['description']]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'attachment' => $attachment,
                    'preview_url' => $this->getPreviewUrl($attachment),
                ]);
            }

            return back()->with('success', 'Attachment uploaded successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to upload attachment: ' . $e->getMessage());
        }
    }

    /**
     * Display attachment (inline or download).
     */
    public function show(Attachment $attachment, Request $request)
    {
        // Load attachable relationship if it exists
        $attachable = $attachment->attachable;

        // If attachable doesn't exist, user may not have permission
        if (!$attachable) {
            abort(404, 'Associated resource not found');
        }

        // Check authorization
        $this->authorizeAttachment($attachable);

        $inline = $request->get('inline', false);

        try {
            // Verify file exists before attempting to serve it
            if (!Storage::disk('local')->exists($attachment->file_path)) {
                abort(404, 'File not found in storage');
            }

            if ($inline && $this->isImage($attachment)) {
                // Display image inline - return file with proper headers
                $fullPath = Storage::disk('local')->path($attachment->file_path);
                $content = file_get_contents($fullPath);

                return response($content, 200)
                    ->header('Content-Type', $attachment->mime_type)
                    ->header('Content-Length', strlen($content))
                    ->header('Cache-Control', 'public, max-age=31536000')
                    ->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            }

            // Download file
            return $this->attachmentService->downloadFile($attachment);
        } catch (\Exception $e) {
            \Log::error('Attachment retrieval error', [
                'attachment_id' => $attachment->id,
                'file_path' => $attachment->file_path,
                'error' => $e->getMessage(),
            ]);
            abort(404, 'File not found');
        }
    }

    /**
     * Download attachment.
     */
    public function download(Attachment $attachment)
    {
        // Load attachable relationship if it exists
        $attachable = $attachment->attachable;

        // If attachable doesn't exist, user may not have permission
        if (!$attachable) {
            abort(404, 'Associated resource not found');
        }

        // Check authorization
        $this->authorizeAttachment($attachable);

        try {
            // Verify file exists before attempting to serve it
            if (!Storage::disk('local')->exists($attachment->file_path)) {
                abort(404, 'File not found in storage');
            }

            return $this->attachmentService->downloadFile($attachment);
        } catch (\Exception $e) {
            \Log::error('Attachment download error', [
                'attachment_id' => $attachment->id,
                'file_path' => $attachment->file_path,
                'error' => $e->getMessage(),
            ]);
            abort(404, 'File not found');
        }
    }

    /**
     * Delete attachment.
     */
    public function destroy(Attachment $attachment)
    {
        $user = auth()->user();
        $attachable = $attachment->attachable;

        // Check authorization
        $canDelete = false;

        if ($attachable instanceof Expense) {
            $canDelete = $attachable->payer_id === $user->id || $attachable->group->isAdmin($user);
        } elseif ($attachable instanceof Payment) {
            $canDelete = $attachable->paid_by === $user->id ||
                $attachable->split->expense->payer_id === $user->id ||
                $attachable->split->expense->group->isAdmin($user);
        } elseif ($attachable instanceof Comment) {
            $canDelete = $attachable->user_id === $user->id || $attachable->expense->group->isAdmin($user);
        }

        if (!$canDelete) {
            abort(403, 'You are not authorized to delete this attachment');
        }

        try {
            $this->attachmentService->deleteAttachment($attachment);

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'Attachment deleted successfully!');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete attachment: ' . $e->getMessage());
        }
    }

    /**
     * Get all attachments for an entity (AJAX).
     */
    public function getAttachments(Request $request)
    {
        $validated = $request->validate([
            'attachable_type' => 'required|in:expense,payment,comment',
            'attachable_id' => 'required|integer',
        ]);

        try {
            $attachable = $this->getAttachableModel(
                $validated['attachable_type'],
                $validated['attachable_id']
            );

            // Check authorization
            $this->authorizeAttachment($attachable);

            $attachments = $attachable->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                    'description' => $attachment->description,
                    'created_at' => $attachment->created_at->format('Y-m-d H:i:s'),
                    'download_url' => route('attachments.download', $attachment),
                    'preview_url' => $this->getPreviewUrl($attachment),
                    'is_image' => $this->isImage($attachment),
                ];
            });

            return response()->json([
                'success' => true,
                'attachments' => $attachments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update attachment description.
     */
    public function updateDescription(Request $request, Attachment $attachment)
    {
        // Check authorization
        $this->authorizeAttachment($attachment->attachable);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        try {
            $attachment->update(['description' => $validated['description']]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'attachment' => $attachment,
                ]);
            }

            return back()->with('success', 'Description updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to update description: ' . $e->getMessage());
        }
    }

    /**
     * Get attachable model instance.
     */
    private function getAttachableModel(string $type, int $id)
    {
        switch ($type) {
            case 'expense':
                return Expense::findOrFail($id);
            case 'payment':
                return Payment::findOrFail($id);
            case 'comment':
                return Comment::findOrFail($id);
            default:
                abort(400, 'Invalid attachable type');
        }
    }

    /**
     * Check if user is authorized to access attachment.
     */
    private function authorizeAttachment($attachable): void
    {
        $user = auth()->user();

        if ($attachable instanceof Expense) {
            if (!$attachable->group->hasMember($user)) {
                abort(403, 'You must be a member of the group to access this attachment');
            }
        } elseif ($attachable instanceof Payment) {
            $expense = $attachable->split->expense;
            if (!$expense->group->hasMember($user)) {
                abort(403, 'You must be a member of the group to access this attachment');
            }
        } elseif ($attachable instanceof Comment) {
            if (!$attachable->expense->group->hasMember($user)) {
                abort(403, 'You must be a member of the group to access this attachment');
            }
        }
    }

    /**
     * Check if attachment is an image.
     */
    private function isImage(Attachment $attachment): bool
    {
        return in_array($attachment->mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }

    /**
     * Get preview URL for attachment.
     */
    private function getPreviewUrl(Attachment $attachment): ?string
    {
        if ($this->isImage($attachment)) {
            return route('attachments.show', ['attachment' => $attachment, 'inline' => true]);
        }

        return null;
    }
}
