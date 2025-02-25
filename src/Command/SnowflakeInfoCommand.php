<?php

namespace App\Command;

use App\Service\SnowflakeIdGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to generate and analyze Snowflake IDs
 */
class SnowflakeInfoCommand extends Command
{
    /**
     * Command name
     */
    protected static $defaultName = 'app:snowflake:info';

    /**
     * Command description
     */
    protected static $defaultDescription = 'Generate and analyze Snowflake IDs';

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
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Snowflake ID to analyze')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of IDs to generate', 1)
            ->addOption('benchmark', 'b', InputOption::VALUE_NONE, 'Run a benchmark to test ID generation speed')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $snowflakeId = $input->getArgument('id');
        $count = (int) $input->getOption('count');
        $benchmark = $input->getOption('benchmark');

        // Analyze existing ID
        if ($snowflakeId !== null) {
            $this->analyzeId($io, $snowflakeId);
            return Command::SUCCESS;
        }

        // Run benchmark
        if ($benchmark) {
            $this->runBenchmark($io);
            return Command::SUCCESS;
        }

        // Generate IDs
        $this->generateIds($io, $count);
        return Command::SUCCESS;
    }

    /**
     * Analyze a Snowflake ID
     *
     * @param SymfonyStyle $io
     * @param string $snowflakeId
     */
    private function analyzeId(SymfonyStyle $io, string $snowflakeId): void
    {
        $io->title('Snowflake ID Analysis');

        try {
            $timestamp = $this->generator->extractTimestamp($snowflakeId);
            $nodeId = $this->generator->extractNodeId($snowflakeId);
            $sequence = $this->generator->extractSequence($snowflakeId);

            $date = new \DateTime();
            $date->setTimestamp((int) ($timestamp / 1000));

            $io->section('ID: ' . $snowflakeId);
            $io->table(
                ['Component', 'Value', 'Info'],
                [
                    ['Timestamp', $timestamp, 'Generated at ' . $date->format('Y-m-d H:i:s.v')],
                    ['Node ID', $nodeId, 'Server/process identifier'],
                    ['Sequence', $sequence, 'Sequence number in the same millisecond'],
                ]
            );

            // Binary representation
            $binary = decbin((int) $snowflakeId);
            $io->section('Binary Representation');
            $io->writeln('Full ID (64 bits): ' . str_pad($binary, 64, '0', STR_PAD_LEFT));
        } catch (\Exception $e) {
            $io->error('Invalid Snowflake ID: ' . $e->getMessage());
        }
    }

    /**
     * Generate Snowflake IDs
     *
     * @param SymfonyStyle $io
     * @param int $count
     */
    private function generateIds(SymfonyStyle $io, int $count): void
    {
        $io->title('Snowflake ID Generator');

        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $id = $this->generator->nextId();
            $timestamp = $this->generator->extractTimestamp($id);
            $date = new \DateTime();
            $date->setTimestamp((int) ($timestamp / 1000));

            $ids[] = [
                $id,
                $date->format('Y-m-d H:i:s.v'),
                $this->generator->extractNodeId($id),
                $this->generator->extractSequence($id),
            ];

            // If generating many IDs, make sure we don't exhaust sequence numbers
            if ($count > 1000) {
                usleep(1000); // 1ms sleep
            }
        }

        $io->table(
            ['ID', 'Timestamp', 'Node ID', 'Sequence'],
            $ids
        );

        if ($count > 1) {
            $io->success(sprintf('Generated %d Snowflake IDs', $count));
        } else {
            $io->success('Generated Snowflake ID: ' . $ids[0][0]);
        }
    }

    /**
     * Run a benchmark
     *
     * @param SymfonyStyle $io
     */
    private function runBenchmark(SymfonyStyle $io): void
    {
        $io->title('Snowflake ID Benchmark');

        $counts = [1000, 10000, 100000];
        $results = [];

        foreach ($counts as $count) {
            $io->write(sprintf('Generating %d IDs... ', $count));

            $start = microtime(true);
            $ids = [];

            for ($i = 0; $i < $count; $i++) {
                $ids[] = $this->generator->nextId();
            }

            $end = microtime(true);
            $duration = $end - $start;
            $idsPerSecond = $count / $duration;

            $results[] = [
                $count,
                sprintf('%.2f ms', $duration * 1000),
                sprintf('%.2f', $idsPerSecond),
            ];

            $io->writeln('done!');
        }

        $io->section('Results');
        $io->table(
            ['Count', 'Duration', 'IDs/second'],
            $results
        );

        $io->success('Benchmark completed');
    }
}