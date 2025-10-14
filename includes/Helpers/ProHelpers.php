<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * @package LocoAI – Auto Translate for Loco Translate (Pro)
 */

class ProHelpers{

    // return user type
    public static function userType(){
        $type='';
      if(get_option('atlt-type')==false || get_option('atlt-type')=='free'){
            return $type='free';
        }else if(get_option('atlt-type')=='pro'){
            return $type='pro';
        }  
       
    }
  
    // validate key
    public static function validKey($key){
        // Sanitize input
        $key = sanitize_text_field($key);
        if (preg_match("/^([A-Z0-9]{8})-([A-Z0-9]{8})-([A-Z0-9]{8})-([A-Z0-9]{8})$/", $key)) {
            return true;
        } else {
            return false;
        }
    }
    //grab key
    public static function getLicenseKey(){
        $licenseKey=get_option("LocoAutomaticTranslateAddonPro_lic_Key","");
        if($licenseKey==''||$licenseKey==false){
            return false;
        }else{
            return sanitize_text_field($licenseKey); // Sanitize output
          }
    }
  
    /*
   |----------------------------------------------------------------|
   |       return the total amount of time saved on translation     |
   | @param $characters int number of translated charachters        |
   |----------------------------------------------------------------|
   */
   public static function atlt_time_saved_on_translation( $characters ){
        $num_chars   = intval( $characters );
        if ( $num_chars <= 0 ) {
            return;
        }
        $total_saved = $num_chars / 1800;
        if( $total_saved >=1 && is_float( $total_saved ) ){
            $hour = intval( $total_saved );
            $minute =  $total_saved - $hour;
            $minute = intval( $minute * 60 );
            return $hour .' hour and '. round($minute,2).' minutes';
        }else{
            $minute = floatval($total_saved) * 60;
            if( $minute <1 ){
                return round($minute * 60, 2) . ' seconds';
            }
            return round($minute,2) . ' minutes';
        }
    }

    //  For Get User Extra Data And Server Infomation

	static function atlt_get_user_info() {

		global $wpdb;
	
		// Server and WP environment details
        $server_info = [
			'server_software'        => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : 'N/A',
            'mysql_version'          => $wpdb ? sanitize_text_field( method_exists( $wpdb, 'db_version' ) ? $wpdb->db_version() : $wpdb->get_var( 'SELECT VERSION()' ) ) : 'N/A',
			'php_version'            => sanitize_text_field(phpversion() ?: 'N/A'),
			'wp_version'             => sanitize_text_field(get_bloginfo('version') ?: 'N/A'),
			'wp_debug'               => (defined('WP_DEBUG') && WP_DEBUG) ? 'Enabled' : 'Disabled',
			'wp_memory_limit'        => sanitize_text_field(ini_get('memory_limit') ?: 'N/A'),
			'wp_max_upload_size'     => sanitize_text_field(ini_get('upload_max_filesize') ?: 'N/A'),
			'wp_permalink_structure' => sanitize_text_field(get_option('permalink_structure') ?: 'Default'),
			'wp_multisite'           => is_multisite() ? 'Enabled' : 'Disabled',
			'wp_language'            => sanitize_text_field(get_option('WPLANG') ?: get_locale()),
			'wp_prefix'              => isset($wpdb->prefix) ? sanitize_key($wpdb->prefix) : 'N/A',
		];
	
		// Theme details
		$theme = wp_get_theme();

		$theme_data = [
			'name'      => sanitize_text_field($theme->get('Name')),
			'version'   => sanitize_text_field($theme->get('Version')),
			'theme_uri' => esc_url($theme->get('ThemeURI')),
		];
	
		// Ensure plugin functions are loaded
		if ( ! function_exists('get_plugins') ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	
		// Active plugins details
		$active_plugins = get_option('active_plugins', []);
		$plugin_data = [];
	
		foreach ( $active_plugins as $plugin_path ) {

			$raw_plugin_path = is_string( $plugin_path ) ? $plugin_path : '';
			if ( $raw_plugin_path === '' ) {
				continue;
			}
			$normalized_relative = wp_normalize_path( sanitize_text_field( $raw_plugin_path ) );
			$plugin_base_dir = wp_normalize_path( WP_PLUGIN_DIR );
			$full_candidate = wp_normalize_path( trailingslashit( WP_PLUGIN_DIR ) . ltrim( $normalized_relative, '/\\' ) );
			$real_full_path = function_exists( 'realpath' ) ? realpath( $full_candidate ) : $full_candidate;
			if ( ! $real_full_path ) {
				continue;
			}
			$normalized_real = wp_normalize_path( $real_full_path );
			if ( strpos( $normalized_real . '/', $plugin_base_dir . '/' ) !== 0 ) {
				continue;
			}
			$plugin_info = get_plugin_data( $normalized_real );
			$author_url = ( isset( $plugin_info['AuthorURI'] ) && !empty( $plugin_info['AuthorURI'] ) ) ? esc_url( $plugin_info['AuthorURI'] ) : 'N/A';
			$plugin_url = ( isset( $plugin_info['PluginURI'] ) && !empty( $plugin_info['PluginURI'] ) ) ? esc_url( $plugin_info['PluginURI'] ) : '';

			$plugin_data[] = [

				'name'       => sanitize_text_field($plugin_info['Name']),
				'version'    => sanitize_text_field($plugin_info['Version']),
				'plugin_uri' =>  !empty($plugin_url) ? $plugin_url : $author_url,
			];
		}
	
		return [
			'server_info'   => $server_info,
			'extra_details' => [
				'wp_theme'       => $theme_data,
				'active_plugins' => $plugin_data,
			],
		];
	}

    /**
     * Get version available message if update is available
     * 
     * @return string Version available message or empty string
     */
    public static function getVersionAvailableMessage() {
        
        // Get license info and update info for the plugin
        $update_info = LocoAutomaticTranslateAddonProBase::getInstance()->__plugin_updateInfo();

        // Initialize and sanitize version information
        $latest_version = isset($update_info->new_version) ? sanitize_text_field($update_info->new_version) : '';
        $version_available_message = '';

        // Prepare update available message if current version is outdated
        $plugin_basename = plugin_basename(ATLT_PRO_FILE);
        if ( ! empty($latest_version) && version_compare(ATLT_PRO_VERSION, $latest_version, '<') ) {

            list($plugin_slug, $plugin_file) = explode('/', $plugin_basename);

            $plugin_info_url = add_query_arg([
                'tab'       => 'plugin-information',
                'plugin'    => $plugin_slug . '/' . $plugin_file,
                'section'   => 'changelog',
                'TB_iframe' => 'true',
                'width'     => 772,
                'height'    => 390,
            ], admin_url('plugin-install.php'));

            $version_available_message = sprintf(
                /* translators: %s: version number with link */
                __('Version %s is available.', 'atlt'),
                sprintf(
                    '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
                    esc_url($plugin_info_url),
                    esc_attr(sprintf(__('View changelog for version %s', 'atlt'), $latest_version)),
                    esc_html(sprintf(__('%s (View details)', 'atlt'), $latest_version))
                )
            );
        }
        
        return $version_available_message;
    }
    
      
}
