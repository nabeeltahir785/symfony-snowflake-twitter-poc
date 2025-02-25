<?php

namespace App\MessageHandler;

use App\Entity\Product;
use App\Message\ProductUpdated;
use App\Service\SnowflakeIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Message handler for ProductUpdated messages
 *
 * This demonstrates how to use Snowflake IDs with asynchronous messaging.
 */
#[AsMessageHandler]
class ProductUpdatedHandler
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private SnowflakeIdGenerator $snowflakeGenerator;

    /**
     * Constructor
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        SnowflakeIdGenerator $snowflakeGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->snowflakeGenerator = $snowflakeGenerator;
    }

    /**
     * Handle the message
     */
    public function __invoke(ProductUpdated $message): void
    {
        $productId = $message->getProductId();

        // Log with Snowflake ID timestamp info
        $timestamp = $this->snowflakeGenerator->extractTimestamp($productId);
        $date = new \DateTime('@' . (int)($timestamp / 1000));
        $nodeId = $this->snowflakeGenerator->extractNodeId($productId);

        $this->logger->info('Processing product update', [
            'product_id' => $productId,
            'updated_at' => $message->getUpdatedAt()->format('Y-m-d H:i:s'),
            'snowflake_created_at' => $date->format('Y-m-d H:i:s.v'),
            'generating_node' => $nodeId,
        ]);

        // Find the product using the Snowflake ID
        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        if (!$product) {
            $this->logger->warning('Product not found', ['product_id' => $productId]);
            return;
        }

        // Process the product update
        // ... additional business logic here ...

        $this->logger->info('Product update processed successfully', [
            'product_id' => $productId,
            'product_name' => $product->getName(),
        ]);
    }
}