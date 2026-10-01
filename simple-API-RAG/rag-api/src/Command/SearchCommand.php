<?php

namespace App\Command;

use App\Retrieval\Retriever;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:search', description: 'Zoek chunks bij een vraag')]
final class SearchCommand extends Command
{
    public function __construct(private Retriever $retriever)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('question', InputArgument::REQUIRED);
        $this->addOption('k', null, InputOption::VALUE_REQUIRED, 'Aantal resultaten', 5);
        $this->addOption('hybrid', null, InputOption::VALUE_NONE, 'Ook op woorden zoeken');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $results = $this->retriever->search(
            $input->getArgument('question'),
            (int) $input->getOption('k'),
            $input->getOption('hybrid'),
        );

        $rows = [];
        foreach ($results as $r) {
            $rows[] = [round($r->score, 3), $r->source, mb_substr($r->content, 0, 80).'...'];
        }
        $io->table(['Score', 'Bron', 'Tekst'], $rows);

        return Command::SUCCESS;
    }
}
