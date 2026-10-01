<?php

namespace App\Retrieval;

use App\Embedding\EmbeddingProviderInterface;
use Elastic\Elasticsearch\Client;

final class Retriever
{
    public function __construct(
        private Client $client,
        private EmbeddingProviderInterface $embedder,
        private string $indexName,
    ) {
    }

    /** @return RetrievedChunk[] */
    public function search(string $question, int $k = 5, bool $hybrid = false): array
    {
        // 1. Maak een embedding van de vraag
        $vector = $this->embedder->embed([$question])[0];

        // 2. Bouw de zoekopdracht (vergelijk met module 1!)
        $body = [
            'size' => $k,
            'knn' => [
                'field' => 'embedding',
                'query_vector' => $vector,
                'k' => $k,
                'num_candidates' => $k * 10,
            ],
            // Stuur de lange embedding niet terug, alleen wat we nodig hebben
            '_source' => ['content', 'source', 'chunk_index'],
        ];

        // 3. Optioneel: zoek ook op gewone woorden (uitleg hieronder)
        if ($hybrid) {
            $body['query'] = ['match' => ['content' => ['query' => $question, 'boost' => 0.3]]];
            $body['knn']['boost'] = 0.7;
        }

        // 4. Voer de zoekopdracht uit
        $response = $this->client->search([
            'index' => $this->indexName,
            'body' => $body,
        ])->asArray();

        // 5. Zet elk resultaat om in een RetrievedChunk
        $results = [];
        foreach ($response['hits']['hits'] as $hit) {
            $results[] = new RetrievedChunk(
                $hit['_source']['content'],
                $hit['_source']['source'],
                $hit['_source']['chunk_index'],
                $hit['_score'],
            );
        }

        return $results;
    }
}
