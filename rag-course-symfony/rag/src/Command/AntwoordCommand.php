<?php

namespace App\Command;


use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
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

#[AsCommand(name: 'app:antwoord', description: 'Zoek chunks en laat het taalmodel antwoorden')]
class AntwoordCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.vectorizer.openai_small')]
        private VectorizerInterface $vectorizer,
        #[Autowire(service: 'ai.platform.openai')]
        private PlatformInterface $platform,
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
        $vraag = $input->getArgument('vraag');

        $elasticsearchClient = HttpClient::createForBaseUri('http://localhost:9200/', [
            'auth_basic' => ['elastic', $this->elasticWachtwoord],
        ]);

        $store = StoreFactory::create('docs',
            httpClient: $elasticsearchClient
        );

        $retriever = new Retriever(
            store: $store,
            vectorizer: $this->vectorizer ?? null
        );

        $resultaten = $retriever->retrieve($vraag, ['k' => 3]);

        $context = '';
        foreach ($resultaten as $chunk) {
            $context .= $chunk->getMetadata()->getText()."\n\n";
        }

        $berichten = new MessageBag(
            Message::forSystem("Beantwoord de vraag alleen met de informatie hieronder. Antwoord in het Nederlands. Staat het antwoord er niet in, zeg dat dan eerlijk dat je het niet weet.\n\n".$context),
            Message::ofUser($vraag),
        );

        $agent = new Agent(
            platform: $this->platform,
            model: 'gpt-5-mini'
        );


        $antwoord = $this->platform->invoke('gpt-5-mini', $berichten)->asText();

        $output->writeln("Gevonden context:\n".$context);
        $output->writeln('Antwoord: '.$antwoord);

        return Command::SUCCESS;
    }
}
