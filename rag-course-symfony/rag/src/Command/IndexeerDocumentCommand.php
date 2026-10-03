<?php

namespace App\Command;


use Symfony\AI\Store\Bridge\Elasticsearch\StoreFactory;
use Symfony\AI\Store\Document\Loader\TextFileLoader;
use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\AI\Store\Indexer\DocumentProcessor;
use Symfony\AI\Store\Indexer\SourceIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(name: 'app:indexeer:document', description: 'Lees, knip, embed en sla op in Elasticsearch')]
class IndexeerDocumentCommand  extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.vectorizer.openai_small')]
        private readonly VectorizerInterface $vectorizer,
        #[Autowire(env: 'ELASTICSEARCH_PASSWORD')]
        private readonly string $elasticWachtwoord,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Maak een HTTP client voor Elasticsearch
        $elasticsearchClient = HttpClient::createForBaseUri('http://localhost:9200/', [
            'auth_basic' => ['elastic', $this->elasticWachtwoord],
        ]);

        $store = StoreFactory::create(
           "docs",
            httpClient: $elasticsearchClient,
        );
        $store->setup(); // maakt de index aan, als die nog niet bestaat
        $store->clear();  // maakt de index leeg, zodat je geen dubbele chunks krijgt

        $loader = new TextFileLoader();
        $splitter = new TextSplitTransformer(chunkSize: 300, overlap: 50);

        // 4. De lopende band: splitter -> vectorizer -> store
        $band = new DocumentProcessor(
            vectorizer: $this->vectorizer,
            store: $store,
            transformers: [$splitter],
        );

        $indexer = new SourceIndexer($loader, $band);

        $indexer->index(['rag-data/schoolinfo.txt', 'rag-data/ict.txt']);

        return Command::SUCCESS;
    }

}
