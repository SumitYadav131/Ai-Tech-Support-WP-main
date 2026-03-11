<?php
/*
Plugin Name: AI Support Widget
Description: Floating AI-powered support chat widget.
Version: 1.0
Author: My-Devit-Solutions
*/

if (!defined('ABSPATH')) {
    exit;
}

include_once plugin_dir_path(__FILE__) . 'includes/aiSupportHandlers.php';
include_once plugin_dir_path(__FILE__) . 'admin/admin-main.php';

/*
|--------------------------------------------------------------------------
| Create Database Tables
|--------------------------------------------------------------------------
*/
register_activation_hook(__FILE__, 'create_session_store_table');
function create_session_store_table()
{
    global $wpdb;

    $sessions = $wpdb->prefix . 'ai_support_sessions';
    $messages = $wpdb->prefix . 'ai_support_messages';
    $charset = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $session_table = "CREATE TABLE $sessions (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_ip VARCHAR(255),
        support_type VARCHAR(200),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        user_id VARCHAR(20)

    ) $charset;";

    $messages_table = "CREATE TABLE $messages (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        session_id BIGINT(20) NOT NULL,
        role VARCHAR(50),
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    dbDelta($session_table);
    dbDelta($messages_table);
}

/*
|--------------------------------------------------------------------------
| AI Integration Layer
|--------------------------------------------------------------------------
*/

function ai_support_generate_response($question)
{

    $site_context = ai_support_get_site_context();

    $prompt = "You are a WordPress technical support expert.\n\n"
        . "Site Info:\n" . $site_context
        . "\n\nUser Question:\n" . $question;


    include_once plugin_dir_path(__FILE__) . 'config.php';

    $body = [
        // "model" => "mistralai/mistral-7b-instruct",
        "model" => "openrouter/free",
        "messages" => [
            [
                "role" => "system",
                "content" => $system_prompt
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ]
    ];

    $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => get_site_url(), // required by OpenRouter
            'X-Title' => get_bloginfo('name'),
        ],
        'body' => json_encode($body),
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return 'WP Error: ' . $response->get_error_message();
    }

    $result = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($result['error'])) {

        error_log('OpenRouter Error: ' . print_r($result, true));
        return 'API Error: ' . $result['error']['message'];
    }

    return $result['choices'][0]['message']['content'] ?? 'No response.';
}

/*
|--------------------------------------------------------------------------
| Enqueue Assets
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', 'ai_support_enqueue_assets');
function ai_support_enqueue_assets()
{
    wp_enqueue_style('ai-support-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('ai-support-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
    wp_localize_script(
        'ai-support-script',
        'ai_support_obj',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_support_nonce')
        )
    );

    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
    );
    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        null,
        true
    );
}
