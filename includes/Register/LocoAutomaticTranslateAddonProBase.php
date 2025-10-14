<?php
if ( ! class_exists( 'LocoAutomaticTranslateAddonProBase' ) ) {
	class LocoAutomaticTranslateAddonProBase {
		public $key              = 'E7567ED169A8EF6C';
		private $product_id      = '7';
		private $product_base    = 'automatic-translator-addon-for-loco-translate';
		private $server_host     = 'https://license.coolplugins.net/wp-json/licensor/';
		private $hasCheckUpdate  = true;
		private $isEncryptUpdate = true;
		private $pluginFile;
		private static $selfobj          = null;
		private $version                 = '';
		private $isTheme                 = false;
		private $emailAddress            = '';
		private static $_onDeleteLicense = array();
		function __construct( $plugin_base_file = '' ) {
			$this->pluginFile = $plugin_base_file;
			$dir              = dirname( $plugin_base_file );
			$dir              = str_replace( '\\', '/', $dir );
			if ( strpos( $dir, 'wp-content/themes' ) !== false ) {
				$this->isTheme = true;
			}
			$this->version = $this->getCurrentVersion();
			if ( $this->hasCheckUpdate ) {
				if ( function_exists( 'add_action' ) ) {
					add_action(
						'admin_post_automatic-translator-addon-for-loco-translate_fupc',
						function() {
							update_option( '_site_transient_update_plugins', '' );
							update_option( '_site_transient_update_themes', '' );
							set_site_transient( 'update_themes', null );
							delete_transient( $this->product_base . '_up' );
							wp_redirect( admin_url( 'plugins.php' ) );
							exit;
						}
					);
					add_action( 'init', array( $this, 'initActionHandler' ) );

				}
				if ( function_exists( 'add_filter' ) ) {
					if ( $this->isTheme ) {
						add_filter( 'pre_set_site_transient_update_themes', array( $this, 'PluginUpdate' ) );
						add_filter( 'themes_api', array( $this, 'checkUpdateInfo' ), 10, 3 );
					} else {
						add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'PluginUpdate' ) );
						add_filter( 'plugins_api', array( $this, 'checkUpdateInfo' ), 10, 3 );
						add_filter(
							'plugin_row_meta',
							function( $links, $plugin_file ) {
								if ( $plugin_file == plugin_basename( $this->pluginFile ) ) {
									$links[] = " <a class='edit coption' href='" . esc_url( admin_url( 'admin-post.php' ) . '?action=automatic-translator-addon-for-loco-translate_fupc' ) . "'>Update Check</a>";
								}
								return $links;
							},
							10,
							2
						);
						add_action( 'in_plugin_update_message-' . plugin_basename( $this->pluginFile ), array( $this, 'updateMessageCB' ), 20, 2 );
					}
				}
			}
		}
		public function setEmailAddress( $emailAddress ) {
			$this->emailAddress = $emailAddress;
		}
		function initActionHandler() {
			$handler = hash( 'crc32b', $this->product_id . $this->key . $this->getDomain() ) . '_handle';
			if ( isset( $_GET['action'] ) && sanitize_key($_GET['action']) == $handler ) {
				$this->handleServerRequest();
				exit;
			}
		}
		function handleServerRequest() {
			// Validate and sanitize the 'type' parameter
			$type = isset( $_GET['type'] ) ? strtolower( sanitize_text_field( $_GET['type'] ) ) : ''; // Sanitize input
			if (!in_array($type, ['rl', 'rc', 'dl'])) { // Allow only specific values
				return; // Exit if the type is not valid
			}
			switch ( $type ) {
				case 'rl': // remove license
					$this->cleanUpdateInfo();
					$this->removeOldWPResponse();
					$obj          = new stdClass();
					$obj->product = $this->product_id;
					$obj->status  = true;
					echo esc_html( $this->encryptObj( $obj ) );

					return;
				case 'rc': // remove license
					$key = $this->getKeyName();
					delete_option( sanitize_key( $key ) ); // Sanitize key
					$obj          = new stdClass();
					$obj->product = $this->product_id;
					$obj->status  = true;
					echo esc_html( $this->encryptObj( $obj ) );
					return;
				case 'dl': // delete plugins
					$obj          = new stdClass();
					$obj->product = $this->product_id;
					$obj->status  = false;
					$this->removeOldWPResponse();
					require_once ABSPATH . 'wp-admin/includes/file.php';
					if ( $this->isTheme ) {
						$res = delete_theme( $this->pluginFile );
						if ( ! is_wp_error( $res ) ) {
							$obj->status = true;
						}
						echo esc_html( $this->encryptObj( $obj ) );
					} else {
						deactivate_plugins( array( plugin_basename( $this->pluginFile ) ) );
						$res = delete_plugins( array( plugin_basename( $this->pluginFile ) ) );
						if ( ! is_wp_error( $res ) ) {
							$obj->status = true;
						}
						echo esc_html( $this->encryptObj( $obj ) );
					}

					return;
				default:
					return;
			}
		}
		/**
		 * @param callable $func
		 */
		static function addOnDelete( $func ) {
			self::$_onDeleteLicense[] = $func;
		}
		function getCurrentVersion() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$data = get_plugin_data( $this->pluginFile, true, false );
			if ( isset( $data['Version'] ) ) {
				return $data['Version'];
			}
			return 0;
		}
		public function cleanUpdateInfo() {
			update_option( '_site_transient_update_plugins', '' );
			update_option( '_site_transient_update_themes', '' );
			delete_transient( $this->product_base . '_up' );
		}
		public function updateMessageCB( $data, $response ) {
			if ( is_array( $data ) ) {
				$data = (object) $data;
			}
			if ( isset( $data->package ) && empty( $data->package ) ) {
				if ( empty( $data->update_denied_type ) ) {
					print "<br/><span style='display: block; border-top: 1px solid #ccc;padding-top: 5px; margin-top: 10px;'>Please <strong>active product</strong> or  <strong>renew support period</strong> to get latest version</span>";
				} elseif ( $data->update_denied_type == 'L' ) {
					print "<br/><span style='display: block; border-top: 1px solid #ccc;padding-top: 5px; margin-top: 10px;'>Please <strong>active product</strong> to get latest version</span>";
				} elseif ( $data->update_denied_type == 'S' ) {
					print "<br/><span style='display: block; border-top: 1px solid #ccc;padding-top: 5px; margin-top: 10px;'>Please <strong>renew support period</strong> to get latest version</span>";
				}
			}
		}
		function __plugin_updateInfo() {

			if ( function_exists( 'wp_remote_get' ) ) {
			
				$response = get_transient( $this->product_base . '_up' );
				$oldFound = false;
				if ( ! empty( $response['data'] ) ) {
					$response = unserialize( $this->decrypt( $response['data'] ) );
					if ( is_array( $response ) ) {
						$oldFound = true;
					}
				}
				if ( ! $oldFound ) {
					$licenseInfo = self::GetRegisterInfo();
					$url         = $this->server_host . 'product/update/' . $this->product_id; 
					if ( ! empty( $licenseInfo->license_key ) ) {
						$url .= '/' . $licenseInfo->license_key . '/' . $this->version;
					}
					$args     = array(
						'sslverify'   => true,
						'timeout'     => 120,
						'redirection' => 5,
						'cookies'     => array(),
					);
					$response = wp_remote_get( $url, $args );
					if ( is_wp_error( $response ) ) {
						$args['sslverify'] = false;
						$response          = wp_remote_get( $url, $args );
					}
				}
		

				if ( ! is_wp_error( $response ) ) {
					$body         = $response['body'];
					$responseJson = @json_decode( $body );
					
					$licenseKey = get_option("LocoAutomaticTranslateAddonPro_lic_Key","");
					if ( ! $oldFound ) {
						set_transient( $this->product_base . '_up', array( 'data' => $this->encrypt( serialize( array( 'body' => $body ) ) ) ), DAY_IN_SECONDS );
					}

					if ( ! ( is_object( $responseJson ) && isset( $responseJson->status ) ) && $this->isEncryptUpdate ) {
						$body         = $this->decrypt( $body, $this->key );
						$responseJson = json_decode( $body );
					}

					if ( is_object( $responseJson ) && ! empty( $responseJson->status ) && ! empty( $responseJson->data->new_version ) ) {
						$responseJson->data->slug = plugin_basename( $this->pluginFile );

						$responseJson->data->new_version        = ! empty( $responseJson->data->new_version ) ? $responseJson->data->new_version : '';
						$responseJson->data->url                = ! empty( $responseJson->data->url ) ? $responseJson->data->url : '';
						$responseJson->data->package            = (! empty($licenseKey) && ! empty( $responseJson->data->download_link )) ? $responseJson->data->download_link : '';
						$responseJson->data->update_denied_type = ! empty( $responseJson->data->update_denied_type ) ? $responseJson->data->update_denied_type : '';

						$responseJson->data->sections    = (array) $responseJson->data->sections;
						$responseJson->data->plugin      = plugin_basename( $this->pluginFile );
						$responseJson->data->icons       = (array) $responseJson->data->icons;
						$responseJson->data->banners     = (array) $responseJson->data->banners;
						$responseJson->data->banners_rtl = (array) $responseJson->data->banners_rtl;
						unset( $responseJson->data->IsStoppedUpdate );
						return $responseJson->data;
					}
				}
			}

			return null;
		}
		
		function PluginUpdate( $transient ) {

			$response = $this->__plugin_updateInfo();

			if ( ! empty( $response->plugin ) ) {

				if ( $this->isTheme ) {
					$theme_data = wp_get_theme();
					$index_name = '' . $theme_data->get_template();
				} else {
					$index_name = $response->plugin;
				}
			
				
				if ( ! empty( $response ) && version_compare( ATLT_PRO_VERSION, $response->new_version, '<' )) {
					unset( $response->download_link );
					unset( $response->IsStoppedUpdate );

					if ( $this->isTheme ) {

						$transient->response[ $index_name ] = (array) $response;
					} else {
						$transient->response[ $index_name ] = (object) $response;
					}
				} else {
					if ( isset( $transient->response[ $index_name ] ) ) {
						unset( $transient->response[ $index_name ] );
					}
				}
			}
			return $transient;
		}
		
		final function checkUpdateInfo( $false, $action, $arg ) {

			if ( empty( $arg->slug ) ) {
				return $false;
			}
			
			if ( $this->isTheme ) {
				if ( ! empty( $arg->slug ) && $arg->slug === $this->product_base ) {
					$response = $this->__plugin_updateInfo();
					if ( ! empty( $response ) ) {
						return $response;
					}
				}
			} else {
				if ( ! empty( $arg->slug ) && $arg->slug === plugin_basename( $this->pluginFile ) ) {
					$response = $this->__plugin_updateInfo();
					if ( ! empty( $response ) ) {
						return $response;
					}
				}
			}

			return $false;
		}

		/**
		 * @param $plugin_base_file
		 *
		 * @return self|null
		 */
		static function &getInstance( $plugin_base_file = null ) {

			if ( empty( self::$selfobj ) ) {
				if ( ! empty( $plugin_base_file ) ) {
					self::$selfobj = new self( $plugin_base_file );
				}
			}
			return self::$selfobj;
		}


		private function encrypt( $plainText, $password = '' ) {

			if ( empty( $password ) ) {
				$password = $this->key;
			}
			$plainText = rand( 10, 99 ) . $plainText . rand( 10, 99 );
			$method    = 'aes-256-cbc';
			$key       = substr( hash( 'sha256', $password, true ), 0, 32 );
			$iv        = substr( strtoupper( md5( $password ) ), 0, 16 );
			return base64_encode( openssl_encrypt( $plainText, $method, $key, OPENSSL_RAW_DATA, $iv ) );
		}

		private function decrypt( $encrypted, $password = '' ) {

			if ( empty( $password ) ) {
				$password = $this->key;
			}
			$method    = 'aes-256-cbc';
			$key       = substr( hash( 'sha256', $password, true ), 0, 32 );
			$iv        = substr( strtoupper( md5( $password ) ), 0, 16 );
			$plaintext = openssl_decrypt( base64_decode( $encrypted ), $method, $key, OPENSSL_RAW_DATA, $iv );
			return substr( $plaintext, 2, -2 );
		}

		function encryptObj( $obj ) {

			$text = serialize( $obj );

			return $this->encrypt( $text );
		}

		private function decryptObj( $ciphertext ) {
			$text = $this->decrypt( $ciphertext );

			return unserialize( $text );
		}

		private function getDomain() {
			if ( defined( 'WPINC' ) && function_exists( 'get_bloginfo' ) ) {
				$server_name = get_bloginfo( 'url' );
				$domain      = $this->get_domains( $server_name );
				return $domain;
			} else {
				$base_url  = ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http' );
				$base_url .= '://' . $_SERVER['HTTP_HOST'];
				$base_url .= str_replace( basename( $_SERVER['SCRIPT_NAME'] ), '', $_SERVER['SCRIPT_NAME'] );
				return $base_url;
			}
		}
		private function get_domains( $url ) {
			$pieces = parse_url( $url );
			$domain = isset( $pieces['host'] ) ? $pieces['host'] : $pieces['path'];
			if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
				return $regs['domain'];
			}
			return $domain;
		}

		private function getEmail() {
			return $this->emailAddress;
		}
		private function processs_response( $response ) {
			$resbk = '';
			
			if ( ! empty( $response ) ) {
				if ( ! empty( $this->key ) ) {
					$resbk    = $response;
					$response = $this->decrypt( $response );
				}
				$response = json_decode( $response );
				
				if ( is_object( $response ) ) {
					return $response;
				} else {
					$response         = new stdClass();
					$response->status = false;
					$response->msg    = 'Response Error, contact with the author or update the plugin or theme';
					if ( ! empty( $bkjson ) ) {
						$bkjson = @json_decode( $resbk );
						if ( ! empty( $bkjson->msg ) ) {
							$response->msg = $bkjson->msg;
						}
					}
					$response->data = null;
					return $response;

				}
			}
			$response         = new stdClass();
			$response->msg    = 'unknown response';
			$response->status = false;
			$response->data   = null;

			return $response;
		}
		private function _request( $relative_url, $data, &$error = '' ) {
			$response                   = new stdClass();
			$response->status           = false;
			$response->msg              = 'Empty Response';
			$response->is_request_error = false;
			$finalData                  = json_encode( $data );
			if ( ! empty( $this->key ) ) {
				$finalData = $this->encrypt( $finalData );
			}
			$url = rtrim( $this->server_host, '/' ) . '/' . ltrim( $relative_url, '/' );
			if ( function_exists( 'wp_remote_post' ) ) {
				$rq_params      = array(
					'method'      => 'POST',
					'sslverify'   => true,
					'timeout'     => 120,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => $finalData,
					'cookies'     => array(),
				);
				$serverResponse = wp_remote_post( $url, $rq_params );
				
				if ( is_wp_error( $serverResponse ) ) {
					$rq_params['sslverify'] = false;
					$serverResponse         = wp_remote_post( $url, $rq_params );
					if ( is_wp_error( $serverResponse ) ) {
						$response->msg = $serverResponse->get_error_message();

						$response->status           = false;
						$response->data             = null;
						$response->is_request_error = true;
						return $response;
					} else {
						if ( ! empty( $serverResponse['body'] ) && ( is_array( $serverResponse ) && 200 === (int) wp_remote_retrieve_response_code( $serverResponse ) ) && $serverResponse['body'] != 'GET404' ) {
							return $this->processs_response( $serverResponse['body'] );
						}
					}
				} else {
					if ( ! empty( $serverResponse['body'] ) && ( is_array( $serverResponse ) && 200 === (int) wp_remote_retrieve_response_code( $serverResponse ) ) && $serverResponse['body'] != 'GET404' ) {
						return $this->processs_response( $serverResponse['body'] );
					}
				}
			}
			if ( ! extension_loaded( 'curl' ) ) {
				$response->msg              = 'Curl extension is missing';
				$response->status           = false;
				$response->data             = null;
				$response->is_request_error = true;
				return $response;
			}
			// curl when fall back
			$curlParams = array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 120,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => $finalData,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: text/plain',
					'cache-control: no-cache',
				),
			);
			$curl       = curl_init();
			curl_setopt_array( $curl, $curlParams );
			$serverResponse = curl_exec( $curl );
			$curlErrorNo    = curl_errno( $curl );
			$error          = curl_error( $curl );
			curl_close( $curl );
			if ( ! $curlErrorNo ) {
				if ( ! empty( $serverResponse ) ) {
					return $this->processs_response( $serverResponse );
				}
			} else {
				$curl                                 = curl_init();
				$curlParams[ CURLOPT_SSL_VERIFYPEER ] = false;
				$curlParams[ CURLOPT_SSL_VERIFYHOST ] = false;
				curl_setopt_array( $curl, $curlParams );
				$serverResponse = curl_exec( $curl );
				$curlErrorNo    = curl_errno( $curl );
				$error          = curl_error( $curl );
				curl_close( $curl );
				
				if ( ! $curlErrorNo ) {
					if ( ! empty( $serverResponse ) ) {
						return $this->processs_response( $serverResponse );
					}
				} else {
					$response->msg              = $error;
					$response->status           = false;
					$response->data             = null;
					$response->is_request_error = true;
					return $response;
				}
			}
			$response->msg              = 'unknown response';
			$response->status           = false;
			$response->data             = null;
			$response->is_request_error = true;
			return $response;
		}

		private function getParam( $purchase_key, $app_version, $admin_email = '' ) {

			$req               = new stdClass();
			$req->license_key  = $purchase_key;
			$req->email        = ! empty( $admin_email ) ? $admin_email : $this->getEmail();
			$req->domain       = $this->getDomain();
			$req->app_version  = $app_version;
			$req->product_id   = $this->product_id;
			$req->product_base = $this->product_base;

			return $req;
		}

		private function getKeyName() {
			return hash( 'crc32b', $this->getDomain() . $this->pluginFile . $this->product_id . $this->product_base . $this->key . 'LIC' );
		}

		private function SaveWPResponse( $response ) {
			$key  = $this->getKeyName();
			$data = $this->encrypt( serialize( $response ), $this->getDomain() );
			update_option( $key, $data ) or add_option( $key, $data );
		}

		private function getOldWPResponse() {
			$key      = $this->getKeyName();
			$response = get_option( $key, null );
			if ( empty( $response ) ) {
				return null;
			}

			return unserialize( $this->decrypt( $response, $this->getDomain() ) );
		}
		
		private function removeOldWPResponse() {
			$key       = $this->getKeyName();
			$isDeleted = delete_option( $key );
			foreach ( self::$_onDeleteLicense as $func ) {
				if ( is_callable( $func ) ) {
					call_user_func( $func );
				}
			}

			return $isDeleted;
		}

		public static function RemoveLicenseKey( $plugin_base_file, &$message = '' ) {
			$obj = self::getInstance( $plugin_base_file );
			$obj->cleanUpdateInfo();
			return $obj->_removeWPPluginLicense( $message );
		}

		public static function CheckWPPlugin( $purchase_key, $email, &$error = '', &$responseObj = null, $plugin_base_file = '' ) {
			$obj = self::getInstance( $plugin_base_file );
			$obj->setEmailAddress( $email );
			return $obj->_CheckWPPlugin( $purchase_key, $error, $responseObj );
		}

		final function _removeWPPluginLicense( &$message = '' ) {

			$oldRespons = $this->getOldWPResponse();
			if ( ! empty( $oldRespons->is_valid ) ) {
				if ( ! empty( $oldRespons->license_key ) ) {
					$param    = $this->getParam( $oldRespons->license_key, $this->version );
					$response = $this->_request( 'product/deactive/' . $this->product_id, $param, $message );
				
					if ( empty( $response->code ) ) {
						if ( ! empty( $response->status ) ) {
							$message = $response->msg;
							$this->removeOldWPResponse();
							return true;
						} else {
							$message = $response->msg;
						}
					} else {
						$message = $response->message;
					}
				}
			} else {
				$this->removeOldWPResponse();
				return true;
			}
			return false;

		}

		public static function GetRegisterInfo() {
			if ( ! empty( self::$selfobj ) ) {
				return self::$selfobj->getOldWPResponse();
			}
			return null;

		}

		final function _CheckWPPlugin( $purchase_key, &$error = '', &$responseObj = null ) {

			if ( empty( $purchase_key ) ) {
				$this->removeOldWPResponse();
				$error = '';
				return false;
			}
	
			$oldRespons = $this->getOldWPResponse();
			$isForce    = false;
			
			if(!empty($_POST['action']) && sanitize_key($_POST['action']) == 'atlt_refresh_license_ajax'){

				$isForce = true;
				$ajax_sent = 'yes';
				// Force refresh license verification
				$res = $this->Atlt_verify_license( $purchase_key, $oldRespons,$ajax_sent);
				$responseObj = $this->getOldWPResponse();
			
				return $res;

			}else if ( ! empty( $oldRespons ) ) {
				
				 if ( ! empty( $oldRespons->expire_date ) && strtolower( $oldRespons->expire_date ) != 'no expiry' && strtotime( $oldRespons->expire_date ) < time() ||  ! empty( $oldRespons->support_end) && strtolower( $oldRespons->support_end ) != 'unlimited' && strtotime( $oldRespons->support_end ) < time()) {
				
					$valid = $this->Atlt_verify_license( $purchase_key, $oldRespons,'no');
					return $valid;
				}
				
				if ( ! $isForce && ! empty( $oldRespons->is_valid ) && $oldRespons->next_request > time() && ( ! empty( $oldRespons->license_key ) && $purchase_key == $oldRespons->license_key ) ) {
				
					$responseObj = clone $oldRespons;
					unset( $responseObj->next_request );
					return true;
				}
			
			}
			
			$param    = $this->getParam( $purchase_key, $this->version );
			$response = $this->_request( 'product/active/' . $this->product_id, $param, $error );
			$error = $this->isLicenseError($response->msg );
			if($error){
				return;
			}
		
			if ( empty( $response->is_request_error ) ) {
				
				if ( empty( $response->code ) ) {
					
					if ( ! empty( $response->status ) ) {
					
						if ( ! empty( $response->data ) ) {
						
							$serialObj = $this->decrypt( $response->data, $param->domain );
							$licenseObj = unserialize( $serialObj );
							if ( $licenseObj->is_valid ) {
								$responseObj           = new stdClass();
								$responseObj->is_valid = $licenseObj->is_valid;

								if ( $licenseObj->request_duration > 0 ) {
									$responseObj->next_request = strtotime( "+ {$licenseObj->request_duration} hour" );
								} else {
									$responseObj->next_request = time();
								}
								
								$responseObj->expire_date        = $licenseObj->expire_date;
								$responseObj->support_end        = $licenseObj->support_end;
								$responseObj->license_title      = $licenseObj->license_title;
								$responseObj->license_key        = $purchase_key;
								$responseObj->market             = $licenseObj->market;
								$responseObj->msg                = $response->msg;
								$responseObj->re_tried              = 0;
							
								$this->SaveWPResponse( $responseObj );
								unset( $responseObj->next_request );
								delete_transient( $this->product_base . '_up' );
								return true;
							} else {
								if ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {
									return true;
								} else {
									$this->removeOldWPResponse();
									$error = ! empty( $response->msg ) ? $response->msg : '';
								}
							}
						} else {

								if ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {
									return true;
								} else {
										$this->removeOldWPResponse();
										$error = 'Invalid data';
								}
						}
					} else {
						
						 	$res = $this->Atlt_verify_license( $purchase_key, $oldRespons, 'no' );
							if($res === 'wrong_license_status'){
								
						    	$error = $res;
								if($error){
									return;
								}	
							}
							elseif ($res === 'domain_exceeded'){
								$error = $res;
								if($error){
									return;
								}	
							}
							elseif ($res === 'inactive_license'){
								$error = $res;
								if($error){
									return;
								}	
							}
							elseif ($res === 'refunded_license'){
								$error = $res;
								if($error){
									return;
								}	
							}
							elseif ($res === true) {
								// Get the updated license info from cache after Atlt_verify_license
								$updatedResponse = $this->getOldWPResponse();
								if ($updatedResponse) {
									$responseObj = clone $updatedResponse;
									unset($responseObj->next_request);
									if (isset($responseObj->re_tried)) {
										unset($responseObj->re_tried);
									}
								}
							}
							return $res;
					}
				} else {
					
						if ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {

							return true;

						} else {
								$this->removeOldWPResponse();
								$error = $response->message;
						}
					}
			} else {
			
				if ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {
					return true;
				} else {
					$this->removeOldWPResponse();
					$error = ! empty( $response->msg ) ? $response->msg : '';
				}
			}
			return $this->__checkoldtied( $oldRespons, $responseObj );
		}
		
		private function __checkoldtied( &$oldRespons, &$responseObj ) {

			if ( ! empty( $oldRespons ) && ( empty( $oldRespons->tried ) || $oldRespons->tried <= 12 ) ) {

				$oldRespons->next_request = strtotime( '+ 6 hour' );
				$oldRespons->tried        = empty( $oldRespons->tried ) ? 1 : ( $oldRespons->tried + 1 );
				$responseObj              = clone $oldRespons;
				unset( $responseObj->next_request );
				if ( isset( $responseObj->tried ) ) {
					unset( $responseObj->tried );
				}
				$this->SaveWPResponse( $oldRespons );
				return true;
			}
			return false;
		}
	
	/**
	 * Check if the response contains a known license error.
	 *
	 * @param string $response The response string from the API.
	 * @return bool True if license error exists, false otherwise.
	 */
	private function isLicenseError( $response ) {
		if ( ! empty( $response ) ) {

			$license_errors = [
				'Invalid license code',
				'License quota has been over, you can not add more domain with this license key',
				'Your purchase key has been temporary inactivated',
				'Your key has been installed on another domain',
				'License information is invalid',
				'invalid_license_details',
				'wrong_license_status',
				'domain_exceeded',
				'Your purchase key has been refunded',
				'inactive_license',
				'refunded_license',
			];

			foreach ( $license_errors as $error ) {
				if ( strpos( $response, $error ) !== false ) {
					$this->removeOldWPResponse();
					return $response;
				}
			}
		}
	}
	/**
	 * Verify license with the remote server
	 * 
	 * @param string $purchase_key The license key to verify
	 * @param object|null $oldRespons Previous response object from cache
	 * @param object|null $response Response object from previous request
	 * @param string $ajax_sent Whether this is an AJAX request ('yes' or 'null')
	 * @return bool|void Returns true if license is valid, false if invalid, void if license is inactive/revoked
	 */
	public function Atlt_verify_license( $purchase_key, $oldRespons = null, $ajax_sent = 'no' ) {

		if(empty($purchase_key)){

			return;
		}
	
		// 1. Use cached valid response if still within the allowed window
		if($ajax_sent==='no'){
		
			if ( ! empty( $oldRespons->is_valid ) &&
					! empty( $oldRespons->next_request ) &&
					$oldRespons->next_request > time() &&
					! empty( $oldRespons->license_key ) &&
					$purchase_key === $oldRespons->license_key ) {
					
						$responseObj = clone $oldRespons;
						unset( $responseObj->next_request );
						return true;
			} 

			// 2. Block if too many failed attempts

			if ( ! empty( $oldRespons->re_tried ) && $oldRespons->re_tried >= 120 ) {

					$oldRespons->msg = 'limit_reached';

					// Save the updated object
					$this->SaveWPResponse( $oldRespons );
				
				return true;
			}
		}
		$param    = $this->getParam( $purchase_key, $this->version );
		// 3. Make remote API request
		$api_url = $this->server_host . 'product/v1/license-view';
		$response = wp_remote_post($api_url, [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => json_encode([
				'license_key' => $purchase_key,
				'product_id' => $this->product_id,
				'param'     => $param,
			]),
		]);
		
		if ( is_wp_error( $response ) ) {

			$responseObj = new stdClass();
			$responseObj->is_valid = false;
			$responseObj->msg = 'License server unavailable';
			$this->SaveWPResponse( $responseObj );
			return false;
		}

		// 4. Handle response from server
		$body = wp_remote_retrieve_body( $response );
		$body_data = json_decode($body);
		if($body_data && ! empty($body_data->message) && ($body_data->message == "Max domain exceeded." || $body_data->message == "Maximum domain limit reached.")){
			$error = $this->isLicenseError( 'domain_exceeded' );
			if($error){
				return $error;
			}
		}else if($body_data && ! empty($body_data->status) && $body_data->status == "I"){
			$error = $this->isLicenseError( 'inactive_license' );
			if($error){
				return $error;
			}
		}else if($body_data && ! empty($body_data->status) && $body_data->status == "R"){
			$error = $this->isLicenseError( 'refunded_license' );
			if($error){
				return $error;
			}
		}
		if (!is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);
		}
		// Check for API response success and data structure
		if (empty($data['success']) || empty($data['data']) || empty($data['data']['status'])) {
			$err_msg = 'invalid_license_details';
    		$error = $this->isLicenseError($err_msg);
			
			return false;
		}
		
		$max_domains = '';
		$active_domains_count = '';
		
		// Handle infinity case for max_domain
		if ( ! empty( $data['data']['max_domain'] ) ) {

				$max_domains = (int) $data['data']['max_domain'];
				$active_domains_count = ! empty( $data['data']['active_domain_count'] ) ? (int) $data['data']['active_domain_count'] : 0;

				if($max_domains  === '∞' ){

					$max_domains = ''; 
					$active_domains_count = ''; 
				}
		}
		if($data['data']['status']=='I' || $data['data']['status']=='R' || ( $max_domains !== '' && $active_domains_count > $max_domains ) ){
			$error = $this->isLicenseError('wrong_license_status' );
		   if($error){
				return $error;
		   }
			
		}else{

			$status = true;
			$current_time = new DateTime();


			if($data['data']['has_support']=='Y'){

				$support_end_time = $data['data']['support_end_time'];
				$support_expire = new DateTime($support_end_time);
				
				if($current_time > $support_expire){

					$status 		= "support_expired";
					$support_end 	= $support_end_time;
				}else{
					$support_end = $support_end_time;
				}

			}elseif($data['data']['has_support']=='U'){

				$support_end = 'unlimited';

			}else{
				$status 		= "support_expired";
				$support_end 	= 'no support';
			}
			if($data['data']['has_expiry']=='Y'){

				$expiry_time = $data['data']['expiry_time'];
				$expiry_date = new DateTime($expiry_time);
				
				if ($current_time > $expiry_date) {

					$status = "license_expired";
					$expire_date = $expiry_time;

				}elseif($current_time < $expiry_date){
					
					$expire_date = $expiry_time;
				}
				
			}
			
			$responseObj = new stdClass();
			$responseObj->is_valid     = $status;
			$responseObj->license_key  = $purchase_key;
			$responseObj->msg          = '';
			$responseObj->next_request = strtotime( '+6 hours' );
			

			// 5. Add retry counter if license is invalid
			if ($responseObj->is_valid === 'license_expired' || $responseObj->is_valid === 'support_expired'  ) {

				$responseObj->re_tried = empty( $oldRespons->re_tried ) ? 1 : ( $oldRespons->re_tried + 1 );

				if ( $responseObj->re_tried >= 120 ) {
					$responseObj->msg = 'Maximum verification attempts reached';
				}
			}

			// 6. Store additional license info
			if ( ! empty( $data['data'] ) ) {
				$responseObj->expire_date   = isset($expire_date)?$expire_date:'no expiry';
				$responseObj->support_end   = $support_end;
				$responseObj->market       = $data['data']['market'];
				$responseObj->license_title = $data['data']['license_title'];
				$responseObj->status 		= $data['data']['status'];
			}
			
			// Save response
			$this->SaveWPResponse( $responseObj );


			return true;
		}
		
	}

	}
}
