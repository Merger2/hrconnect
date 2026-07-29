<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollaborationWorkspaceUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly string $action,
        public readonly string $resource,
        public readonly int|string|null $resourceId = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('collaboration.company.'.$this->companyId);
    }

    public function broadcastAs(): string
    {
        return 'collaboration.updated';
    }

    /**
     * @return array<string, int|string|null>
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'action' => $this->action,
            'resource' => $this->resource,
            'resource_id' => $this->resourceId,
        ];
    }
}
