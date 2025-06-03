<?php

namespace App\Service;

use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function publish(string $topic, array $data, array $target = []): void
    {
        $update = new Update(
            $topic,
            json_encode(['data' => $data, 'type' => 'message']),
        );

        $this->hub->publish($update);
    }

    public function publishChatMessage(int $chatId, array $messageData): void
    {
        $this->publish("chat/{$chatId}", $messageData);
    }


}