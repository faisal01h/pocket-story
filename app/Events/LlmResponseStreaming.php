<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LlmResponseStreaming implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The game session ID.
     */
    public int $sessionId;

    /**
     * The token/chunk of text being streamed.
     */
    public ?string $token;

    /**
     * Whether this is the final chunk.
     */
    public bool $done;

    /**
     * Additional metadata (token counts, etc.).
     */
    public ?array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $sessionId,
        ?string $token = null,
        bool $done = false,
        ?array $metadata = null
    ) {
        $this->sessionId = $sessionId;
        $this->token = $token;
        $this->done = $done;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("game-session.{$this->sessionId}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'token' => $this->token,
            'done' => $this->done,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'llm.response.streaming';
    }
}
