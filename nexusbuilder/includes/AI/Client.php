<?php
namespace NexusBuilder\AI;

class Client {

    private static ?Client $instance = null;
    public static function instance(): self { return self::$instance ??= new self(); }

    private string $api_key    = '';
    private string $model      = 'claude-sonnet-4-20250514';
    private string $base_url   = 'https://api.anthropic.com/v1/messages';

    public function init(): void {
        $this->api_key = nexusbuilder_get_ai_key();
    }

    /**
     * Send a message to Claude and get a response.
     */
    public function chat(array $messages, string $system = '', int $max_tokens = 2048): array|\WP_Error {
        if (empty($this->api_key)) {
            return new \WP_Error('no_api_key', 'AI API key not configured.');
        }

        $body = [
            'model'      => $this->model,
            'max_tokens' => $max_tokens,
            'messages'   => $messages,
        ];

        if ($system) {
            $body['system'] = $system;
        }

        $response = wp_remote_post($this->base_url, [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) return $response;

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return new \WP_Error(
                'api_error',
                $data['error']['message'] ?? 'API request failed',
                ['status' => $code]
            );
        }

        return $data;
    }

    public function get_text(array $api_response): string {
        return $api_response['content'][0]['text'] ?? '';
    }
}
