<?php

include_once __DIR__ . '/../sessionWorkspace/sessionFunctions.php';

/*
|--------------------------------------------------------------------------
| Floating Chat UI
|--------------------------------------------------------------------------
*/

add_action('wp_footer', 'ai_support_render_widget');
function ai_support_render_widget()
{
    if (is_admin())
        return;
    ?>

    <div id="ai-chat-toggle">💬</div>

    <div id="ai-chat-container">

        <div id="ai-chat-header">
            🤖 AI Support
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="aiChatTabs">

            <li class="nav-item">
                <button id="ai-toogle-btn" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-messages"
                    type="button">
                    <span class="back-btn-arrow">🠔</span> Messages
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-new" type="button">
                    New Message
                </button>
            </li>

        </ul>

        <!-- Tab Content -->
        <div class="tab-content">

            <div class="tab-pane fade show active" id="tab-messages">
                <div>
                    <div id="ai-chat-sidebar">
                    </div>
                    <div id="ai-chat-messages"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-new">
                <div>
                    <!-- made temporary change  -->
                    <div id="ai-chat-messagess">
                        <div id="ai-support-options">
                            <?php
                            $ai_support_options = get_option('ai_support_options', []);
                            foreach ($ai_support_options as $opt) { ?>
                                <button class="ai-option"
                                    data-type="<?php echo $opt['type'] ?>"><?php echo $opt['label'] ?></button>
                                <?php
                            }
                            ?>
                            <!-- <button class="ai-option" data-type="technical">🔧 Technical Support</button>
                            <button class="ai-option" data-type="broken">💥 Broken Site</button>
                            <button class="ai-option" data-type="speed">🚀 Speed & Performance</button>
                            <button class="ai-option" data-type="security">🔐 Security</button> -->
                        </div>
                    </div>
                    <div id="ai-input-area">
                        <textarea id="ai-question" rows="1" placeholder="Ask your issue..."></textarea>
                        <button id="ai-ask">➤</button>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <?php
}


/*
|--------------------------------------------------------------------------
| Site Context
|--------------------------------------------------------------------------
*/

function ai_support_get_site_context()
{

    $theme = wp_get_theme()->get('Name');
    $wp_version = get_bloginfo('version');
    $plugins = get_option('active_plugins');

    return "Theme: $theme\n"
        . "WordPress Version: $wp_version\n"
        . "Active Plugins: " . implode(', ', $plugins);
}

/*
|--------------------------------------------------------------------------
| AJAX Handler
|--------------------------------------------------------------------------
*/

add_action('wp_ajax_ai_support_request', 'ai_support_handle_request');
add_action('wp_ajax_nopriv_ai_support_request', 'ai_support_handle_request');


function ai_support_handle_request()
{

    check_ajax_referer('ai_support_nonce', 'security');

    $question = sanitize_text_field($_POST['question']);


    if (empty($question)) {
        wp_send_json_error('Please enter a question.');
    }
    // $supportType
    $response = ai_support_generate_response($question);

    $supportType = sanitize_text_field($_POST['supportType']);

    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;

    if (!$session_id || $session_id == 0) {

        $session_id = ai_support_create_sessions($supportType);
    }


    ai_support_save_message($session_id, 'user', $question);

    ai_support_save_message($session_id, 'assistant', $response);

    wp_send_json_success([
        'response' => $response,
        'session_id' => $session_id
    ]);
}


add_action('wp_ajax_get_session_messages', 'get_session_messages');
add_action('wp_ajax_nopriv_get_session_messages', 'get_session_messages');


/*
|--------------------------------------------------------------------------
| Get Session Messages 
|--------------------------------------------------------------------------
*/

function get_session_messages()
{
    global $wpdb;
    $session_id = intval($_POST['session_id']);
    $table = $wpdb->prefix . 'ai_support_messages';
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %d ORDER BY created_at ASC",
            $session_id
        )
    );
    wp_send_json_success($messages);
}

/*
|--------------------------------------------------------------------------
| Get Stored Sessions
|--------------------------------------------------------------------------
*/

add_action('wp_ajax_get_sessions', 'get_stored_sessions');
add_action('wp_ajax_nopriv_get_sessions', 'get_stored_sessions');
function get_stored_sessions()
{
    $current_user = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'ai_support_sessions';
    $join_table = $wpdb->prefix . 'ai_support_messages';
    $sessions = $wpdb->get_results("SELECT s.*,
       (
           SELECT m.message
           FROM $join_table m
           WHERE m.session_id = s.id
           ORDER BY m.created_at ASC
           LIMIT 1
       ) AS first_message
FROM $table s where user_id = $current_user
ORDER BY s.created_at DESC; ");
    wp_send_json_success($sessions);
}