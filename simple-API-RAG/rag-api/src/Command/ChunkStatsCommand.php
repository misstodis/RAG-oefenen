<?php

namespace App\Command;


use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:chunk-stats', description: 'Toont statistieken van de chunks')]
class ChunkStatsCommand extends Command
{
    public function __construct(
        private  readonly Client $client,
        private readonly string $indexName
    ) {
        parent::__construct();
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

        if (!$exists) {
            $output->writeln('Index bestaat niet. Maak eerst de index aan met app:create-index.');
            return Command::SUCCESS;
        }

        $countResponse = $this->client->count(['index' => $this->indexName]);
        $count = $countResponse['count'] ?? 0;

        $output->writeln("Aantal chunks in index '{$this->indexName}': {$count}");

        return Command::SUCCESS;
    }
}
