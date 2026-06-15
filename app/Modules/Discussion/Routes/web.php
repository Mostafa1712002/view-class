<?php

// ==========================================
// Discussion Rooms module
// ==========================================
//
// Gating model (per design.md §1 + card #235):
//   - Manage (staff) routes are gated per-action with `permission:discussion.*`
//     via the CheckPermission middleware (canDo() → 403). Role middleware stays
//     as a coarse first gate; canDo() is the fine-grained, configurable one.
//   - Member routes are NOT gated on discussion.create/edit/delete: those are
//     room-MANAGEMENT permissions. Student/parent participation (post topic /
//     reply) is governed by room behaviour flags (allow_topics / allow_comments
//     / requires_approval) enforced in DiscussionController, so members are never
//     fail-closed out of a room they can see. Reading is gated on discussion.view.

use App\Modules\Discussion\Controllers\DiscussionController;
use App\Modules\Discussion\Controllers\ManageDiscussionController;

// Staff: manage rooms (super-admin, school-admin, teacher), permission-gated per action
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/discussion-rooms')
    ->name('manage.discussion-rooms.')
    ->group(function () {
        Route::get('/', [ManageDiscussionController::class, 'index'])
            ->middleware('permission:discussion.view')->name('index');
        Route::get('create', [ManageDiscussionController::class, 'create'])
            ->middleware('permission:discussion.create')->name('create');
        Route::post('/', [ManageDiscussionController::class, 'store'])
            ->middleware('permission:discussion.create')->name('store');
        Route::get('{id}/edit', [ManageDiscussionController::class, 'edit'])
            ->middleware('permission:discussion.edit')->name('edit');
        Route::put('{id}', [ManageDiscussionController::class, 'update'])
            ->middleware('permission:discussion.edit')->name('update');
        Route::delete('{id}', [ManageDiscussionController::class, 'destroy'])
            ->middleware('permission:discussion.delete')->name('destroy');
        Route::post('{id}/close', [ManageDiscussionController::class, 'close'])
            ->middleware('permission:discussion.edit')->name('close');
        Route::post('{id}/reopen', [ManageDiscussionController::class, 'reopen'])
            ->middleware('permission:discussion.edit')->name('reopen');
        Route::post('{id}/toggle-comments', [ManageDiscussionController::class, 'toggleRoomComments'])
            ->middleware('permission:discussion.toggle_comments')->name('toggle-comments');
        Route::get('{id}/report', [ManageDiscussionController::class, 'report'])
            ->middleware('permission:discussion.view')->name('report');

        // Topic moderation actions (staff only)
        Route::post('topics/{topicId}/pin', [ManageDiscussionController::class, 'pinTopic'])
            ->middleware('permission:discussion.edit')->name('topics.pin');
        Route::post('topics/{topicId}/close', [ManageDiscussionController::class, 'closeTopic'])
            ->middleware('permission:discussion.edit')->name('topics.close');
        Route::post('topics/{topicId}/toggle-comments', [ManageDiscussionController::class, 'toggleTopicComments'])
            ->middleware('permission:discussion.toggle_comments')->name('topics.toggle-comments');
        Route::post('topics/{topicId}/hide', [ManageDiscussionController::class, 'hideTopic'])
            ->middleware('permission:discussion.edit')->name('topics.hide');
        Route::delete('topics/{topicId}', [ManageDiscussionController::class, 'deleteTopic'])
            ->middleware('permission:discussion.delete')->name('topics.destroy');

        // Comment moderation action (staff only)
        Route::delete('comments/{commentId}', [ManageDiscussionController::class, 'deleteComment'])
            ->middleware('permission:discussion.delete')->name('comments.destroy');
    });

// Members: read + participate (all authenticated users; read gated on discussion.view)
Route::middleware(['auth'])
    ->name('discussion.')
    ->group(function () {
        Route::get('discussion', [DiscussionController::class, 'index'])
            ->middleware('permission:discussion.view')->name('index');
        Route::get('discussion/rooms/{roomId}', [DiscussionController::class, 'room'])
            ->middleware('permission:discussion.view')->name('room');
        Route::get('discussion/rooms/{roomId}/topics/create', [DiscussionController::class, 'topicCreate'])->name('topic.create');
        Route::post('discussion/rooms/{roomId}/topics', [DiscussionController::class, 'topicStore'])->name('topic.store');
        Route::get('discussion/topics/{topicId}', [DiscussionController::class, 'topicShow'])
            ->middleware('permission:discussion.view')->name('topic');
        Route::post('discussion/topics/{topicId}/comments', [DiscussionController::class, 'commentStore'])->name('comment.store');
        Route::delete('discussion/comments/{commentId}', [DiscussionController::class, 'commentDestroy'])->name('comment.destroy');
    });
