<?php

namespace App\Command;

use App\Embedding\EmbeddingProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:similarity', description: 'Vergelijk twee teksten')]
class SimilarityCommand extends Command
{
    public function __construct(private EmbeddingProviderInterface $embedder)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('a', InputArgument::REQUIRED);
        $this->addArgument('b', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$a, $b] = $this->embedder->embed([
            $input->getArgument('a'),
            $input->getArgument('b'),
        ]);

        $output->writeln(sprintf('Aantal getallen per vector: %d', count($a)));
        $output->writeln(sprintf('Gelijkenis (cosine): %.4f', $this->cosine($a, $b)));

        return Command::SUCCESS;
    }

    private function cosine(array $a, array $b): float
    {
        $dot = $normA = $normB = 0.0;
        foreach ($a as $i => $value) {
            $dot   += $value * $b[$i];
            $normA += $value * $value;
            $normB += $b[$i] * $b[$i];
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
