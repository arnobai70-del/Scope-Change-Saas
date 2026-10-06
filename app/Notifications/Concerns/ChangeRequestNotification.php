<?php

namespace App\Notifications\Concerns;

use App\Models\ChangeRequest;

abstract class ChangeRequestNotification extends AppNotification
{
    public function __construct(public ChangeRequest $changeRequest)
    {
        parent::__construct();
    }

    protected function workspaceId(): ?int
    {
        return $this->changeRequest->workspace_id;
    }

    protected function title(): string
    {
        return $this->changeRequest->currentRevision->title ?? $this->changeRequest->reference;
    }

    protected function appUrl(): string
    {
        return route('change-requests.show', $this->changeRequest);
    }

    /**
     * @return array<string, mixed>
     */
    protected function databasePayload(string $headline, string $kind): array
    {
        return [
            'kind' => $kind,
            'headline' => $headline,
            'reference' => $this->changeRequest->reference,
            'change_request_id' => $this->changeRequest->id,
            'url' => $this->appUrl(),
        ];
    }
}
