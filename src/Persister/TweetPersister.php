<?php

namespace App\Persister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Tweet;
use App\Service\AsyncService;

class TweetPersister implements ContextAwareDataPersisterInterface
{
    private ContextAwareDataPersisterInterface $decoratedPersister;

    private AsyncService $asyncService;

    public function __construct(ContextAwareDataPersisterInterface $decoratedPersister, AsyncService $asyncService)
    {
        $this->decoratedPersister = $decoratedPersister;
        $this->asyncService = $asyncService;
    }

    public function supports($data, array $context = []): bool
    {
        return $this->decoratedPersister->supports($data, $context);
    }

    public function persist($data, array $context = [])
    {
        /** @var Tweet $result */
        $result = $this->decoratedPersister->persist($data, $context);

        $this->asyncService->publishToExchange(AsyncService::PUBLISH_TWEET, $result->toAMPQMessage());

        return $result;
    }

    public function remove($data, array $context = [])
    {
        return $this->decoratedPersister->remove($data, $context);
    }
}
