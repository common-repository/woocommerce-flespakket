<?php
class WC_Flespakket_Export {
	public $order_id;

	/**
	 * Construct.
	 */
			
	public function __construct() {
		add_action( 'load-edit.php', array( &$this, 'wcflespakket_action' ) ); // Export actions (popup & file export)
		$this->settings = get_option( 'wcflespakket_settings' );
		$this->log_file = dirname(dirname(__FILE__)).'/flespakket_log.txt';
	}

	/**
	 * Export selected orders
	 *
	 * @access public
	 * @return void
	 */
	public function wcflespakket_action() {
		if ( isset($_REQUEST['action']) ) {
		$action = $_REQUEST['action'];

		switch($action) {
			case 'wcflespakket':
				if ( empty($_GET['order_ids']) )
					die('U heeft geen orders geselecteerd!');
				
				$order_ids = explode('x',$_GET['order_ids']);
				
				foreach($order_ids as $order_id){
					$order_meta = get_post_meta( $order_id );
					$order = new WC_Order( $order_id );
					$order_number = $order->get_order_number();
					$formatted_address = $order->get_formatted_shipping_address();
					$full_country = new WC_Countries;
					$data[] = array(
						'naam'					=> $order_meta['_shipping_first_name'][0].' '.$order_meta['_shipping_last_name'][0],
						'bedrijfsnaam'			=> $order_meta['_shipping_company'][0],
						'postcode'				=> $order_meta['_shipping_postcode'][0],
						'adres1'				=> isset($order_meta['_shipping_address_1'][0])?$order_meta['_shipping_address_1'][0]:'',
						'adres2'				=> isset($order_meta['_shipping_address_2'][0])?$order_meta['_shipping_address_2'][0]:'',
						'huisnummer'			=> isset($order_meta['_shipping_house_number'][0])?$order_meta['_shipping_house_number'][0]:'',
						'huisnummertoevoeging'	=> isset($order_meta['_shipping_house_number_suffix'][0])?$order_meta['_shipping_house_number_suffix'][0]:'',
						'straat'				=> isset($order_meta['_shipping_street_name'][0])?$order_meta['_shipping_street_name'][0]:'',
						'woonplaats'			=> $order_meta['_shipping_city'][0],
						'landcode'				=> $order_meta['_shipping_country'][0],
						'land'					=> $full_country->countries[$order_meta['_shipping_country'][0]],
						'email'					=> $order_meta['_billing_email'][0],
						'telefoon'				=> $order_meta['_billing_phone'][0],
						'orderid'				=> $order_id,							
						'ordernr'				=> $order_number,
						'bestelling'			=> $this->get_order_items( $order_id ),
						'formatted_address'		=> $formatted_address,
					);
				}
				
				// Include HTML for export page/iframe
				?><?php include('wcflespakket-export-html.php'); ?><?php

				die();
			break;
			case 'wcflespakket-export':
				// die(print_r($_POST['data'])); // for debugging
				// ERROR LOGGING
				if (isset($this->settings['error_logging']))
					file_put_contents($this->log_file, date("Y-m-d H:i:s")." Export started\n", FILE_APPEND);

				// Get the data
				if (!isset($_POST['data'])) 
					die('Er zijn geen orders om te exporteren!');

				// stripslashes! Wordpress always slashes POST data, regardless of magic quotes settings... http://stackoverflow.com/q/8949768/1446634
				$post_data = stripslashes_deep($_POST['data']);

				$array = array(
					'process'		=> isset($this->settings['process'])?1:0, // NOTE: process parameter is active, put on 0 to create a consignment without processing it
					'consignments'	=> array()
				);

				foreach ($post_data as $order_id => $consignment) {
					$array['consignments'][$order_id] = array(
						'colli_amount'	=> (isset($consignment['colli_amount'])) ? $consignment['colli_amount'] : '1',
						'package'		=> (isset($consignment['package'])) ? $consignment['package'] : 'bottle_1', // default to bottle_1...
						'ToAddress'		=> array(),
						'ProductCode'	=> array(
							'signature_on_receipt'	=> (isset($consignment['handtekening'])) ? '1' : '0',
							'return_if_no_answer'	=> (isset($consignment['retourbgg'])) ? '1' : '0',
							'home_address_only'		=> (isset($consignment['huisadres'])) ? '1' : '0',
							'home_address_signature'=> (isset($consignment['huishand'])) ? '1' : '0',
							'insured'				=> (isset($consignment['verzekerd'])) ? '1' : '0',
							
						),
						'insured_amount'	=> $consignment['verzekerdbedrag'],
						'custom_id'			=> (isset($consignment['kenmerk'])) ? $consignment['kenmerk'] : '',
						'comments'			=> (isset($consignment['bericht'])) ? $consignment['bericht'] : '',
						'weight'			=> $consignment['gewicht'],
					);

					if( $consignment['landcode'] == 'NL' ) {
						$array['consignments'][$order_id]['ToAddress'] = array(
							'name'			=> $consignment['naam'],
							'business'		=> $consignment['bedrijfsnaam'],
							'town'			=> $consignment['woonplaats'],
							'email'			=> (isset($consignment['email'])) ? $consignment['email'] : '',
							'phone_number'	=> (isset($consignment['telefoon'])) ? $consignment['telefoon'] : '',
							// Country specific from here //
							'postcode'		=> $consignment['postcode'],
							'house_number'	=> $consignment['huisnummer'],
							'number_addition' => $consignment['huisnummertoevoeging'],
							'street'		  => $consignment['straat'],
						);

						if ($consignment['colli_amount'] > 1) {
							$array['consignments'][$order_id]['MultiColli'] = array();
							for ($x=2; $x<=$consignment['colli_amount']; $x++) {
								$array['consignments'][$order_id]['MultiColli']['package_'.$x] = $consignment['package_'.$x];
							}
						}

					} else {
						$array['consignments'][$order_id]['ToAddress'] = array(
							'name'			=> $consignment['naam'],
							'business'		=> $consignment['bedrijfsnaam'],
							'town'			=> $consignment['woonplaats'],
							'email'			=> (isset($consignment['email'])) ? $consignment['email'] : '',
							'phone_number'	=> (isset($consignment['telefoon'])) ? $consignment['telefoon'] : '',
							// Country specific from here //
							'country_code'	=> $consignment['landcode'],
							'eps_postcode'	=> $consignment['postcode'],
							'street'		=> $consignment['adres1'].' '.$consignment['adres2'],
						);
					}
				}

				// ERROR LOGGING
				if (isset($this->settings['error_logging']))
					file_put_contents($this->log_file, date("Y-m-d H:i:s")." consignment data:\n".var_export($array['consignments'],true)."\n", FILE_APPEND);
				// die( print_r( $array ) );

				// Send consignments to Flespakket API
				$decode = $this->api_request( 'create-consignments', $array);
				
				// ERROR LOGGING
				if (isset($this->settings['error_logging']))
					file_put_contents($this->log_file, date("Y-m-d H:i:s")." API response:\n".print_r($decode,true)."\n", FILE_APPEND);
				
				if (isset($decode['error'])) {
					echo $this->translate_error($decode['error']);
					exit;
				}

				// put order_id in key!
				$decode = array_combine( array_keys($array['consignments']), array_values($decode) );
				
				//die( print_r( $decode, true ) ); //for debugging

				$consignment_list = array();
				$order_ids = array();
				$error = array();
				foreach ($decode as $order_id => $order_decode ) {
					if ( !isset($order_decode['error']) ) {
						$consignment_id = $order_decode['consignment_id'];
						$order_ids[] = $order_id;
						$consignment_list[] = $consignment_id; //collect consigment_ids in an array for pdf retreival
						$tracktrace = $order_decode['tracktrace'];

						update_post_meta ( $order_id, '_flespakket_consignment_id', $consignment_id );
						update_post_meta ( $order_id, '_flespakket_tracktrace', $tracktrace );

						// set status to complete
						if ( isset($this->settings['auto_complete']) ) {
							$order = new WC_Order( $order_id );
							$order->update_status( 'completed', 'Order voltooid na Flespakket export' );
						}

					} else {
						//$error[$order_id] = $order_decode['error'];
						$error[$order_id] = implode( ', ', $this->array_flatten($order_decode) );
					}

				}

				$consignment_list_flat = implode('x', $consignment_list);
				$order_ids_flat = implode('x', $order_ids);
				$pdf_url = wp_nonce_url( admin_url( 'edit.php?&action=wcflespakket-label&consignment=' . $consignment_list_flat . '&order_ids=' . $order_ids_flat ), 'wcflespakket-label' );
				
				$this->export_done($pdf_url, $consignment_list, $error);

				exit;
			case 'wcflespakket-label':
				if ( empty($_GET['consignment']) && empty($_GET['order_ids']) )
					die('U heeft geen orders geselecteerd!');

				// ERROR LOGGING
				if (isset($this->settings['error_logging']))
					file_put_contents($this->log_file, date("Y-m-d H:i:s")." Label request\n", FILE_APPEND);

				$order_ids = explode('x',$_GET['order_ids']);

				$consignment_list = array();

				if ( !isset($_GET['consignment']) ) {
					// Bulk export label
					foreach ($order_ids as $order_id) {
						if (get_post_meta($order_id,'_flespakket_consignment_id',true)) {
							$order_consignment_id = get_post_meta($order_id,'_flespakket_consignment_id',true);
							$consignment_list[$order_id] = $order_consignment_id;
						}
					}
					$consignment_id_encoded = implode('x', $consignment_list);					
				} else {
					// Label request from modal (directly after export)
					// consignments already given!
					$consignments = explode('x',$_GET['consignment']);
					$consignment_list = array_combine($order_ids, $consignments);
					$consignment_id_encoded = implode('x', $consignment_list);					
				}

				$consignment_id = str_replace('x', ',', $consignment_id_encoded);

				// retrieve pdf for the consignment (this is another api call to retrieve-pdf)
				$array = array(
					'consignment_id' => $consignment_id,
					'format'		 => 'json',
				);

				// ERROR LOGGING
				if (isset($this->settings['error_logging']))
					file_put_contents($this->log_file, date("Y-m-d H:i:s")." consignment(s) requested: ".$consignment_id."\n", FILE_APPEND);

				// Request labels from Flespakket API
				$decode = $this->api_request( 'retrieve-pdf', $array);
				
				if (isset($decode['consignment_pdf'])) {
					$pdf_data = $decode['consignment_pdf'];
					$consigments_tracktrace = array_combine( explode(',',$decode['consignment_id']), explode(',',$decode['tracktrace']) );
					
					// track & trace fallback
					foreach ( $consignment_list as $order_id => $consignment_id ) {
						if ( isset($consigments_tracktrace[$consignment_id]) ) {
							// create array with $order_id => $tracktrace
							$orders_tracktrace[$order_id] = $consigments_tracktrace[$consignment_id];
							
							// put track&trace code in order meta
							update_post_meta ( $order_id, '_flespakket_tracktrace', $consigments_tracktrace[$consignment_id] );
						}
					}
					
					unset($decode['consignment_pdf']);
					
					// ERROR LOGGING
					if (isset($this->settings['error_logging'])) {
						file_put_contents($this->log_file, date("Y-m-d H:i:s")." PDF data received\n", FILE_APPEND);
						file_put_contents($this->log_file, print_r($orders_tracktrace,true)."\n", FILE_APPEND);
					}

					do_action( 'wcflespakket_before_label_print', $consignment_list );

					$filename  = 'Flespakket';
					$filename .= '-' . date('Y-m-d') . '.pdf';
					
					// Get output setting
					$output_mode = isset($this->settings['download_display'])?$this->settings['download_display']:'';

					// Switch headers according to output setting
					if ( $output_mode == 'display' ) {
						header('Content-type: application/pdf');
						header('Content-Disposition: inline; filename="'.$filename.'"');
					} else {
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename="'.$filename.'"'); 
						header('Content-Transfer-Encoding: binary');
						header('Connection: Keep-Alive');
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
					}

					// stream data
					echo urldecode($pdf_data);
				} elseif (isset($decode['error'])) {
					echo 'Error: ' . $decode['error'];
					
					if (isset($this->settings['error_logging']))
						file_put_contents($this->log_file, date("Y-m-d H:i:s")." server response:\n".print_r($decode,true)."\n", FILE_APPEND);
				} else {
					echo 'An unknown error occured<br/>';
					echo 'Server response: ' . print_r($decode);
					
					if (isset($this->settings['error_logging']))
						file_put_contents($this->log_file, date("Y-m-d H:i:s")." server response:\n".print_r($decode,true)."\n", FILE_APPEND);
				}
				exit;
			default: return;
		}
			
		}
	}

	public function api_request( $request_type, $data, $method = 'POST' ) {
		// collect API credentials/settings
		$target_site_api = 'http://www.flespakket.nl/api/';
		$username = $this->settings['api_username'];
		$api_key = $this->settings['api_key'];
		$timestamp = time();
		$nonce = rand(0,255); // note: this should be incremented in case 2 requests occur within the same timestamp (second)

		// JSON encode data
		$json = urlencode(json_encode($data));

		// create GET/POST string (keys in alphabetical order)
		$string = implode('&', array(
			'json=' . $json,
			'nonce=' . $nonce,
			'test=' . (isset( $this->settings['testmode'] ) ? '1' : '0'),
			'timestamp=' . $timestamp,
			'username=' . $username,
		));	

		// ERROR LOGGING
		if (isset($this->settings['error_logging']))
			file_put_contents($this->log_file, date("Y-m-d H:i:s")." Post content:\n".$string."\n", FILE_APPEND);

		// create hash
		$signature = hash_hmac('sha1', $method . '&' . urlencode($string), $api_key);

		if($method == 'POST')
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $target_site_api . $request_type . '/');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $string . '&signature=' . $signature);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			$result = curl_exec($ch);
			curl_close ($ch);
		}
		else // GET
		{
			// depricated, long urls for batch processing gives issues
			$request = $target_site_api . $request_type . '/?' . $string . '&signature=' . $signature;
			$result = file_get_contents($request);
		}

		// decode result
		$decode = json_decode($result, true);
		
		// ERROR LOGGING
		if (isset($this->settings['error_logging']))
			file_put_contents($this->log_file, date("Y-m-d H:i:s")." API response:\n".print_r($decode,true)."\n", FILE_APPEND);

		return $decode;
	}

	/**
	 * Get the current order items
	 */
	public function get_order_items( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		//global $_product;
		$items = $order->get_items();
		$data_list = array();
	
		if( sizeof( $items ) > 0 ) {
			foreach ( $items as $item ) {
				// Array with data for the printing template
				$data = array();
				
				// Create the product
				$product = $order->get_product_from_item( $item );

				// Set the variation
				if( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
					$data['variation'] = woocommerce_get_formatted_variation( $product->get_variation_attributes() );
				} else {
					$data['variation'] = null;
				}
									
				// Set item name
				$data['name'] = $item['name'];
				
				// Set item quantity
				$data['quantity'] = $item['qty'];
																							
				// Set item SKU
				$data['sku'] = $product->get_sku();

				// Set item weight
				$weight = $product->get_weight();
				$weight_unit = get_option( 'woocommerce_weight_unit' );
				switch ($weight_unit) {
					case 'kg':
						$data['weight'] = $weight;
						break;
					case 'g':
						$data['weight'] = $weight / 1000;
						break;
					case 'lbs':
						$data['weight'] = $weight * 0.45359237;
						break;
					case 'oz':
						$data['weight'] = $weight * 0.0283495231;
						break;
					default:
						$data['weight'] = $weight;
						break;
				}
				
				$data['total_weight'] = $data['quantity']*$data['weight'];
				
				// Set item dimensions
				$data['dimensions'] = $product->get_dimensions();
														
				$data_list[] = $data;
			}
		}

		return $data_list;
	}


	/**
	 * Get shipping data for current order
	 */
	public function name_length_check($names) {
	$voornaam = $names['voornaam'];
	$achternaam = $names['achternaam'];
	$bedrijfsnaam = $names['bedrijfsnaam'];
	
	if (strlen($voornaam) + strlen($achternaam) + 1 > 30 ) { $voornaam = preg_replace('/(\w)(\w+) *-*/', '\1.', $voornaam);	}							
	$naam = $voornaam . ' ' . $achternaam;
	
	if (!$bedrijfsnaam=="") {
		if (strlen($bedrijfsnaam) > 35 ) { $bedrijfsnaam = substr($bedrijfsnaam, 0, 35); }
		
		if (strlen($bedrijfsnaam) + strlen($naam) > 30) {
			if (strlen($bedrijfsnaam) + strlen($achternaam) <= 30) {$naam = $achternaam;}
			else {$bedrijfsnaam = "";}
		}
	}
	$checked_names['naam'] = $naam;
	$checked_names['bedrijfsnaam'] = $bedrijfsnaam;		
	return $checked_names;					
	}
	
	/**
	 * Multi-dimensional array flatten
	 */
	public function array_flatten($a,$f=array()){
		if(!$a||!is_array($a))return '';
		foreach($a as $k=>$v){
			if(is_array($v))$f=$this->array_flatten($v,$f);
			else $f[$k]=$v;
		}
		return $f;
	}
	
	/**
	 * Vertaal engelse Flespakket foutmeldingen
	 */
	public function translate_error($error){
		switch ($error) {
			case 'access denied - Signature does not match request - parameters need to be hashed in alphabetical order':
				$error = 'Toegang geweigerd - De API key komt niet overeen met de gebruikersnaam.';
				break;
			case 'access denied - Username \''.$this->settings['api_username'].'\' does not exist':
				$error = 'Toegang geweigerd - De gebruikersnaam <strong>'.$this->settings['api_username'].'</strong> bestaat niet.';
				break;
		}

		return $error;
	}

	/**
	 * Export result page
	 */
	public function export_done ($pdf_url, $consignment_list, $error) {
		?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?php
		require_once(ABSPATH . 'wp-admin/admin.php');
		wp_register_style( 'wcflespakket-admin-styles', dirname(plugin_dir_url(__FILE__)) .  '/css/wcflespakket-admin-styles.css', array(), '', 'all' );
		wp_enqueue_style( 'wcflespakket-admin-styles' );		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
		do_action('admin_print_styles');
	?>
</head>
<body style="padding:10px 20px;">
<h1>Export voltooid</h1>
<?php
		if (!empty($error)) {
			echo '<p>Er hebben zich fouten voorgedaan bij de volgende orders, deze zijn niet verwerkt:<ul style="margin-left:20px;">';
			foreach($error as $order_id => $error_message) {
				$order = new WC_Order($order_id);
				$order_number = $order->get_order_number();
				echo '<li><strong>'.$order_number.'</strong> <i>'.$error_message.'</i></li>';
			}
			echo '</ul></p>';
		}
		if (!empty($consignment_list)) {
			if (!empty($error)) {
				echo '<p>De overige orders zijn succesvol verwerkt bij Flespakket.<br />';
			} else {
				echo '<p>De geselecteerde orders zijn succesvol verwerkt bij Flespakket.<br />';		
			}
			$target = ( isset($this->settings['download_display']) && $this->settings['download_display'] == 'display') ? 'target="_blank"' : '';

?>
Hieronder kunt u de labels in PDF formaat downloaden.</p>
<?php printf('<a href="%1$s" %2$s><img src="%3$s"></a>', $pdf_url, $target, dirname(plugin_dir_url(__FILE__)) . '/img/download-pdf.png'); ?>
<p><strong>Let op!</strong><br />
Uw pakket met daarop het verzendetiket dient binnen 9 werkdagen na het aanmaken bij PostNL binnen te zijn. Daarna verliest het zijn geldigheid.
</body></html>
<?php
		}
	}

}