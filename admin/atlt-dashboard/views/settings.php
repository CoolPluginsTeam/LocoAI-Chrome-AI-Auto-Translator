<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function atlt_render_settings_page() {
    $text_domain = 'loco-translate-addon';
    
    // Define APIs configuration
    $apis = [
        'gemini' => [
            'name' => 'Gemini AI',
            'docs_url' => 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/generate-gemini-api-key/'
        ],
        'openai' => [
            'name' => 'OpenAI',
            'docs_url' => 'https://locoaddon.com/docs/how-to-generate-open-api-key/'
        ],
        'deepl' => [
            'name' => 'DeepL',
            'docs_url' => 'https://locoaddon.com/docs/generate-deepl-api-key-loco-ai/'
        ]
    ];
        ?>
        <div class="atlt-dashboard-settings">
            <div class="atlt-dashboard-settings-container">
                <div class="header">
                    <h1><?php esc_html_e('LocoAI Settings', $text_domain); ?></h1>
                </div>  
                <p class="description">
                    <?php 
                    printf(
                    esc_html__(
                        'Configure your settings for the LocoAI to optimize your translation experience. Start by entering your %1$slicense key%2$s. Once it\'s activated, you\'ll be able to add your Gemini or OpenAI API keys and manage your preferences for seamless integration.',
                        $text_domain
                    ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=loco-atlt-dashboard&tab=license' ) ) . '">',
                    '</a>'
                    ); 
                    ?>
            <div class="atlt-dashboard-api-settings-container">
                <div class="atlt-dashboard-api-settings">
                    <?php foreach ($apis as $key => $api): ?>
                        <label for="<?php echo esc_attr($key); ?>-api"><?php printf(__('Add %s API key', $text_domain), esc_html($api['name'])); ?></label>
                        <div class="input-group">
                            <input type="text" id="<?php echo esc_attr($key); ?>-api" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" disabled>
                        </div>
                        <?php
                        printf(
                            __('%s to See How to Generate %s API Key', $text_domain),
                            '<a href="' . esc_url($api['docs_url']) . '" target="_blank">' . esc_html__('Click Here', $text_domain) . '</a>',
                            esc_html($api['name'])
                        );
                    endforeach; ?>

                    <div class="atlt-dashboard-save-btn-container">
                        <button disabled class="button button-primary"><?php esc_html_e('Save', $text_domain); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="atlt-dashboard-geminiAPIkey">
            <h3>Rate Limits of Free Gemini AI API Key</h3>
            <ul>
                <li><strong>15 RPM</strong>: This API Key allows a maximum of 15 requests per minute</li>
                <li><strong>1 million TPM</strong>: With this API Key, you can process up to 1 million tokens per minute</li>
                <li><strong>1,500 RPD</strong>: To ensure smooth performance, it allows up to 1,500 requests per day</li>
            </ul>
        </div> 
    </div>
    <?php
}

function atlt_render_settings_page_pro() {
    $text_domain = 'loco-translate-addon';
    
    // Move API configuration to a separate function for better organization
    $apis = atlt_get_api_configurations();
    
    // Handle form submission early
    if (atlt_check_form_submission()) {
        atlt_handle_api_key_submission();
    }
    
    atlt_render_settings_page_html($apis, $text_domain);
}

function atlt_get_api_configurations() {
    return [
        'gemini' => [
            'name' => 'Gemini AI',
            'option_key' => 'LocoAutomaticTranslateAddonPro_google_api_key',
            'docs_url' => 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/generate-gemini-api-key/',
            'value' => get_option('LocoAutomaticTranslateAddonPro_google_api_key', '')
        ],
        'openai' => [
            'name' => 'OpenAI',
            'option_key' => 'LocoAutomaticTranslateAddonPro_openai_api_key',
            'docs_url' => 'https://locoaddon.com/docs/how-to-generate-open-api-key/',
            'value' => get_option('LocoAutomaticTranslateAddonPro_openai_api_key', '')
        ],
        'deepl' => [
            'name' => 'DeepL',
            'option_key' => 'LocoAutomaticTranslateAddonPro_deepl_api_key',
            'docs_url' => 'https://locoaddon.com/docs/generate-deepl-api-key-loco-ai/',
            'value' => get_option('LocoAutomaticTranslateAddonPro_deepl_api_key', '')
        ]
    ];
}

function atlt_check_form_submission() {
    if (current_user_can('manage_options')) {
    return $_SERVER['REQUEST_METHOD'] === 'POST' && 
           isset($_POST['nonce']) && 
           wp_verify_nonce($_POST['nonce'], 'api_keys');
    }
    return false;
}

function atlt_validate_google_api_key($key) {
    if (empty($key)) return false;

    if (!preg_match('/^AIza[0-9A-Za-z\-_]{35}$/', $key)) {
        atlt_show_admin_notice('error', 'Invalid Gemini AI API Key.');
        return false;
    }

    $response = wp_remote_get(
        'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key,
        [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]
    );

    if (is_wp_error($response)) {
        atlt_show_admin_notice('error', 'API request failed: ' . esc_html($response->get_error_message()));
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['models']) || empty($body['models'])) {
        atlt_show_admin_notice('error', 'Invalid or unauthorized Gemini API Key.');
        return false;
    }

    $text_models = [];
    foreach ($body['models'] as $model) {
        if (
            empty($model['name']) ||
            empty($model['supportedGenerationMethods']) ||
            !is_array($model['supportedGenerationMethods']) ||
            !in_array('generateContent', $model['supportedGenerationMethods'], true)
        ) {
            continue;
        }

        $model_name = $model['name'];

        if (
            (isset($model['state']) && $model['state'] !== 'ACTIVE') ||
            preg_match('/(tts|image-generation)/i', $model_name)
        ) {
            continue;
        }

        $clean_name = str_replace('models/', '', $model_name);

        $text_models[] = $clean_name;
    }

    update_option('atlt_google_models', $text_models);

    return true;
}

function atlt_validate_openai_api_key($key) {
    if (empty($key)) return false;

    $response = wp_remote_get('https://api.openai.com/v1/models', [
        'headers' => [
            'Authorization' => 'Bearer ' . $key,
        ],
    ]);

    if (is_wp_error($response)) {
        atlt_show_admin_notice('error', 'Unable to connect to OpenAI API.');
        return false;
    }

    $response_data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($response_data['error'])) {
        $error_message = $response_data['error']['message'] ?? 'Invalid OpenAI API Key.';
        atlt_show_admin_notice('error', esc_html($error_message));
        return false;
    }

    if (empty($response_data['data'])) {
        atlt_show_admin_notice('error', 'No models found. Your API key may not have access.');
        return false;
    }

    $model_ids = array_reduce(
        $response_data['data'],
        function ( array $ids, array $model_data ) {
            $model_slug = $model_data['id'];
    
            if (
                ( str_starts_with( $model_slug, 'gpt-' ) || str_starts_with( $model_slug, 'o1-' ) )
                && ! str_contains( $model_slug, '-instruct' )
                && ! str_contains( $model_slug, '-realtime' )
                && ! str_contains( $model_slug, '-audio' )
                && ! str_contains( $model_slug, '-tts' )
                && ! str_contains( $model_slug, '-transcribe' )
                && ! str_contains( $model_slug, '-image' )
                && $model_slug !== 'o1-pro'
                && $model_slug !== 'o1-pro-2025-03-19'
            ) {
                $ids[] = $model_slug;
            }
    
            return $ids;
        },
        []
    );
    
    update_option('atlt_openai_models', $model_ids);

    return true;
}

function atlt_validate_deepl_api_key( $key ) {
    if (empty($key)) return false;

    $client = new Client();

    try {
        $response = $client->request('GET', 'https://api.deepl.com/v2/usage', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $key,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $reason = $response->getReasonPhrase();

        if($statusCode === 200){
            update_option('atlt_deepl_api_key_type', 'pro');
            return true;
        }else{
            atlt_show_admin_notice('error', str_replace('<a ', '<a target="_blank" ', make_clickable($reason)));
            return false;
        }

    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            // Extract error details from response body
            $errorBody = (string) $e->getResponse()->getBody();
            
            // Decode JSON response to array
            $errorData = json_decode($errorBody, true);
    
            // Get error message if available
            $errorMessage = $errorData['message'] ?? $errorBody;
        } else {
            // Use exception message if no response body is available
            $errorMessage = $e->getMessage();
        }
    
        if(str_contains($errorMessage, 'Use https://api-free.deepl.com')){
            update_option('atlt_deepl_api_key_type', 'free');
            return true;
        }else{
            atlt_show_admin_notice('error', str_replace('<a ', '<a target="_blank" ', make_clickable($errorMessage)));
            return false;
        }
    }

    atlt_show_admin_notice('error', 'Invalid DeepL API Key.');
    return false;
}



function atlt_show_admin_notice($type, $message) {
    if (!isset($GLOBALS['atlt_admin_notices'])) {
        $GLOBALS['atlt_admin_notices'] = array();
    }
    $GLOBALS['atlt_admin_notices'][] = '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . wp_kses($message, ['a' => ['href' => [], 'target' => []]]) . '</p></div>';
}

function atlt_handle_api_key_submission() {
    // Clear any existing notices at the start
    $GLOBALS['atlt_admin_notices'] = array();
    
    if (isset($_POST['reset_gemini_api_key'])) {
        delete_option('LocoAutomaticTranslateAddonPro_google_api_key');
        delete_option('atlt_google_models');
        delete_option('atlt_selected_google_model');
        atlt_show_admin_notice('success', 'Gemini AI API Key has been removed.');
        return true;
    } 
    
    if (isset($_POST['reset_openai_api_key'])) {
        delete_option('LocoAutomaticTranslateAddonPro_openai_api_key');
        delete_option('atlt_openai_models');
        delete_option('atlt_selected_openai_model');
        atlt_show_admin_notice('success', 'OpenAI API Key has been removed.');
        return true;
    }

    if (isset($_POST['reset_deepl_api_key'])) {
        delete_option('LocoAutomaticTranslateAddonPro_deepl_api_key');
        atlt_show_admin_notice('success', 'DeepL API Key has been removed.');
        return true;
    }
    
    if (isset($_POST['submit_api_keys'])) {
        return atlt_handle_api_key_save();
    }
    
    return false;
}

function atlt_handle_api_key_save() {

    $success = false;
    $any_validation_attempted = false;

    
    $current_openai_model = get_option('atlt_selected_openai_model', '');
    $current_google_model = get_option('atlt_selected_google_model', '');
    $current_deepl_model = get_option('atlt_selected_deepl_model', '');

    // Save selected OpenAI model if set and validate
    if (isset($_POST['atlt_selected_openai_model']) && $_POST['atlt_selected_openai_model'] !== $current_openai_model) {

        $selected_model = sanitize_text_field($_POST['atlt_selected_openai_model']);
        $openai_key = get_option('LocoAutomaticTranslateAddonPro_openai_api_key', '');
        $is_valid = false;
        $error_message = '';
    
        if ($selected_model === '') {
            update_option('atlt_selected_openai_model', '');
            atlt_show_admin_notice('success', 'OpenAI model selection has been cleared.');
        } else {
            if ($openai_key && $selected_model) {
                $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $openai_key,
                    ],
                    'body' => wp_json_encode([
                        'model' => $selected_model,
                        'messages' => [['role' => 'user', 'content' => 'Test']],
                        'max_completion_tokens' => 20
                    ]),
                    'timeout' => 20,
                ]);
    
                if (is_wp_error($response)) {
                    $error_message = esc_html($response->get_error_message());
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (empty($body['error'])) {
                        $is_valid = true;
                    } else {
                        $error_message = esc_html($body['error']['message'] ?? 'Unknown error from OpenAI.');
                    }
                }
            }
    
            if ($is_valid) {
                update_option('atlt_selected_openai_model', $selected_model);
                atlt_show_admin_notice('success', 'The OpenAI model has been successfully validated and saved.');
            } else {
                atlt_show_admin_notice('error', 'OpenAI API Error: ' . $error_message);
            }
        }
    }    


    if (isset($_POST['atlt_selected_google_model']) && $_POST['atlt_selected_google_model'] !== $current_google_model) {

        $selected_model = sanitize_text_field($_POST['atlt_selected_google_model']);
        $google_key = get_option('LocoAutomaticTranslateAddonPro_google_api_key', '');
        $is_valid = false;
        $error_message = '';
    
        if ($selected_model === '') {
            delete_option('atlt_selected_google_model');
            atlt_show_admin_notice('success', 'The Google Gemini model selection has been cleared.');
        } else {
            if ($google_key && $selected_model) {
                $response = wp_remote_post(
                    'https://generativelanguage.googleapis.com/v1beta/models/' . $selected_model . ':generateContent?key=' . $google_key,
                    [
                        'headers' => ['Content-Type' => 'application/json'],
                        'body'    => json_encode([
                            'contents' => [[ 'parts' => [['text' => 'Test']] ]]
                        ]),
                        'timeout' => 60,
                    ]
                );
    
                if (!is_wp_error($response)) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (empty($body['error'])) {
                        $is_valid = true;
                    } else if (!empty($body['error']['message'])) {
                        $error_message = esc_html($body['error']['message']);
                    }
                } else {
                    $error_message = esc_html($response->get_error_message());
                }
            }
    
            if ($is_valid) {
                update_option('atlt_selected_google_model', $selected_model);
                atlt_show_admin_notice('success', 'The Gemini model has been successfully validated and saved.');
            } else {
                $notice = $error_message ? $error_message : 'The selected Gemini model is not valid or not accessible with your API key.';
                atlt_show_admin_notice('error', $notice);
            }
        }
    }

    if (isset($_POST['atlt_selected_deepl_model']) && $_POST['atlt_selected_deepl_model'] !== $current_deepl_model) {
        $selected_model = sanitize_text_field($_POST['atlt_selected_deepl_model']);
        $deepl_key = get_option('LocoAutomaticTranslateAddonPro_deepl_api_key', '');
        $is_valid = false;
        $error_message = '';
        
    }

    $feedback_opt_in = null; 
    
    // Handle feedback checkbox
    if (get_option('cpfm_opt_in_choice_cool_translations')) {

        $feedback_opt_in = isset($_POST['atlt-dashboard-feedback-checkbox']) ? 'yes' : 'no';
        update_option('atlt_feedback_opt_in', $feedback_opt_in);
      
    }
    

    // If user opted out, remove the cron job
    if ($feedback_opt_in === 'no' && wp_next_scheduled('atlt_extra_data_update') ){
        
        wp_clear_scheduled_hook('atlt_extra_data_update');
     
    }

    if ($feedback_opt_in === 'yes' && !wp_next_scheduled('atlt_extra_data_update')) {

            wp_schedule_event(time(), 'every_30_days', 'atlt_extra_data_update');

            if (class_exists('ATLT_cronjob')) {

                ATLT_cronjob::atlt_send_data();
            } 
    }
    
    if (isset($_POST['LocoAutomaticTranslateAddonPro_google_api_key'])) {
        $new_google_key = sanitize_text_field($_POST['LocoAutomaticTranslateAddonPro_google_api_key']);
        if (!empty($new_google_key)) {
            $any_validation_attempted = true;
            if (atlt_validate_google_api_key($new_google_key)) {
                update_option('LocoAutomaticTranslateAddonPro_google_api_key', $new_google_key);
                $success = true;
            }
        }
    }
    
    if (isset($_POST['LocoAutomaticTranslateAddonPro_openai_api_key'])) {
        $new_openai_key = sanitize_text_field($_POST['LocoAutomaticTranslateAddonPro_openai_api_key']);
        if (!empty($new_openai_key)) {
            $any_validation_attempted = true;
            if (atlt_validate_openai_api_key($new_openai_key)) {
                update_option('LocoAutomaticTranslateAddonPro_openai_api_key', $new_openai_key);
                $success = true;
            }
        }
    }

    if (isset($_POST['LocoAutomaticTranslateAddonPro_deepl_api_key'])) {
        $new_deepl_key = sanitize_text_field($_POST['LocoAutomaticTranslateAddonPro_deepl_api_key']);
        if (!empty($new_deepl_key)) {
            $any_validation_attempted = true;
            if (atlt_validate_deepl_api_key($new_deepl_key)) {
                update_option('LocoAutomaticTranslateAddonPro_deepl_api_key', $new_deepl_key);
                $success = true;
            }
        }
    }
    
    if ($success) {
        atlt_show_admin_notice('success', 'API keys saved successfully.');
        return true;
    } elseif ($any_validation_attempted && !isset($GLOBALS['atlt_admin_notices'])) {
        // Only show generic error if we attempted validation and no specific error was set
        atlt_show_admin_notice('error', 'Please enter a valid API key.');
    }
    
    return false;
}

function atlt_render_settings_page_html($apis, $text_domain) {
    // Process form submission before rendering
    $form_processed = false;
    if (atlt_check_form_submission()) {
        $form_processed = atlt_handle_api_key_submission();
    }
    
    // Refresh API values after form processing
    if ($form_processed) {
        $apis = atlt_get_api_configurations();
    }
    
    // Get available models for each API
    $openai_models = get_option('atlt_openai_models', []);
    $google_models = get_option('atlt_google_models', []);
    $current_openai_model = get_option('atlt_selected_openai_model', '');
    $current_google_model = get_option('atlt_selected_google_model', '');
    
    ?>
    <div class="atlt-dashboard-settings">
        <div class="atlt-dashboard-settings-container">
            <?php
            // Show notices at the top of the container
            if (isset($GLOBALS['atlt_admin_notices'])) {
                foreach ($GLOBALS['atlt_admin_notices'] as $notice) {
                    echo wp_kses_post($notice);
                }
            }
            ?>
            <div class="header">
                <h1><?php esc_html_e('LocoAI Settings', $text_domain); ?></h1>
            </div>
            
            <p class="description">
                <?php esc_html_e('Configure your settings for the LocoAI to optimize your translation experience. Enter your API keys and manage your preferences for seamless integration.', $text_domain); ?>
            </p>

            <div class="atlt-dashboard-api-settings-container">
                <div class="atlt-dashboard-api-settings">
                    <form method="post">
                        <div class="atlt-dashboard-api-settings-form">
                            <?php wp_nonce_field('api_keys', 'nonce'); ?>
                            
                            <?php foreach ($apis as $key => $api): 
                                $has_key = !empty($api['value']);
                                $masked_value = $has_key ? 
                                    esc_attr(substr($api['value'], 0, 8) . str_repeat('*', 24) . substr($api['value'], -8) . ' ✅') : 
                                    ''; 
                            ?>
                                <label for="<?php echo esc_attr($key); ?>-api" class="api-settings-label">
                                    <?php printf(__('Add %s API key', $text_domain), esc_html($api['name'])); ?>
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                        id="<?php echo esc_attr($key); ?>-api" 
                                        name="<?php echo esc_attr($api['option_key']); ?>" 
                                        value="<?php echo esc_attr($masked_value); ?>" 
                                        placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        <?php echo $has_key ? 'disabled' : ''; ?>>
                                    
                                    <?php if ($has_key): ?>
                                        <button type="submit" name="reset_<?php echo esc_attr($key); ?>_api_key" class="button button-primary">
                                            <?php esc_html_e('Reset', $text_domain); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!$has_key): ?>
                                    <?php
                                    printf(
                                        __('%s to See How to Generate %s API Key', $text_domain),
                                        '<a href="' . esc_url($api['docs_url']) . '" target="_blank">' . esc_html__('Click Here', $text_domain) . '</a>',
                                        esc_html($api['name'])
                                    );
                                    ?>
                                <?php endif; ?>
                                    <?php 
                                    if ($key === 'openai' && $has_key && !empty($openai_models)) : ?>
                                        <div class="atlt-dashboard-api-settings-openai-model">
                                            <label for="atlt_selected_openai_model" class="api-settings-label">
                                                <?php esc_html_e('Select OpenAI Model', $text_domain); ?>
                                            </label>
                                            <select name="atlt_selected_openai_model" class="atlt-openai-model-select">
                                                <option value=""><?php esc_html_e('Select model...', $text_domain); ?></option>
                                                <?php foreach ($openai_models as $model) : ?>
                                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($current_openai_model, $model); ?>>
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($key === 'gemini' && $has_key && !empty($google_models)) : ?>
                                        <div class="atlt-dashboard-api-settings-google-model">
                                            <label for="atlt_selected_google_model" class="api-settings-label">
                                                <?php esc_html_e('Select Gemini Model', $text_domain); ?>
                                            </label>
                                            <select name="atlt_selected_google_model" class="atlt-google-model-select">
                                                <option value=""><?php esc_html_e('Select model...', $text_domain); ?></option>
                                                <?php foreach ($google_models as $model) : ?>
                                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($current_google_model, $model); ?>>
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                            <?php if (get_option('cpfm_opt_in_choice_cool_translations')) : ?>
                              
                            <div class="atlt-dashboard-feedback-container">
                                <div class="feedback-row">
                                    <input type="checkbox" 
                                        id="atlt-dashboard-feedback-checkbox" 
                                        name="atlt-dashboard-feedback-checkbox"
                                        <?php checked(get_option('atlt_feedback_opt_in'), 'yes'); ?>>
                                    <p><?php esc_html_e('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', $text_domain); ?></p>
                                    <a href="#" class="atlt-see-terms">[See terms]</a>
                                </div>
                                <div id="termsBox" style="display: none;padding-left: 20px; margin-top: 10px; font-size: 12px; color: #999;">
                                <p><?php esc_html_e("Opt in to receive email updates about security improvements, new features, helpful tutorials, and occasional special offers. We'll collect: ", 'ccpw'); ?><a href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank"> Click here</a></p>
                                        <ul style="list-style-type:auto;">
                                            <li><?php esc_html_e('Your website home URL and WordPress admin email.', 'ccpw'); ?></li>
                                            <li><?php esc_html_e('To check plugin compatibility, we will collect the following: list of active plugins and themes, server type, MySQL version, WordPress version, memory limit, site language and database prefix.', 'ccpw'); ?></li>
                                        </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="atlt-dashboard-save-btn-container">
                                <button type="submit" name="submit_api_keys" class="button button-primary">
                                    <?php esc_html_e('Save', $text_domain); ?>
                                </button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="atlt-dashboard-geminiAPIkey">
            <h3>Rate Limits of Free Gemini AI API Key</h3>
            <ul>
                <li><strong>15 RPM</strong>: This API Key allows a maximum of 15 requests per minute</li>
                <li><strong>1 million TPM</strong>: With this API Key, you can process up to 1 million tokens per minute</li>
                <li><strong>1,500 RPD</strong>: To ensure smooth performance, it allows up to 1,500 requests per day</li>
            </ul>
        </div> 
    </div>
    <?php
}



