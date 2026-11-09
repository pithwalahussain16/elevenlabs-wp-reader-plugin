<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read ElevenLabs CSS.
 */
function elwr_get_reader_css() {
    $file = ELWR_DIR . 'assets/reader.css';

    if (!is_readable($file)) {
        return '';
    }

    $css = file_get_contents($file);

    return is_string($css) ? $css : '';
}



/**
 * Scope CSS to blog pages.
 */
function elwr_headless_scoped_css() {
    $css = elwr_get_reader_css();

    if ($css === '') {
        return '';
    }

    return preg_replace(
        '/(?<![\w-])\.elwr-reader/',
        ':where(.blog, .single-post, [data-blog-root]) .elwr-reader',
        $css
    );
}

/**
 * Get player color from WordPress Admin.
 */
function elwr_headless_color_css() {
    $settings = function_exists('elwr_settings')
        ? elwr_settings()
        : [];

    $color = isset($settings['theme_color'])
        ? sanitize_hex_color($settings['theme_color'])
        : null;

    if (!$color) {
        $color = '#5b9f4d';
    }

    return
        ':where(.blog, .single-post, [data-blog-root]) '
        . '.elwr-reader { --elwr-color: '
        . $color
        . '; }';
}

/**
 * Complete ElevenLabs stylesheet.
 */
function elwr_headless_complete_css() {
    $css = elwr_headless_scoped_css();

    if ($css === '') {
        return '';
    }

    return $css . "\n" . elwr_headless_color_css();
}

/**
 * Append ElevenLabs CSS to existing global CSS.
 *
 * This helper must be called by the resolver
 * that produces funkycommerceThemeStyles.customCss.
 */
function elwr_append_to_global_css($existing_css) {
    $existing_css = is_string($existing_css)
        ? $existing_css
        : '';

    $reader_css = elwr_headless_complete_css();

    if ($reader_css === '') {
        return $existing_css;
    }

    $marker = '/* ElevenLabs Reader Global CSS */';

    if (strpos($existing_css, $marker) !== false) {
        return $existing_css;
    }

    return $existing_css
        . "\n\n"
        . $marker
        . "\n"
        . $reader_css
        . "\n";
}

/**
 * Register GraphQL types and fields.
 */



/**
 * Register GraphQL types and fields.
 */
add_action('graphql_register_types', function () {

    if (
        !function_exists('register_graphql_field') ||
        !function_exists('register_graphql_object_type')
    ) {
        error_log('[ELWR] WPGraphQL registration functions unavailable.');
        return;
    }

    // 1. Register the assets GraphQL object.
    register_graphql_object_type('ElwrHeadlessAssets', [
        'description' => 'Public ElevenLabs Reader assets.',

        'fields' => [
            'css' => [
                'type' => 'String',
            ],
            'scriptUrl' => [
                'type' => 'String',
            ],
            'styleUrl' => [
                'type' => 'String',
            ],
            'apiBaseUrl' => [
                'type' => 'String',
            ],
            'labelsJson' => [
                'type' => 'String',
            ],
        ],
    ]);

    // 2. IMPORTANT: Register the field on RootQuery.
    register_graphql_field('RootQuery', 'elwrHeadlessAssets', [
        'type' => 'ElwrHeadlessAssets',

        'description' => 'ElevenLabs Reader assets for headless clients.',

        'resolve' => function () {

            $labels = [];

            foreach (array_keys(elwr_label_defaults()) as $key) {
                $labels[$key] = elwr_label($key);
            }

            return [
                'css' => elwr_headless_complete_css(),

                'scriptUrl' => add_query_arg(
                    'ver',
                    ELWR_VERSION,
                    ELWR_URL . 'assets/reader.js'
                ),

                'styleUrl' => add_query_arg(
                    'ver',
                    ELWR_VERSION,
                    ELWR_URL . 'assets/reader.css'
                ),

                'apiBaseUrl' => rest_url('elwr/v1/audio'),

                'labelsJson' => wp_json_encode($labels),
            ];
        },
    ]);

    // 3. Register reader HTML on WordPress posts.
    register_graphql_field('Post', 'elwrReaderHtml', [
        'type' => 'String',

        'description' => 'ElevenLabs Reader HTML for headless frontend.',

        'resolve' => function ($post) {

            $post_id = isset($post->ID)
                ? absint($post->ID)
                : 0;

            if (!$post_id) {
                return null;
            }

            $post_object = get_post($post_id);

            if (
                !$post_object ||
                $post_object->post_status !== 'publish'
            ) {
                return null;
            }

            return do_shortcode(
                '[elevenlabs_reader post_id="' . $post_id . '"]'
            );
        },
    ]);

}); // END graphql_register_types


/**
 * Append ElevenLabs CSS to Superfunky customCss.
 * Keep this filter OUTSIDE graphql_register_types.
 */
add_filter(
    'graphql_resolve_field',
    function ($result, $source, $args, $context, $info) {

        if (
            !isset($info->fieldName) ||
            $info->fieldName !== 'customCss'
        ) {
            return $result;
        }

        $parent = isset($info->path)
            ? $info->path->prev
            : null;

        if (
            !$parent ||
            $parent->key !== 'funkycommerceThemeStyles'
        ) {
            return $result;
        }

        if (!is_string($result) && $result !== null) {
            return $result;
        }

        return elwr_append_to_global_css($result);
    },
    20,
    5
);