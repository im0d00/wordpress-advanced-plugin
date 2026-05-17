<?php
namespace NexusBuilder\API\Endpoints;

class AI {

    public function register(): void {
        $routes = [
            ['/ai/generate-layout', 'generate_layout', 'POST'],
            ['/ai/generate-copy',   'generate_copy',   'POST'],
            ['/ai/suggest-styles',  'suggest_styles',  'POST'],
            ['/ai/seo-analyze',     'seo_analyze',     'POST'],
            ['/ai/chat',            'chat',            'POST'],
        ];

        foreach ($routes as [$route, $cb, $method]) {
            register_rest_route('nexusbuilder/v1', $route, [
                'methods'             => $method,
                'callback'            => [$this, $cb],
                'permission_callback' => [$this, 'can_use_ai'],
            ]);
        }
    }

    public function generate_layout(\WP_REST_Request $request): \WP_REST_Response {
        $prompt  = sanitize_textarea_field($request->get_param('prompt') ?? '');
        $context = (array) ($request->get_param('context') ?? []);

        if (empty($prompt)) {
            return new \WP_REST_Response(['error' => 'Prompt is required'], 400);
        }

        $generator = new \NexusBuilder\AI\LayoutGenerator();
        $result    = $generator->generate($prompt, $context);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['error' => $result->get_error_message()], 500);
        }

        return new \WP_REST_Response(['tree' => $result], 200);
    }

    public function generate_copy(\WP_REST_Request $request): \WP_REST_Response {
        $type    = sanitize_text_field($request->get_param('type') ?? 'heading');  // heading|paragraph|button|meta
        $context = sanitize_textarea_field($request->get_param('context') ?? '');
        $tone    = sanitize_text_field($request->get_param('tone') ?? 'professional');
        $current = sanitize_textarea_field($request->get_param('current') ?? '');

        $prompt = match($type) {
            'heading'   => "Write a compelling website heading. Context: {$context}. Tone: {$tone}. Current: '{$current}'. Respond with ONLY the heading text, no quotes.",
            'paragraph' => "Write a paragraph of website body copy. Context: {$context}. Tone: {$tone}. 2-3 sentences. Just the text.",
            'button'    => "Write a compelling CTA button label. Context: {$context}. 2-4 words max. Just the label text.",
            'meta'      => "Write an SEO meta description. Context: {$context}. 150-160 characters. Just the description.",
            default     => "Write website copy for: {$context}. Tone: {$tone}.",
        };

        $result = \NexusBuilder\AI\Client::instance()->chat([
            ['role' => 'user', 'content' => $prompt]
        ], 'You are an expert copywriter. Respond with only the requested text, no explanation.');

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['error' => $result->get_error_message()], 500);
        }

        return new \WP_REST_Response([
            'text' => trim(\NexusBuilder\AI\Client::instance()->get_text($result))
        ], 200);
    }

    public function chat(\WP_REST_Request $request): \WP_REST_Response {
        $messages      = (array) ($request->get_param('messages') ?? []);
        $current_tree  = $request->get_param('element_tree') ?? [];
        $page_context  = sanitize_text_field($request->get_param('page_context') ?? '');

        $system = "You are an expert NexusBuilder assistant.
The user is editing a web page. They will describe changes in plain language.
You respond with a JSON object: { \"explanation\": string, \"changes\": [{\"element_id\": string, \"settings\": object}] }
Only include changed elements. Respond ONLY with JSON.
Current page structure (truncated): " . wp_json_encode(array_slice((array)$current_tree, 0, 10));

        $result = \NexusBuilder\AI\Client::instance()->chat($messages, $system, 1024);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['error' => $result->get_error_message()], 500);
        }

        $text = \NexusBuilder\AI\Client::instance()->get_text($result);
        $data = json_decode(trim(preg_replace('/^```json?\s*|\s*```$/m', '', $text)), true);

        return new \WP_REST_Response($data ?? ['explanation' => $text, 'changes' => []], 200);
    }

    public function seo_analyze(\WP_REST_Request $request): \WP_REST_Response {
        $tree    = (array) ($request->get_param('element_tree') ?? []);
        $keyword = sanitize_text_field($request->get_param('keyword') ?? '');

        $analyzer = new \NexusBuilder\AI\SEOAnalyzer();
        $report   = $analyzer->analyze($tree, $keyword);

        return new \WP_REST_Response($report, 200);
    }

    public function can_use_ai(): bool {
        if (!current_user_can('edit_posts')) return false;
        if (!nexusbuilder_can_use_ai()) return false;
        return nexusbuilder_check_ai_rate_limit(get_current_user_id());
    }
}
