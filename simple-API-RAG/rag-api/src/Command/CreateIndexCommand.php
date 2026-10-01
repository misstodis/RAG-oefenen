<?php

namespace App\Command;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-index', description: 'Maakt de vector-index aan')]
class CreateIndexCommand extends Command
{
    public function __construct(
        private readonly Client $client,
        private readonly string $indexName,
        private readonly int $embeddingDims,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // Met --recreate gooi je een bestaande index eerst weg
        $this->addOption('recreate', null, InputOption::VALUE_NONE, 'Verwijder en maak opnieuw');
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exists = $this->client->indices()
            ->exists(['index' => $this->indexName])
            ->asBool();

        if ($exists && $input->getOption('recreate')) {
            $this->client->indices()->delete(['index' => $this->indexName]);
            $output->writeln('Oude index verwijderd.');
        } elseif ($exists) {
            $output->writeln('Index bestaat al. Gebruik --recreate om opnieuw te beginnen.');
            return Command::SUCCESS;
        }

        // Dezelfde mapping als in module 1, maar met 1536 dimensies
        // en extra velden om te onthouden waar een chunk vandaan komt
        $this->client->indices()->create([
            'index' => $this->indexName,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'content'     => ['type' => 'text'],     // de tekst van de chunk
                        'source'      => ['type' => 'keyword'],  // bestandsnaam
                        'chunk_index' => ['type' => 'integer'],  // welk stuk van het bestand
                        'embedding'   => [
                            'type' => 'dense_vector',
                            'dims' => $this->embeddingDims,      // 1536 uit env local
                            'index' => true,
                            'similarity' => 'cosine',
                        ],
                    ],
                ],
            ],
        ]);

        $output->writeln(sprintf('Index "%s" aangemaakt.', $this->indexName));
        return Command::SUCCESS;
    }
}
