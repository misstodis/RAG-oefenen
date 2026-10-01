<?php

namespace App\LLM;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenAiChatModel implements ChatModelInterface
{
    public function __construct(
        private HttpClientInterface $http,
        #[Autowire('%env(OPENAI_API_KEY)%')] private string $apiKey,
        #[Autowire('%env(CHAT_MODEL)%')] private string $model,
    ) {
    }

    public function complete(string $system, string $user): string
    {
        $data = $this->http->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'auth_bearer' => $this->apiKey,
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ],
            'timeout' => 60,
        ])->toArray(false); // false = geen exception bij 4xx/5xx, zodat we de foutmelding kunnen tonen

        if (isset($data['error'])) {
            throw new \RuntimeException('OpenAI fout: '.$data['error']['message']);
        }

        // Het antwoord van het model staat hier:
        return $data['choices'][0]['message']['content'];
    }
}
