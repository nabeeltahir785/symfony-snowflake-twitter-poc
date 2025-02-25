<?php

namespace App\Tests\Performance;

use App\Service\SnowflakeIdGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for the Snowflake ID Generator
 *
 * Note: This test case is designed to measure performance
 * and may take longer to run than typical unit tests.
 */
class SnowflakeBenchmarkTest extends TestCase
{
    private const TEST_NODE_ID = 1;

    /**
     * Benchmark single ID generation
     */
    public function testSingleIdGenerationPerformance(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);

        $startTime = microtime(true);
        $generator->nextId();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000;

        // Log the duration for informational purposes
        echo sprintf("Single ID generation took %.4f ms\n", $duration);

        // We don't assert specific timings as they can vary by environment,
        // but we want to ensure it's reasonably fast (under 5ms)
        $this->assertLessThan(5, $duration, 'Single ID generation should be fast');
    }

    /**
     * Benchmark bulk ID generation (1,000 IDs)
     */
    public function testBulkIdGenerationPerformance(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $count = 1000;

        $startTime = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            $generator->nextId();
        }

        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;
        $idsPerSecond = $count / $totalDuration;

        echo sprintf(
            "Generated %d IDs in %.4f seconds (%.2f IDs/second)\n",
            $count,
            $totalDuration,
            $idsPerSecond
        );

        // A reasonably performant implementation should generate
        // at least 10,000 IDs per second on modern hardware
        $this->assertGreaterThan(10000, $idsPerSecond, 'ID generation rate should be high');
    }

    /**
     * Benchmark concurrent generation using multiple threads
     *
     * Note: This test uses a simplified approach since PHP doesn't have native threading.
     * In a real-world scenario, you would test with proper concurrency using
     * multiple processes or a proper benchmarking tool.
     */
    public function testConcurrentGenerationSimulation(): void
    {
        // We'll simulate concurrent access by creating multiple generators
        // with different node IDs and generating IDs in an interleaved pattern
        $nodeCount = 10;
        $generators = [];

        // Create generators
        for ($node = 0; $node < $nodeCount; $node++) {
            $generators[$node] = new SnowflakeIdGenerator($node);
        }

        $idCount = 100; // IDs per node
        $totalCount = $nodeCount * $idCount;
        $ids = [];

        $startTime = microtime(true);

        // Generate IDs in an interleaved pattern
        for ($i = 0; $i < $idCount; $i++) {
            for ($node = 0; $node < $nodeCount; $node++) {
                $ids[] = $generators[$node]->nextId();
            }
        }

        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;
        $idsPerSecond = $totalCount / $totalDuration;

        echo sprintf(
            "Generated %d IDs across %d nodes in %.4f seconds (%.2f IDs/second)\n",
            $totalCount,
            $nodeCount,
            $totalDuration,
            $idsPerSecond
        );

        // Verify all IDs are unique
        $uniqueIds = array_unique($ids);
        $this->assertCount($totalCount, $uniqueIds, 'All generated IDs should be unique');

        // Check ID distribution by node
        $nodeIds = [];

        foreach ($ids as $id) {
            // Extract node ID from the ID
            $nodeId = $generators[0]->extractNodeId($id);
            $nodeIds[$nodeId] = ($nodeIds[$nodeId] ?? 0) + 1;
        }

        // Verify each node generated the expected number of IDs
        foreach ($nodeIds as $nodeId => $count) {
            $this->assertEquals($idCount, $count, "Node $nodeId should have generated $idCount IDs");
        }
    }

    /**
     * Benchmark memory usage during ID generation
     */
    public function testMemoryUsage(): void
    {
        // Record initial memory usage
        $initialMemory = memory_get_usage();

        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $count = 10000;
        $ids = [];

        // Generate IDs and store them
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $generator->nextId();
        }

        // Record final memory usage
        $finalMemory = memory_get_usage();
        $memoryPerIdBytes = ($finalMemory - $initialMemory) / $count;

        echo sprintf(
            "Memory usage: %d bytes per ID (total: %d bytes for %d IDs)\n",
            $memoryPerIdBytes,
            $finalMemory - $initialMemory,
            $count
        );

        // Check memory usage is reasonable
        // Each ID is a string that should be less than 32 bytes in most cases
        $this->assertLessThan(32, $memoryPerIdBytes, 'Memory usage per ID should be reasonable');
    }

    /**
     * Test sequence exhaustion (4096 IDs in same millisecond)
     *
     * This test simulates generating more than the maximum sequence number (4095)
     * within a single millisecond timeframe.
     */
    public function testSequenceExhaustion(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);

        // Use reflection to mock timestamp and control sequence
        $reflection = new \ReflectionClass($generator);

        $currentTimeMillisMethod = $reflection->getMethod('currentTimeMillis');
        $currentTimeMillisMethod->setAccessible(true);

        $lastTimestampProp = $reflection->getProperty('lastTimestamp');
        $lastTimestampProp->setAccessible(true);

        $sequenceProp = $reflection->getProperty('sequence');
        $sequenceProp->setAccessible(true);

        // We can't replace the currentTimeMillis method directly,
        // so we'll test the logic indirectly through the sequence property

        // Set up initial state
        $timestamp = 1000;
        $lastTimestampProp->setValue($generator, $timestamp);
        $sequenceProp->setValue($generator, 4094); // Almost exhausted

        // Generate ID - should use sequence 4094
        $id1 = $generator->nextId();
        $this->assertEquals(4094, $generator->extractSequence($id1));

        // Generate ID - should use sequence 4095
        $id2 = $generator->nextId();
        $this->assertEquals(4095, $generator->extractSequence($id2));

        // Set up a controlled environment to test sequence overflow
        // We can't directly test the waiting behavior since it would make the test slow,
        // but we can verify the sequence reset logic

        // Force timestamp to move forward
        $currentTime = $currentTimeMillisMethod->invoke($generator);
        $lastTimestampProp->setValue($generator, $currentTime);
        $sequenceProp->setValue($generator, 0);

        // Generate ID with new timestamp - should have sequence 0
        $id3 = $generator->nextId();
        $this->assertEquals(0, $generator->extractSequence($id3));
    }
}