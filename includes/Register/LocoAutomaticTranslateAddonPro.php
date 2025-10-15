<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	require_once ATLT_PRO_PATH . '/includes/Register/LocoAutomaticTranslateAddonProBase.php';
if(!class_exists("LocoAutomaticTranslateAddonPro")) {
	class LocoAutomaticTranslateAddonPro {
        public $plugin_file=ATLT_PRO_FILE;
        public $responseObj;
        public $licenseMessage;
        public $showMessage=false;
        public static $form_status = false;
        private const OPTION_LICENSE_KEY = 'LocoAutomaticTranslateAddonPro_lic_Key';
        private const OPTION_LICENSE_EMAIL = 'LocoAutomaticTranslateAddonPro_lic_email';
        function __construct() {
    	    $this->atlt_register_hooks();
    	    $this->atlt_initialize_license();
            // Add error notice hook
            add_action('atlt_display_admin_notices', array($this, 'atlt_display_license_error_messages'));
            add_action('atlt_display_admin_notices', array($this, 'atlt_display_license_key_notice'));
        }

        private function atlt_register_hooks() {
            add_action( 'admin_print_styles', [ $this, 'atlt_set_admin_style' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'atlt_enqueue_scripts' ] );
            add_action( 'admin_menu', array( $this, 'atlt_add_locotranslate_sub_menu' ), 101 );
            add_action('admin_post_atlt_activate_license', [$this, 'atlt_handle_license_activation']);
            add_action('admin_post_atlt_deactivate_license', [$this, 'atlt_handle_license_deactivation']);
            add_action('wp_ajax_atlt_refresh_license_ajax', [$this, 'atlt_handle_refresh_license_ajax']);
        }

        private function atlt_initialize_license() {
            $licenseKey = get_option(self::OPTION_LICENSE_KEY, "");
            $liceEmail = get_option(self::OPTION_LICENSE_EMAIL, get_bloginfo('admin_email'));
            
            LocoAutomaticTranslateAddonProBase::addOnDelete(function(){
               delete_option(self::OPTION_LICENSE_KEY);
               delete_option(self::OPTION_LICENSE_EMAIL);
            });

            if (LocoAutomaticTranslateAddonProBase::CheckWPPlugin($licenseKey, $liceEmail, $this->licenseMessage, $this->responseObj, ATLT_PRO_FILE)) {
                self::$form_status = true;
            } else {
                self::$form_status = false;
                if(!empty($licenseKey) && !empty($this->licenseMessage)) {
                    $this->showMessage = true;
                }
            }
        }

        function atlt_set_admin_style() {
            if (isset($_GET['page']) && sanitize_key($_GET['page']) === 'loco-atlt-dashboard') {
                wp_enqueue_style(
                    'atlt-dashboard-style',
                    ATLT_PRO_URL . 'admin/atlt-dashboard/css/admin-styles.css',
                    array(),
                    ATLT_PRO_VERSION,
                    'all'
            );
    		wp_enqueue_style("atlt-dashboard-style");
            }
        }

        function atlt_enqueue_scripts() {

            wp_enqueue_script(
                    'atlt-plugin-setting',
                    esc_url(ATLT_PRO_URL . 'admin/atlt-dashboard/js/atlt-plugin-setting.js'),
                    array('jquery'),
                    ATLT_PRO_VERSION,
                    true
                );

                if (isset($_GET['page']) && sanitize_key($_GET['page']) === 'loco-atlt-dashboard') {
                wp_enqueue_script(
                    'atlt-data-share-setting',
                    esc_url(ATLT_PRO_URL . 'admin/atlt-dashboard/js/atlt-data-share-setting.js'),
                    array('jquery'),
                    ATLT_PRO_VERSION,
                    true
                );
                
                // Localize script with AJAX nonce
                wp_localize_script('atlt-data-share-setting', 'atlt_ajax', array(
                    'nonce' => wp_create_nonce('atlt_refresh_license_nonce')
                ));
            }
        }

        /*
		|-------------------------------------------------------
		|   LocoAI – Auto Translate for Loco Translate (Pro)  admin page
		|-------------------------------------------------------
		*/
		function atlt_add_locotranslate_sub_menu() {

			add_submenu_page(
				'loco',
				'Loco Automatic Translate',
				'LocoAI',
				'manage_options',
				'loco-atlt-dashboard',
				array( $this, 'atlt_dashboard_page' )
			);
		}

        /**
         * Render the dashboard page with dynamic text domain support
         * 
         * @param string $text_domain The text domain for translations (default: 'loco-auto-translate')
         */
            function atlt_dashboard_page() {
                $text_domain = 'loco-auto-translate';
                $file_prefix = 'admin/atlt-dashboard/views/';
                
                $valid_tabs = $this->atlt_get_valid_tabs($text_domain);
                $buttons = $this->atlt_get_action_buttons($text_domain);

                // Whitelist of allowed tab files for security
                $allowed_tab_files = array(
                    'dashboard',
                    'ai-translations',
                    'settings',
                    'license'
                );

                // Get current tab with strict validation
                $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'license';
                
                // Validate against whitelist
                if (!in_array($tab, $allowed_tab_files, true)) {
                    $tab = 'dashboard';
                }
                
                $current_tab = array_key_exists($tab, $valid_tabs) ? $tab : 'license';
                
                // Start HTML output
                ?>
                <div class="atlt-dashboard-wrapper">
                    <div class="atlt-dashboard-header">
                        <div class="atlt-dashboard-header-left">
                            <img src="<?php echo esc_url(ATLT_PRO_URL . 'admin/atlt-dashboard/images/loco-addon-logo.svg'); ?>" 
                                alt="<?php esc_attr_e('Loco Translate Logo', $text_domain); ?>">
                            <div class="atlt-dashboard-tab-title">
                                <span>↳</span> <?php echo esc_html($valid_tabs[$current_tab]); ?>
                            </div>
                        </div>
                        <div class="atlt-dashboard-header-right">
                            <span><?php esc_html_e('Auto translate plugins & themes.', $text_domain); ?></span>
                            <?php foreach ($buttons as $button): ?>
                                <a href="<?php echo esc_url($button['url']); ?>" 
                                class="atlt-dashboard-btn" 
                                target="_blank"
                                aria-label="<?php echo isset($button['alt']) ? esc_attr($button['alt']) : ''; ?>">
                                    <?php if (isset($button['img'])): ?>
                                        <img src="<?php echo esc_url(ATLT_PRO_URL . 'admin/atlt-dashboard/images/' . $button['img']); ?>" 
                                            alt="<?php echo esc_attr($button['alt']); ?>">
                                    <?php endif; ?>
                                    <?php if (isset($button['text'])): ?>
                                        <span><?php echo esc_html($button['text']); ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Dashboard navigation', $text_domain); ?>">
                        <?php foreach ($valid_tabs as $tab_key => $tab_title): ?>
                            <a href="?page=loco-atlt-dashboard&tab=<?php echo esc_attr($tab_key); ?>" 
                            class="nav-tab <?php echo esc_attr($tab === $tab_key ? 'nav-tab-active' : ''); ?>">
                                <?php echo esc_html($tab_title); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    
                    <div class="tab-content">
                        <?php

                        // Construct file path with validated tab
                        $file_path = ATLT_PRO_PATH . $file_prefix . $tab . '.php';
                        
                        // Additional security: Prevent directory traversal
                        $real_file = realpath($file_path);
                        $base_path = realpath(ATLT_PRO_PATH . $file_prefix);
                        
                        if ($real_file === false || $base_path === false || 
                            strpos($real_file, $base_path) !== 0 || 
                            !file_exists($real_file)) {
                            wp_die(__('Sorry, you are not allowed to access this page.', 'atlt'));
                            return;
                        }
                        
                        require_once $real_file;

                        if($tab === 'license'||$tab === 'settings'){
                            
                            $licenseKey = get_option(self::OPTION_LICENSE_KEY,"");
                            $liceEmail = get_option( self::OPTION_LICENSE_EMAIL,"");
                            LocoAutomaticTranslateAddonProBase::addOnDelete(function(){
                               delete_option(self::OPTION_LICENSE_KEY);
                            });
                            
                            // if(LocoAutomaticTranslateAddonProBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,ATLT_PRO_FILE)){
                            if(get_option(self::OPTION_LICENSE_KEY)){
                                // echo "ddk";die();
                                if($tab === 'license'){
                                    atlt_render_license_page_pro($this->responseObj);
                                }
                                if($tab === 'settings'){
                                    atlt_render_settings_page_pro();
                                }
                            }else{
                                if($tab === 'license'){
                                    atlt_render_license_page();
                                }
                                if($tab === 'settings'){
                                    atlt_render_settings_page();
                                }
                            }
                        }
                        require_once ATLT_PRO_PATH . $file_prefix . 'sidebar.php';
                        
                        ?>
                    </div>
                    <?php require_once ATLT_PRO_PATH . $file_prefix . 'footer.php'; ?>
                </div>
                <?php
            }

        private function atlt_get_valid_tabs($text_domain) {
            return [
                // 'dashboard'       => __('Dashboard', $text_domain),
                // 'ai-translations' => __('AI Translations', $text_domain),
                // 'settings'        => __('Settings', $text_domain),
                'license'         => __('License', $text_domain)
            ];
        }

        private function atlt_get_action_buttons($text_domain) {
            return [
                [
                    'url'  => 'https://coolplugins.net/products/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=dashboard_header_pro',
                    'alt'  => __('Explore Cool Plugins', $text_domain),
                    'img'  => 'upgrade-now.svg',
                    'text' => __('Explore Cool Plugins', $text_domain)
                ],
                [
                    'url' => 'https://locoaddon.com/docs/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_header_pro',
                    'img' => 'document.svg',
                    'alt' => __('document', $text_domain)
                ],
                [
                    'url' => 'https://locoaddon.com/support/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=support&utm_content=dashboard_header_pro',
                    'img' => 'contact.svg',
                    'alt' => __('contact', $text_domain)
                ]
            ];
        }

        function atlt_handle_license_activation() {
            $this->atlt_check_user_capabilities();
            check_admin_referer('atlt-license');
            
            // Validate license key
            $license_key = !empty($_POST['license_code']) ? sanitize_text_field(wp_unslash($_POST['license_code'])) : '';
            if (empty($license_key)) {
                $this->atlt_redirect_with_error('missing_key');
                return;
            }

            // Validate email
            $license_email = !empty($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
            if (empty($license_email)) {
                $this->atlt_redirect_with_error('missing_email');
                return;
            }

            // Validate email format
            if (!is_email($license_email)) {
                $this->atlt_redirect_with_error('invalid_email');
                return;
            }
            
            if (!$this->atlt_is_valid_license_key($license_key)) {
                $this->atlt_redirect_with_error('invalid_format');
                return;
            }
            $error = '';
            $responseObj = null;
            
            if ($this->atlt_activate_license($license_key, $license_email, $error, $responseObj)) {
                update_option(self::OPTION_LICENSE_KEY, $license_key);
                update_option(self::OPTION_LICENSE_EMAIL, $license_email);
                delete_site_transient('update_plugins');
                $this->atlt_redirect_with_success();
            }
            
            // Map error message to error code
            $error_code = $this->atlt_map_error_to_code($error);
            $this->atlt_redirect_with_error($error_code);
        }

        private function atlt_check_user_capabilities() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
        }

        private function atlt_activate_license($licenseKey, $licenseEmail, &$error, &$responseObj) {
           
            if (LocoAutomaticTranslateAddonProBase::CheckWPPlugin($licenseKey, $licenseEmail, $error, $responseObj, ATLT_PRO_FILE)) {
                update_option(self::OPTION_LICENSE_KEY, $licenseKey);
                update_option(self::OPTION_LICENSE_EMAIL, $licenseEmail);
                delete_site_transient('update_plugins');
                return true;
            }
            return false;
        }

        private function atlt_redirect_with_success() {
            wp_redirect(admin_url('admin.php?page=loco-atlt-dashboard&tab=license&activated=true'));
            exit;
        }

        private function atlt_redirect_with_error($error) {
            wp_redirect(admin_url('admin.php?page=loco-atlt-dashboard&tab=license&error=' . urlencode($error)));
            exit;
        }

        function atlt_handle_license_deactivation() {
            $this->atlt_check_user_capabilities();
            check_admin_referer('atlt-license');
            
            $message = '';
            if (LocoAutomaticTranslateAddonProBase::RemoveLicenseKey(ATLT_PRO_FILE, $message)) {
                update_option(self::OPTION_LICENSE_KEY, '');
                update_option(self::OPTION_LICENSE_EMAIL, '');
                delete_site_transient('update_plugins');
                
                wp_redirect(admin_url('admin.php?page=loco-atlt-dashboard&tab=license&deactivated=true'));
                exit;
            }
            wp_redirect(admin_url('admin.php?page=loco-atlt-dashboard&tab=license&error=' . urlencode($message)));
            exit;
        }

        /**
         * Handle AJAX license refresh request
         */
        function atlt_handle_refresh_license_ajax() {
            // Check nonce for security
            if (!wp_verify_nonce($_POST['nonce'], 'atlt_refresh_license_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed.'));
                return;
            }

            $this->atlt_check_user_capabilities();
            
            // Get existing license key and email
            $license_key    = get_option(self::OPTION_LICENSE_KEY, '');
            $license_email  = get_option(self::OPTION_LICENSE_EMAIL, '');

            if (empty($license_key) || empty($license_email)) {
                wp_send_json_error(array('message' => 'No license information found to refresh.'));
                return;
            }

            $error = '';
            $responseObj = null;

            // Use the same activation logic to refresh the license
            if ($this->atlt_activate_license($license_key, $license_email, $error, $responseObj)) {
                
                // Get updated license information
                $license_info = array(
                    'is_valid' => $responseObj->is_valid ? $responseObj->is_valid:false,
                    'license_title' => $responseObj->license_title ? $responseObj->license_title : '',
                    'expire_date' => $responseObj->expire_date ? $responseObj->expire_date : '',
                    'market' => $responseObj->market ? $responseObj->market : '',
                    'support_end' => $responseObj->support_end ? $responseObj->support_end : '',
                );
                
                
                wp_send_json_success(array(
                    'message' => 'License Status Updated successfully!',
                    'license_info' => $license_info,
                    'version_available_message' => ProHelpers::getVersionAvailableMessage()
                ));
            } else {
                // Map error message to error code
                $error_code = $this->atlt_map_error_to_code($error);
                $error_message = $this->atlt_get_error_message($error_code);
                wp_send_json_error(array('message' => $error_message));
            }
        }


        private function atlt_is_valid_license_key($licenseKey) {
            // Define the pattern for the license key structure
            $pattern = '/^[0-9A-F][0-9A-F]{7}-[0-9A-F]{8}-[0-9A-F]{8}-[0-9A-F]{8}$/i';
            
            // Check if the license key matches the pattern
            return preg_match($pattern, $licenseKey) ? true : false;
        }

        // Add new method to display error messages
        public function atlt_display_license_error_messages() {
           
            if (isset($_GET['page']) && sanitize_key($_GET['page']) === 'loco-atlt-dashboard' && isset($_GET['tab']) && sanitize_key($_GET['tab']) === 'license') {
              
                // Display error message if exists
                if ( isset( $_GET['error'] ) ) {
                    $error_param   = sanitize_text_field( wp_unslash( $_GET['error'] ) );
                    $error_message = $this->atlt_get_error_message( $error_param );
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html( $error_message ); ?></p>
                    </div>
                    <?php
                }

                // Display success message
                if (isset($_GET['activated']) && sanitize_key($_GET['activated']) === 'true') {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e('License activated successfully!', 'loco-auto-translate'); ?></p>
                    </div>
                    <?php
                }

                // Display deactivation message
                if (isset($_GET['deactivated']) && sanitize_key($_GET['deactivated']) === 'true') {
                    ?>
                    <div class="notice notice-info is-dismissible">
                        <p><?php esc_html_e('License deactivated successfully!', 'loco-auto-translate'); ?></p>
                    </div>
                    <?php
                }

                // Display license message if exists
                if ($this->showMessage && !empty($this->licenseMessage)) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html($this->licenseMessage); ?></p>
                    </div>
                    <?php
                }
            }
        }

        // Add new method to get error messages
        private function atlt_get_error_message($error_code) {
            
            $messages = array(
                'missing_key' => __('License key is required.', 'loco-auto-translate'),
                'missing_email' => __('Email address is required.', 'loco-auto-translate'),
                'invalid_email' => __('Please enter a valid email address.', 'loco-auto-translate'),
                'invalid_format' => __('Invalid license key format. Please check your license key.', 'loco-auto-translate'),
                'invalid_key' => __('Invalid license key. Please check your license key and try again.', 'loco-auto-translate'),
                'expired' => __('Your license key has expired. Please renew your license.', 'loco-auto-translate'),
                'disabled' => __('Your license key has been disabled.', 'loco-auto-translate'),
                'no_activations' => __('License quota has been over, you can not add more domain with this license key.', 'loco-auto-translate'),
                'refunded' => __('Your purchase key has been refunded.', 'loco-auto-translate'),
                'wrong_license_status' => __('Your license key is inactive, has been refunded, or has exceeded the allowed domain limit.', 'loco-auto-translate'),
                'domain_exceeded' => __('Your license key has exceeded the allowed domain limit.', 'loco-auto-translate'),
                'default' => __('An error occurred while validating your license. Please try again.', 'loco-auto-translate')
            );

            return isset($messages[$error_code]) ? $messages[$error_code] : $messages['default'];
        }

        // Add new method to map error messages to codes
        private function atlt_map_error_to_code($error) {
            $error = strtolower($error);
           
            if (strpos($error, 'invalid') !== false) {
                return 'invalid_key';
            } elseif (strpos($error, 'disabled') !== false || strpos($error, 'temporary inactivated') !== false || strpos($error, 'inactive_license') !== false) {
                return 'disabled';
            } elseif (strpos($error, 'license quota has been over') !== false || strpos($error, 'installed on another domain') !== false) {
                return 'no_activations';
            } elseif (strpos($error, 'refunded') !== false || strpos($error, 'refunded_license') !== false) {
                return 'refunded';
            } elseif (strpos($error, 'expired') !== false) {
                return 'expired';
            } elseif (strpos($error, 'wrong_license_status') !== false) {
                return 'wrong_license_status';
            } elseif (strpos($error, 'domain_exceeded') !== false) {
                return 'domain_exceeded';
            }
            return 'default';
        }

        // Add this new method
        public function atlt_display_license_key_notice() {
            // Only show if license is not activated and we're not already on the license page
            if (!self::$form_status && 
                (!isset($_GET['page']) || sanitize_key($_GET['page']) !== 'loco-atlt-dashboard' || 
                !isset($_GET['tab']) || sanitize_key($_GET['tab']) !== 'license')) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php echo wp_kses_post( __( 'Please <a href="admin.php?page=loco-atlt-dashboard&tab=license">enter your license key</a> to enable automatic updates and premium support for LocoAI – Auto Translate for Loco Translate (Pro).', 'loco-auto-translate' ) ); ?></p>
                </div>
                <?php
            }
        }
    }

    new LocoAutomaticTranslateAddonPro();
}