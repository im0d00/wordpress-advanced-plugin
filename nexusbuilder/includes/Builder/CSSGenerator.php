<?php
namespace NexusBuilder\Builder;

class CSSGenerator {

    private static array $breakpoints = [
        'desktop' => null,    // No media query — default
        'laptop'  => 1024,
        'tablet'  => 768,
        'mobile'  => 480,
    ];

    public static function generate(array $tree, int $post_id): string {
        $rules = [];
        self::collect_rules($tree, $rules);

        $css = '';

        // Desktop first (no media query)
        foreach ($rules as $selector => $devices) {
            if (!empty($devices['desktop'])) {
                $css .= ".nexus-page-{$post_id} {$selector}{\n";
                foreach ($devices['desktop'] as $prop => $val) {
                    $css .= "  {$prop}:{$val};\n";
                }
                $css .= "}\n";
            }
        }

        // Tablet and Mobile inside media queries
        foreach (['laptop' => 1024, 'tablet' => 768, 'mobile' => 480] as $device => $bp) {
            $media_block = '';
            foreach ($rules as $selector => $devices) {
                if (!empty($devices[$device])) {
                    $media_block .= "  .nexus-page-{$post_id} {$selector}{\n";
                    foreach ($devices[$device] as $prop => $val) {
                        $media_block .= "    {$prop}:{$val};\n";
                    }
                    $media_block .= "  }\n";
                }
            }
            if ($media_block) {
                $css .= "@media(max-width:{$bp}px){\n{$media_block}}\n";
            }
        }

        return $css;
    }

    private static function collect_rules(array $nodes, array &$rules): void {
        foreach ($nodes as $node) {
            $id       = $node['id']       ?? '';
            $settings = $node['settings'] ?? [];

            if (!$id) continue;

            $selector = "#nexus-el-{$id}";
            $rules[$selector] = self::settings_to_css($settings);

            if (!empty($node['children'])) {
                self::collect_rules($node['children'], $rules);
            }
        }
    }

    private static function settings_to_css(array $settings): array {
        $rules = ['desktop' => [], 'tablet' => [], 'mobile' => []];

        // Padding
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $pad = $settings['padding'][$device] ?? null;
            if ($pad) {
                $rules[$device]['padding'] =
                    "{$pad['top']} {$pad['right']} {$pad['bottom']} {$pad['left']}";
            }

            // Font size
            $fs = $settings['typography'][$device]['size'] ?? null;
            if ($fs) $rules[$device]['font-size'] = $fs;

            // Font weight
            $fw = $settings['typography'][$device]['weight'] ?? null;
            if ($fw) $rules[$device]['font-weight'] = $fw;
        }

        // Color (non-responsive)
        if (!empty($settings['color'])) {
            $rules['desktop']['color'] = $settings['color'];
        }

        // Background
        if (!empty($settings['background'])) {
            $bg = $settings['background'];
            if ($bg['type'] === 'color') {
                $rules['desktop']['background-color'] = $bg['color'] ?? '';
            } elseif ($bg['type'] === 'gradient') {
                $rules['desktop']['background'] = $bg['gradient'] ?? '';
            } elseif ($bg['type'] === 'image') {
                $rules['desktop']['background-image'] = "url('{$bg['url']}')";
                $rules['desktop']['background-size']     = $bg['size']     ?? 'cover';
                $rules['desktop']['background-position'] = $bg['position'] ?? 'center center';
            }
        }

        // Flex layout
        if (!empty($settings['layout']) && $settings['layout'] === 'flex') {
            $rules['desktop']['display']         = 'flex';
            $rules['desktop']['flex-direction']  = $settings['flex_direction']['desktop'] ?? 'row';
            $rules['desktop']['gap']             = $settings['gap']['desktop'] ?? '0';
            $rules['tablet']['flex-direction']   = $settings['flex_direction']['tablet'] ?? '';
            $rules['mobile']['flex-direction']   = $settings['flex_direction']['mobile'] ?? 'column';
        }

        return $rules;
    }
}
