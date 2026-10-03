<?php

namespace App\Command;

use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:embedding:document', description: 'Chunks a document')]
class EmbeddingDocumentCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.vectorizer.openai_small')]
        private readonly VectorizerInterface $vectorizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tekst = 'Wanneer?';
        $vector = $this->vectorizer->vectorize($tekst);
        $getallen = $vector->getData();

        $output->writeln('Tekst:        '.$tekst);
        $output->writeln('Aantal getallen: '.count($getallen));
        $output->writeln('Eerste vijf:  '.implode(', ', array_slice($getallen, 0, 5)));
        return Command::SUCCESS;
    }
}
