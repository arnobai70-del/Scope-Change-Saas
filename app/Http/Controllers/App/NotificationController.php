<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        $user = $this->user();
        $timezone = $this->workspace()->timezone;

        $notifications = $user->notifications()
            ->latest()
            ->paginate(30)
            ->through(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->copy()->setTimezone($timezone)->diffForHumans(),
            ]);

        return Inertia::render('notifications/Index', ['notifications' => $notifications]);
    }

    public function read(string $id): RedirectResponse
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        /** @var array{url?: string} $data */
        $data = $notification->data;
        $url = $data['url'] ?? null;

        return is_string($url) && (str_starts_with($url, url('/')) || str_starts_with($url, (string) config('app.url'))) ? redirect()->to($url) : back();
    }

    public function readAll(): RedirectResponse
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->toast('All caught up.');

        return back();
    }
}
