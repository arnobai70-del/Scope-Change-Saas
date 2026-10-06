<?php

use App\Http\Controllers\Webhooks\MailWebhookController;
use App\Http\Controllers\Webhooks\PaddleWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Provider webhooks. CSRF is disabled for /webhooks/* in bootstrap/app.php;
| each endpoint authenticates the sender itself.
*/
Route::prefix('webhooks')->middleware('throttle:webhooks')->group(function () {
    Route::post('/paddle', PaddleWebhookController::class)->name('webhooks.paddle');
    Route::post('/mail', MailWebhookController::class)->name('webhooks.mail');
});
