<?php

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SnowflakeInfoCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('app:snowflake:info');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test basic command execution
     */
    public function testExecute(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Snowflake ID Generator', $output);
        $this->assertStringContainsString('Generated Snowflake ID:', $output);
        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Timestamp', $output);
        $this->assertStringContainsString('Node ID', $output);
        $this->assertStringContainsString('Sequence', $output);
    }

    /**
     * Test generating multiple IDs
     */
    public function testGenerateMultipleIds(): void
    {
        $this->commandTester->execute([
            '--count' => 5,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Generated 5 Snowflake IDs', $output);

        // Should display 5 ID rows plus header row
        $rowCount = substr_count($output, '|') / 6; // Each row has 6 vertical bars
        $this->assertGreaterThanOrEqual(6, $rowCount); // Header + 5 data rows
    }

    /**
     * Test analyzing a specific ID
     */
    public function testAnalyzeId(): void
    {
        // First generate an ID
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        // Extract the ID from the output using regex
        preg_match('/\|\s+(\d+)\s+\|/', $output, $matches);
        $id = $matches[1];

        // Now analyze this ID
        $this->commandTester->execute([
            'id' => $id,
        ]);

        $analysisOutput = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Snowflake ID Analysis', $analysisOutput);
        $this->assertStringContainsString('ID: ' . $id, $analysisOutput);
        $this->assertStringContainsString('Component', $analysisOutput);
        $this->assertStringContainsString('Timestamp', $analysisOutput);
        $this->assertStringContainsString('Node ID', $analysisOutput);
        $this->assertStringContainsString('Sequence', $analysisOutput);
    }

    /**
     * Test benchmark option
     */
    public function testBenchmark(): void
    {
        $this->commandTester->execute([
            '--benchmark' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Snowflake ID Benchmark', $output);
        $this->assertStringContainsString('Generating', $output);
        $this->assertStringContainsString('Count', $output);
        $this->assertStringContainsString('Duration', $output);
        $this->assertStringContainsString('IDs/second', $output);
    }

    /**
     * Test invalid ID
     */
    public function testInvalidId(): void
    {
        $this->commandTester->execute([
            'id' => 'invalid',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Invalid Snowflake ID', $output);
    }
}