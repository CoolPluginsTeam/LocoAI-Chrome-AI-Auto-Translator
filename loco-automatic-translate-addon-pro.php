<?php
/*
Plugin Name:LocoAI – Auto Translate for Loco Translate (Pro)
Description:Auto translation addon for Loco Translate – translate plugin & theme strings using AI tools like Google Translate, DeepL, ChatGPT, Gemini, OpenAI & more.
Version:2.2.2
License:GPLv3
Text Domain:loco-translate-addon
Domain Path:languages
Author:Cool Plugins
Author URI:https://coolplugins.net/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'ATLT_PRO_FILE', __FILE__ );
define( 'ATLT_PRO_URL', plugin_dir_url( ATLT_PRO_FILE ) );
define( 'ATLT_PRO_PATH', plugin_dir_path( ATLT_PRO_FILE ) );
define( 'ATLT_PRO_VERSION', '2.2.2' );
define( 'ATLT_PLUGIN_BASENAME', plugin_basename( ATLT_PRO_FILE ) );

if ( ! defined( 'ATLT_FEEDBACK_API' ) ) {
    define( 'ATLT_FEEDBACK_API', "https://feedback.coolplugins.net/" );
}


if ( ! class_exists( 'LocoAutoTranslateAddonPro' ) ) {

	/** Singleton ************************************/
	final class LocoAutoTranslateAddonPro {


		/**
		 * The unique instance of the plugin.
		 *
		 * @var LocoAutoTranslateAddonPro
		 */

		private static $instance;

		/**
		 * Gets an instance of plugin.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();

				self::$instance->register();

			}

			return self::$instance;
		}
		/**
		 * Constructor.
		 */
		public function __construct() {
		
			//  $this->init_ai_translate_service();
			
			
		}

		/**
		 * Registers our plugin with WordPress.
		 */
		public static function register() {

			 $thisPlugin = self::$instance;
			register_activation_hook( ATLT_PRO_FILE, array( $thisPlugin, 'atlt_activate' ) );
			register_deactivation_hook( ATLT_PRO_FILE, array( $thisPlugin, 'atlt_deactivate' ) );

			add_action('admin_init', array($thisPlugin, 'atlt_do_activation_redirect'));

			// run actions and filter only at admin end.
			if ( is_admin() ) {

				add_action( 'plugins_loaded', array( $thisPlugin, 'atlt_check_required_loco_plugin' ) );
				add_action( 'init', array( $thisPlugin, 'atlt_load_textdomain' ) );
				// add notice to use latest loco translate addon
				add_action( 'init', array( $thisPlugin, 'atlt_verify_loco_version' ) );
				add_action( 'init', array( $thisPlugin, 'onInit' ) );
				/*** Plugin Setting Page Link inside All Plugins List */
				add_filter( 'plugin_action_links_' . plugin_basename( ATLT_PRO_FILE ), array( $thisPlugin, 'atlt_settings_page_link' ) );

				add_filter('plugin_row_meta', array($thisPlugin,'atlt_add_docs_link_to_plugin_meta'), 10, 2);

				add_action( 'plugins_loaded', array( $thisPlugin, 'atlt_include_files' ) );
				add_action( 'admin_enqueue_scripts', array( $thisPlugin, 'atlt_enqueue_scripts' ) );

				
				if ( isset( $_GET['page'] ) ) {
					// Sanitize immediately after retrieving the parameter
					$page_param = sanitize_key( wp_unslash( $_GET['page'] ) );
					
					// Validate against whitelist of allowed page values
					$allowed_pages = array( 'loco-atlt-dashboard' );
					if ( in_array( $page_param, $allowed_pages, true ) ) {
							add_action('admin_print_scripts', array($thisPlugin, 'atlt_hide_unrelated_notices'));
					}
				}

				/*since version 2.1 */
				add_filter( 'loco_api_providers', array( $thisPlugin, 'atlt_register_api' ), 10, 1 );
				add_action( 'loco_api_ajax', array( $thisPlugin, 'atlt_ajax_init' ), 0, 0 );
				add_action( 'wp_ajax_save_all_translations', array( $thisPlugin, 'atlt_save_translations_handler' ) );
				add_action( 'wp_ajax_atlt_cool_plugins_admin_notice', array( $thisPlugin, 'atlt_admin_notice_dismiss' ) );
				
				/*
				since version 2.0
				Yandex translate widget integration
				*/
				// add no translate attribute in html tag
				$action_param = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
				if ( $action_param === 'file-edit' ) {
					// add_action( 'admin_footer', array( $thisPlugin, 'atlt_load_ytranslate_scripts' ), 100 );
					add_action( 'admin_footer', array( $thisPlugin, 'atlt_load_gtranslate_scripts' ), 100 );
					// add_filter( 'admin_body_class', array( $thisPlugin, 'atlt_add_custom_class' ) );
// 
				}

				// add_action( 'init', array( $thisPlugin, 'atlt_set_gtranslate_cookie' ) );

				add_action( 'after_plugin_row_' . ATLT_PLUGIN_BASENAME, array( $thisPlugin, 'atlt_plugin_custom_notice' ), 10, 2 );
				add_filter( 'site_transient_update_plugins', array( $thisPlugin, 'atlt_hide_plugin_update_notice' ) );
			}

		}

		public function atlt_add_docs_link_to_plugin_meta($links, $file) {
			if (plugin_basename(ATLT_PRO_FILE) === $file) {
				$docs_link = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url('https://locoaddon.com/docs/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=plugins_list'), esc_html__('Docs', 'loco-translate-addon'));
				$links[] = $docs_link;
			}
			return $links;
		}

		

		public function atlt_plugin_custom_notice( $plugin_file, $plugin_data ) {
			
    				// Get license info and update info for the plugin
		$license_info = LocoAutomaticTranslateAddonProBase::GetRegisterInfo();
		$update_info = LocoAutomaticTranslateAddonProBase::getInstance()->__plugin_updateInfo();
		// Get version available message using common helper
		// $version_available_message = ProHelpers::getVersionAvailableMessage();
		$plugin_basename = plugin_basename(ATLT_PRO_FILE);

		if ( ! LocoAutomaticTranslateAddonPro::$form_status &&  $plugin_basename === ATLT_PLUGIN_BASENAME ) {
				
				$renew_link = wp_kses_post(
					__( $version_available_message.' Please <a href="admin.php?page=loco-atlt-dashboard&tab=license">enter your license key</a> to enable automatic updates and premium support for LocoAI – Auto Translate for Loco Translate (Pro)', 'atlt' ),
					array(
						'a' => array(
							'href' => array()
						)
					)
				);

			} else {

				// Check if new version is available and license is invalid/expired
				if ( empty( $update_info->download_link ) ) {
					
					$is_expired = (empty($update_info->is_downloadable) ) ? 'license' : 'support';
					
					$message = ' Your ' . $is_expired . ' has expired,';
					$renew_text = ( ! empty( $license_info->market ) && $license_info->market === 'E' ) 
						? 'Please renew your ' . $is_expired . ' to continue receiving automatic updates and priority support.'
						: 'Please <a href="https://my.coolplugins.net/account/subscriptions/" target="_blank" rel="noopener noreferrer">Renew now</a> to continue receiving automatic updates and priority support.';
					
					$renew_link = $version_available_message . $message . ' ' . $renew_text;
				}
			}

			if ( ! empty( $renew_link )) {
					?>
					<tr class="plugin-update-tr active atlt-pro">
						<td colspan="4" class="plugin-update colspanchange">
							<div class="update-message notice inline notice-warning notice-alt"> 
								<p><?php echo wp_kses_post( $renew_link ); ?></p>
							</div>
						</td>
					</tr>
					<?php
			}
		}

		/**
		 * Hide WordPress core plugin update notice when license is not valid
		 * 
		 * @param object $transient The site transient for plugin updates
		 * @return object Modified transient
		 */
		public function atlt_hide_plugin_update_notice( $transient ) {

			$update_info = LocoAutomaticTranslateAddonProBase::getInstance()->__plugin_updateInfo();
			$license_info = LocoAutomaticTranslateAddonProBase::GetRegisterInfo();

				if ( empty($update_info->download_link) || $license_info == null){
					if ( isset( $transient->response ) && isset( $transient->response[ATLT_PLUGIN_BASENAME] ) ) {
						unset( $transient->response[ATLT_PLUGIN_BASENAME] );
					}
				}
			
			return $transient;
		}


		public function init_ai_translate_service() {	
			// require_once ATLT_PRO_PATH . 'includes/Helpers/ProHelpers.php';	
		}
		
		/*
		|----------------------------------------------------------------------	
		| Redirect to plugin page after activation
		|----------------------------------------------------------------------
		*/

		public function atlt_plugin_redirection($plugin) {
			if (plugin_basename(ATLT_PRO_FILE) === $plugin) {
				wp_safe_redirect( admin_url( 'admin.php?page=loco-atlt-dashboard' ) );
				exit;
			}
		}
		


		
			public function atlt_admin_notice_dismiss() {
		$id = isset( $_REQUEST['id'] ) ? sanitize_key( wp_unslash( $_REQUEST['id'] ) ) : '';
		if ( $id === '' ) {
			wp_send_json_error( array( 'message' => 'invalid_id' ), 400 );
		}
		$wp_nonce = $id . '_notice_nonce';
		// CSRF Protection - Verify nonce first before any other operations
		if ( ! check_ajax_referer( $wp_nonce, '_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'nonce verification failed!' ), 403 );
		}
		// Capability check - prevent unauthorized users from dismissing admin notices
		// Use manage_options capability for admin notice dismissal (administrative operation)
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		update_option( $id . '_remove_notice', 'yes' );
		wp_send_json_success( array( 'message' => 'Admin message removed!' ) );
	}
		public function onInit() {
			if ( in_array(
				'automatic-translator-addon-for-loco-translate/automatic-translator-addon-for-loco-translate.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
			) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				// Ensure the plugin is deactivated securely
				if ( current_user_can( 'activate_plugins' ) ) {
					deactivate_plugins( 'automatic-translator-addon-for-loco-translate/automatic-translator-addon-for-loco-translate.php' );
					$get_opt_in = get_option('atlt_feedback_opt_in');

					if ($get_opt_in =='yes' && !wp_next_scheduled('atlt_extra_data_update')) {

						wp_schedule_event(time(), 'every_30_days', 'atlt_extra_data_update');
					}
				}
				return;
			}

			if ( isset( $_GET['action'] ) && $_GET['action'] === 'file-edit' ) {
				$action_param_oninit = sanitize_key( wp_unslash( $_GET['action'] ) );
				// add notice if license key is missing
				// $key = trim( ProHelpers::getLicenseKey() );
				// if ( ProHelpers::validKey( $key ) === false ) {
				// 	add_action( 'admin_notices', array( self::$instance, 'atlt_add_license_notice' ) );
				// }
			}
		}
		/*
		|----------------------------------------------------------------------
		| Register API Manager inside Loco Translate Plugin
		|----------------------------------------------------------------------
		*/
		function atlt_register_api( array $apis ) {
			$apis[] = array(
				'id'   => 'loco_auto',
				'key'  => '122343',
				'url'  => 'https://locoaddon.com/',
				'name' => 'Automatic Translate Addon',
			);
			return $apis;
		}
		/*
		|----------------------------------------------------------------------
		| Auto Translate Request handler
		|----------------------------------------------------------------------
		*/
		function atlt_ajax_init() {
			if( version_compare( loco_plugin_version(), '2.7', '>=' ) ){
				add_filter( 'loco_api_translate_loco_auto', array( self::$instance, 'atlt_loco_auto_translator_process_batch' ), 0, 4 );
			}
			else {
				add_filter('loco_api_translate_loco_auto',array( self::$instance, 'atlt_loco_auto_translator_process_batch_legacy' ), 0,3);
			}
		}
		
		public function atlt_loco_auto_translator_process_batch_legacy( array $sources, Loco_Locale $locale, array $config ) {
			$items = [];
			foreach( $sources as $text ){
				$items[] = [ 'source' => $text ];
			}
			return $this->atlt_loco_auto_translator_process_batch( [], $items, $locale, $config );
		}

		/**
		 * Hook fired as a filter for the "loco_auto" translation API.
		 *
		 * @param array       $sources Input strings.
		 * @param Loco_Locale $Locale  Target locale for translations.
		 * @param array       $config  Our own API configuration.
		 *
		 * @return array Output strings.
		 */
		function atlt_loco_auto_translator_process_batch(array $targets, array $items, Loco_Locale $locale, array $config) {
			$targets = array();

			// Extract and validate domain component safely
			$domain   = 'temp';
			$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';
			if ( is_string( $referer ) && $referer !== '' ) {
				$referer_host = parse_url( $referer, PHP_URL_HOST );
				$site_host    = parse_url( admin_url(), PHP_URL_HOST );
				if ( $referer_host && $site_host && strtolower( $referer_host ) === strtolower( $site_host ) ) {
					$query = parse_url( $referer, PHP_URL_QUERY );
					if ( is_string( $query ) ) {
						$params = array();
						parse_str( $query, $params );
						if ( isset( $params['domain'] ) && is_string( $params['domain'] ) ) {
							$domain_candidate = sanitize_key( $params['domain'] );
							if ( $domain_candidate !== '' ) {
								$domain = $domain_candidate;
							}
						}
					}
				}
			}
			$lang       = sanitize_text_field( $locale->lang );
			$region     = sanitize_text_field( $locale->region );
			$project_id = $domain . '-' . $lang . '-' . $region;

			// Combine transient parts if available
			$allString = array();
			$translationData = array();
			for ( $i = 0; $i <= 4; $i++ ) {
				$transient_data = get_transient( $project_id . '-part-' . $i );

				if ( ! empty( $transient_data ) ) {
					if (isset( $transient_data['strings'] )) {
						$allString = array_merge( $allString, $transient_data['strings'] );
					}
				}
			}
			if (!empty($allString)) {
				foreach ($items as $i => $item) {
					$normalizedSource = preg_replace('/\s+/', ' ', trim($item['source']));
		
					// Find the index of the normalized source string in the cached strings
					$index = array_search($normalizedSource, array_column($allString, 'source'));
					if (is_numeric($index) && isset($allString[$index]['target'])) {
						$targets[$i] = $allString[$index]['target'];
					} else {
						$targets[$i] = '';
					}
				}

				return $targets;
			} else {
				throw new Loco_error_Exception( 'Please translate strings using the Auto Translate addon button first.' );
			}

		}

		/**
		 * Parse the query string from the URL.
		 *
		 * @param string $var URL to parse.
		 *
		 * @return array Parsed query parameters.
		 */
		function atlt_parse_query( $var ) {
			$var = parse_url( $var, PHP_URL_QUERY );
			$var = html_entity_decode( $var );
			$var = explode( '&', $var );
			$arr = array();

			foreach ( $var as $val ) {
				$x            = explode( '=', $val );
				$arr[ $x[0] ] = $x[1];
			}

			unset( $val, $x, $var );
			return $arr;
		}

		/*
		|----------------------------------------------------------------------
		| Save string translation inside cache for later use
		|----------------------------------------------------------------------
		*/
		 // save translations inside transient cache for later use
		 function atlt_save_translations_handler() {

			check_ajax_referer( 'loco-addon-nonces', 'wpnonce' );

			// Add capability check
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'error' => 'Insufficient permissions.' ) );
			}
		
			if ( ! isset( $_POST['data'], $_POST['part'], $_POST['project-id'] ) || empty( $_POST['data'] ) ) {
				wp_send_json_error( array( 'error' => 'Invalid request. Missing required parameters.' ) );
			}
		
			$raw_data        = wp_unslash( $_POST['data'] );
			$raw_translation = isset( $_POST['translation_data'] ) ? wp_unslash( $_POST['translation_data'] ) : null;
			$part            = sanitize_text_field( wp_unslash( $_POST['part'] ) );
			$project_id      = sanitize_text_field( wp_unslash( $_POST['project-id'] ) );
	
		
			$allStrings = json_decode( $raw_data, true );
			if ( json_last_error() !== JSON_ERROR_NONE || empty( $allStrings ) || ! is_array( $allStrings ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'error'   => 'No data found in the request. Unable to save translations.',
					)
				);
			}
		
			$translationData = null;
			if ( null !== $raw_translation && '' !== $raw_translation ) {
				$translationData = json_decode( $raw_translation, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					wp_send_json_error( array( 'error' => 'Invalid JSON in translation_data.' ) );
				}
			}
		
			$projectId   = $project_id . $part;
			$dataToStore = array(
				'strings' => $allStrings,
			);
		
			// Save metadata exactly when original did: only for -part-0
			if ( '-part-0' === $part && is_array( $translationData ) ) {
				$metadata = array(
					'translation_provider' => isset( $translationData['translation_provider'] ) ? sanitize_text_field( $translationData['translation_provider'] ) : '',
					'string_count'         => isset( $translationData['string_count'] ) ? absint( $translationData['string_count'] ) : 0,
					'character_count'      => isset( $translationData['character_count'] ) ? absint( $translationData['character_count'] ) : 0,
					'time_taken'           => isset( $translationData['time_taken'] ) ? absint( $translationData['time_taken'] ) : 0,
					'pluginORthemeName'    => isset( $translationData['pluginORthemeName'] ) ? sanitize_text_field( $translationData['pluginORthemeName'] ) : '',
					'target_language'      => isset( $translationData['target_language'] ) ? sanitize_text_field( $translationData['target_language'] ) : '',
				);
		
				
			}
		
			$rs = set_transient( $projectId, $dataToStore, 5 * MINUTE_IN_SECONDS );
		
			wp_send_json_success(
				array(
					'success'  => true,
					'message'  => 'Translations successfully stored in the cache.',
					'response' => ( true === $rs ? 'saved' : 'cache already exists' ),
				)
			);
		}
		


	
		function atlt_load_ytranslate_scripts() {
			if ( isset( $_REQUEST['action'] ) && sanitize_key( wp_unslash( $_REQUEST['action'] ) ) === 'file-edit' ) {
				wp_add_inline_script( 'loco-addon-custom', "document.getElementsByTagName('html')[0].setAttribute('translate', 'no');" );
			}
		}
		 // add no translate class in admin body to disable whole page translation
		function atlt_add_custom_class( $classes ) {
			return "$classes notranslate";
		}

		
		function atlt_load_gtranslate_scripts() {
			echo "<script>
			function gTranslateWidget() {
				var locale=locoConf.conf.locale;
				var defaultcode = locale.lang?locale.lang:null;
				switch(defaultcode){
					case 'kir':
						defaultlang='ky';
						break;
					case 'oci':
						defaultlang='oc';
						break;
					case 'bel':
					defaultlang='be';
					break;
					case 'he':
						defaultlang='iw';
						break;
					case'snd':
						defaultlang='sd';
					break;
					case 'jv':
						defaultlang='jw';
						break;
						case 'nb':
							defaultlang='no';
							break;
							case 'nn':
							  defaultlang='no';
							  break;
					default:
					defaultlang=defaultcode;
				break;
				return defaultlang;
				}
			   if(defaultlang=='zh'){
			   new google.translate.TranslateElement(
					{
					pageLanguage: 'en',
					includedLanguages: 'zh-CN,zh-TW',
					defaultLanguage: 'zh-CN,zh-TW',
					multilanguagePage: true
					},
					'google_translate_element'
				);
			}
			else{
				new google.translate.TranslateElement(
					{
					pageLanguage: 'en',
					includedLanguages: defaultlang,
					defaultLanguage: defaultlang,
					multilanguagePage: true
					},
					'google_translate_element'
				);
			}
			}
			</script>
		
			";
		}

		// set default option in google translate widget using cookie
		function atlt_set_gtranslate_cookie() {
			// setting your cookies there
			if ( ! isset( $_COOKIE['googtrans'] ) ) {
				$expires = time() + ( 10 * YEAR_IN_SECONDS );
				setcookie( 'googtrans', '/en/Select Language', array(
					'expires'  => $expires,
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				) );
			}
		}
		/*
		|----------------------------------------------------------------------
		| check if required "Loco Translate" plugin is active
		| also register the plugin text domain
		|----------------------------------------------------------------------
		*/
		public function atlt_load_textdomain() {
			// load language files
			load_plugin_textdomain( 'loco-auto-translate', false, basename( dirname( ATLT_PRO_FILE ) ) . '/languages/' );
			if (!get_option('atlt_pro_initial_save_version')) {
				add_option('atlt_pro_initial_save_version', ATLT_PRO_VERSION);
			}
		}

		public function atlt_check_required_loco_plugin() {
			if ( ! function_exists( 'loco_plugin_self' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'atlt_plugin_required_admin_notice' ) );
			}
		}
		/*
		|----------------------------------------------------------------------
		| Notice to 'Admin' if "Loco Translate" is not active
		|----------------------------------------------------------------------
		*/
		public function atlt_plugin_required_admin_notice() {
			if ( current_user_can( 'activate_plugins' ) ) {
				$url         = 'plugin-install.php?tab=plugin-information&plugin=loco-translate&TB_iframe=true';
				$title       = 'Loco Translate';
				$plugin_info = get_plugin_data( ATLT_PRO_FILE, true, true );

				// Sanitize the output to prevent XSS
				$plugin_name = esc_html( $plugin_info['Name'] );
				$escaped_url = esc_url( $url );
				$escaped_title = esc_attr( $title );

				echo '<div class="error"><p>' .
				sprintf(
					__(
						'In order to use <strong>%1$s</strong> plugin, please install and activate the latest version of <a href="%2$s" class="thickbox" title="%3$s">%4$s</a>',
						'automatic-translator-addon-for-loco-translate'
					),
					$plugin_name,
					$escaped_url,
					$escaped_title,
					$escaped_title
				) . '.</p></div>';

				deactivate_plugins( ATLT_PRO_FILE );
			}
		}
		
		public function atlt_settings_page_link( $links ) {
			$links[] = '<a style="font-weight:bold" href="' . esc_url( get_admin_url( null, 'admin.php?page=loco-atlt-dashboard' ) ) . '">Settings</a>';
			return $links;
		}


	
		public function atlt_verify_loco_version() {
			if ( function_exists( 'loco_plugin_version' ) ) {
				 $locoV = loco_plugin_version();
				if ( version_compare( $locoV, '2.4.0', '<' ) ) {
					add_action( 'admin_notices', array( self::$instance, 'use_loco_latest_version_notice' ) );
				}
			}
		}
		
		public function use_loco_latest_version_notice() {
			if ( current_user_can( 'activate_plugins' ) ) {
				$url         = 'plugin-install.php?tab=plugin-information&plugin=loco-translate&TB_iframe=true';
				$title       = 'Loco Translate';
				$plugin_info = get_plugin_data( ATLT_PRO_FILE, true, true );

				// Sanitize the plugin name and version for output
				$plugin_name = esc_html($plugin_info['Name']);
				$plugin_version = esc_html($plugin_info['Version']);
				$escaped_url = esc_url($url);
				$escaped_title = esc_attr($title);

				echo '<div class="error"><p>' .
				sprintf(
					__(
						'In order to use <strong>%1$s</strong> (version <strong>%2$s</strong>), Please update <a href="%3$s" class="thickbox" title="%4$s">%5$s</a> official plugin to a latest version (2.4.0 or upper)',
						'automatic-translator-addon-for-loco-translate'
					),
					$plugin_name,
					$plugin_version,
					$escaped_url,
					$escaped_title,
					$escaped_title
				) . '.</p></div>';
			}
		}

		public function atlt_include_files() {

			
			
			require_once ATLT_PRO_PATH . 'includes/Register/LocoAutomaticTranslateAddonPro.php';
			
			$ratingDiv = get_option('atlt-pro-ratingDiv', 'no');
			$alreadyRated = get_option('atlt-already-rated', 'no');

			
		}

		
		public function atlt_hide_unrelated_notices()
			{ // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
				// Security: Additional capability check within the method
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				
				$cfkef_pages = false;

				// Whitelist expected page values for security - validate BEFORE processing
				$allowed_pages = array( 'loco-atlt-dashboard' );
				if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages, true ) ) {
					$page_param = sanitize_key( wp_unslash( $_GET['page'] ) );
					$cfkef_pages = true;
				}

				if ($cfkef_pages) {
					global $wp_filter;
					// Define rules to remove callbacks.
					$rules = [
						'user_admin_notices' => [], // remove all callbacks.
						'admin_notices'      => [],
						'all_admin_notices'  => [],
						'admin_footer'       => [
							'render_delayed_admin_notices', // remove this particular callback.
						],
					];
					$notice_types = array_keys($rules);
					foreach ($notice_types as $notice_type) {
						if (empty($wp_filter[$notice_type]->callbacks) || ! is_array($wp_filter[$notice_type]->callbacks)) {
							continue;
						}
						$remove_all_filters = empty($rules[$notice_type]);
						foreach ($wp_filter[$notice_type]->callbacks as $priority => $hooks) {
							foreach ($hooks as $name => $arr) {
								if (is_object($arr['function']) && is_callable($arr['function'])) {
									if ($remove_all_filters) {
										unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
									}
									continue;
								}
								$class = ! empty($arr['function'][0]) && is_object($arr['function'][0]) ? strtolower(get_class($arr['function'][0])) : '';
								// Remove all callbacks except WPForms notices.
								if ($remove_all_filters && strpos($class, 'wpforms') === false) {
									unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
									continue;
								}
								$cb = is_array($arr['function']) ? $arr['function'][1] : $arr['function'];
								// Remove a specific callback.
								if (! $remove_all_filters) {
									if (in_array($cb, $rules[$notice_type], true)) {
										unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
									}
									continue;
								}
							}
						}
					}
				}

				add_action( 'admin_notices', [ $this, 'atlt_admin_notices' ], PHP_INT_MAX );
			}

			function atlt_admin_notices() {
				do_action( 'atlt_display_admin_notices' );
			}

			function atlt_display_admin_notices() {
				$ratingDiv = get_option('atlt-pro-ratingDiv', 'no');
				$alreadyRated = get_option('atlt-already-rated', 'no');
	
				
			}

		
		function atlt_enqueue_scripts( $hook ) {
			// load assets only on editor page
			if ( $hook == 'loco-translate_page_loco-atlt-register' ) {
				return;
			}
			if (
			( isset( $_REQUEST['action'] ) && sanitize_key( wp_unslash( $_REQUEST['action'] ) ) === 'file-edit' ) ) {
				// $key = trim( ProHelpers::getLicenseKey() );
				// if ( ProHelpers::validKey( $key ) ) {
					wp_register_script( 'loco-addon-custom', ATLT_PRO_URL . 'assets/js/pro-custom.min.js', array( 'loco-translate-admin' ), ATLT_PRO_VERSION, true );
					wp_register_script( 'atlt-chrome-ai-translator-for-loco', ATLT_PRO_URL . 'assets/js/chrome-ai-translator.min.js', array( 'loco-addon-custom' ), ATLT_PRO_VERSION, true );
				// } else {
				// 	wp_register_script( 'loco-addon-custom', ATLT_PRO_URL . 'assets/js/custom.min.js', array( 'loco-translate-admin' ), ATLT_PRO_VERSION, true );
				// }

					wp_register_style(
						'loco-addon-custom-css',
						ATLT_PRO_URL . 'assets/css/custom.min.css',
						null,
						ATLT_PRO_VERSION,
						'all'
					);
					

						wp_enqueue_script( 'loco-addon-custom' );
						wp_enqueue_script( 'atlt-chrome-ai-translator-for-loco' );
						wp_enqueue_style( 'loco-addon-custom-css' );

						// $key = trim( ProHelpers::getLicenseKey() );
				

				$extraData['ajax_url']        = admin_url( 'admin-ajax.php' );
				$extraData['nonce']           = wp_create_nonce( 'loco-addon-nonces' );
				$extraData['ATLT_URL']        = ATLT_PRO_URL;
				$extraData['preloader_path']  = 'preloader.gif';
				$extraData['gt_preview']      = 'google.png';
				$extraData['dpl_preview']     = 'deepl.png';
				$extraData['yt_preview']      = 'yandex.png';
				$extraData['chatGPT_preview'] = 'chatgpt.png';
				$extraData['geminiAI_preview']= 'gemini.png';
				$extraData['chromeAi_preview']      = 'chrome.png';
				$extraData['document_preview'] = 'document.svg';
				$extraData['openai_preview']    = 'openai.png';
				$extraData['error_preview']    = 'error-icon.svg';

				$extraData['extra_class']= is_rtl() ? 'atlt-rtl' : '';
				// $extraData['api_key']         = $api_keys;
				$extraData['dashboard_url'] = admin_url('admin.php?page=loco-atlt-dashboard');
				    $extraData['loco_settings_url'] = admin_url( 'admin.php?page=loco-config&action=apis' );

					wp_localize_script( 'loco-addon-custom', 'extradata', $extraData );
					// copy object
					wp_add_inline_script(
						'loco-translate-admin',
						'
            var returnedTarget = JSON.parse(JSON.stringify(window.loco));
            window.locoConf=returnedTarget;'
					);	
			}
		}

		// public function atlt_add_license_notice() {
		// 	$settings_page_link = esc_url( get_admin_url( null, 'admin.php?page=loco-atlt-dashboard&tab=license' ) );
		// 	$notice             = __( '<strong>LocoAI – Auto Translate for Loco Translate (Pro)</strong> - License key is missing! Please add your License key in the settings panel to activate all premium features.', 'loco-translate-addon' );
		// 	echo '<div class="error loco-pro-missing" style="border:2px solid;border-color:#dc3232;"><p>' . wp_kses_post( $notice ) . '</p>
        //   <p><a class="button button-primary" href="' . esc_url( $settings_page_link ) . '">' . __( 'Add License Key' ) . '</a> (You can find license key inside order purchase email or visit <a href="' . esc_url( 'https://my.coolplugins.net/account/orders/' ) . '" target="_blank" rel="noopener noreferrer">https://my.coolplugins.net/account/orders/</a>)</p></div>';
		// }
		
		public function atlt_activate() {

			$active_plugins = get_option('active_plugins', array());
            if (!in_array("automatic-translator-addon-for-loco-translate/automatic-translator-addon-for-loco-translate.php", $active_plugins)) {
                add_option('atlt_do_activation_redirect', true);
            }

			update_option('atlt-pro-version', ATLT_PRO_VERSION);
			update_option('atlt-pro-installDate', gmdate('Y-m-d h:i:s'));
			update_option('atlt-type', 'PRO');

			
			
		}

		
		public function atlt_do_activation_redirect() {
			if (get_option('atlt_do_activation_redirect', false)) {
				// Only redirect if not part of a bulk activation
				if (!isset($_GET['activate-multi'])) {
		
					// Check if required Loco Translate plugin is active (or required function exists)
					if (function_exists('loco_plugin_self')) {
						update_option('atlt_do_activation_redirect', false);
						wp_safe_redirect(admin_url('admin.php?page=loco-atlt-dashboard'));
						exit;
					}
				}
			}
			
		}
		
		public function atlt_deactivate() {

		}

		
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'loco-auto-translate' ), '2.3' );
		}

		
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'loco-auto-translate' ), '2.3' );
		}

	}

	function ATLT_PRO() {
		return LocoAutoTranslateAddonPro::get_instance();
	}
	ATLT_PRO();

}

