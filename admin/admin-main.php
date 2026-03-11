<?php
add_action('admin_menu', 'add_ai_support_page');

function add_ai_support_page()
{
    add_menu_page(
        'AI Support Dashboard', // Page Title
        'AI Support', // Menu Title
        'manage_options', // Capability
        'ai-support-dashboard', // Mneu slug 
        'ai_support_dashboard_page', // Callback function 
        'dashicons-format-chat',     // Icon
    );
}

function ai_support_dashboard_page()
{
    ob_start();
    ?>

    <div class="wrap">
        <h1>AI Support Dashboard</h1>
    </div>

    <?php
}