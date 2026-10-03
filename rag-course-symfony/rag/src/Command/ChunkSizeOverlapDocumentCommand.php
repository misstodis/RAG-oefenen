<?php

namespace App\Command;

use Symfony\AI\Store\Document\Loader\TextFileLoader;
use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:chunksizeoverlap:document', description: 'Chunks a document')]
class ChunkSizeOverlapDocumentCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new TextFileLoader();
        $documents = $loader->load('rag-data/schoolinfo.txt');

        $splitter = new TextSplitTransformer(chunkSize: 600, overlap: 100);

        $chunks = $splitter->transform($documents);

        foreach ($chunks as $chunk) {
            dump($chunk);
        }

        return Command::SUCCESS;
    }
}
