<?php

namespace App\Command;

use App\Loader\PdfDocumentLoader;
use App\VectorStore\InMemoryVector;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\OpenAi\Factory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\MultiPartResult;
use Symfony\AI\Store\Document\Vectorizer;
use Symfony\AI\Store\Indexer\DocumentProcessor;
use Symfony\AI\Store\Indexer\SourceIndexer;
use Symfony\AI\Store\Retriever;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\AI\Agent\Bridge\SimilaritySearch\SimilaritySearch;
use Symfony\AI\Agent\Agent;


#[AsCommand(name: 'app:create-index-league', description: 'Maakt de vector-index aan voor de league')]

class CreateIndexLeagueCommand extends Command
{
    public function __construct(
        private readonly InMemoryVector $vectorStore,
        private readonly PdfDocumentLoader $pdfLoader,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%env(OPENAI_API_KEY)%')] private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = $this->vectorStore::create();

        $platform = Factory::createPlatform($this->apiKey);
        $vectorizer = new Vectorizer($platform, 'text-embedding-3-small');

        $indexer = new SourceIndexer($this->pdfLoader, new DocumentProcessor($vectorizer, $store));

        // glob() zet het *-patroon om naar een lijst echte bestandspaden
        foreach (glob($this->projectDir.'/data/league/*.pdf') as $file) {
            $indexer->index($file);
        }

        $retriever = new Retriever($store, $vectorizer);
        $similaritySearch = new SimilaritySearch($retriever);
        $toolbox = new Toolbox([$similaritySearch]);

        $agent = new Agent($platform, 'gpt-5-mini', toolbox: $toolbox);


        $messages = new MessageBag(
            Message::forSystem('Please answer all user questions only using SimilaritySearch function. if you cannot find the answer, say "I cannot find the answer to your question."'),
            Message::ofUser('WAT IS LEAGUE OF LEGENDS?')
        );
        $result = $agent->call($messages)->getResult();

        // Reasoning-modellen (gpt-5-mini) geven een MultiPartResult terug: denkstappen + tekst.
        // asText() pakt alleen de tekstdelen.
        $answer = $result instanceof MultiPartResult ? $result->asText() : $result->getContent();
        $test = $result instanceof MultiPartResult ? $result->getMetadata(): [];

        $output->writeln($answer);
        dump($test);

        return Command::SUCCESS;
    }
}

