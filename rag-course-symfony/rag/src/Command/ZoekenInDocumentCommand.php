<?php

namespace App\Command;

use Symfony\AI\Store\Bridge\Elasticsearch\StoreFactory;
use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\AI\Store\Retriever;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(name: 'app:zoekin:document', description: 'Zoek op betekenis in een index')]
class ZoekenInDocumentCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.vectorizer.openai_small')]
        private VectorizerInterface $vectorizer,
        #[Autowire(env: 'ELASTICSEARCH_PASSWORD')]
        private string $elasticWachtwoord,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('vraag', InputArgument::REQUIRED, 'Je vraag in gewone taal');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Verbinding en store, net als in les 7
        $elasticsearchClient = HttpClient::createForBaseUri('http://localhost:9200/', [
            'auth_basic' => ['elastic', $this->elasticWachtwoord],
        ]);

        $store = StoreFactory::create(
           'docs',
            httpClient: $elasticsearchClient
        );

        // De retriever: embedding van de vraag maken en de dichtstbijzijnde chunks zoeken
        $retriever = new Retriever(
            store: $store,
            vectorizer: $this->vectorizer ?? null
        );
        $resultaten = $retriever->retrieve(
            $input->getArgument('vraag'),
            ['k' => 3]
        );

        // 3. De resultaten tonen
        $plaats = 1;
        foreach ($resultaten as $chunk) {
            $output->writeln(sprintf('--- Plaats %d (score %.3f) ---', $plaats++, $chunk->getScore()));
            $output->writeln($chunk->getMetadata()->getText());
            $output->writeln('');
        }

        return Command::SUCCESS;
    }
}
