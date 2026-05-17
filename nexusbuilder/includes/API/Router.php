<?php
namespace NexusBuilder\API;

class Router {

    private static ?Router $instance = null;
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        $endpoints = [
            new Endpoints\Pages(),
            new Endpoints\Templates(),
            new Endpoints\GlobalStyles(),
            new Endpoints\Elements(),
            new Endpoints\AI(),
        ];

        foreach ($endpoints as $endpoint) {
            $endpoint->register();
        }
    }
}
