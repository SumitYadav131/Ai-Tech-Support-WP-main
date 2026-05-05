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

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'configuration';

    // Save API Key
    if (isset($_POST['ai_support_api_key'])) {
        update_option(
            'ai_support_api_key',
            sanitize_text_field($_POST['ai_support_api_key'])
        );
    }
    // Ai model here 
    if (isset($_POST['ai_model'])) {
        update_option(
            'ai_model',
            sanitize_text_field($_POST['ai_model'])
        );
    }

    // Save Support Options ONLY on support-options tab
    if ($active_tab === 'support-options') {
        if (isset($_POST['support_type'])) {
            $options = [];
            foreach ($_POST['support_type'] as $index => $type) {

                $type = sanitize_text_field($type);
                $label = isset($_POST['support_label'][$index])
                    ? sanitize_text_field($_POST['support_label'][$index])
                    : '';
                $default_msg = isset($_POST['support_default_msg'][$index])
                    ? sanitize_text_field($_POST['support_default_msg'][$index])
                    : '';

                if ($type === '' && $label === '' && $default_msg === '') {
                    continue;
                }

                $options[] = [
                    'type' => $type,
                    'label' => $label,
                    'default_msg' => $default_msg
                ];
            }
            update_option('ai_support_options', $options);

        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // If all rows removed
            delete_option('ai_support_options');
        }
    }


    $api_key = get_option('ai_support_api_key');
    $ai_model = get_option('ai_model');
    $options = get_option('ai_support_options', []);
    ?>
    <div class="wrap">

        <h1 class="wp-heading-inline">AI Support Dashboard</h1>

        <nav class="nav-tab-wrapper">

            <a href="?page=ai-support-dashboard&tab=configuration"
                class="nav-tab <?php echo ($active_tab == 'configuration') ? 'nav-tab-active' : ''; ?>">
                Configuration
            </a>

            <a href="?page=ai-support-dashboard&tab=support-options"
                class="nav-tab <?php echo ($active_tab == 'support-options') ? 'nav-tab-active' : ''; ?>">
                Support Options
            </a>

        </nav>

        <div style="margin-top:20px;">

            <?php if ($active_tab == 'configuration'): ?>
                <form method="post">

                    <table class="form-table">

                        <tr>
                            <th scope="row">
                                <label for="ai_support_api_key">API Key</label>
                            </th>

                            <td>
                                <input type="text" id="ai_support_api_key" name="ai_support_api_key"
                                    value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter API Key">

                                <p class="description">Enter your AI API key.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ai_model">AI Model</label>
                            </th>

                            <td>
                                <input type="text" id="ai_model" name="ai_model" value="<?php echo esc_attr($ai_model); ?>"
                                    class="regular-text" placeholder="AI Model">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ai_models">AI Models</label>
                            </th>

                            <td>
                                <select name="ai_models" id="ai_models">
                                    <option selected value="openrouter/free">Openrouter</option>
                                    <option value="opennApi">OpenApi</option>
                                </select>
                            </td>
                        </tr>

                    </table>

                    <?php submit_button('Save Settings'); ?>

                </form>
            <?php endif; ?>


            <?php if ($active_tab == 'support-options'): ?>
                <form method="post">
                    <table class="widefat" id="support-options-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Label</th>
                                <th>Default Messages</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($options)): ?>

                                <?php foreach ($options as $opt): ?>

                                    <tr>

                                        <td>
                                            <input type="text" name="support_type[]" value="<?php echo esc_attr($opt['type']); ?>">
                                        </td>

                                        <td>
                                            <input type="text" name="support_label[]" value="<?php echo esc_attr($opt['label']); ?>"
                                                class="regular-text">
                                        </td>

                                        <td>
                                            <input type="text" name="support_default_msg[]"
                                                value="<?php echo esc_attr($opt['default_msg'] ?? ''); ?>" class="regular-text">
                                        </td>

                                        <td>
                                            <button type="button" class="button remove-option">
                                                Remove
                                            </button>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>
                    </table>
                    <br>
                    <button type="button" class="button button-primary" id="add-support-option">
                        Add Option
                    </button>

                    <?php submit_button('Save Options'); ?>

                </form>
            <?php endif; ?>





        </div>

    </div>
    <?php

    echo ob_get_clean();
}