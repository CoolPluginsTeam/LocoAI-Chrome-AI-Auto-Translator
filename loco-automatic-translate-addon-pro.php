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
		}
		
		public static function register() {

			$thisPlugin = self::$instance;
			register_activation_hook( ATLT_PRO_FILE, array( $thisPlugin, 'atlt_activate' ) );
			register_deactivation_hook( ATLT_PRO_FILE, array( $thisPlugin, 'atlt_deactivate' ) );

			if ( is_admin() ) {

				add_action( 'init', array( $thisPlugin, 'atlt_load_textdomain' ) );
				add_action( 'admin_enqueue_scripts', array( $thisPlugin, 'atlt_enqueue_scripts' ) );
				add_filter( 'loco_api_providers', array( $thisPlugin, 'atlt_register_api' ), 10, 1 );
				add_action( 'loco_api_ajax', array( $thisPlugin, 'atlt_ajax_init' ), 0, 0 );
				add_action( 'wp_ajax_save_all_translations', array( $thisPlugin, 'atlt_save_translations_handler' ) );
				$action_param = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
				if ( $action_param === 'file-edit' ) {
					add_action( 'admin_footer', array( $thisPlugin, 'atlt_load_gtranslate_scripts' ), 100 );
				}
		
			}

		}

		function atlt_register_api( array $apis ) {
			$apis[] = array(
				'id'   => 'loco_auto',
				'key'  => '122343',
				'url'  => 'https://locoaddon.com/',
				'name' => 'Automatic Translate Addon',
			);
			return $apis;
		}
		
		function atlt_ajax_init() {
				add_filter( 'loco_api_translate_loco_auto', array( self::$instance, 'atlt_loco_auto_translator_process_batch' ), 0, 4 );
		}
		
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

		
		public function atlt_load_textdomain() {
			// load language files
			load_plugin_textdomain( 'loco-auto-translate', false, basename( dirname( ATLT_PRO_FILE ) ) . '/languages/' );
			
		}

		function atlt_enqueue_scripts( $hook ) {
			
			if ( $hook == 'loco-translate_page_loco-atlt-register' ) {
				return;
			}
			if (
			( isset( $_REQUEST['action'] ) && sanitize_key( wp_unslash( $_REQUEST['action'] ) ) === 'file-edit' ) ) {
			
					wp_register_script( 'loco-addon-custom', ATLT_PRO_URL . 'assets/js/pro-custom.min.js', array( 'loco-translate-admin' ), ATLT_PRO_VERSION, true );
					wp_register_script( 'atlt-chrome-ai-translator-for-loco', ATLT_PRO_URL . 'assets/js/chrome-ai-translator.min.js', array( 'loco-addon-custom' ), ATLT_PRO_VERSION, true );
				
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

				$extraData['ajax_url']        = admin_url( 'admin-ajax.php' );
				$extraData['nonce']           = wp_create_nonce( 'loco-addon-nonces' );
				$extraData['ATLT_URL']        = ATLT_PRO_URL;
				$extraData['preloader_path']  = 'preloader.gif';
				$extraData['chromeAi_preview']      = 'chrome.png';
				$extraData['error_preview']    = 'error-icon.svg';

				$extraData['extra_class']= is_rtl() ? 'atlt-rtl' : '';


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

		public function atlt_activate() {

			$active_plugins = get_option('active_plugins', array());
            if (!in_array("automatic-translator-addon-for-loco-translate/automatic-translator-addon-for-loco-translate.php", $active_plugins)) {
              
            }

			update_option('atlt-pro-version', ATLT_PRO_VERSION);
			update_option('atlt-pro-installDate', gmdate('Y-m-d h:i:s'));
			update_option('atlt-type', 'PRO');
	
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

