<?php

namespace App\Tests\Integration\MessageHandler;

use App\Entity\Product;
use App\Message\ProductUpdated;
use App\MessageHandler\ProductUpdatedHandler;
use App\Repository\ProductRepository;
use App\Service\SnowflakeIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductUpdatedHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SnowflakeIdGenerator $snowflakeGenerator;
    private ProductUpdatedHandler $handler;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->snowflakeGenerator = $container->get(SnowflakeIdGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ProductUpdatedHandler(
            $this->entityManager,
            $this->logger,
            $this->snowflakeGenerator
        );
    }

    /**
     * Test handling a valid message
     */
    public function testInvokeWithValidMessage(): void
    {
        // Create a test product
        $product = new Product();
        $product->setName('Test Product for Message Handler');
        $product->setPrice('29.99');
        $product->setStock(10);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->getId();

        // Create a message
        $updatedAt = new \DateTime();
        $message = new ProductUpdated($productId, $updatedAt);

        // Configure logger expectations
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$this->equalTo('Processing product update'), $this->anything()],
                [$this->equalTo('Product update processed successfully'), $this->anything()]
            );

        // Handle the message
        $this->handler->__invoke($message);

        // Clean up
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    /**
     * Test handling a message for a non-existent product
     */
    public function testInvokeWithInvalidProductId(): void
    {
        // Generate a random ID that doesn't exist
        $nonExistentId = $this->snowflakeGenerator->nextId();

        // Create a message with invalid product ID
        $updatedAt = new \DateTime();
        $message = new ProductUpdated($nonExistentId, $updatedAt);

        // Configure logger expectations
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Processing product update'), $this->anything());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->equalTo('Product not found'), $this->anything());

        // Handle the message
        $this->handler->__invoke($message);
    }

    /**
     * Test extracting information from Snowflake ID
     */
    public function testSnowflakeIdExtraction(): void
    {
        // Generate a test Snowflake ID
        $id = $this->snowflakeGenerator->nextId();

        // Create a message
        $updatedAt = new \DateTime();
        $message = new ProductUpdated($id, $updatedAt);

        // Configure logger to capture the log data
        $logData = null;
        $this->logger->method('info')
            ->willReturnCallback(function ($message, $context) use (&$logData) {
                if ($message === 'Processing product update') {
                    $logData = $context;
                }
            });

        // We expect a warning since the product doesn't exist
        $this->logger->expects($this->once())
            ->method('warning');

        // Handle the message
        $this->handler->__invoke($message);

        // Verify Snowflake ID data was properly extracted
        $this->assertNotNull($logData);
        $this->assertArrayHasKey('product_id', $logData);
        $this->assertArrayHasKey('snowflake_created_at', $logData);
        $this->assertArrayHasKey('generating_node', $logData);

        // Verify node ID matches extraction
        $extractedNodeId = $this->snowflakeGenerator->extractNodeId($id);
        $this->assertEquals($extractedNodeId, $logData['generating_node']);
    }
}