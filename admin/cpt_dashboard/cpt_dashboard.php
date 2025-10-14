<?php

if(!defined('ABSPATH')){
    exit;
}

/**
 * Dashboard
 * 
 * example:
 * 
 * Dashbord initialize
 * if(!class_exists('Atlt_Dashboard')){
 * $dashboard=Atlt_Dashboard::instance();
 * }
 * 
 * Store options
 * if(class_exists('Atlt_Dashboard')){
 *  Atlt_Dashboard::store_options(
 *      'prefix', // Required plugin prefix
 *      'unique_key',// Optional unique key is used to update the data based on post/page id or plugin/themes name
 *      'update', // Optional preview string count or character count update or replace
 *      array(
 *           'post/page or theme/plugin name' => 'name or id',
 *          'post_title (optional)' => 'Post Title',
 *          'service_provider' => 'google', // don't change this key
 *          'source_language' => 'en', // don't change this key
 *          'target_language' => 'fr', // don't change this key
 *          'time_taken' => '10', // don't change this key
 *          'string_count'=>10, 
 *          'character_count'=>100, 
 *          'date_time' => date('Y-m-d H:i:s'),
 *      ) // Required data array
 *  );
 * }
 * 
 * Add Tabs
 * add_filter('cpt_dashboard_tabs', function($tabs){
 *  $tabs[]=array(
 *      'prefix'=>'tab_name', // Required
 *      'tab_name'=>'Tab Name', // Required
 *      'columns'=>array(
 *          'post_id or plugin_name'=>'Post Id or Plugin Name',
 *          'post_title (optional)'=>'Post Title',
 *          'string_count'=>'String Count',
 *           'character_count'=>'Character Count',
 *           'service_provider'=>'Service Provider',
 *           'time_taken'=>'Time Taken',
 *           'date_time'=>'Date Time',
 *      ) // columns Required
 *  );
 *  return $tabs;
 * });
 * 
 * Display review notice
 * if(class_exists('Atlt_Dashboard')){
 *  Atlt_Dashboard::review_notice(
 *      'prefix', // Required
 *      'plugin_name', // Required
 *      'url', // Required
 *      'icon' // Optional
 *  );
 * }
 * 
 * Get translation data
 * if(class_exists('Atlt_Dashboard')){
 *  Atlt_Dashboard::get_translation_data(
 *      'prefix', // Required
 *      array(
 *          'editor_type' => 'gutenberg', // optional return data based on editor type
 *          'post_id' => '123', // optional return data based on post id
 *      ) // Optional
 *  );
 * }
 */

if(!class_exists('Atlt_Dashboard')){
    class Atlt_Dashboard{

        /**
         * Init
         * @var object
         */
        private static $init;

        /**
         * Tabs data
         * @var array
         */
        private $tabs_data=array();

        /**
         * Instance
         * @return object
         */
        public static function instance(){
            if(!isset(self::$init)){
                self::$init = new self();
            }
            return self::$init;
        }

        public function __construct(){
            add_action('wp_ajax_atlt_hide_review_notice', array($this, 'atlt_hide_review_notice'));
        }

        /**
         * Sort column data
         * @param array $columns
         * @param array $value
         * @return array
         */
        public function sort_column_data($columns, $value){
            $result = array();
            foreach($columns as $key => $label) {
                $result[$key] = isset($value[$key]) ? sanitize_text_field($value[$key]) : '';
            }
            return $result;
        }

        /**
         * Store options
         * @param string $plugin_name
         * @param string $prefix
         * @param array $data
         * @return void
         */
        public static function store_options($prefix='', $unique_key='', $old_data='update', array $data = array()){
            if(!empty($prefix) && isset($data['string_count']) && isset($data['character_count'])){
                $prefix = sanitize_key($prefix);
                $all_data = get_option('cpt_dashboard_data', array());
                
                if(isset($all_data[$prefix])){
                    $data_update = false;
                    foreach($all_data[$prefix] as $key => $translate_data){
                        if(!empty($unique_key) && isset($translate_data[$unique_key]) && 
                        sanitize_text_field($translate_data[$unique_key]) === sanitize_text_field($data[$unique_key]) && 
                        sanitize_text_field($translate_data['service_provider']) === sanitize_text_field($data['service_provider']) &&
                        sanitize_text_field($translate_data['target_language']) === sanitize_text_field($data['target_language']) &&
                        sanitize_text_field($translate_data['source_language']) === sanitize_text_field($data['source_language'])
                        ){
                            
                            if($old_data=='update'){
                                $data['string_count'] = absint($data['string_count']) + absint($translate_data['string_count']);
                                $data['character_count'] = absint($data['character_count']) + absint($translate_data['character_count']);
                                $data['time_taken'] = absint($data['time_taken']) + absint($translate_data['time_taken']);
                            }
                            
                            foreach($data as $id => $value){
                                $all_data[$prefix][$key][sanitize_key($id)] = sanitize_text_field($value);
                            }
                            $data_update = true;
                        }
                    }

                    if(!$data_update){
                        $all_data[$prefix][] = array_map('sanitize_text_field', $data);
                    }
                }else{
                    $all_data[$prefix][] = array_map('sanitize_text_field', $data);
                }

                update_option('cpt_dashboard_data', $all_data);
            }
        }

        /**
         * Get translation data
         * @param string $prefix
         * @return array
         */
        public static function get_translation_data($prefix, $key_exists=array()){
            $prefix = sanitize_key($prefix);
            $all_data = get_option('cpt_dashboard_data', array());
            $data = array();

            if(isset($all_data[$prefix])){
                $total_string_count = 0;
                $total_character_count = 0;

                foreach($all_data[$prefix] as $key => $value){

                    $continue=false;
                    foreach($key_exists as $key_exists_key => $key_exists_value){
                        if(!isset($value[$key_exists_key]) || (isset($value[$key_exists_key]) && $value[$key_exists_key] !== $key_exists_value)){
                            $continue=true;
                            break;
                        }
                    }

                    if($continue){
                        continue;
                    }

                    $total_string_count += isset($value['string_count']) ? absint($value['string_count']) : 0;
                    $total_character_count += isset($value['character_count']) ? absint($value['character_count']) : 0;
                }

                $data = array(
                    'prefix' => $prefix,
                    'data' => array_map(function($item) {
                        return array_map('sanitize_text_field', $item);
                    }, $all_data[$prefix]),
                    'total_string_count' => $total_string_count,
                    'total_character_count' => $total_character_count,
                );
            }else{
                $data = array(
                    'prefix' => $prefix,
                    'total_string_count' => 0,
                    'total_character_count' => 0,
                );
            }

            return $data;
        }

        public static function ctp_enqueue_assets(){
            if(function_exists('wp_style_is') && !wp_style_is('atlt-review-style', 'enqueued')){
                $plugin_url = plugin_dir_url(ATLT_PRO_FILE);
                wp_enqueue_style('atlt-review-style', esc_url($plugin_url.'admin/cpt_dashboard/assets/css/cpt-dashboard.css'), array(), ATLT_PRO_VERSION, 'all');
                wp_enqueue_script('atlt-review-script', esc_url($plugin_url.'admin/cpt_dashboard/assets/js/cpt-dashboard.js'), array('jquery'), ATLT_PRO_VERSION, true);
            }
        }

        public static function format_number_count($number){
            if ($number >= 1000000) {
                return round($number / 1000000, 1) . 'M';
            } elseif ($number >= 1000) {
                return round($number / 1000, 1) . 'K';
            }
            return $number;
        }

        public static function review_notice($prefix, $plugin_name, $url){
            if(self::atlt_hide_review_notice_status($prefix)){
                return;
            }
            
            $translation_data = self::get_translation_data($prefix);
            
            $total_character_count = is_array($translation_data) && isset($translation_data['total_character_count']) ? $translation_data['total_character_count'] : 0;
            
            if($total_character_count < 50000){ 
                return;
            }

            $total_character_count = self::format_number_count($total_character_count);

            add_action('admin_enqueue_scripts', array(self::class, 'ctp_enqueue_assets'));

            

            $prefix = sanitize_key($prefix);
            $plugin_name = wp_kses_post($plugin_name);
            $url = esc_url($url);

            $characters_text = sprintf(
                esc_html__('%s characters', 'cp-notice'),
                esc_html($total_character_count)
            );

            $message_filled = sprintf(
                __('Thanks for using <b>%1$s</b>! You have translated <b>%2$s</b> characters so far using our plugin!<br>Please give us a quick rating, it works as a boost for us to keep working on more <a style="text-decoration: none;" href="'.esc_url('https://coolplugins.net/').'" target="_blank" rel="noopener noreferrer"><b>Cool Plugins</b></a>!', 'cp-notice'),
                $plugin_name,
                $total_character_count
            );
            // Use restricted HTML filtering for security - only allow safe formatting tags
            $allowed_tags = array(
                'strong' => array(),
                'em' => array(),
                'br' => array(),
                'b' => array(),
                'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array(), 'style' => array()),
            );
            $message = wp_kses($message_filled, $allowed_tags);

            add_action('admin_notices', function() use ($message, $prefix, $url, $plugin_name){
                $nonce = wp_create_nonce('atlt_hide_review_notice_' . get_current_user_id());

                $html= '<div class="notice notice-info cpt-review-notice notice notice-info is-dismissible">';
                
                $html .= '<div class="cpt-review-notice-content"><p>'.wp_kses_post($message).'</p><div class="atlt-review-notice-dismiss" data-prefix="'.esc_attr($prefix).'" data-nonce="'.esc_attr($nonce).'"><a href="'.esc_url($url).'" target="_blank" class="button button-primary">Rate Now! ★★★★★</a><button class="button cpt-already-reviewed">'.esc_html__('Already Reviewed', 'cp-notice').'</button><button class="button cpt-not-interested">'.esc_html__('Not Interested', 'cp-notice').'</button></div></div></div>';
                
                // Output controlled HTML structure with pre-escaped variables
                // Using wp_kses with specific allowed tags for enhanced security
                $allowed_html = array(
                    'div' => array('class' => array(), 'data-prefix' => array(), 'data-nonce' => array()),
                    'img' => array('class' => array(), 'src' => array(), 'alt' => array()),
                    'p' => array(),
                    'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array(), 'style' => array()),
                    'button' => array('class' => array()),
                    'strong' => array(),
                    'b' => array(),
                    'em' => array(),
                    'br' => array()
                );
                echo wp_kses($html, $allowed_html);
            });

            add_action('atlt_display_admin_notices', function() use ($message, $prefix, $url){
                $nonce = wp_create_nonce('atlt_hide_review_notice_' . get_current_user_id());

                $html= '<div class="notice notice-info cpt-review-notice notice notice-info is-dismissible">';

                $html .= '<div class="cpt-review-notice-content"><p>'.wp_kses_post($message).'</p><div class="atlt-review-notice-dismiss" data-prefix="'.esc_attr($prefix).'" data-nonce="'.esc_attr($nonce).'"><a href="'.esc_url($url).'" target="_blank" class="button button-primary">Rate Now! ★★★★★</a><button class="button cpt-already-reviewed">'.esc_html__('Already Reviewed', 'cp-notice').'</button><button class="button cpt-not-interested">'.esc_html__('Not Interested', 'cp-notice').'</button></div></div></div>';
                
                // Output controlled HTML structure with pre-escaped variables
                // Using wp_kses with specific allowed tags for enhanced security
                $allowed_html = array(
                    'div' => array('class' => array(), 'data-prefix' => array(), 'data-nonce' => array()),
                    'img' => array('class' => array(), 'src' => array(), 'alt' => array()),
                    'p' => array(),
                    'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array(), 'style' => array()),
                    'button' => array('class' => array()),
                    'strong' => array(),
                    'b' => array(),
                    'em' => array(),
                    'br' => array()
                );
                echo wp_kses($html, $allowed_html);
            });
        }

        public static function atlt_hide_review_notice_status($prefix){
            $review_notice_dismissed = get_option('cpt_review_notice_dismissed', array());
            return isset($review_notice_dismissed[$prefix]) ? $review_notice_dismissed[$prefix] : false;
        }

        public function atlt_hide_review_notice(){
            // User-specific nonce verification for enhanced CSRF protection
            $nonce_action = 'atlt_hide_review_notice_' . get_current_user_id();
            $nonce_verified = check_ajax_referer($nonce_action, 'nonce', false);
            
            if (!$nonce_verified) {
                // Log potential CSRF attempt (without exposing sensitive user data)
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('CSRF attempt detected: Invalid nonce for review notice dismissal');
                }
                wp_send_json_error(array('message' => 'nonce_verification_failed'), 403);
            }
            
            // Capability check - ensure only administrators can dismiss review notices
            if ( ! current_user_can('manage_options') ) {
                wp_send_json_error(array('message' => 'forbidden'), 403);
            }
            
            // Verify HTTP method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                wp_send_json_error(array('message' => 'invalid_request_method'), 405);
            }
            
            $prefix = isset($_POST['prefix']) ? sanitize_key(wp_unslash($_POST['prefix'])) : '';
            if ($prefix === '') {
                wp_send_json_error(array('message' => 'invalid_prefix'), 400);
            }
            
            // Additional validation: ensure prefix is reasonable length and format
            if (strlen($prefix) > 50 || !preg_match('/^[a-zA-Z0-9_-]+$/', $prefix)) {
                wp_send_json_error(array('message' => 'invalid_prefix_format'), 400);
            }
            
            $review_notice_dismissed = get_option('cpt_review_notice_dismissed', array());
            $review_notice_dismissed[$prefix] = true;
            update_option('cpt_review_notice_dismissed', $review_notice_dismissed);
            wp_send_json_success();
        }
    }
}
