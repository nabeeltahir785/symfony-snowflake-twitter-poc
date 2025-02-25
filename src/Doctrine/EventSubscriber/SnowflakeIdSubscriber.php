<?php

namespace App\Doctrine\EventSubscriber;

use App\Doctrine\SnowflakeIdInterface;
use App\Service\SnowflakeIdGenerator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Doctrine Event Subscriber for Auto-generating Snowflake IDs
 *
 * This subscriber automatically assigns Snowflake IDs to entities that
 * implement the SnowflakeIdInterface before they are persisted to the database.
 */
class SnowflakeIdSubscriber implements EventSubscriber
{
    /**
     * @var SnowflakeIdGenerator
     */
    private SnowflakeIdGenerator $generator;

    /**
     * Constructor
     *
     * @param SnowflakeIdGenerator $generator
     */
    public function __construct(SnowflakeIdGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    /**
     * Generate Snowflake ID before entity is persisted
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Check if entity uses Snowflake IDs
        if (!$entity instanceof SnowflakeIdInterface) {
            return;
        }

        // Skip if ID is already set
        if ($entity->getId() !== null) {
            return;
        }

        // Generate and set new Snowflake ID
        $entity->setId($this->generator->nextId());
    }
}