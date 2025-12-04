<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Expense;
use App\Services\AttachmentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    private AttachmentService $attachmentService;
    private NotificationService $notificationService;

    public function __construct(
        AttachmentService $attachmentService,
        NotificationService $notificationService
    ) {
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Store a new comment on an expense.
     */
    public function store(Request $request, Expense $expense)
    {
        $user = auth()->user();

        // Check if user is a member of the group
        if (!$expense->group->hasMember($user)) {
            abort(403, 'You must be a member of the group to comment');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $comment = Comment::create([
                'expense_id' => $expense->id,
                'user_id' => $user->id,
                'content' => $validated['content'],
            ]);

            // Handle attachment if provided
            if ($request->hasFile('attachment')) {
                $this->attachmentService->uploadAttachment(
                    $request->file('attachment'),
                    $comment,
                    'comments'
                );
            }

            // Notify expense participants (except commenter)
            $this->notificationService->notifyCommentAdded($expense, $user, $validated['content']);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'comment' => $comment->load('user', 'attachments'),
                ]);
            }

            return back()->with('success', 'Comment added successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to add comment: ' . $e->getMessage());
        }
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, Comment $comment)
    {
        $user = auth()->user();

        // Check authorization - only comment author can edit
        if ($comment->user_id !== $user->id) {
            abort(403, 'You can only edit your own comments');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        try {
            $comment->update([
                'content' => $validated['content'],
                'edited_at' => now(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'comment' => $comment,
                ]);
            }

            return back()->with('success', 'Comment updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to update comment: ' . $e->getMessage());
        }
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment)
    {
        $user = auth()->user();

        // Check authorization - comment author or group admin can delete
        $expense = $comment->expense;
        if ($comment->user_id !== $user->id && !$expense->group->isAdmin($user)) {
            abort(403, 'You can only delete your own comments or be a group admin');
        }

        try {
            // Delete attachments
            foreach ($comment->attachments as $attachment) {
                $this->attachmentService->deleteAttachment($attachment);
            }

            $comment->delete();

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'Comment deleted successfully!');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to delete comment: ' . $e->getMessage());
        }
    }

    /**
     * Get comments for an expense (AJAX).
     */
    public function getComments(Expense $expense)
    {
        $user = auth()->user();

        // Check if user is a member of the group
        if (!$expense->group->hasMember($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $comments = $expense->comments()
            ->with('user', 'attachments')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }
}
