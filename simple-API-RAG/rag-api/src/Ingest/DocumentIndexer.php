<?php

namespace App\Ingest;

use App\Embedding\EmbeddingProviderInterface;
use Elastic\Elasticsearch\Client;

final class DocumentIndexer
{
    public function __construct(
        private Client $client,
        private EmbeddingProviderInterface $embedder,
        private TextChunker $chunker,
        private string $indexName,
    ) {
    }

    /** Indexeert één document en geeft het aantal chunks terug. */
    public function index(string $source, string $text): int
    {
        // 1. Verwijder oude chunks van dit bestand (als je het opnieuw indexeert)
        $this->client->deleteByQuery([
            'index' => $this->indexName,
            'body' => ['query' => ['term' => ['source' => $source]]],
        ]);

        // 2. Knip de tekst op
        $chunks = $this->chunker->chunk($text);
        if ($chunks === []) {
            return 0;
        }

        // 3. Haal voor alle chunks in één keer de embeddings op
        $vectors = $this->embedder->embed($chunks);

        // 4. Bouw een bulk-verzoek: per chunk twee regels
        //    (eerst "wat wil je doen", dan "de data")
        $body = [];
        foreach ($chunks as $i => $content) {
            $body[] = ['index' => ['_index' => $this->indexName]];
            $body[] = [
                'content'     => $content,
                'source'      => $source,
                'chunk_index' => $i,
                'embedding'   => $vectors[$i],
            ];
        }

        // 5. Sla alles in één keer op. refresh=true maakt het direct doorzoekbaar.
        $result = $this->client->bulk(['body' => $body, 'refresh' => 'true'])->asArray();

        if ($result['errors']) {
            throw new \RuntimeException('Opslaan mislukt: '.json_encode($result['items'][0]));
        }

        return count($chunks);
    }
}
