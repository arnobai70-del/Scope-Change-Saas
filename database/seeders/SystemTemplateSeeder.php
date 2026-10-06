<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Built-in change request templates available to every workspace.
 * Idempotent: safe to run on every deploy.
 */
class SystemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Extra page or screen' => [
                'title' => 'Add an extra page',
                'description' => 'Design and build one additional page, matching the existing layout and style.',
                'scope_reason' => 'The agreed scope covers the pages listed in the project baseline. This page was not included.',
            ],
            'Additional revision round' => [
                'title' => 'Additional revision round',
                'description' => 'One more round of revisions on the current deliverable, consolidated into a single list of feedback.',
                'scope_reason' => 'The agreed revision allowance for this deliverable has been used.',
            ],
            'New integration' => [
                'title' => 'Integrate a new service',
                'description' => 'Connect the project to an additional third-party service, including setup, testing and a short handover note.',
                'scope_reason' => 'Third-party integrations beyond those listed in the baseline were excluded.',
            ],
            'Content writing' => [
                'title' => 'Write content for new sections',
                'description' => 'Write and proofread copy for the requested sections. Client provides key facts; one revision included.',
                'scope_reason' => 'Content was to be supplied by the client per the agreed assumptions.',
            ],
            'Rush delivery' => [
                'title' => 'Expedited delivery',
                'description' => 'Re-prioritise the schedule to deliver earlier than the agreed date.',
                'scope_reason' => 'The agreed timeline did not include expedited delivery.',
            ],
        ];

        foreach ($templates as $name => $content) {
            Template::query()->updateOrCreate(
                ['workspace_id' => null, 'type' => 'change_request', 'name' => $name],
                ['content_json' => $content + ['terms_note' => null]],
            );
        }
    }
}
