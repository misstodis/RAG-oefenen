<?php

namespace App\Command;

use App\Ingest\DocumentIndexer;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'app:ingest', description: 'Indexeer alle documenten in een map')]
final class IngestCommand extends Command
{
    public function __construct(private DocumentIndexer $indexer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('dir', InputArgument::REQUIRED, 'Map met documenten');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Zoek alle .md-, .txt- en .pdf-bestanden in de map
        $files = new Finder()
            ->files()
            ->in($input->getArgument('dir'))
            ->name(['*.md', '*.txt', '*.pdf']);

        $pdfParser = new Parser();

        foreach ($files as $file) {
            // PDF? Haal eerst de tekst eruit. Anders: lees het bestand gewoon.
            if ($file->getExtension() === 'pdf') {
                $text = $pdfParser->parseFile($file->getRealPath())->getText();
            } else {
                $text = $file->getContents();
            }

            $count = $this->indexer->index($file->getRelativePathname(), $text);
            $output->writeln(sprintf('%s: %d chunks', $file->getRelativePathname(), $count));
        }

        return Command::SUCCESS;
    }
}
