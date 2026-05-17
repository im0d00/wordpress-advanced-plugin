<?php
namespace NexusBuilder\AI;

class LayoutGenerator {

    private Client $client;

    public function __construct() {
        $this->client = Client::instance();
    }

    public function generate(string $prompt, array $context = []): array|\WP_Error {
        $system = <<<SYS
You are an expert web designer and NexusBuilder expert.
NexusBuilder uses a JSON element tree format. You must respond ONLY with valid JSON.

ELEMENT SCHEMA (use only these types):
- section: Container with settings {layout, flex_direction, gap, padding, background}
- container: Inner wrapper with settings {layout, flex_direction, gap, padding}
- heading: Text heading with settings {text, tag (h1-h6), typography, color}
- paragraph: Text with settings {text, typography, color}
- image: Image with settings {url, alt, width, height, border_radius}
- button: CTA button with settings {text, link, style (primary|secondary|outline|ghost), size, border_radius, background, color}
- spacer: Empty space with settings {height}
- divider: Horizontal rule with settings {style, color, width}
- icon: Icon with settings {icon (Tabler icon name), size, color}

Each node must have: id (unique 8-char string), type, settings (object), children (array).
Generate beautiful, modern, professional designs. Use generous padding. Think in real px values.
Respond with ONLY the JSON array, no explanation, no markdown fences.
SYS;

        $brand = $context['brand'] ?? '';
        $tone  = $context['tone']  ?? 'professional';
        $full_prompt = "Create a NexusBuilder element tree for: {$prompt}.\n";
        if ($brand) $full_prompt .= "Brand: {$brand}.\n";
        $full_prompt .= "Tone: {$tone}. Use real placeholder content (not Lorem Ipsum).";

        $result = $this->client->chat([
            ['role' => 'user', 'content' => $full_prompt]
        ], $system, 4096);

        if (is_wp_error($result)) return $result;

        $json_text = $this->client->get_text($result);

        // Strip markdown fences if Claude accidentally added them
        $json_text = preg_replace('/^```(?:json)?\s*/m', '', $json_text);
        $json_text = preg_replace('/\s*```$/m', '', $json_text);

        $tree = json_decode(trim($json_text), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($tree)) {
            return new \WP_Error('parse_error', 'AI returned invalid JSON: ' . $json_text);
        }

        return $tree;
    }
}
