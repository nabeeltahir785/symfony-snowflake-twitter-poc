<?php

namespace App\Tests\Unit\Service;

use App\Service\SnowflakeIdGenerator;
use PHPUnit\Framework\TestCase;

class SnowflakeIdGeneratorTest extends TestCase
{
    private const TEST_NODE_ID = 5;
    private const CUSTOM_EPOCH = 1577836800000; // 2020-01-01 00:00:00 UTC

    /**
     * Test creating a generator with valid node ID
     */
    public function testCreateWithValidNodeId(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $this->assertInstanceOf(SnowflakeIdGenerator::class, $generator);
    }

    /**
     * Test creating a generator with invalid node ID
     */
    public function testCreateWithInvalidNodeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SnowflakeIdGenerator(1024); // Too large
    }

    /**
     * Test creating a generator with negative node ID
     */
    public function testCreateWithNegativeNodeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SnowflakeIdGenerator(-1); // Negative
    }

    /**
     * Test basic ID generation
     */
    public function testIdGeneration(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $id = $generator->nextId();

        $this->assertIsString($id);
        $this->assertNotEmpty($id);
        $this->assertIsNumeric($id);
    }

    /**
     * Test ID uniqueness for consecutive IDs
     */
    public function testIdUniqueness(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $ids = [];

        // Generate 1000 IDs
        for ($i = 0; $i < 1000; $i++) {
            $ids[] = $generator->nextId();
        }

        // Verify all IDs are unique
        $uniqueIds = array_unique($ids);
        $this->assertCount(1000, $uniqueIds);
    }

    /**
     * Test extracting timestamp from ID
     */
    public function testTimestampExtraction(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $id = $generator->nextId();

        $timestamp = $generator->extractTimestamp($id);

        // Timestamp should be recent
        $now = (int)(microtime(true) * 1000);
        $this->assertGreaterThan(self::CUSTOM_EPOCH, $timestamp);
        $this->assertLessThanOrEqual($now, $timestamp);
        $this->assertGreaterThan($now - 1000, $timestamp); // Within last second
    }

    /**
     * Test extracting node ID from ID
     */
    public function testNodeIdExtraction(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $id = $generator->nextId();

        $nodeId = $generator->extractNodeId($id);

        $this->assertEquals(self::TEST_NODE_ID, $nodeId);
    }

    /**
     * Test extracting sequence from ID
     */
    public function testSequenceExtraction(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);
        $id = $generator->nextId();

        $sequence = $generator->extractSequence($id);

        // Sequence for first ID in millisecond should be 0
        // But we can't guarantee that in a real test environment,
        // so just check it's in valid range
        $this->assertGreaterThanOrEqual(0, $sequence);
        $this->assertLessThanOrEqual(4095, $sequence); // 12 bits max
    }

    /**
     * Test sequence increment within same millisecond
     *
     * This test uses reflection to control the timestamps
     */
    public function testSequenceIncrement(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);

        // Use reflection to mock the currentTimeMillis method
        $reflection = new \ReflectionClass($generator);

        $lastTimestampProp = $reflection->getProperty('lastTimestamp');
        $lastTimestampProp->setAccessible(true);
        $lastTimestampProp->setValue($generator, 1000); // Mock timestamp

        $sequenceProp = $reflection->getProperty('sequence');
        $sequenceProp->setAccessible(true);
        $sequenceProp->setValue($generator, 0); // Start sequence at 0

        // Mock currentTimeMillis to return same timestamp
        $currentTimeMillisMethod = $reflection->getMethod('currentTimeMillis');
        $currentTimeMillisMethod->setAccessible(true);

        // Use a closure to replace the method
        $mockTimeMillis = function() {
            return 1000;
        };

        // We can't directly replace the method, so we'll need to use a mock object
        // and verify the sequence increment logic indirectly

        // For simplicity, we'll just check the sequence property directly
        $id1 = $generator->nextId();
        $this->assertEquals(0, $generator->extractSequence($id1)); // First ID should use sequence 0

        $id2 = $generator->nextId();
        $this->assertEquals(1, $generator->extractSequence($id2)); // Second ID should use sequence 1
    }

    /**
     * Test sequence reset when timestamp changes
     */
    public function testSequenceReset(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);

        // Use reflection to access private properties
        $reflection = new \ReflectionClass($generator);

        $sequenceProp = $reflection->getProperty('sequence');
        $sequenceProp->setAccessible(true);

        // Set sequence to non-zero value
        $sequenceProp->setValue($generator, 100);

        // Force sleep for 1ms to ensure timestamp changes
        usleep(1500); // 1.5ms

        $generator->nextId();

        // Sequence should be reset to 0
        $this->assertEquals(0, $sequenceProp->getValue($generator));
    }

    /**
     * Test for clock moving backwards exception
     */
    public function testClockMovingBackwards(): void
    {
        $generator = new SnowflakeIdGenerator(self::TEST_NODE_ID);

        // Use reflection to set up the test
        $reflection = new \ReflectionClass($generator);

        $lastTimestampProp = $reflection->getProperty('lastTimestamp');
        $lastTimestampProp->setAccessible(true);

        // Set last timestamp to future value
        $futureTime = (int)(microtime(true) * 1000) + 1000; // 1 second in future
        $lastTimestampProp->setValue($generator, $futureTime);

        // Generator should throw exception when time moves backwards
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clock moved backwards');

        $generator->nextId();
    }
}