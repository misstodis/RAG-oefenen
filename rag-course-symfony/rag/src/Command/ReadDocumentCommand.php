<?php

namespace App\Command;


use Symfony\AI\Store\Document\Loader\TextFileLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:read:document', description: 'Laat zien wat de loader maakt')]
class ReadDocumentCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new TextFileLoader();

        $documents = $loader->load('rag-data/schoolinfo.txt');

        foreach ($documents as $document) {
            dump($document);
        }

        return Command::SUCCESS;
    }
}
