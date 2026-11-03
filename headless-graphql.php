<?php
/** WPGraphQL bridge for the ElevenLabs Reader. */
if (!defined('ABSPATH')) exit;

function elwr_headless_scoped_css() {
    $path = ELWR_DIR . 'assets/reader.css';
    if (!is_readable($path)) return '';
    $css = file_get_contents($path);
    // Every selector in the bundled stylesheet starts with .elwr- and is reader-specific.
    // Scope to blog surfaces using :where() to avoid increasing specificity.
    return preg_replace('/(?<![\w-])\.elwr-reader/', ':where(.blog, .single-post, [data-blog-root]) .elwr-reader', $css);
}

add_action('graphql_register_types', function () {
    if (!function_exists('register_graphql_field')) return;
    register_graphql_field('RootQuery', 'elwrHeadlessAssets', [
        'type' => 'ElwrHeadlessAssets',
        'description' => 'Blog-scoped ElevenLabs reader assets for headless clients.',
        'resolve' => function () {
            return [
                'css' => elwr_headless_scoped_css(),
                'scriptUrl' => add_query_arg('ver', ELWR_VERSION, ELWR_URL . 'assets/reader.js'),
                'styleUrl' => add_query_arg('ver', ELWR_VERSION, ELWR_URL . 'assets/reader.css'),
                'apiBaseUrl' => rest_url('elwr/v1/audio'),
                'labelsJson' => wp_json_encode(array_map('elwr_label', array_combine(array_keys(elwr_label_defaults()), array_keys(elwr_label_defaults())))),
            ];
        },
    ]);
    register_graphql_object_type('ElwrHeadlessAssets', [
        'description' => 'Public frontend assets; never includes private API credentials.',
        'fields' => [
            'css' => ['type' => 'String'],
            'scriptUrl' => ['type' => 'String'],
            'styleUrl' => ['type' => 'String'],
            'apiBaseUrl' => ['type' => 'String'],
            'labelsJson' => ['type' => 'String'],
        ],
    ]);
});
