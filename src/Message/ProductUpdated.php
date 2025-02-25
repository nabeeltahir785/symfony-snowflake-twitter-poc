<?php

namespace App\Message;

/**
 * Message for product updates
 *
 * This class represents a message that is sent when a product is updated.
 * It uses a Snowflake ID to identify the product.
 */
final class ProductUpdated
{
    private string $productId;
    private \DateTimeInterface $updatedAt;

    /**
     * Constructor
     */
    public function __construct(string $productId, \DateTimeInterface $updatedAt)
    {
        $this->productId = $productId;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get the product ID (Snowflake ID)
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * Get the updated timestamp
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}