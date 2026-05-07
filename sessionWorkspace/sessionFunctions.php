<?php
function ai_support_create_sessions($support_type)
{
    global $wpdb;
    $table = $wpdb->prefix . 'ai_support_sessions';
    if (is_user_logged_in()) {
        $wpdb->insert(
            $table,
            array(
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'support_type' => $support_type,
                'created_at' => current_time('mysql'),
                'user_id' => get_current_user_id()
            )
        );
    }
    return $wpdb->insert_id;
}

function ai_support_save_message($session_id, $role, $message)
{
    global $wpdb;
    $table = $wpdb->prefix . 'ai_support_messages';
    $wpdb->insert(
        $table,
        [
            'session_id' => $session_id,
            'role' => $role,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]
    );
}

add_action('wp_ajax_delete_session', 'ai_support_delete_session');
add_action('wp_ajax_nopriv_delete_session', 'ai_support_delete_session');

function ai_support_delete_session()
{
    global $wpdb;

    $session_id = intval($_POST['session_id']);

    $session_table = $wpdb->prefix . 'ai_support_sessions';
    $messages_table = $wpdb->prefix . 'ai_support_messages';

    // ❗ Delete messages first (important)
    $wpdb->delete($messages_table, ['session_id' => $session_id]);

    // Then delete session
    $wpdb->delete($session_table, ['id' => $session_id]);

    wp_send_json_success('Deleted');
}