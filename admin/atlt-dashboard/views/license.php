<?php
/**
 * Renders the license activation page
 */
function atlt_render_license_page() {
  
    $text_domain = 'loco-translate-addon';
    $purchase_email = get_option('LocoAutomaticTranslateAddonPro_lic_email', get_bloginfo('admin_email'));
    
    
    $admin_url = esc_url(admin_url('admin-post.php'));
    $purchase_email_escaped = esc_attr($purchase_email);

    ?>

    <div class="atlt-dashboard-license">

        <div class="atlt-dashboard-license-container">

            <div class="header">
                <h1>🔑 <?php esc_html_e('Activate License', $text_domain); ?></h1>
            </div>

            <div class="atlt-dashboard-license-form">

                <form method="post" action="<?php echo esc_url($admin_url); ?>">

                    <input type="hidden" name="action" value="atlt_activate_license"/>

                    <?php wp_nonce_field('atlt-license'); ?>
                    
                    <div class="license-field">

                        <label for="license_code"><?php esc_html_e('License Key', $text_domain); ?></label>
                        <input type="text" name="license_code" id="license_code" required 
                            placeholder="<?php esc_attr_e('xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx', $text_domain); ?>">

                    </div>
                    
                    <div class="license-field">

                        <label for="email"><?php esc_html_e('Email Address', $text_domain); ?></label>
                        <input type="email" name="email" id="email" required value="<?php echo esc_attr($purchase_email_escaped); ?>">
                        <small><?php esc_html_e("Plugin updates news will be sent to this email. Don't worry, we hate spam.", $text_domain); ?></small>

                    </div>
                    
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Activate License', $text_domain); ?>
                    </button>

                </form>

                <p class="activation-note">
                    <?php esc_html_e('Activate to receive automatic plugin updates and support.', $text_domain); ?>
                </p>
                
                <?php atlt_render_license_help_buttons($text_domain); ?>

            </div>
        </div>
    </div>
<?php

}

/**
 * Renders the license information page for Pro users
 * 
 * @param object|null $license_info License information object
 */
function atlt_render_license_page_pro($license_info = null) {
   
    $text_domain = 'loco-translate-addon';
    
    // Early return if invalid license info
    if (!$license_info) {

        $license_info = LocoAutomaticTranslateAddonProBase::GetRegisterInfo();
        
    }

    if (!is_object($license_info) || !isset($license_info->is_valid) || !isset($license_info->license_title) || !isset($license_info->expire_date)) {
       
        wp_die(esc_html__('Error: Invalid license information', $text_domain));
        return;
    }
   
    // Sanitize license key before masking
    $license_key = sanitize_text_field(get_option('LocoAutomaticTranslateAddonPro_lic_Key', ''));

    $masked_key = !empty($license_key) ? esc_html(substr($license_key, 0, 8) . '-XXXXXXXX-XXXXXXXX-' . substr($license_key, -8)) : '';
    
    $admin_url = esc_url(admin_url('admin-post.php'));
   
?>
   <div class="atlt-dashboard-license">
    <div class="atlt-dashboard-license-pro-container">

        <div class="license-header-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">

            <h1>🔒 <?php esc_html_e('Your License Info', $text_domain); ?></h1>
            
            <?php if (atlt_needs_refresh($license_info)): ?>

                <button type="button" class="atlt-refresh-btn" id="atlt-refresh-license-btn">
                    <?php esc_html_e('🔄Check license status', $text_domain); ?>

                </button>
            <?php endif; ?>
        </div>
        
        <ul>
            <li><strong><?php esc_html_e('Status:', $text_domain); ?></strong> 

                <span class="validity">

                    <?php if ($license_info->is_valid): ?>

                        <?php if (atlt_is_license_expired($license_info)): ?>

                            <strong>❌ <?php esc_html_e('License Expired', $text_domain); ?></strong>

                        <?php elseif (atlt_is_support_expired($license_info)): ?>

                            <strong>❌ <?php esc_html_e('Support Expired', $text_domain); ?></strong>

                        <?php else: ?>

                            <strong class="valid">✅ <?php esc_html_e('Valid', $text_domain); ?></strong>

                        <?php endif; ?>

                    <?php else: ?>

                        <strong>❌ <?php esc_html_e('Invalid', $text_domain); ?></strong>
                        
                    <?php endif; ?>
                </span>
            </li>

            <li><strong><?php esc_html_e('License Type:', $text_domain); ?></strong> <span class="license-type"><?php echo esc_html($license_info->license_title); ?></span></li>

                <li><strong><?php esc_html_e('Plugin Updates & Support Validity:', $text_domain); ?></strong> <span class="validity">

                    <?php 
                    $current_time = time();
                 
                    // Handle "No expiry" case for expire_date
                    $expire_date_expired = false;
                    if (strtolower($license_info->expire_date) !== 'no expiry') {
                        $expire_date_timestamp = strtotime($license_info->expire_date);
                        $expire_date_expired = $expire_date_timestamp && $expire_date_timestamp < $current_time;
                    }
                    
                    // Handle "no support" case for support_end
                    if (strtolower($license_info->support_end) === 'no support') {

                        esc_html_e('No Support', $text_domain);

                    } else {

                        // Handle "unlimited" case for support_end
                        $support_end_expired = false;

                        if (strtolower($license_info->support_end) !== 'unlimited') {

                            $support_end_timestamp = strtotime($license_info->support_end);
                            $support_end_expired = $support_end_timestamp && $support_end_timestamp < $current_time;

                        }
                        if ($expire_date_expired) {
                            
                            echo esc_html(atlt_pro_formatLicenseDate($license_info->expire_date));

                        } elseif ($support_end_expired) {                            
                            
                            echo esc_html(atlt_pro_formatLicenseDate($license_info->support_end));
                          
                        } else {

                            echo esc_html(atlt_pro_formatLicenseDate($license_info->expire_date));
                            
                        }
                       
                    }
                    ?>
                    </span>
                </li>
                <li><strong><?php esc_html_e('Your License Key:', $text_domain); ?></strong> <span class="license-key"><?php echo esc_html($masked_key); ?></span></li>
            </ul>

        <!-- Deactivate button section -->

        <div class="atlt-dashboard-license-pro-container-deactivate-btn">
                        <p><?php esc_html_e('Want to deactivate the license for any reason?', $text_domain); ?></p>
                        <form method="post" action="<?php echo esc_url($admin_url); ?>">
                            <input type="hidden" name="action" value="atlt_deactivate_license" />
                            <?php wp_nonce_field('atlt-license'); ?>
                        <button type="submit" class="deactivate-btn">
                                <?php esc_html_e('Deactivate License', $text_domain); ?>
                            </button>
                        </form>
        </div>
        
        <?php if (atlt_is_license_expired($license_info)): ?>

            <div class="notice notice-error" style="margin-top: 10px; color: #d63638;">
                <?php atlt_render_expiry_message($license_info, 'license'); ?>
        </div>

        <?php elseif (atlt_is_support_expired($license_info)): ?>

            <div class="notice notice-error" style="margin-top: 10px; color: #d63638;">
                <?php atlt_render_expiry_message($license_info, 'support'); ?>
        </div>

        <?php endif; ?>

        <?php atlt_render_license_help_buttons($text_domain); ?>
    </div>
</div>
<?php
}

/**
 * Renders the license help buttons section
 * 
 * @param string $text_domain The text domain for translations
 */
function atlt_render_license_help_buttons($text_domain) {

    ?>
    <div class="atlt-dashboard-license-pro-container-buttons">

        <p><?php esc_html_e('Want to know more about the license key?', $text_domain); ?></p>

        <div class="btns">

            <a href="<?php echo esc_url('https://my.coolplugins.net/account/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=store_site&utm_content=license'); ?>" target="_blank" rel="noopener noreferrer" class="atlt-dashboard-btn">
                <?php esc_html_e('Check Account', $text_domain); ?>
            </a>

            <a href="<?php echo esc_url('https://locoaddon.com/support/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=support&utm_content=license'); ?>" target="_blank" rel="noopener noreferrer" class="atlt-dashboard-btn">
                <?php esc_html_e('Contact Support', $text_domain); ?>
            </a>

        </div>

    </div>
    <?php
}
function atlt_is_license_expired($license_info) {

    return $license_info->is_valid === 'license_expired';
}

function atlt_is_support_expired($license_info) {

    return $license_info->support_end === 'no support' || 
          ($license_info->is_valid === 'support_expired' || 
          (strtolower($license_info->support_end) !== 'unlimited' && 
          strtotime($license_info->support_end) < time()));
}

function atlt_needs_refresh($license_info) {

    return atlt_is_license_expired($license_info) || atlt_is_support_expired($license_info);
}


function atlt_render_expiry_message($license_info, $type = 'license') {
 
    $text_domain = 'loco-translate-addon';
    
    // Generate version available message using common helper
    $version_available_message = ProHelpers::getVersionAvailableMessage();
    
    if ($license_info->msg === 'limit_reached') {
        $support_link = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url('https://my.coolplugins.net/account/support-tickets/'), esc_html__('clicking here', 'atlt'));
        echo wp_kses_post(sprintf(
            /* translators: %s: link to support ticket page */
            __('There was an issue with your account. Please contact our plugin support team by %s.', 'atlt'),
            $support_link
        ));
        return;
    }

    $message = $type === 'license' 
        ? __('Your license has expired,', 'atlt') 
        : __('Your support has expired,', 'atlt');

    $renew_link = isset($license_info->market) && $license_info->market === 'E'
        ? ''
        : ' <a href="'.esc_url('https://my.coolplugins.net/account/subscriptions/').'" target="_blank" rel="noopener noreferrer">'.esc_html__('Renew now', 'atlt').'</a>';

     $final_message = '';
    
    // Add version message if available
    if (!empty($version_available_message)) {

        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        
        $final_message .= wp_kses_post($version_available_message) . ' ';
    }
    
    // Add license expiry message
    $final_message .= esc_html($message) . $renew_link . esc_html__(' to continue receiving updates and priority support.', 'atlt');
    
    echo $final_message;
}

function atlt_pro_formatLicenseDate($dateString) {

if (!empty($dateString) && strtolower($dateString) !== 'no expiry') {
     
        $date = new DateTime($dateString);
        return $date->format('d M Y');
    }
    return $dateString;
}