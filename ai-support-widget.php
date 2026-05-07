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
    $context = ai_support_get_site_context();

    // Convert array → readable string
    $context_string = "
Site Info:
- Site URL: {$context['site_url']}
- WordPress Version: {$context['wp_version']}
- PHP Version: {$context['php_version']}
- Theme: {$context['theme']}
- Plugins: " . implode(', ', $context['plugins']) . "
";

    // Strong system prompt (YOU WERE MISSING THIS)
    $system_prompt = "
You are a senior WordPress support engineer.

Analyze the issue using site data.
Always:
- Identify root cause (plugin/theme/conflict)
- Give step-by-step solution
- Avoid generic answers
";

    //  Better user prompt
    $prompt = $context_string . "\nUser Issue:\n" . $question;

    $api_key = get_option('ai_support_api_key');
    $ai_model = get_option('ai_model');

    $body = [
        "model" => $ai_model,
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

    // DEBUG (very important for you right now)
    error_log($prompt);

    $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => get_site_url(),
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

    wp_enqueue_script(
        'ai-support-admin-js',
        plugin_dir_url(__FILE__) . '/admin/admin.js',
        array('jquery'),
        null,
        true
    );
}

add_action('admin_enqueue_scripts', 'ai_support_admin_scripts');

function ai_support_admin_scripts()
{
    wp_enqueue_script(
        'ai-support-admin-js',
        plugin_dir_url(__FILE__) . '/admin/admin.js',
        array('jquery'),
        null,
        true
    );
}

add_action('admin_enqueue_scripts', 'ai_support_admin_assets');

function ai_support_admin_assets($hook)
{
    // load only on your plugin page
    if ($hook !== 'toplevel_page_ai-support-dashboard') {
        return;
    }

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