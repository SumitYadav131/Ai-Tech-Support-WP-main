<?php
function ai_support_create_sessions($support_type)
{
    global $wpdb;
    $table = $wpdb->prefix . 'ai_support_sessions';
    $wpdb->insert(
        $table,
        array(
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'support_type' => $support_type,
            'created_at' => current_time('mysql'),
            'user_id' => get_current_user_id()
        )
    );

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