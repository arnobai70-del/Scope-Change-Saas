<?php

use App\Http\Controllers\ClientPortal\ApprovalController;
use App\Http\Middleware\NoIndex;
use Illuminate\Support\Facades\Route;

/*
| Public client approval pages: no login, opaque token, never indexed.
*/
Route::middleware([NoIndex::class])->prefix('c')->group(function () {
    Route::get('/{publicId}/reminders/mute', [ApprovalController::class, 'muteReminders'])
        ->middleware('throttle:client-portal')
        ->name('client.reminders.mute');

    Route::middleware('throttle:client-portal')->group(function () {
        Route::get('/{publicId}/{token}', [ApprovalController::class, 'show'])->name('client.show');
        Route::get('/{publicId}/{token}/receipt', [ApprovalController::class, 'receipt'])->name('client.receipt');
        Route::get('/{publicId}/{token}/pay', [ApprovalController::class, 'pay'])->name('client.pay');
    });

    Route::middleware('throttle:client-actions')->group(function () {
        Route::post('/{publicId}/{token}/approve', [ApprovalController::class, 'approve'])->name('client.approve');
        Route::post('/{publicId}/{token}/decline', [ApprovalController::class, 'decline'])->name('client.decline');
        Route::post('/{publicId}/{token}/comment', [ApprovalController::class, 'comment'])->name('client.comment');
        Route::post('/{publicId}/{token}/payment-sent', [ApprovalController::class, 'paymentSent'])->name('client.payment-sent');
    });
});
