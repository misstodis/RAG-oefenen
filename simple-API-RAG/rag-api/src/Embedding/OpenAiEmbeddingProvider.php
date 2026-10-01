<?php

namespace App\Embedding;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenAiEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(
        private HttpClientInterface $http,
        #[Autowire('%env(OPENAI_API_KEY)%')] private string $apiKey,
        #[Autowire('%env(EMBEDDING_MODEL)%')] private string $model,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function embed(array $texts): array {
        $response = $this->http->request('POST', 'https://api.openai.com/v1/embeddings', [
            'auth_bearer' => $this->apiKey,
            'json' => [
                'model' => $this->model,
                'input' => array_values($texts),
            ],
            'timeout' => 60,
        ]);

        // toArray() zet de JSON om in een PHP-array
        // en gooit een fout als OpenAI een fout teruggeeft
        $data = $response->toArray();

        $vectors = [];
        foreach ($data['data'] as $item) {
            $vectors[$item['index']] = $item['embedding'];
        }

        ksort($vectors);

        return array_values($vectors);
    }
}
