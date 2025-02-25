<?php

namespace App\Service;

/**
 * Snowflake ID Generator
 *
 * This implementation follows Twitter's Snowflake ID specification:
 * - 41 bits for timestamp (milliseconds since epoch or custom epoch)
 * - 10 bits for machine/node ID (configurable via constructor)
 * - 12 bits for sequence number (per-millisecond counter)
 *
 * The resulting ID is a 64-bit integer, returned as a string to avoid
 * integer overflow issues in PHP.
 */
class SnowflakeIdGenerator
{
    /**
     * Timestamp bits (41 bits)
     */
    private const TIMESTAMP_BITS = 41;

    /**
     * Node ID bits (10 bits)
     */
    private const NODE_ID_BITS = 10;

    /**
     * Sequence bits (12 bits)
     */
    private const SEQUENCE_BITS = 12;

    /**
     * Max node ID (2^10 - 1 = 1023)
     */
    private const MAX_NODE_ID = -1 ^ (-1 << self::NODE_ID_BITS);

    /**
     * Max sequence (2^12 - 1 = 4095)
     */
    private const MAX_SEQUENCE = -1 ^ (-1 << self::SEQUENCE_BITS);

    /**
     * Epoch timestamp (2020-01-01 00:00:00 UTC)
     * Custom epoch to maximize lifetime of the ID space
     */
    private const EPOCH = 1577836800000; // milliseconds

    /**
     * Timestamp left shift (22 bits)
     */
    private const TIMESTAMP_LEFT_SHIFT = self::NODE_ID_BITS + self::SEQUENCE_BITS;

    /**
     * Node ID left shift (12 bits)
     */
    private const NODE_ID_LEFT_SHIFT = self::SEQUENCE_BITS;

    /**
     * Node ID (0-1023)
     */
    private int $nodeId;

    /**
     * Sequence (0-4095)
     */
    private int $sequence = 0;

    /**
     * Last timestamp in milliseconds
     */
    private int $lastTimestamp = -1;

    /**
     * Constructor
     *
     * @param int $nodeId Node ID (0-1023)
     * @throws \InvalidArgumentException When node ID is invalid
     */
    public function __construct(int $nodeId)
    {
        if ($nodeId < 0 || $nodeId > self::MAX_NODE_ID) {
            throw new \InvalidArgumentException(
                sprintf('Node ID must be between 0 and %d', self::MAX_NODE_ID)
            );
        }

        $this->nodeId = $nodeId;
    }

    /**
     * Generate a new Snowflake ID
     *
     * @return string ID as a string to avoid integer overflow in PHP
     * @throws \RuntimeException When clock moves backwards
     */
    public function nextId(): string
    {
        $timestamp = $this->currentTimeMillis();

        // Clock moved backwards, refuse to generate ID
        if ($timestamp < $this->lastTimestamp) {
            throw new \RuntimeException(
                sprintf(
                    'Clock moved backwards. Refusing to generate ID for %d milliseconds',
                    $this->lastTimestamp - $timestamp
                )
            );
        }

        // Same millisecond, increment sequence
        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;

            // Sequence exhausted, wait until next millisecond
            if ($this->sequence === 0) {
                $timestamp = $this->waitNextMillis($this->lastTimestamp);
            }
        } else {
            // Different millisecond, reset sequence
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        // Shift and combine the 64-bit ID components
        // We use GMP for 64-bit integer arithmetic to avoid overflow
        $id = gmp_init($timestamp - self::EPOCH);
        $id = gmp_mul($id, gmp_pow(2, self::TIMESTAMP_LEFT_SHIFT));
        $id = gmp_add($id, gmp_mul($this->nodeId, gmp_pow(2, self::NODE_ID_LEFT_SHIFT)));
        $id = gmp_add($id, $this->sequence);

        return gmp_strval($id);
    }

    /**
     * Get current timestamp in milliseconds
     *
     * @return int Current timestamp in milliseconds
     */
    private function currentTimeMillis(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Wait until next millisecond
     *
     * @param int $lastTimestamp Last timestamp
     * @return int Next timestamp in milliseconds
     */
    private function waitNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->currentTimeMillis();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->currentTimeMillis();
        }
        return $timestamp;
    }

    /**
     * Extract timestamp from an ID
     *
     * @param string $id Snowflake ID
     * @return int Timestamp in milliseconds
     */
    public function extractTimestamp(string $id): int
    {
        $binaryId = gmp_init($id);
        $timestamp = gmp_div(
            $binaryId,
            gmp_pow(2, self::TIMESTAMP_LEFT_SHIFT)
        );

        return gmp_intval($timestamp) + self::EPOCH;
    }

    /**
     * Extract node ID from an ID
     *
     * @param string $id Snowflake ID
     * @return int Node ID
     */
    public function extractNodeId(string $id): int
    {
        $binaryId = gmp_init($id);
        $nodeId = gmp_div(
            gmp_mod(
                $binaryId,
                gmp_pow(2, self::TIMESTAMP_LEFT_SHIFT)
            ),
            gmp_pow(2, self::NODE_ID_LEFT_SHIFT)
        );

        return gmp_intval($nodeId);
    }

    /**
     * Extract sequence from an ID
     *
     * @param string $id Snowflake ID
     * @return int Sequence
     */
    public function extractSequence(string $id): int
    {
        $binaryId = gmp_init($id);
        $sequence = gmp_mod(
            $binaryId,
            gmp_pow(2, self::SEQUENCE_BITS)
        );

        return gmp_intval($sequence);
    }
}