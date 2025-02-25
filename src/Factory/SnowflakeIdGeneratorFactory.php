<?php

namespace App\Factory;

use App\Service\SnowflakeIdGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Factory for creating Snowflake ID Generator instances
 *
 * This factory allows configuring the node ID from environment variables or
 * container parameters, providing a centralized way to manage node IDs across
 * distributed systems.
 */
class SnowflakeIdGeneratorFactory
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * Constructor
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * Create a new Snowflake ID Generator with the configured node ID
     *
     * @return SnowflakeIdGenerator
     */
    public function create(): SnowflakeIdGenerator
    {
        // Get node ID from parameters (with fallback to environment variable)
        $nodeId = $this->parameterBag->get('app.snowflake.node_id');

        // If not configured, generate a stable node ID based on hostname
        if ($nodeId === null) {
            $nodeId = $this->generateNodeIdFromHostname();
        }

        return new SnowflakeIdGenerator($nodeId);
    }

    /**
     * Generate a stable node ID from the hostname
     *
     * This ensures that the same machine always gets the same node ID,
     * which is useful in containerized environments where explicit
     * configuration might be complex.
     *
     * @return int Node ID (0-1023)
     */
    private function generateNodeIdFromHostname(): int
    {
        $hostname = gethostname();
        return abs(crc32($hostname) % 1024); // Ensure it's between 0-1023
    }
}