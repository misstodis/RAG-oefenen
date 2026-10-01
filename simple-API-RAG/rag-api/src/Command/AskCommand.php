<?php

namespace App\Command;

use App\Rag\RagService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ask', description: 'Stel een vraag aan je documenten')]
final class AskCommand extends Command
{
    public function __construct(private RagService $rag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('question', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->rag->ask($input->getArgument('question'));

        $io->section('Antwoord');
        $io->writeln($result->answer);

        $io->section('Bronnen');
        foreach ($result->sources as $i => $chunk) {
            $io->writeln(sprintf('[%d] %s (score %.3f)', $i + 1, $chunk->source, $chunk->score));
        }

        return Command::SUCCESS;
    }
}
