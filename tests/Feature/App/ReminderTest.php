<?php

namespace Tests\Feature\App;

use App\Models\ReminderLog;
use App\Notifications\ClientReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use BuildsWorkspaces;
    use RefreshDatabase;

    public function test_first_reminder_is_sent_after_24_hours_once(): void
    {
        Notification::fake();
        [$cr] = $this->sent($this->project($this->workspace()));

        $this->travel(25)->hours();
        $this->artisan('change-requests:remind')->assertSuccessful();
        $this->artisan('change-requests:remind')->assertSuccessful();

        Notification::assertSentOnDemandTimes(ClientReminder::class, 1);
        $this->assertSame(['first'], ReminderLog::query()->where('change_request_id', $cr->id)->pluck('kind')->all());
    }

    public function test_reminders_stop_after_a_decision(): void
    {
        Notification::fake();
        [$cr, $token] = $this->sent($this->project($this->workspace()));
        $this->post($this->clientUrl($cr, $token, '/decline'), [
            'revision_id' => $cr->current_revision_id,
            'client_name' => 'Casey',
            'client_email' => 'casey@example.com',
        ]);

        $this->travel(4)->days();
        $this->artisan('change-requests:remind')->assertSuccessful();

        Notification::assertSentOnDemandTimes(ClientReminder::class, 0);
    }

    public function test_client_can_mute_reminders_with_signed_link(): void
    {
        Notification::fake();
        [$cr] = $this->sent($this->project($this->workspace()));

        $this->get('/c/'.$cr->public_id.'/reminders/mute')->assertForbidden();

        $url = URL::temporarySignedRoute('client.reminders.mute', now()->addDay(), ['publicId' => $cr->public_id]);
        $this->get($url)->assertOk()->assertSee('Reminders stopped');
        $this->assertNotNull($cr->refresh()->reminders_muted_at);

        $this->travel(2)->days();
        $this->artisan('change-requests:remind')->assertSuccessful();
        Notification::assertSentOnDemandTimes(ClientReminder::class, 0);
    }

    public function test_free_plan_has_no_reminders(): void
    {
        Notification::fake();
        $this->sent($this->project($this->workspace(['trial_ends_at' => null])));

        $this->travel(2)->days();
        $this->artisan('change-requests:remind')->assertSuccessful();

        Notification::assertSentOnDemandTimes(ClientReminder::class, 0);
    }
}
