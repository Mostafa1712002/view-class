<?php

// ==========================================
// Discussion Rooms module
// ==========================================

// Staff: manage rooms (super-admin, school-admin, teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/discussion-rooms')
    ->name('manage.discussion-rooms.')
    ->group(function () {
        Route::get('/', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'index'])->name('index');
        Route::get('create', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'store'])->name('store');
        Route::get('{id}/edit', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'edit'])->name('edit');
        Route::put('{id}', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'update'])->name('update');
        Route::delete('{id}', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'destroy'])->name('destroy');
        Route::post('{id}/close', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'close'])->name('close');

        // Topic moderation actions (staff only)
        Route::post('topics/{topicId}/pin', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'pinTopic'])->name('topics.pin');
        Route::post('topics/{topicId}/close', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'closeTopic'])->name('topics.close');
        Route::delete('topics/{topicId}', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'deleteTopic'])->name('topics.destroy');

        // Comment moderation action (staff only)
        Route::delete('comments/{commentId}', [\App\Modules\Discussion\Controllers\ManageDiscussionController::class, 'deleteComment'])->name('comments.destroy');
    });

// Members: read + participate (all authenticated users)
Route::middleware(['auth'])
    ->name('discussion.')
    ->group(function () {
        Route::get('discussion', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'index'])->name('index');
        Route::get('discussion/rooms/{roomId}', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'room'])->name('room');
        Route::get('discussion/rooms/{roomId}/topics/create', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'topicCreate'])->name('topic.create');
        Route::post('discussion/rooms/{roomId}/topics', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'topicStore'])->name('topic.store');
        Route::get('discussion/topics/{topicId}', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'topicShow'])->name('topic');
        Route::post('discussion/topics/{topicId}/comments', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'commentStore'])->name('comment.store');
        Route::delete('discussion/comments/{commentId}', [\App\Modules\Discussion\Controllers\DiscussionController::class, 'commentDestroy'])->name('comment.destroy');
    });
