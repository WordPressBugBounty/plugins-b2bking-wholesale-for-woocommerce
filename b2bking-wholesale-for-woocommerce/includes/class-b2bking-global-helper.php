<?php

class B2bking_Globalhelpercore{

	private static $instance = null;

	public static function init() {
	    if ( self::$instance === null ) {
	        self::$instance = new self();
	    }

	    return self::$instance;
	}

	public static function format_price_range( $from, $to ) {
		$price = wc_format_price_range($from, $to);

		// remove screen reader text as it breaks some b2bking scripts
		return preg_replace('/<span\s+class=["\']screen-reader-text["\'][^>]*>.*?<\/span>/s', '', $price);
	}

	public static function convert_date_from_to_range($date_from){
		$local_date_start = new DateTime("$date_from 00:00:00", wp_timezone());
		$local_date_end = new DateTime("$date_from 23:59:59", wp_timezone());
		$date_from_range = $local_date_start->format('U').'...'.$local_date_end->format('U');
		return $date_from_range;
	}

	// for a user pending approval, check if they applied for agent or customer
	public static function get_user_application_type($user_id){
		if (get_user_meta($user_id, 'registration_role_agent', true) === 'yes'){
			return 'agent';
		} else {
			return 'customer';
		}
	}

	public static function safe_get_queried_object_id() {
	    global $wp_query;
	    if ($wp_query !== null && is_object($wp_query) && method_exists($wp_query, 'get_queried_object_id')) {
	        return get_queried_object_id();
	    }
	    return 0; 
	}

	public static function force_purge_caches(){
		// Try common actions
		do_action('wp_cache_clear_cache');
		do_action('cache_cleared');
		do_action('wp_cache_flush');
		do_action( 'litespeed_purge_all' );

		// Try specific plugins
		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}

		if (function_exists('w3tc_pgcache_flush')) {
			w3tc_pgcache_flush();
		}

		if (function_exists('wp_cache_clear_cache')) {
			wp_cache_clear_cache();
		}

		if (class_exists('LiteSpeed_Cache_API')) {
			LiteSpeed_Cache_API::purge_all();
		}


		// WP Fastest Cache
		if (class_exists('WpFastestCache')) {
			$cache = new WpFastestCache();
			$cache->deleteCache(true);
		}
	}

	// Check function - validates against highest number in log
	public static function check_visibility_cache_integrity($cache_product_ids) {

		// disable for now 
		return $cache_product_ids;


	    if (!$cache_product_ids) {
	        // cache is false, will regenerate, nothing to do here
	        return $cache_product_ids;
	    }
	    
	    $use_cache_integrity_checks = get_option('b2bking_use_cache_integrity_checks', 0);
	    if (intval($use_cache_integrity_checks) === 0) {
	        return $cache_product_ids;
	    }

	    // proceed with cache checks

	    // get current user
	    $currentuserid = get_current_user_id();
	    $account_type = get_user_meta($currentuserid, 'b2bking_account_type', true);
	    if ($account_type === 'subaccount') {
	        // for all intents and purposes set current user as the subaccount parent
	        $parent_user_id = get_user_meta($currentuserid, 'b2bking_account_parent', true);
	        $currentuserid = $parent_user_id;
	    }
	    // get current count
	    $product_ids_count = count($cache_product_ids);

	    // if current page shows nothing found, clear site cache, but do not invalidate current ajax visibility cache
	    if (is_shop() || is_product_category()) {
	        global $wp_query;
	        if ($wp_query->post_count === 0) {
				b2bking()->force_purge_caches();
				
	            $cache_product_ids = false;
	        }
	    }

	    // get current ajax visibility cache
	    $visibility_cache_db = get_transient('b2bking_user_'.$currentuserid.'_ajax_visibility');
	    $user_cache_integrity_log = get_option('b2bking_cache_integrity_log' . $currentuserid, false);

	    // If no log exists yet, assume cache is valid
	    if (!is_array($user_cache_integrity_log) || empty($user_cache_integrity_log)) {
	        return $cache_product_ids;
	    }

	    // get the highest recorded log
	    $highest_count = max($user_cache_integrity_log);
	    $current_count_db = count($visibility_cache_db);

	    // if current visibility in DB is more than 100 less than highest ever recorded, invalidate DB cache
	    if (abs($current_count_db - $highest_count) > 100) {
    		delete_transient('b2bking_user_'.$currentuserid.'_ajax_visibility');
    		
    		if (apply_filters('b2bking_flush_cache_wp', true)){
    			wp_cache_flush();
    		}
	    }


	    return $cache_product_ids;
	}

	// Update function - only called when generating fresh values
	public static function update_visibility_integrity_log($product_ids) {
	    $use_cache_integrity_checks = get_option('b2bking_use_cache_integrity_checks', 0);
	    if (intval($use_cache_integrity_checks) === 0) {
	        return;
	    }
	    
	    // Proceed only if we have products (avoid logging zero values from corrupt generations)
	    $product_ids_count = count($product_ids);
	    $min_expected_products = intval(get_option('b2bking_min_expected_products', 100));
	    if ($product_ids_count < $min_expected_products) {
	        return; // Don't log potentially corrupted values
	    }
	    
	    // get current user id
	    $currentuserid = get_current_user_id();
	    $account_type = get_user_meta($currentuserid, 'b2bking_account_type', true);
	    if ($account_type === 'subaccount') {
	        // for all intents and purposes set current user as the subaccount parent
	        $parent_user_id = get_user_meta($currentuserid, 'b2bking_account_parent', true);
	        $currentuserid = $parent_user_id;
	    }
	    
	    // get current products count
	    $user_cache_integrity_log = get_option('b2bking_cache_integrity_log' . $currentuserid, false);
	    
	    // if no log exists, create it
	    if (!is_array($user_cache_integrity_log)) {
	        $user_cache_integrity_log = array($product_ids_count);
	    } else {
	        // if log exists, add latest number to log and if log is higher than 10000 items, cut out the first (oldest) numbers
	        $user_cache_integrity_log[] = $product_ids_count;
	        while (count($user_cache_integrity_log) > 50000) {
	            array_shift($user_cache_integrity_log);
	        }
	    }
	    
	    // save log
	    update_option('b2bking_cache_integrity_log' . $currentuserid, $user_cache_integrity_log);
	}

	public static function is_marketplace_dashboard() {
	    // Check if we're on any admin page first
	    if (!is_admin()) {
	        // Check for Dokan dashboard
	        if (function_exists('dokan_is_seller_dashboard')) {
	            if (dokan_is_seller_dashboard()) {
	                return 'dokan';
	            }
	        }
	        
	        // Check for WCFM dashboard
	        if (function_exists('is_wcfm_page')) {
	            if (is_wcfm_page()) {
	                return 'wcfm';
	            }
	        }
	    }
	    
	    return false;
	}

	// returns float price
	public static function get_tiered_raise_price_val($price, $product_id){

		$raise_percentage = b2bking()->get_raise_price_percentage($product_id);
		if ($raise_percentage > 0){
			$price = $price*(100+$raise_percentage)/100;
			$price = apply_filters('b2bking_raise_price_final', $price, $product_id);
		}

		$disc_percentage = b2bking()->get_discount_everywhere_percentage($product_id);
		if ($disc_percentage > 0){
			$price = $price*(100-$disc_percentage)/100;
		}
		
		return $price;
	}

	public static function get_selectable_attributes_count($product_id) {
	    $product = wc_get_product($product_id);
	    
	    if (!$product || !$product->is_type('variable')) {
	        return 0;
	    }
	    
	    $attributes = $product->get_variation_attributes();
	    return count($attributes);
	}

	function get_variation_attribute_string($variation_id) {
	    $product = wc_get_product($variation_id);
	    
	    if (!$product || !$product->is_type('variation')) {
	        return '';
	    }
	    
	    $attributes = $product->get_variation_attributes();
	    $parent_product = wc_get_product($product->get_parent_id());
	    $output = [];
	    
	    foreach ($attributes as $attribute_name => $attribute_value) {
	        $taxonomy = str_replace('attribute_', '', $attribute_name);
	        
	        if (empty($attribute_value)) {
	            $output[] = $taxonomy . '_any';
	        } else {
	            $term = get_term_by('slug', $attribute_value, $taxonomy);
	            if ($term && !is_wp_error($term)) {
	                // Replace inch symbol with something safe for HTML
	                $safe_slug = str_replace('"', 'in', $term->slug);
	                $output[] = $taxonomy . '_' . $safe_slug;
	            } else {
	                // Replace inch symbol with something safe for HTML
	                $safe_value = str_replace('"', 'in', $attribute_value);
	                $output[] = $taxonomy . '_' . $safe_value;
	            }
	        }
	    }
	    
	    return implode(' ', $output);
	}

	public static function vies_validation($country_code, $vat_number){

		if ( ! apply_filters('b2bking_vies_use_curl', false)){
			// SoapClient validation (normal)
			$client = b2bking()->get_soap_client();

			$validation = $client->checkVat(array(
			  'countryCode' => $country_code,
			  'vatNumber' => $vat_number
			));
		} else {
			// cURL validation (fallback)

			$validation = new stdClass();
			$validation -> valid = 1;

			// Craft the SOAP request XML
			$soap_request = <<<EOT
			<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
			   <soapenv:Header/>
			   <soapenv:Body>
				  <urn:checkVat>
					 <urn:countryCode>{$country_code}</urn:countryCode>
					 <urn:vatNumber>{$vat_number}</urn:vatNumber>
				  </urn:checkVat>
			   </soapenv:Body>
			</soapenv:Envelope>
			EOT;

			// Set the cURL options
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: text/xml; charset=utf-8',
				'SOAPAction: ""'
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification

			// Execute the cURL request
			$response = curl_exec($ch);

			if (curl_errno($ch)) {
				echo 'Error: ' . curl_error($ch);
			} else {
				// Parse the response
				$responseXml = simplexml_load_string($response, null, null, "http://schemas.xmlsoap.org/soap/envelope/");
				if ($responseXml === false) {
					// echo 'Failed to parse XML';
				} else {
					$responseXml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
					$responseXml->registerXPathNamespace('ns2', 'urn:ec.europa.eu:taxud:vies:services:checkVat:types');

					// Extract the relevant part of the response
					if (isset($responseXml->xpath('//soapenv:Body/ns2:checkVatResponse/ns2:valid')[0])){
						$body = $responseXml->xpath('//soapenv:Body/ns2:checkVatResponse/ns2:valid')[0];
						if ($body) {
							$isValid = (string)$body == 'true' ? true : false;
							if ($isValid){
								$validation -> valid = 1;
							} else {
								$validation -> valid = 0;
							}
						} else {
							// 'No valid field found in response';
						}
					}
				}
			}

			curl_close($ch);
			
		}

		return $validation;
		
	}

	public static function has_id_in_cart($id, $type = 'product') {
	    // Get the cart items
	    $cart_items = WC()->cart->get_cart();

	    if ($type === 'product') {
	        // Loop through cart items to check for product ID
	        foreach ($cart_items as $cart_item) {
	            if ($cart_item['product_id'] == $id) {
	                return true;
	            }
	        }
	    } elseif ($type === 'category') {
	        // Loop through cart items to check for category ID
	        foreach ($cart_items as $cart_item) {
	            $product_id = $cart_item['product_id'];
	            $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
	            if (in_array($id, $product_categories)) {
	                return true;
	            }
	        }
	    }

	    // Return false if the ID is not found
	    return false;
	}

	/*
	Updates user credit
	$amount can be negative or positive to add or remove credit
	optional $note
	*/

	public static function update_user_credit($user_id, $amount, $note = ''){ 

		// get user history
		$user_credit_history = sanitize_text_field(get_user_meta($user_id,'b2bking_user_credit_history', true));

		// create reimbursed transaction
		$date = date_i18n( 'Y/m/d', time()+(get_option('gmt_offset')*3600) ); 

		$operation = 'reimburse';
		$consumed_balance = get_user_meta($user_id,'b2bking_user_credit_consumed_balance', true);
		$new_consumed_balance = floatval($consumed_balance) - floatval($amount);		
		$transaction_new = $date.':'.$operation.':'.$amount.':'.$new_consumed_balance.':'.$note;

		// update credit history
		update_user_meta($user_id,'b2bking_user_credit_history',$user_credit_history.';'.$transaction_new);
		// update user consumed balance
		update_user_meta($user_id,'b2bking_user_credit_consumed_balance',$new_consumed_balance);
	}

	public static function get_soap_client(){
		if (apply_filters('b2bking_vies_use_https', true)){
			$url = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService.wsdl';
		} else {
			$url = 'http://ec.europa.eu/taxation_customs/vies/services/checkVatService.wsdl';
		}

		$options = [
		    'user_agent' => 'PHP/SOAP',
		];

		$options = apply_filters('b2bking_vies_client_options', $options);

		$client = new SoapClient($url, $options);

		return $client;
	}

	public static function get_tag_id_by_name($tag_name) {
	    // Get the term by name
	    $term = get_term_by('name', $tag_name, 'product_tag');
	    
	    // Check if the term exists and return its ID, otherwise return false
	    if ($term && !is_wp_error($term)) {
	        return $term->term_id;
	    } else {
	        return false; // Tag not found
	    }
	}

		// returns all group rules (b2bking_grule) that apply to this group id
	public static function get_group_rules($group_id){
		$rules_that_apply = array();
		// get all group rules
		$group_rules = get_posts([
	    		'post_type' => 'b2bking_grule',
	    	  	'post_status' => 'publish',
	    	  	'numberposts' => -1,
	    	  	'fields'	=> 'ids',
	    	]);

    	// remove if not enabled here
    	foreach ($group_rules as $index => $grule_id){
       		$status = get_post_meta($grule_id,'b2bking_post_status_enabled', true);
	       	if (intval($status) !== 1){
	       		unset($group_rules[$index]);
	       	}
    	}

		foreach ($group_rules as $grule_id){
			$who = get_post_meta($grule_id,'b2bking_rule_agents_who', true);
			if ($who === 'group_'.$group_id){
				array_push($rules_that_apply, $grule_id);
				continue;
			}

			if ($who === 'multiple_options'){
				$multiple_options = get_post_meta($grule_id, 'b2bking_rule_agents_who_multiple_options', true);
				$multiple_options_array = explode(',', $multiple_options);

				if (in_array('group_'.$group_id, $multiple_options_array)){
					array_push($rules_that_apply, $grule_id);
					continue;
				}
			}
		}

		return $rules_that_apply;
	}

	public static function fix_stock_status($product_id){
		$stockqty = get_post_meta($product_id,'_stock', true);
		if ($stockqty !== '' && $stockqty !== null){ // stock is TRACKED
			if (intval($stockqty) > 0){
				// if status is incorrectly set to outofstock, change it
				$status = get_post_meta($product_id,'_stock_status', true);
				if ($status === 'outofstock'){
					update_post_meta($product_id,'_stock_status', 'instock');
				}
			}

			if (intval($stockqty) === 0){
				// if status is incorrectly set to outofstock, change it
				$status = get_post_meta($product_id,'_stock_status', true);

				// check if backorders
				$backorders = get_post_meta($product_id, '_backorders', true);
				if ($backorders !== 'no'){
					update_post_meta($product_id, '_stock_status', 'onbackorder');
				} else {
					if ($status === 'instock'){
						update_post_meta($product_id,'_stock_status', 'outofstock');
					}
				}
			}
		}

		$status = get_post_meta($product_id,'_stock_status', true);
		$backorders = get_post_meta($product_id, '_backorders', true);

		if ($backorders === 'no' && $status === 'onbackorder'){ // status is not backorder, it is either outofstock or instock
			if (intval($stockqty) > 0){
				update_post_meta($product_id,'_stock_status', 'instock');
			} else {
				update_post_meta($product_id,'_stock_status', 'outofstock');
			}
		}
	}

	public static function has_tiered_price_table($product_id){
		$is_enabled = get_post_meta($product_id, 'b2bking_show_pricing_table', true);
		if ($is_enabled !== 'no'){
			$has_tiers = false;
			// check if any of the variations have tiers set for the regular group or the current user's group
			$product = wc_get_product($product_id);
			$user_id = get_current_user_id();
			$user_id = b2bking()->get_top_parent_account($user_id);
			$currentusergroupidnr = apply_filters('b2bking_b2b_group_for_pricing', b2bking()->get_user_group($user_id), $user_id, $product_id);

			foreach ($product->get_children() as $variation_id){
				$price_tiers_b2c = get_post_meta($variation_id, 'b2bking_product_pricetiers_group_b2c', true);
				$price_tiers_group = get_post_meta($variation_id, 'b2bking_product_pricetiers_group_'.$currentusergroupidnr, true);
				if (!empty($price_tiers_b2c) || !empty($price_tiers_group)){
					$has_tiers = true;
					break;
				}
			}

			return $has_tiers;
		} else {
			return false;
		}
	}



	public static function has_blocks_cart(){
		$page_id = wc_get_page_id('cart');

		if (WC_Blocks_Utils::has_block_in_page( $page_id, 'woocommerce/cart' )){
			return true;
		}

		$page_content = get_post_field('post_content', $page_id);
		if  (!has_shortcode($page_content, 'woocommerce_cart')){
			return true;
		}

		return false;
	}
	public static function has_blocks_checkout(){

		$page_id = wc_get_page_id('checkout');

		if (WC_Blocks_Utils::has_block_in_page( $page_id, 'woocommerce/checkout' )){
			return true;
		}

		$page_content = get_post_field('post_content', $page_id);
		if  (!has_shortcode($page_content, 'woocommerce_checkout')){
			return true;
		}

		return false;
	}

	public static function make_clickable($text) {
	    if (apply_filters('b2bking_disable_make_clickable', false)) {
	        return $text;
	    }
	    
	    $pattern = '~(?<!href=")(?<!src=")(https?://\S+)(?![^<]*>|[^<>]*</a>)~i';
	    $replacement = '<a href="$1" target="_blank">$1</a>';
	    
	    return preg_replace($pattern, $replacement, $text);
	}

	public static function convert_after_date_from_to_range($date_from){
		$local_date_start = new DateTime("$date_from 00:00:00", wp_timezone());

		$date_to = get_date_from_gmt(date('Y-m-d H:i:s'), 'Y-m-d');
		$local_date_end = new DateTime("$date_to 23:59:59", wp_timezone());
		$date_from_range = $local_date_start->format('U').'...'.$local_date_end->format('U');
		return $date_from_range;
	}

	public static function get_raise_price_percentage($product_id){
		$percentage = 0;

		$raiseprice_rules = b2bking()->get_applicable_rules('discount_everywhere', $product_id);
		// abort early
		if ($raiseprice_rules !== 'norules'){
			$raiseprice_rules = $raiseprice_rules[0];
			// remove non raise price rules
			foreach ($raiseprice_rules as $index => $rule_id){
				$raise_price = get_post_meta($rule_id,'b2bking_rule_raise_price', true);
				if ($raise_price !== 'yes'){
					unset($raiseprice_rules[$index]);
				}
			}
			foreach ($raiseprice_rules as $index => $rule_id){
				// find highest percentage
				$howmuch = get_post_meta($rule_id, 'b2bking_rule_howmuch', true);
				if (floatval($howmuch) > $percentage){
					$percentage = floatval($howmuch);
				}
			}
		}

		return $percentage;
	}

	public static function get_discount_everywhere_percentage($product_id){
		$percentage = 0;

		$raiseprice_rules = b2bking()->get_applicable_rules('discount_everywhere', $product_id);
		// abort early
		if ($raiseprice_rules !== 'norules'){
			$raiseprice_rules = $raiseprice_rules[0];
			// remove raise price rules
			foreach ($raiseprice_rules as $index => $rule_id){
				$raise_price = get_post_meta($rule_id,'b2bking_rule_raise_price', true);
				if ($raise_price === 'yes'){
					unset($raiseprice_rules[$index]);
				}
			}
			foreach ($raiseprice_rules as $index => $rule_id){

				// eliminate discount amount rules
				$rule_type = get_post_meta($rule_id,'b2bking_rule_what', true);
				if ($rule_type === 'discount_amount'){
					unset($raiseprice_rules[$index]);
				}
			}

			foreach ($raiseprice_rules as $index => $rule_id){

				// find highest percentage
				$howmuch = get_post_meta($rule_id, 'b2bking_rule_howmuch', true);
				if (floatval($howmuch) > $percentage){
					$percentage = floatval($howmuch);
				}
			}
		}

		return $percentage;
	}

	public static function get_discount_everywhere_amount($product_id){
		$percentage = 0;

		$raiseprice_rules = b2bking()->get_applicable_rules('discount_everywhere', $product_id);
		// abort early
		if ($raiseprice_rules !== 'norules'){
			$raiseprice_rules = $raiseprice_rules[0];
			// remove raise price rules
			foreach ($raiseprice_rules as $index => $rule_id){
				$raise_price = get_post_meta($rule_id,'b2bking_rule_raise_price', true);
				if ($raise_price === 'yes'){
					unset($raiseprice_rules[$index]);
				}
			}
			foreach ($raiseprice_rules as $index => $rule_id){

				// eliminate discount percentage rules
				$rule_type = get_post_meta($rule_id,'b2bking_rule_what', true);
				if ($rule_type === 'discount_percentage'){
					unset($raiseprice_rules[$index]);
				}
			}

			foreach ($raiseprice_rules as $index => $rule_id){

				// find highest amount
				$howmuch = get_post_meta($rule_id, 'b2bking_rule_howmuch', true);
				if (floatval($howmuch) > $percentage){
					$percentage = floatval($howmuch);
				}
			}
		}

		return $percentage;
	}

	public static function filter_check_rules_apply_countries_requires($rules){
		$user_id = b2bking()->get_top_parent_account(get_current_user_id());

		$currentusergroupidnr = b2bking()->get_user_group($user_id);
		if (!$currentusergroupidnr || empty($currentusergroupidnr)){
			$currentusergroupidnr = 'invalid';
		}

		global $woocommerce;
		$customer = $woocommerce->customer;
		$user_country = '';
		$user_vat = get_user_meta($user_id, 'b2bking_user_vat_status', true);

		if ($user_vat !== 'validated_vat'){
			// if validate vat button is enabled
			if (intval(get_option('b2bking_validate_vat_button_checkout_setting', 0)) === 1){
				delete_transient('b2bking_tax_exemption_user_'.get_current_user_id());
				if(isset($_COOKIE['b2bking_validated_vat_status'])){
					$user_vat = sanitize_text_field($_COOKIE['b2bking_validated_vat_status']);
				} else {
					$user_vat = 'invalid';
				}
			}
		}

		if (is_a($customer, 'WC_Customer')){
			$tax_setting = get_option('woocommerce_tax_based_on');
			if ($tax_setting === 'shipping'){
				$user_country = WC()->customer->get_shipping_country();
			} else {
				$user_country = WC()->customer->get_billing_country();
			}
		}

		// first check countries
		foreach ($rules as $index => $rule_id){
			$applies = false;
			
			// get rule countries
			$rule_countries = get_post_meta($rule_id, 'b2bking_rule_countries', true);
			if (strpos($rule_countries, $user_country) !== false) {
				$applies = true;
				continue;
			}

			if (!$applies){
				unset($rules[$index]);
			}
		}

		// second check requires
		foreach ($rules as $index => $rule_id){
			$applies = false;
			
			// get rule countries
			$requires = get_post_meta($rule_id, 'b2bking_rule_requires', true);
			if ($requires === 'nothing'){
				$applies = true;
				continue;
			}
			if ($requires == $user_vat){
				$applies = true;
				continue;
			}

			if (!$applies){
				unset($rules[$index]);
			}
		}

		return $rules;
	}	

	public static function filter_check_rules_apply_meta_value($rules, $meta_key, $meta_value, $operator = 'equal'){
		foreach ($rules as $index => $rule_id){
			$applies = false;

			// check if user applies directly
			$rule_value = get_post_meta($rule_id, $meta_key, true);

			if ($operator === 'equal'){
				if ($rule_value === $meta_value){
					$applies = true;
					continue;
				}
			}

			if ($operator === 'LIKE'){
				if (strpos($rule_value, $meta_value) !== false) {
					$applies = true;
					continue;
				}
			}

			if (!$applies){
				unset($rules[$index]);
			}
		}

		return $rules;
	}
	
	public static function filter_check_rules_apply_current_user($rules){

		$user_id = b2bking()->get_top_parent_account(get_current_user_id());

		$currentusergroupidnr = b2bking()->get_user_group($user_id);
		if (!$currentusergroupidnr || empty($currentusergroupidnr)){
			$currentusergroupidnr = 'invalid';
		}

		foreach ($rules as $index => $rule_id){

			$applies = false;

			// check if user applies directly
			$rule_who = get_post_meta($rule_id, 'b2bking_rule_who', true);
			if ($rule_who === 'user_'.$user_id){
				$applies = true;
				continue;
			}
			if ($rule_who === 'group_'.$currentusergroupidnr){
				$applies = true;
				continue;
			}

			// if user is registered, also select rules that apply to all registered users
			if ($user_id !== 0){
				if ($rule_who === 'all_registered'){
					$applies = true;
					continue;
				}

				// add rules that apply to all registered b2b/b2c users
				$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
				if ($user_is_b2b === 'yes'){
					if ($rule_who === 'everyone_registered_b2b'){
						$applies = true;
						continue;
					}
				} else {
					if ($rule_who === 'everyone_registered_b2c'){
						$applies = true;
						continue;
					}
				}
			}

			if ($rule_who === 'multiple_options'){
				$options = get_post_meta($rule_id,'b2bking_rule_who_multiple_options', true);
				$options_array = array_filter(array_unique(explode(',', $options)));
				$options_array = array_map('trim', $options_array);


				if (in_array('user_'.$user_id, $options_array)){
					$applies = true;
					continue;
				}
				if (in_array('group_'.$currentusergroupidnr, $options_array)){
					$applies = true;
					continue;
				}
				if ($user_id !== 0){

					if (in_array('all_registered', $options_array)){
						$applies = true;
						continue;
					}

					// add rules that apply to all registered b2b/b2c users
					$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
					if ($user_is_b2b === 'yes'){
						if (in_array('everyone_registered_b2b', $options_array)){
							$applies = true;
							continue;
						}
					} else {
						if (in_array('everyone_registered_b2c', $options_array)){
							$applies = true;
							continue;
						}
					}
				}
			}


			if (!$applies){
				unset($rules[$index]);
			}
		}


		return $rules;
	}

	public static function get_credit_gateway_content(){

		if (isset($_POST['action']) && $_POST['action'] === 'b2bking_update_fees'){
			// continue
		} else {
			if (is_admin()){
				return '';
			}
		}
		

		ob_start();

		// calculate available credit
		$user_id = get_current_user_id();
		$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// for all intents and purposes set current user as the subaccount parent
			$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
			$user_id = $parent_user_id;

			if (apply_filters('b2bking_allow_multiple_subaccount_levels', false)){
				$parent_account_type = get_user_meta($parent_user_id,'b2bking_account_type', true);
				if ($parent_account_type === 'subaccount'){
					// for all intents and purposes set current user as the subaccount parent
					$parent_parent_user_id = get_user_meta($parent_user_id, 'b2bking_account_parent', true);
					$user_id = $parent_parent_user_id;
				}
			}
		}

	    $credit_limit = get_user_meta($user_id,'b2bking_user_credit_limit', true);
	    // if not set, go to user's group credit setting
	    if (!$credit_limit || empty($credit_limit)){
	   	 $currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
	    	$credit_limit = get_post_meta($currentusergroupidnr,'b2bking_group_credit_limit', true);
	    }
	    // if not set, go to default credit limit setting
	    if (!$credit_limit || empty($credit_limit)){
	    	$credit_limit = get_option('b2bking_default_credit_limit_setting', 0);
	    }

	    $consumed_balance = get_user_meta($user_id,'b2bking_user_credit_consumed_balance', true);
	    if (!$consumed_balance){
	        $consumed_balance = 0;
	    }

		$available_credit = floatval($credit_limit) - $consumed_balance;

		$cart_total = WC()->cart->total;

		if ($available_credit >= $cart_total){
			?>
			<p class="form-row"><?php echo esc_html__('Available credit: ','b2bking').wc_price($available_credit);?></p>
			<?php
		} else {
			?>
			<p class="form-row"><?php echo esc_html__('Insufficient credit - your available credit is: ','b2bking').wc_price($available_credit);?></p>
			<?php
			if (intval(get_option( 'b2bking_allow_redeem_setting', 1 )) === 1){
				// show can redeem credit
				?>
				<p class="form-row"><?php 

				echo esc_html__('You can ','b2bking'); 
				echo '<a target="_blank" href="'.trailingslashit(get_permalink( get_option('woocommerce_myaccount_page_id') ) ).get_option('b2bking_credit_endpoint_setting', 'company-credit').'">'.esc_html__('redeem credits','b2bking').'</a>';
				echo esc_html__(' as a coupon.','b2bking');

				?></p>
				<?php
			}
		}

		$content = ob_get_clean();
		return $content;
	}

	public static function get_stock_val_new($variation_id, $meta_key){

		$value = get_post_meta($variation_id, $meta_key, true);
		// if new value is empty, try to find is there is an old value
		if (empty($value)){
			// search old deprecated value
			$old_val = get_post_meta($variation_id, $meta_key.'_'.$variation_id, true);
			if (!empty($old_val)){
				$value = $old_val;
				update_post_meta($variation_id, $meta_key, $value);
				
				// set old value to empty, so it's no longer used again
				update_post_meta($variation_id, $meta_key.'_'.$variation_id, '');

			}
		}

		return $value;
	}

	public static function has_notice($text){
		global $b2bking_notices;
		if (!is_array($b2bking_notices)){
			$b2bking_notices = array();
		}

		if (in_array($text, $b2bking_notices)){
			return true;
		} else {
			// does not have this notice yet, add it
			$b2bking_notices[] = $text;
			return false;
		}
	}

	public static function duplicate_post($post_id){
	    $old_post = get_post($post_id);
	    if (!$old_post) {
	        // Invalid post ID, return early.
	        return 0;
	    }

	    $title = $old_post->post_title;

	    // Create new post array.
	    $new_post = [
	        'post_title'  => $title,
	        'post_name'   => sanitize_title($title),
	        'post_status' => get_post_status($post_id),
	        'post_type'   => $old_post->post_type,
	    ];

	    // Insert new post.
	    $new_post_id = wp_insert_post($new_post);

	    b2bking()->update_sort_order($old_post->menu_order, $new_post_id);


	    // Copy post meta.
	    $post_meta = get_post_custom($post_id);
	    foreach ($post_meta as $key => $values) {
	        foreach ($values as $value) {
	            update_post_meta($new_post_id, $key, maybe_unserialize($value));
	        }
	    }

	    // Copy post taxonomies.
	    $taxonomies = get_post_taxonomies($post_id);
	    foreach ($taxonomies as $taxonomy) {
	        $term_ids = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
	        wp_set_object_terms($new_post_id, $term_ids, $taxonomy);
	    }

	    // Return new post ID.
	    return $new_post_id;
	}

	public static function get_icons(){
		$icons = array(
			'lock' => '<svg class="b2bking_lock_icon" focusable="false" width="15" height="15" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"></path></svg>',
			'login' => '<svg class="b2bking_login_icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path d="M12 21v-2h7V5h-7V3h7c.55 0 1.021.196 1.413.588.392.392.588.863.587 1.412v14c0 .55-.196 1.021-.588 1.413A1.922 1.922 0 0 1 19 21h-7Zm-2-4-1.375-1.45 2.55-2.55H3v-2h8.175l-2.55-2.55L10 7l5 5-5 5Z"/></svg>',
			'wholesale' => '<svg class="b2bking_wholesale_icon" xmlns="http://www.w3.org/2000/svg" width="18" height="17" fill="none" viewBox="0 0 24 22">
				  <g clip-path="url(#a)">
				    <path fill="#000" d="M0 1.333C0 .596.596 0 1.333 0h3.038C5.517 0 6.537.733 6.9 1.825l3.838 11.508a3.985 3.985 0 0 1 3.083 1.542l8.42-2.808c.7-.234 1.455.146 1.688.841a1.337 1.337 0 0 1-.841 1.688l-8.421 2.808a4 4 0 0 1-8-.07c0-1.284.604-2.426 1.541-3.159L4.371 2.667H1.333A1.332 1.332 0 0 1 0 1.333Zm10.2 4.271a1.33 1.33 0 0 1 .854-1.679l1.904-.617.825 2.538 2.538-.825-.83-2.538 1.905-.616a1.33 1.33 0 0 1 1.679.854l2.058 6.341a1.33 1.33 0 0 1-.854 1.68L13.938 12.8a1.33 1.33 0 0 1-1.68-.854L10.2 5.604Z"/>
				  </g>
				  <defs>
				    <clipPath id="a">
				      <path fill="#fff" d="M0 0h24v21.333H0z"/>
				    </clipPath>
				  </defs>
				</svg>',
			'business' => '<svg class="b2bking_business_icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24">
				  <path fill="#000" d="M10 2h4a2 2 0 0 1 2 2v2h4a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8c0-1.11.89-2 2-2h4V4c0-1.11.89-2 2-2Zm4 4V4h-4v2h4Z"/>
				</svg>',
			'custom' => apply_filters('b2bking_custom_icon', ''),

		);

		return $icons;
	}

	public static function get_all_offer_products_integrations(){
		// dokan and wcfm integration
		$dokan_offer_products = get_option('b2bking_dokan_hidden_offer_product_ids', 'string');
		$dokan_offer_products_clean = array();
		if ($dokan_offer_products !== 'string' && !empty($dokan_offer_products)){
			$dokan_offer_products = explode(',', $dokan_offer_products);
			$dokan_offer_products_clean = array_unique(array_filter($dokan_offer_products));
		}

		$wcfm_offer_products = get_option('b2bking_wcfm_hidden_offer_product_ids', 'string');
		$wcfm_offer_products_clean = array();
		if ($wcfm_offer_products !== 'string' && !empty($wcfm_offer_products)){
			$wcfm_offer_products = explode(',', $wcfm_offer_products);
			$wcfm_offer_products_clean = array_unique(array_filter($wcfm_offer_products));
		}

		$marketking_offer_products = get_option('b2bking_marketking_hidden_offer_product_ids', 'string');
		$marketking_offer_products_clean = array();
		if ($marketking_offer_products !== 'string' && !empty($marketking_offer_products)){
			$marketking_offer_products = explode(',', $marketking_offer_products);
			$marketking_offer_products_clean = array_unique(array_filter($marketking_offer_products));
		}

		$offer_products_integrations = array_merge($dokan_offer_products_clean, $wcfm_offer_products_clean, $marketking_offer_products_clean);

		return $offer_products_integrations;
	}

	public static function get_offer_quantity_total(){

		$qty = 0;

		$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));

		$offer_products_integrations = b2bking()->get_all_offer_products_integrations();
		
		if (is_object( WC()->cart )){

			foreach(WC()->cart->get_cart() as $cart_item){

				if ($cart_item['product_id'] === $offer_id || in_array($cart_item['product_id'], $offer_products_integrations)){

					// is offer, get offer quantity
					$details = get_post_meta( $cart_item['b2bking_offer_id'], 'b2bking_offer_details', true);
					$offer_products = array_filter(array_unique(explode('|', $details)));

					foreach ($offer_products as $product){

						$productdata = explode(';', $product);
						$qty += $productdata[1] * $cart_item['quantity'];
					}

					$qty -= $cart_item['quantity']; // remove the offer product qty
				}
			}
		}

		return $qty;
	}

	public static function get_top_parent_account($user_id){

    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){

    		if (get_option('b2bking_enable_parent_account_equivalence', 'yes') === 'no'){
    			return $user_id;
    		}    		

    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;

    		//if (apply_filters('b2bking_allow_multiple_subaccount_levels', false)){ // interferes sometimes with loading order 
    			$parent_account_type = get_user_meta($parent_user_id,'b2bking_account_type', true);
    			if ($parent_account_type === 'subaccount'){
    				// for all intents and purposes set current user as the subaccount parent
    				$parent_parent_user_id = get_user_meta($parent_user_id, 'b2bking_account_parent', true);
    				$user_id = $parent_parent_user_id;
    			}
    		//}
    	}

    	// apply pricing for customer in backend order search
    	if (b2bking()->is_manual_backend_order_price()){
    		$customer_id = get_user_meta(get_current_user_id(), 'b2bking_backend_customer_order_search', true);
    		if (!empty($customer_id)){
    			$user_id = $customer_id;
    		}
    	}

    	return $user_id;
	}

	public static function get_substitute_user_id($user_id){
		// apply pricing for customer in backend order search
		if (b2bking()->is_manual_backend_order_price()){
			$customer_id = get_user_meta(get_current_user_id(), 'b2bking_backend_customer_order_search', true);
			if (!empty($customer_id)){
				$user_id = $customer_id;
			}
		}

		return $user_id;
	}

	// returns true if current situation is an admin or manager creating a manual backend order
	// useful for setting prices as that user
	public static function is_manual_backend_order_price(){
		$is = false;
		if (wp_doing_ajax()){
			if (isset($_POST['action'])){
				if ($_POST['action'] === 'woocommerce_add_order_item'){
					$is = true;
				}
			}
		}

		return apply_filters('b2bking_use_customer_pricing_backend_orders', $is);
	}

	public static function update_sort_order($sort_number, $post_id){
		global $wpdb;
		$table_name = $wpdb->prefix.'posts';
		$data_update = array('menu_order' => $sort_number);
		$data_where = array('ID' => $post_id);
		$wpdb->update($table_name , $data_update, $data_where);
	}
	public static function update_title($title, $post_id){
		global $wpdb;
		$table_name = $wpdb->prefix.'posts';
		$data_update = array('post_title' => $title);
		$data_where = array('ID' => $post_id);
		$wpdb->update($table_name , $data_update, $data_where);
	}
	public static function update_status($status, $post_id){
		global $wpdb;
		$table_name = $wpdb->prefix.'posts';
		$data_update = array('post_status' => $status);
		$data_where = array('ID' => $post_id);
		$wpdb->update($table_name , $data_update, $data_where);
	}

	function order_has_enough_stock($order_id){
		// check stock and if unavailable, reject order
		$order = wc_get_order($order_id);
		$has_enough_stock = true;

		if ($order){
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( ! $item->is_type( 'line_item' ) ) {
					continue;
				}

				$qty  = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );

				$product_id = $item->get_product_id();
				$product = wc_get_product($product_id);
				if ($product){
					if (!$product->has_enough_stock($qty)){
						$has_enough_stock = false;
					}
				}
			}
		}

		return $has_enough_stock;
	}

	// takes formatted price via wc_price e.g. $15,56 and returns a float number price
	function reverse_wc_price($price){

		$price = str_replace('&nbsp;', '', $price);
		$price = strip_tags($price);

		$currency_symbol = get_woocommerce_currency_symbol();
		$thousands_separator = get_option('woocommerce_price_thousand_sep', ',');

		$decimals_separator = get_option('woocommerce_price_decimal_sep', '.');
		if (empty($decimals_separator)){
			$decimals_separator = '.';
		}
		if (empty($thousands_separator)){
			$decimals_separator = ',';
		}



		$decimalsexp = explode($decimals_separator, $price);
		if (isset($decimalsexp[1])){
			$decimals = $decimalsexp[1];
			if (empty($decimals)){
				$decimals = 0;
			} else {
				$decimalsstring = '0.'.$decimals;
				$decimals = floatval($decimalsstring);
			}
		} else {
			$decimals = 0;
		}

		// remove thousands and currency symbol
		$leftprice = str_replace($currency_symbol, '', $decimalsexp[0]);		
		$leftprice = str_replace($thousands_separator, '', $leftprice);

		$symbols = array('$','€','£','¥','₹');
		foreach ($symbols as $symbol){
			$leftprice = str_replace($symbol, '', $leftprice);
		}

		$finalprice = floatval($leftprice)+$decimals;

		return $finalprice;
		
	}

	// if prices are 0.001% close to each other, return true, likely just a rounding difference
	function price_difference_may_be_rounding_error($wholesale_price, $retail_price){

		if (!is_numeric($wholesale_price)){
			$wholesale_price = b2bking()->reverse_wc_price($wholesale_price);
		}

		if (!is_numeric($retail_price)){
			$retail_price = b2bking()->reverse_wc_price($retail_price);
		}

		if (is_numeric($wholesale_price) && is_numeric($retail_price)){

			if ($wholesale_price !== $retail_price){

				$max_price = max($wholesale_price, $retail_price);
				$min_price = min($wholesale_price, $retail_price);
				$difference = $max_price-$min_price;

				if ($max_price === 0){
					return false;
				}

				if ($max_price != 0){
					if (($difference / $max_price) < 0.002){
						return true;
					}
				}
			}
		}
		

		return false;
	}

	// returns either false if not have, or the value if have
	public static function get_product_meta_max($product_id, $variation_id = false){

		if (intval(get_option( 'b2bking_disable_product_level_minmaxstep_setting', 1 )) === 1){
			return false;
		}

		$have = false;

		$user_id = get_current_user_id();
		$is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
		if ($is_b2b === 'yes'){
			$user_group = get_user_meta($user_id,'b2bking_customergroup', true);
			// check if have here
			$max = get_post_meta($product_id,'b2bking_quantity_product_max_'.$user_group, true);
			if (!empty($max)){
				$have = $max;
			}

			if ($variation_id){
				$maxvar = get_post_meta($variation_id,'b2bking_quantity_product_max_'.$user_group, true);
				if (!empty($maxvar)){
					$have = $maxvar;
				}
			}
		}

		if ($have === false){
			// search further
			$max = get_post_meta($product_id,'b2bking_quantity_product_max_b2c', true);
			if (!empty($max)){
				$have = $max;
			}

			if ($variation_id){
				$maxvar = get_post_meta($variation_id,'b2bking_quantity_product_max_b2c', true);
				if (!empty($maxvar)){
					$have = $maxvar;
				}
			}
		}

		$have = apply_filters('b2bking_product_max_value', $have, $product_id, $user_id);
		if (!is_numeric($have)){
			return false;
		}

		return $have;
	}

	// returns either false if not have, or the value if have
	public static function get_product_meta_min($product_id, $variation_id = false){

		if (intval(get_option( 'b2bking_disable_product_level_minmaxstep_setting', 1 )) === 1){
			return false;
		}

		$have = false;

		$user_id = get_current_user_id();
		$is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
		if ($is_b2b === 'yes'){
			$user_group = get_user_meta($user_id,'b2bking_customergroup', true);
			// check if have here
			$min = get_post_meta($product_id,'b2bking_quantity_product_min_'.$user_group, true);
			if (!empty($min)){
				$have = $min;
			}

			if ($variation_id){
				$minvar = get_post_meta($variation_id,'b2bking_quantity_product_min_'.$user_group, true);
				if (!empty($minvar)){
					$have = $minvar;
				}
			}
		}

		if ($have === false){
			// search further
			$min = get_post_meta($product_id,'b2bking_quantity_product_min_b2c', true);
			if (!empty($min)){
				$have = $min;
			}

			if ($variation_id){
				$minvar = get_post_meta($variation_id,'b2bking_quantity_product_min_b2c', true);
				if (!empty($minvar)){
					$have = $minvar;
				}
			}
		}

		$have = apply_filters('b2bking_product_min_value', $have, $product_id, $user_id);

		if (!is_numeric($have)){
			return false;
		}


		return $have;
	}

	public static function get_product_meta_applies_variations($product_id){

		return true; // force true, this should be deleted if giving users the option to apply the quantities for the entire product

		if (!metadata_exists('post', $product_id, 'b2bking_apply_minmaxstep_individual_variations')){
			$valinfo = 'yes';
		} else {
			$valinfo = get_post_meta( $product_id, 'b2bking_apply_minmaxstep_individual_variations', true );
		}

		if ($valinfo === 'yes'){
			return true;
		}

		return false;
	}

	// returns either false if not have, or the value if have
	public static function get_product_meta_step($product_id, $variation_id = false){

		if (intval(get_option( 'b2bking_disable_product_level_minmaxstep_setting', 1 )) === 1){
			return false;
		}

		$have = false;

		$user_id = get_current_user_id();
		$is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
		if ($is_b2b === 'yes'){
			$user_group = get_user_meta($user_id,'b2bking_customergroup', true);
			// check if have here
			$step = get_post_meta($product_id,'b2bking_quantity_product_step_'.$user_group, true);
			if (!empty($step)){
				$have = $step;
			}

			if ($variation_id){
				$stepvar = get_post_meta($variation_id,'b2bking_quantity_product_step_'.$user_group, true);
				if (!empty($stepvar)){
					$have = $stepvar;
				}
			}
		}

		if ($have === false){
			// search further
			$step = get_post_meta($product_id,'b2bking_quantity_product_step_b2c', true);
			if (!empty($step)){
				$have = $step;
			}

			if ($variation_id){
				$stepvar = get_post_meta($variation_id,'b2bking_quantity_product_step_b2c', true);
				if (!empty($stepvar)){
					$have = $stepvar;
				}
			}
		}

		$have = apply_filters('b2bking_product_step_value', $have, $product_id, $user_id);

		if (!is_numeric($have)){
			return false;
		}

		return $have;
	}

	public static function switch_to_user_locale($email_address){

		$user = get_user_by('email', $email_address);
		$locale = get_user_locale($user);
		switch_to_locale($locale);

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		unload_textdomain( 'b2bking', true ); //remove recent
		load_textdomain( 'b2bking', WP_PLUGIN_DIR . '/' . B2BKING_LANG.'/b2bking-'.$locale.'.mo'); 
		load_plugin_textdomain( 'b2bking', false, B2BKING_LANG );  
	}

	public static function restore_locale(){
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init WC locale.
		$locale = get_locale();
		//unload_textdomain( 'b2bking' ); //remove recent
		//load_textdomain( 'b2bking', B2BKING_LANG.'/b2bking-'.$locale.'.mo'); //remove recent
		load_plugin_textdomain( 'b2bking', false, B2BKING_LANG );  
	}

	public static function wc_get_product_bk($product_id){
		if (is_object($product_id)){
			return $product_id; // likely a product object already
		}
		if (empty($product_id) || $product_id == 0){
			return $product_id;
		}

		global $b2bking_product_objects;
		if (!isset($b2bking_product_objects[$product_id])){
			$b2bking_product_objects[$product_id] = wc_get_product($product_id);
		}

		return $b2bking_product_objects[$product_id];
	}

	// for the admin backend, this shows groups only when they have visibility to see that product.
	public static function group_has_product($group_id, $product_id){

		$has = true;

		if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){
			$parent_id = wp_get_post_parent_id($product_id);
			// if there is a parent
			if ($parent_id !== 0){
				$product_id = $parent_id;
			}

			// if not manual visibility
			$visibility = get_post_meta( $product_id, 'b2bking_product_visibility_override', true );

			if ($visibility !== 'manual'){
				$terms = get_the_terms( $product_id, apply_filters('b2bking_visibility_taxonomy','product_cat') );

				global $b2bking_productcategories;
				global $b2bking_productcategories_groups_visible;
				global $b2bking_productcategories_users_visible;

				if (!is_array($b2bking_productcategories_groups_visible) or !is_array($b2bking_productcategories_users_visible) or !is_array($b2bking_productcategories)){

					$b2bking_productcategories = array(); // category names
					$b2bking_productcategories_groups_visible = array();
					$b2bking_productcategories_users_visible = array();

					if(!empty($terms)){
						foreach ($terms as $term) {
							// build array of category names
						    array_push($b2bking_productcategories, $term->name);

						    // build array of users with visibility access
						    $usersarray = explode(',', esc_html(get_term_meta($term->term_id, 'b2bking_category_users_textarea', true)));
						    $b2bking_productcategories_users_visible = array_merge($b2bking_productcategories_users_visible, $usersarray);

						    // build array of user groups with visibility access
						    $allgroups = get_posts(['post_type' => 'b2bking_group', 'post_status' => 'publish', 'numberposts' => -1]);
						    foreach ($allgroups as $group){
						    	if (intval(get_term_meta($term->term_id, 'b2bking_group_'.$group->ID, true)) === 1){
						    		array_push($b2bking_productcategories_groups_visible, $group->ID);
						    	}
						    }
						    // Also add Guest Users Group
						    if (intval(get_term_meta($term->term_id, 'b2bking_group_0', true)) === 1){
						    	array_push($b2bking_productcategories_groups_visible, 0);
						    }
						    // Also add B2C Users Group
						    if (intval(get_term_meta($term->term_id, 'b2bking_group_b2c', true)) === 1){
						    	array_push($b2bking_productcategories_groups_visible, 'b2c');
						    }
						}
					}


					// trim users array
					$b2bking_productcategories_users_visible = array_map('trim', $b2bking_productcategories_users_visible);
					// remove duplicates and empty values from users array
					$b2bking_productcategories_users_visible = array_filter(array_unique($b2bking_productcategories_users_visible));
					// remove duplicates from groups array
					$b2bking_productcategories_groups_visible = array_unique($b2bking_productcategories_groups_visible);


					// if user has enabled "hidden has priority", override setting
					if (intval(get_option( 'b2bking_hidden_has_priority_setting', 0 )) === 1){
						if(!empty($terms)){
							// if there is at least 1 category that is hidden to a group, remove the group from visible
							$allgroups = get_posts(['post_type' => 'b2bking_group', 'post_status' => 'publish', 'numberposts' => -1]);
							foreach ($allgroups as $group){
								$hidden = 'no';
								$hiddenguests = 'no';
								$hiddenb2c = 'no';
								foreach ($terms as $term) {
									if (intval(get_term_meta($term->term_id, 'b2bking_group_'.$group->ID, true)) !== 1){
										$hidden = 'yes';
									}
									if (intval(get_term_meta($term->term_id, 'b2bking_group_0', true)) !== 1){
										$hiddenguests = 'yes';
									}
									if (intval(get_term_meta($term->term_id, 'b2bking_group_b2c', true)) !== 1){
										$hiddenb2c = 'yes';
									}
								}
								if ($hidden === 'yes'){
									// remove group from options
									if (($key = array_search($group->ID, $b2bking_productcategories_groups_visible)) !== false) {
									    unset($b2bking_productcategories_groups_visible[$key]);
									}
								}
								if ($hiddenguests === 'yes'){
									// remove guest group from options
									if (($key = array_search(0, $b2bking_productcategories_groups_visible)) !== false) {
									    unset($b2bking_productcategories_groups_visible[$key]);
									}
								}
								if ($hiddenb2c === 'yes'){
									// remove b2c group from options
									if (($key = array_search('b2c', $b2bking_productcategories_groups_visible)) !== false) {
									    unset($b2bking_productcategories_groups_visible[$key]);
									}
								}
							}

							// if there is at least 1 category that is hidden to a user (from the list of visible users), remove the user from visible
							foreach ($b2bking_productcategories_users_visible as $key => $user_login){
								foreach ($terms as $term) {
									$users_visible = array_filter(array_unique(explode(',', esc_html(get_term_meta($term->term_id, 'b2bking_category_users_textarea', true)))));
									$users_visible = array_map('trim', $users_visible);


									if (!in_array($user_login, $users_visible)){
										unset($b2bking_productcategories_users_visible[$key]);
									}
								}
							}
						}
					}
				}

				$find = 'no';
				foreach ($b2bking_productcategories_groups_visible as $group){
					if (intval($group_id) === intval($group)){
						$find = 'yes';
					}
				}

				if ($find === 'no'){
					$has = false;
				}
			}

		}

		return $has;
	}

	// takes rule IDs array. If any of them has priority, it returns array of highest priority rules
	// if none has priority, it returns all rules
	public static function get_rules_apply_priority($rules){

		// apply general rules filter, here you can remove specific rules by ID
		$rules = apply_filters('b2bking_applied_rules', $rules);

		$priority_used = 0;
		$have_priority = 'no';

		foreach ($rules as $rule_id){
			$priority = intval(get_post_meta($rule_id,'b2bking_standard_rule_priority', true));
			if (!empty($priority)){
				if (intval($priority) !== 0){
					$have_priority = 'yes';
					if ($priority > $priority_used){
						$priority_used = $priority;
					}
				}
			}
		}

		if ($have_priority === 'no'){
			return $rules;
		} else {
			// continue to sort and get the rules with the highest priority.
			foreach ($rules as $index => $rule_id){
				$priority = intval(get_post_meta($rule_id,'b2bking_standard_rule_priority', true));

				if ($priority !== $priority_used){
					// remove rule
					unset($rules[$index]);
				}
			}

			return $rules;
		}
	}

	// applies wc_get_price_to_display for each option in tiered prices string, (for scripts)
	public static function apply_tax_to_tiers($tieredpricing, $product){

		$price_tiers = array_filter(explode(';', $tieredpricing));
		$converted_tiers = array();

		foreach($price_tiers as $tier){
			
			if (!empty($tier)){
				$tier_values = explode(':', $tier);
				if (isset($tier_values[1])){
					$tier_values[1] = b2bking()->tofloat($tier_values[1]);
					$tier_values[1] = b2bking()->b2bking_wc_get_price_to_display( $product, array( 'price' => $tier_values[1] ) ); 
				}

				$converted_tiers[] = implode(':', $tier_values);
			}
			
		}

		$tieredpricing = implode(';', $converted_tiers);

		return $tieredpricing;
	}

	// takes price tiers string and converts it for the situation where tiers are entered as percentages
	// also applies dynamic rules tiered tables
	public static function convert_price_tiers($price_tiers_original, $product){

		// trim space
		$price_tiers_original = trim($price_tiers_original);

		// if not variation or simple, return
		$supported_types = array('variation', 'simple', 'group', 'license', 'course', 'courses'); //support learndash types as well
		$product_type = $product->get_type();
		if (!in_array($product_type, $supported_types)){
			return $price_tiers_original;
		}

		// if there is a table on the product page, this table will overwrite the dynamic rule.
		// if no product price tiers, proceed with tiered pricing rules
		if (empty($price_tiers_original) || apply_filters('b2bking_rules_override_product_price_tiers', false, $product)){
			// check for dynamic rules here and replace price with dynamic rules
			$rules_tiered = b2bking()->get_applicable_rules('tiered_price', $product->get_id());
			if (isset($rules_tiered[0])){
				$rules_tiered = $rules_tiered[0];

				if (!empty($rules_tiered)){
					if (is_array($rules_tiered)){

						foreach ($rules_tiered as $index => $rule_id){
							if (get_post_status($rule_id) !== 'publish'){
								unset($rules_tiered[$index]);
							}
						}

						if (!empty($rules_tiered)){
							// get which rule has the highest priority
							$applied_rule = reset($rules_tiered);
							$priority_used = 0;

							foreach ($rules_tiered as $rule_id){
								$priority = intval(get_post_meta($rule_id,'b2bking_rule_priority', true));
								if ($priority > $priority_used){
									$priority_used = $priority;
									$applied_rule = $rule_id;
								}
							}

							$table = get_post_meta($applied_rule,'b2bking_product_pricetiers_group_b2c', true);
							$price_tiers_original = $table;
						}

						
					}
					
				}
			}
			
		}
		

		// convert to percentages
		if (intval(get_option( 'b2bking_enter_percentage_tiered_setting', 0 )) === 1){

			$user_id = get_current_user_id();
	    	$user_id = b2bking()->get_top_parent_account($user_id);

			$currentusergroupidnr = apply_filters('b2bking_b2b_group_for_pricing', b2bking()->get_user_group($user_id), $user_id, $product->get_id());
			$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );

			$original_user_price_sale = get_post_meta($product->get_id(),'_sale_price',true);
			$original_user_price_reg = get_post_meta($product->get_id(),'_regular_price',true);

			if (empty($original_user_price_sale)){
				$original_user_price = $original_user_price_reg;
			} else {
				$original_user_price = $original_user_price_sale;
			}

			if ($is_b2b_user === 'yes'){
				// Search if there is a specific price set for the user's group
				$b2b_price_sale = b2bking()->tofloat(get_post_meta($product->get_id(), 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				$b2b_price_reg = b2bking()->tofloat(get_post_meta($product->get_id(), 'b2bking_regular_product_price_group_'.$currentusergroupidnr, true ));
									
				if (!empty($b2b_price_sale)){
					$original_user_price = $b2b_price_sale;
				} else {
					if (!empty($b2b_price_reg)){
						$original_user_price = $b2b_price_reg;
					} 
				}
			}

			// adjust price for tax
			//$original_user_price = b2bking()->b2bking_wc_get_price_to_display( $product, array( 'price' => $original_user_price ) ); // get sale price


			$price_tiers = array_filter(explode(';', $price_tiers_original));
			$converted_tiers = array();

			foreach($price_tiers as $tier){
				$tier_values = explode(':', $tier);
				if (isset($tier_values[1])){
					$tier_values[1] = b2bking()->tofloat($tier_values[1]);

					// this is a discount percentage, and we must convert it to 'final price'
					if (apply_filters('b2bking_tiered_price_percentage_substracts_flat_value', false, $product)){
						$tier_values[1] = floatval($original_user_price) - $tier_values[1];
					} else {
				 		$tier_values[1] = floatval($original_user_price)*(100-$tier_values[1])/100;
				 	}

					
				}

				$converted_tiers[] = implode(':', $tier_values);
			}

			$price_tiers = implode(';', $converted_tiers);

			$price_tiers_original = $price_tiers;
		}

		$price_tiers_original = apply_filters('b2bking_tiered_prices_modify', $price_tiers_original, $product, get_current_user_id());

		return $price_tiers_original;
	}

	public static function price_is_already_formatted($price){

		$symbol = get_woocommerce_currency_symbol();
		if (strpos($price, $symbol) !== false) {
		    return true;
		}
		if (strpos($price, ',') !== false) {
		    return true;
		}
		if (strpos($price, '.') !== false) {
		    return true;
		}
		
		return false;
	}

	// b2c user that applied for b2b
	public static function has_b2b_application_pending($user_id){

		// if user is already B2B, it should return false
		$is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
		if ($is_b2b === 'yes'){
			return false;
		}

		$pending = get_user_meta($user_id,'b2bking_b2b_application_pending',true);
		if ($pending === 'yes'){
			return true;
		} else {
			return false;
		}
	}

	public static function tofloat($num, $decimalsset = 0) {
		$num = is_null($num) ? '' : (string)$num; // Convert $num to a string, or empty string if null

	    $dotPos = strrpos($num, '.');
	    $commaPos = strrpos($num, ',');

	    if ($dotPos === false && $commaPos === false){
	    	// if number doesnt have either dot or comma, return number
	    	return floatval($num);
	    }
	    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
	        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
	  
	    if (!$sep) {
	        return floatval(preg_replace("/[^0-9]/", "", $num));
	    }

	    $decimals = apply_filters('b2bking_rounding_precision', get_option('woocommerce_price_num_decimals', 2));
	    if ($decimalsset !== 0){
	    	if ($decimalsset > $decimals){
	    		$decimals = $decimalsset;
	    	}
	    }

	    return round(floatval(
	        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
	        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
	    ), $decimals);
	}

	public static function get_customer_total_spent_without_tax( $user_id ) {
        global $wpdb;

        $statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
        
        $spent    = $wpdb->get_var("
            SELECT SUM(pm2.meta_value - (pm3.meta_value))
            FROM $wpdb->posts as p
            LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
            LEFT JOIN {$wpdb->postmeta} AS pm2 ON p.ID = pm2.post_id
            LEFT JOIN {$wpdb->postmeta} AS pm3 ON p.ID = pm3.post_id
            WHERE   pm.meta_key   = '_customer_user'
            AND     pm.meta_value = '" . esc_sql( $user_id ) . "'
            AND     p.post_type   = 'shop_order'
            AND     p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
            AND     pm2.meta_key  = '_order_total'
            AND     pm3.meta_key  = '_order_tax'
        ");

        if ( ! $spent ) {
            $spent = 0;
        }
        
        $value = $spent;

	    return $value;
	}

	public static function is_b2b_user($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);
		$customer_group = get_user_meta($user_id,'b2bking_customergroup', true);
		if ($is_b2b === 'yes' && $customer_group !== 'no'){
			return apply_filters('b2bking_is_b2b_user', true, $user_id, $customer_group);
		}

		return apply_filters('b2bking_is_b2b_user', false, $user_id, $customer_group);
	}

	public static function get_user_group($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$meta_key = apply_filters('b2bking_group_key_name', 'b2bking_customergroup');

		$group = get_user_meta($user_id, $meta_key, true);
		return $group;
	}

	public static function get_user_group_name($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$meta_key = apply_filters('b2bking_group_key_name', 'b2bking_customergroup');

		$group = get_user_meta($user_id, $meta_key, true);
		return get_the_title($group);
	}

	public static function get_user_subaccounts($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$subaccounts_list = get_user_meta($user_id,'b2bking_subaccounts_list', true);
		$subaccounts_list = explode(',', $subaccounts_list);
		$subaccounts_list = array_filter(array_unique($subaccounts_list));

		return $subaccounts_list;
	}

	public static function is_subaccount($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$account_type = get_user_meta($user_id, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			return true;
		}

		return false;
	}

	public static function is_approved($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$account_type = get_user_meta($user_id, 'b2bking_account_approved', true);
		if ($account_type === 'no'){
			return false;
		}

		return true;
	}

	public static function get_user_custom_field($field_id, $user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}

		$field_value = get_user_meta($user_id,'b2bking_custom_field_'.$field_id, true);

		return $field_value;
	}

	public static function get_user_phone($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}
		$phone = get_user_meta($user_id,'billing_phone', true);
		if (empty($phone)){
			$phone = get_user_meta($user_id,'shipping_phone', true);
		}
		if (empty($phone)){
			$phone = '';
		}
		return $phone;
	}
	public static function get_user_company($user_id = 0){
		if ($user_id === 0){
			$user_id = get_current_user_id();
		}
		$phone = get_user_meta($user_id,'billing_company', true);
		if (empty($phone)){
			$phone = get_user_meta($user_id,'shipping_company', true);
		}
		if (empty($phone)){
			$phone = '';
		}
		return $phone;
	}

	public static function find_first_parent_account($user_id){

		$account_type = get_user_meta($user_id, 'b2bking_account_type', true);

		while ($account_type === 'subaccount'){
			$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
			$account_type = get_user_meta($parent_user_id, 'b2bking_account_type', true);
			$user_id = $parent_user_id;
		}

		return $user_id;
	}

	public static function update_user_group($user_id, $value){

		$meta_key = apply_filters('b2bking_group_key_name', 'b2bking_customergroup');
		$previous_value = get_user_meta($user_id, $meta_key, true);

		update_user_meta($user_id, $meta_key, $value);

		// if user is parent account, also update all subaccounts, and subaccounts of subaccounts
		$account_type = get_user_meta($user_id, 'b2bking_account_type', true);
		if ($account_type !== 'subaccount'){
			$subaccounts_list = get_user_meta($user_id,'b2bking_subaccounts_list', true);
			$subaccounts_list = explode(',', $subaccounts_list);
			$subaccounts_list = array_filter($subaccounts_list);
			foreach ($subaccounts_list as $subaccount_id){
				update_user_meta($subaccount_id, $meta_key, $value);

				$subsubaccounts_list = get_user_meta($subaccount_id,'b2bking_subaccounts_list', true);
				$subsubaccounts_list = explode(',', $subsubaccounts_list);
				$subsubaccounts_list = array_filter($subsubaccounts_list);
				foreach ($subsubaccounts_list as $sub_id){
					update_user_meta($sub_id, $meta_key, $value);
				}
			}
		}

		do_action('b2bking_user_group_updated', $user_id, $value, $previous_value); // user_id, new_group, old_group

	}

	public static function custom_modulo($nr1, $nr2){
		$evenlyDivisable = abs(($nr1 / $nr2) - round($nr1 / $nr2, 0)) < 0.00001;

		if ($evenlyDivisable){
			// number has no decimals, therefore remainder is 0
			return 0;
		} else {
			return 1;
		}
	}

	public static function b2bking_wc_get_price_to_display( $product, $args = array() ) {

		if (is_a($product,'WC_Product_Variation') || is_a($product,'WC_Product')){

			// Modify WC function to consider user's vat exempt status
			global $woocommerce;
			$customertest = $woocommerce->customer;

			if (is_a($customertest, 'WC_Customer')){
				$customer = WC()->customer;
				$vat_exempt = $customer->is_vat_exempt();
			} else {
				$vat_exempt = false;
			}
		    $args = wp_parse_args(
		        $args,
		        array(
		            'qty'   => 1,
		            'price' => $product->get_price(),
		        )
		    );

		    $price = $args['price'];
		    $qty   = $args['qty'];

		    if (is_cart() || is_checkout()){
		    	if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) && !$vat_exempt ){
		    		return 
		    	    wc_get_price_including_tax(
		    	        $product,
		    	        array(
		    	            'qty'   => $qty,
		    	            'price' => $price,
		    	        )
		    	    );
		    	} else {
		    		return
		    	    wc_get_price_excluding_tax(
		    	        $product,
		    	        array(
		    	            'qty'   => $qty,
		    	            'price' => $price,
		    	        )
		    	    );
		    	}
		    } else {
		    	//shop
		    	if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) && !$vat_exempt ){
		    		return 
		    	    wc_get_price_including_tax(
		    	        $product,
		    	        array(
		    	            'qty'   => $qty,
		    	            'price' => $price,
		    	        )
		    	    );
		    	} else {
		    		return
		    	    wc_get_price_excluding_tax(
		    	        $product,
		    	        array(
		    	            'qty'   => $qty,
		    	            'price' => $price,
		    	        )
		    	    );
		    	}
		    }
		} else {
			return 0;
		}
	    
	}

	public static function get_woocs_price( $price ) {

		if (defined('WOOCS_VERSION')) {
			global $WOOCS;
			$currrent = $WOOCS->current_currency;
			if ($currrent != $WOOCS->default_currency) {
				$currencies = $WOOCS->get_currencies();
				$rate = $currencies[$currrent]['rate'];
				$price = floatval($price) * floatval($rate);
			}
		}

		// WPML integration
		if (apply_filters('b2bking_apply_wcml_currency', true)){
			$current_currency = apply_filters('wcml_price_currency', NULL );
			if ($current_currency !== NULL){
				$price = apply_filters( 'wcml_raw_price_amount', $price, $current_currency );
			}
		}

		if (defined('WCCS_VERSION')) {
		    global $WCCS;
		    if (isset($WCCS) && !empty($WCCS)){
		    	$price = $WCCS->wccs_price_conveter($price);
		    }
		}

		// CURCY
		if ( defined('WOOMULTI_CURRENCY_F_DIR') ){

			$curcy_settings = WOOMULTI_CURRENCY_F_Data::get_ins();
			$current_currency = $curcy_settings->get_current_currency();
			$default_currency = $curcy_settings->get_default_currency();

			if ($current_currency !== $default_currency){
				$rate = wmc_get_price( 1, $current_currency );
				$price = floatval($price) * floatval($rate);
			}
			
		}

		return apply_filters('b2bking_currency_converted_price', $price);
	}

	public static function is_rest_api_request() {

		if (apply_filters('b2bking_force_cancel_cron_requests', false)){
			if (function_exists('php_sapi_name')){
				$phpsapi = php_sapi_name();
				if ($phpsapi == 'cli'){
					return true;
				}
			}
		}		

	    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
	        // Probably a CLI request
	        return false;
	    }

	    $rest_prefix         = trailingslashit( rest_get_url_prefix() );
	    $is_rest_api_request = strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) !== false;

	    if (defined('REST_REQUEST')){
	    	$is_rest_api_request = true;
	    }

	    if (isset($_GET['consumer_key']) || isset($_GET['consumer_secret'])){
	    	$is_rest_api_request = true;
	    }

	    // get current URL
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "۸۰") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
	    
	    if (strpos($pageURL, '/wc-api') !== false) {
	        $is_rest_api_request = true;
	    }
	    if (strpos($pageURL, '/wp-api') !== false) {
	        $is_rest_api_request = true;
	    }
	    if (strpos($pageURL, '/wc/v') !== false) {
	        $is_rest_api_request = true;
	    }
	    if (strpos($pageURL, '/wp/v') !== false) {
	        $is_rest_api_request = true;
	    }

	   

	    return apply_filters( 'is_rest_api_request', $is_rest_api_request );
	}

	public static function get_taxonomy_name($taxonomy){
		if ($taxonomy === 'category'){
			return 'product_cat';
		}
		if ($taxonomy === 'tag'){
			return apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag');
		}

		return '';
	}

	public static function get_taxonomies(){

		if (apply_filters('b2bking_dynamic_rules_show_tags', true)){
			$taxonomies = array(
				'category' => 'product_cat',
				'tag' => apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag'),
			);
		} else {
			$taxonomies = array(
				'category' => 'product_cat',
			);
		}

		return $taxonomies;
	}

	// returns an array of all categories including all parent categories of subcategories a product belongs to
	public static function get_all_product_categories_taxonomies($product_id, $taxonomy){

		if ($product_id == 0 || empty($product_id)){
			return array();
		}

		// initialize variable
		global ${'b2bking_all_categories'.$taxonomy};
		global $b2bking_cached_categories_original;

		if (!is_array(${'b2bking_all_categories'.$taxonomy})){
			${'b2bking_all_categories'.$taxonomy} = array();

			// we are at the beginning of the execution, the categories global is empty, let's merge it with the cached categories global
			$b2bking_cached_categories = get_transient('b2bking_cached_categories_taxonomies'.$taxonomy);
			$b2bking_cached_categories_original = $b2bking_cached_categories;
			
			if (is_array($b2bking_cached_categories)){
				// if cached categories exist
				${'b2bking_all_categories'.$taxonomy} = $b2bking_cached_categories;
			}
		}

		if (isset(${'b2bking_all_categories'.$taxonomy}[$product_id])){
			// skip
		} else {
			${'b2bking_all_categories'.$taxonomy}[$product_id] = $direct_categories = wc_get_product_term_ids($product_id, $taxonomy);

			/* New 4.6.05 also add in parent product categories, if the product has a parent (e.g. variation) */

			$possible_parent_id = wp_get_post_parent_id($product_id);
			if ($possible_parent_id !== 0){
				// if product has parent
				$parent_product_categories = wc_get_product_term_ids($possible_parent_id, $taxonomy);
				$new_categories = array_merge($direct_categories, $parent_product_categories);

				${'b2bking_all_categories'.$taxonomy}[$product_id] = $new_categories;
				$direct_categories = $new_categories;
			}

			/* end new behaviour */

			//${'b2bking_all_categories'.$taxonomy}[$product_id] = $direct_categories = wp_get_post_terms($product_id,'product_cat', array('hide_empty' => false,'fields' =>'ids'));

			// set via code snippets that rule apply to the direct categories only (And not apply to parent/sub categories)
			if (apply_filters('b2bking_apply_rules_to_direct_categories_only', false)){
				return ${'b2bking_all_categories'.$taxonomy}[$product_id];
			}

			foreach ($direct_categories as $directcat){
				// find all parents
				$term = get_term($directcat, $taxonomy);
				while ($term->parent !== 0){
					array_push(${'b2bking_all_categories'.$taxonomy}[$product_id], $term->parent);
					$term = get_term($term->parent, $taxonomy);
				}
			}
			${'b2bking_all_categories'.$taxonomy}[$product_id] = array_filter(array_unique(${'b2bking_all_categories'.$taxonomy}[$product_id]));
		}

		return ${'b2bking_all_categories'.$taxonomy}[$product_id];
	}

	public static function b2bking_has_taxonomy( $category_id, $taxonomy, $product_id ) {

		// initialize variable
		global ${'b2bking_all_categories'.$taxonomy};
		if (!is_array(${'b2bking_all_categories'.$taxonomy})){
			${'b2bking_all_categories'.$taxonomy} = array();
		}
	 	
	 	if (isset(${'b2bking_all_categories'.$taxonomy}[$product_id])){
	 		// we already have all categories for the product
	 	} else {
	 		// determine all categories for the product
	 		${'b2bking_all_categories'.$taxonomy}[$product_id] = $direct_categories = wc_get_product_term_ids($product_id, $taxonomy);

	 		// set via code snippets that rule apply to the direct categories only (And not apply to parent/sub categories)
	 		if (apply_filters('b2bking_apply_rules_to_direct_categories_only', false)){
	 			// skip
	 		} else {
	 			// continue here
	 			foreach ($direct_categories as $directcat){
	 				// find all parents
	 				$term = get_term($directcat, $taxonomy);
	 				while ($term->parent !== 0){
	 					array_push(${'b2bking_all_categories'.$taxonomy}[$product_id], $term->parent);
	 					$term = get_term($term->parent, $taxonomy);
	 				}
	 			}

	 			${'b2bking_all_categories'.$taxonomy}[$product_id] = array_filter(array_unique(${'b2bking_all_categories'.$taxonomy}[$product_id]));

	 		}
	 	}

	    if (in_array($category_id, ${'b2bking_all_categories'.$taxonomy}[$product_id])){
	    	return true;
	    }

		return false;
	}

	public static function is_side_cart(){
		$side_cart = false;

		global $b2bking_is_mini_cart; 

		if ($b2bking_is_mini_cart === true){
			$side_cart = true;
		}

		if (wp_doing_ajax() && isset($_REQUEST['action']) && $_REQUEST['action'] === 'etheme_added_to_cart_popup') {
			$side_cart = true;
		}


		return apply_filters('b2bking_is_side_cart', $side_cart);
	}

	public static function b2bking_clear_rules_caches(){
		require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
		B2bking_Admin::b2bking_calculate_rule_numbers_database();
	}

	public static function use_wpml_cache(){
		$use_wpml_cache = false;
		if (defined('ICL_LANGUAGE_NAME_EN')) {
			// Get language code from WPML filter for validation
			$current_language_code = function_exists('apply_filters') ? apply_filters('wpml_current_language', null) : '';

			if (!empty($current_language_code)) {
				// Convert language code to language name for comparison
				$current_language_name = function_exists('apply_filters') ? apply_filters('wpml_translated_language_name', '', $current_language_code, 'en') : '';

				// Only use WPML cache if both methods agree on the language
				if (!empty($current_language_name) && $current_language_name === ICL_LANGUAGE_NAME_EN) {
					$use_wpml_cache = true;
				}
			}
		}

		return $use_wpml_cache;
	}

	public static function clear_caches_transients(){
		// set that rules have changed so that pricing cache can be updated
		update_option('b2bking_commission_rules_have_changed', 'yes');
		update_option('b2bking_dynamic_rules_have_changed', 'yes');

		// delete all b2bking transients
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%transient_b2bking%'" );

		// extra clear for object caches
		$currentuserid = get_current_user_id();
    	$currentuserid = b2bking()->get_top_parent_account($currentuserid);
		if (!defined('ICL_LANGUAGE_NAME_EN')){
			delete_transient('b2bking_user_'.$currentuserid.'_ajax_visibility');
		} else {
			delete_transient('b2bking_user_'.$currentuserid.'_ajax_visibility'.ICL_LANGUAGE_NAME_EN);
		}

		if (apply_filters('b2bking_flush_cache_wp', true)){ // deactivating can solve issue with regular price setting
			wp_cache_flush();
		}

		// force permalinks
		update_option('b2bking_force_permalinks_flushing_setting', 1);

		delete_transient('webwizards_dashboard_data_cache');
		delete_transient('webwizards_dashboard_data_cache_time');
	}

	// get all rules by user
	// returns array of rule IDs
	public static function get_all_rules($rule_type = 'all', $user_id = 'current'){

		if ($user_id === 'current'){
			$user_id = get_current_user_id();
		}

		$user_id = b2bking()->get_top_parent_account($user_id);

		$currentusergroupidnr = b2bking()->get_user_group($user_id);
		if (!$currentusergroupidnr || empty($currentusergroupidnr)){
			$currentusergroupidnr = 'invalid';
		}

		$array_who_multiple = array(
			'relation' => 'OR',
			array(
				'key' => 'b2bking_rule_who_multiple_options',
				'value' => 'group_'.$currentusergroupidnr,
				'compare' => 'LIKE'
			),
			array(
			    'key' => 'b2bking_rule_who_multiple_options',
			    'value' => 'user_' . $user_id . '(,|$|[^0-9])', // can use REGEXP, possible concern about performance
			    'compare' => 'REGEXP'
			)
		);

		if ($user_id !== 0){
			array_push($array_who_multiple, array(
							'key' => 'b2bking_rule_who_multiple_options',
							'value' => 'all_registered',
							'compare' => 'LIKE'
						));

			// add rules that apply to all registered b2b/b2c users
			$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
			if ($user_is_b2b === 'yes'){
				array_push($array_who_multiple, array(
							'key' => 'b2bking_rule_who_multiple_options',
							'value' => 'everyone_registered_b2b',
							'compare' => 'LIKE'
						));
			} else {
				array_push($array_who_multiple, array(
							'key' => 'b2bking_rule_who_multiple_options',
							'value' => 'everyone_registered_b2c',
							'compare' => 'LIKE'
						));
			}

		}

		$array_who = array(
						'relation' => 'OR',
						array(
							'key' => 'b2bking_rule_who',
							'value' => 'group_'.$currentusergroupidnr
						),
						array(
							'key' => 'b2bking_rule_who',
							'value' => 'user_'.$user_id
						),
						array(
							'relation' => 'AND',
							array(
								'key' => 'b2bking_rule_who',
								'value' => 'multiple_options'
							),
							$array_who_multiple
						),
					);

		// if user is registered, also select rules that apply to all registered users
		if ($user_id !== 0){
			array_push($array_who, array(
							'key' => 'b2bking_rule_who',
							'value' => 'all_registered'
						));

			// add rules that apply to all registered b2b/b2c users
			$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
			if ($user_is_b2b === 'yes'){
				array_push($array_who, array(
							'key' => 'b2bking_rule_who',
							'value' => 'everyone_registered_b2b'
						));
			} else {
				array_push($array_who, array(
							'key' => 'b2bking_rule_who',
							'value' => 'everyone_registered_b2c'
						));
			}

		}

		$rules = get_posts([
			'post_type' => 'b2bking_rule',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields' 	  => 'ids',
			'meta_query'=> array(
				'relation' => 'AND',
				$array_who,
			)
		]);


		if ($rule_type !== 'all'){
			// remove rules that don't match rule type.
			foreach ($rules as $index=>$rule){
				$type = get_post_meta($rule,'b2bking_rule_what', true);
				if ($rule_type !== $type){
					unset($rules[$index]);
				}
			}
		}

		return $rules;


	}

	/*
	When dealing with a large number of products and / or a large number of dynamic rules,
	We can see situations where the plugin calls for thousands of transients, which affects load times
	It's better for load times to call a single large transient that contains all this info
	We get such a transient and we set it as a global function / data for quicker access
	*/

	public static function get_global_data($requested = false, $product_id = false, $user_id = false){
		//'b2bking_'.$rule_type.'_rules_apply_'.$current_product_id
		global $b2bking_data;

		if (!is_array($b2bking_data)){
			$b2bking_data = array();
		}

		// if data not set, get data from db
		if (empty($b2bking_data)){
			// get it form database
			$b2bking_data = get_transient('b2bking_global_data');
			global $cache_first_retrieved;
			$cache_first_retrieved = $b2bking_data;
		}

		// Request for all data
		if ($requested === false){
			return $b2bking_data;
		}
		// reached here, it means we have some specific data requested

		// if no specific product id or user id
		if (!$product_id && ($user_id === false)){
			// requested could be 'b2bking_discount_everywhere_rules_apply' for example
			$requested_value = isset($b2bking_data[$requested]) ? $b2bking_data[$requested] : false;
		}

		// if product id set, but not user id
		if ($product_id && ($user_id === false)){
			$requested_value = isset($b2bking_data[$requested][$product_id]) ? $b2bking_data[$requested][$product_id] : false;
		}

		// if user id set, but not product id
		if (!$product_id && ($user_id !== false)){
			$requested_value = isset($b2bking_data[$requested][$user_id]) ? $b2bking_data[$requested][$user_id] : false;
		}

		// both product and user id are set
		if ($product_id && ($user_id !== false)){
			$requested_value = isset($b2bking_data[$requested][$product_id][$user_id]) ? $b2bking_data[$requested][$product_id][$user_id] : false;
		}

		if (isset($requested_value)){
			return $requested_value;
		} else {
			return false;
		}
		
	}

	public static function set_global_data($requested = false, $value = false, $product_id = false, $user_id = false){

		global $b2bking_data;

		// if global not set, get it first
		if (empty($b2bking_data)){
			$b2bking_data = b2bking()->get_global_data();
		}

		if (!$product_id && ($user_id === false)){
			// requested could be 'b2bking_discount_everywhere_rules_apply' for example
			$b2bking_data[$requested] = $value;
		}

		// if product id set, but not user id
		if ($product_id && ($user_id === false)){

			// prevent assignment errors
			if (isset($b2bking_data[$requested])){
				if (!is_array($b2bking_data[$requested])){
					$b2bking_data[$requested] = array();
				}
			}
			
			
			$b2bking_data[$requested][$product_id] = $value;
		}

		// if user id set, but not product id
		if (!$product_id && ($user_id !== false)){

			// prevent assignment errors
			if (isset($b2bking_data[$requested])){
				if (!is_array($b2bking_data[$requested])){
					$b2bking_data[$requested] = array();
				}
			}

			$b2bking_data[$requested][$user_id] = $value;
		}

		// both product and user id are set
		if ($product_id && ($user_id !== false)){

			// prevent assignment errors
			if (isset($b2bking_data[$requested])){
				if (!is_array($b2bking_data[$requested])){
					$b2bking_data[$requested] = array();
				}
			}
			if (isset($b2bking_data[$requested][$product_id])){
				if (!is_array($b2bking_data[$requested][$product_id])){
					$b2bking_data[$requested][$product_id] = array();
				}
			}

			$b2bking_data[$requested][$product_id][$user_id] = $value;
		}

	}


	public static function set_global_data_update(){
		global $b2bking_data;

		// if global not set, get it first
		if (!empty($b2bking_data)){

			$abort = 'no';
			// check cache is not identical to cache first retrieved 
			global $cache_first_retrieved;
			if (isset($cache_first_retrieved)){
				if (!empty($cache_first_retrieved)){
					if ($cache_first_retrieved === $b2bking_data){
						$abort = 'yes';
					}
				}
			}


			if ($abort === 'no'){
				set_transient('b2bking_global_data', $b2bking_data);
			}
		}
	}

	public static function product_has_quote_rule($product_id){
		if (is_a($product_id,'WC_Product_Variation') || is_a($product_id,'WC_Product')){
			$product_id = $product_id->get_id();
		}
		$response = b2bking()->get_applicable_rules('quotes_products', $product_id);
		$haverules = 'no';
		if ($response !== 'norules'){
			$rules = $response[0];
			if (!empty($rules)){
				$haverules = 'yes';
			}
		}

		if ($haverules === 'yes'){
			return true;
		}
		return false;
	}

	public static function is_offer_child($product_id) {
	    global $b2bking_offer_child_products;
	    
	    if (!is_array($b2bking_offer_child_products)) {
	        b2bking()->populate_offer_child_products();
	    }
	    
	    return isset($b2bking_offer_child_products[$product_id]) ? $b2bking_offer_child_products[$product_id] : false;
	}

	public static function populate_offer_child_products() {
	    global $b2bking_offer_child_products;
	    
	    if (!is_array($b2bking_offer_child_products)) {
	        $b2bking_offer_child_products = array();
	        
	        if (function_exists('WC') && WC()->cart) {
	            foreach (WC()->cart->get_cart() as $cart_item) {
	                $product_id = intval($cart_item['product_id']);
	                $variation_id = intval($cart_item['variation_id']);
	                
	                $is_offer_child = isset($cart_item['b2bking_is_offer_child']) && $cart_item['b2bking_is_offer_child'] === 'yes';
	                
	                $b2bking_offer_child_products[$product_id] = $is_offer_child;
	                if ($variation_id > 0) {
	                    $b2bking_offer_child_products[$variation_id] = $is_offer_child;
	                }
	            }
	        }
	    }
	}

	public static function user_has_p_in_cart($product_type){

		// product_type can be 'quote' or 'cart' = quote product or cart product

		$has_product = 'no';

		if (!function_exists('WC')){
			return $has_product;
		}


		if (is_object( WC()->cart )){

			foreach(WC()->cart->get_cart() as $cart_item){

				if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
					$current_product_id = $cart_item['variation_id'];
				} else {
					$current_product_id = $cart_item['product_id'];
				}

				// if product is part of an offer, skip
				if (isset($cart_item['b2bking_is_offer_child'])){
					if ($cart_item['b2bking_is_offer_child'] === 'yes'){
						continue;
					}
				}

				// if is offer in cart, should be counted as 'cart' item
				$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
				if (intval($current_product_id) === $offer_id){
					if ($product_type === 'cart'){
						$has_product = 'yes';
						break;
					} else if ($product_type === 'quote'){
						$has_product = 'no';
						break;
					}
				}

				$response = b2bking()->get_applicable_rules('quotes_products', $current_product_id);
				$haverules = 'no';
				if ($response !== 'norules'){
					$rules = $response[0];
					if (!empty($rules)){
						$haverules = 'yes';
					}
				}


				if ($product_type === 'quote'){
					if ($haverules === 'yes'){
						$has_product = 'yes';

						if (defined('MARKETKINGPRO_DIR') && defined('MARKETKINGCORE_DIR')){
							if (marketking()->is_pack_product($current_product_id)){
								$has_product = 'no';
							}
						}

						break; // don't need to search further
					}
				}
				
				if ($product_type === 'cart'){
					if ($haverules === 'no'){
						$has_product = 'yes';
						break; // don't need to search further
					}

					if (defined('MARKETKINGPRO_DIR') && defined('MARKETKINGCORE_DIR')){
						if (marketking()->is_pack_product($current_product_id)){
							$has_product = 'yes';
						}
					}
				}

			}
		}

		return $has_product;
	}


	// Function that gets which rules apply for the user /& product
	// Must be fast and efficient - used in dynamic rules
	//
	// This function sets transients, does not actually retrieve rules
	public static function get_applicable_rules($rule_type, $current_product_id = 0){

		$user_id = get_current_user_id();
		$user_id = b2bking()->get_top_parent_account($user_id);

		$currentusergroupidnr = b2bking()->get_user_group($user_id);
		if (!$currentusergroupidnr || empty($currentusergroupidnr)){
			$currentusergroupidnr = 'invalid';
		}


		if ($rule_type === 'quotes_products'){
			if (apply_filters('b2bking_product_has_quote', false, $current_product_id)){
				return array(123, 123); // force product to have quote
			} else {
				if (apply_filters('b2bking_product_does_not_have_quote', false, $current_product_id)){
					return 'norules'; // force product to not have quote
				}
			}
		}

		// 1. Get list of all fixed price rules applicable to the user
		// $user_applicable_rules = get_transient('b2bking_'.$rule_type.'_user_applicable_rules_'.$user_id);
		$user_applicable_rules = b2bking()->get_global_data('b2bking_'.$rule_type.'_user_applicable_rules',false,$user_id);

		if (!$user_applicable_rules){
			$rules_ids_elements = get_option('b2bking_have_'.$rule_type.'_rules_list_ids_elements', array());

			$user_rules = array();
			if (isset($rules_ids_elements['user_'.$user_id])){
				$user_rules = $rules_ids_elements['user_'.$user_id];
			}

			$group_rules = array();
			if (isset($rules_ids_elements['group_'.$currentusergroupidnr])){
				$group_rules = $rules_ids_elements['group_'.$currentusergroupidnr];
			}

			$user_applicable_rules = array_merge($user_rules, $group_rules);
			if (is_user_logged_in()){

				if (isset($rules_ids_elements['all_registered'])){
					// add everyone_registered rules
					$user_applicable_rules = array_merge($user_applicable_rules, $rules_ids_elements['all_registered']);
				}

				// if is user b2b add b2b rules
				if (get_user_meta($user_id,'b2bking_b2buser', true) === 'yes'){
					if (isset($rules_ids_elements['everyone_registered_b2b'])){
						$user_applicable_rules = array_merge($user_applicable_rules, $rules_ids_elements['everyone_registered_b2b']);
					}
				} else {
					// add b2c rules
					if (isset($rules_ids_elements['everyone_registered_b2c'])){
						$user_applicable_rules = array_merge($user_applicable_rules, $rules_ids_elements['everyone_registered_b2c']);
					}
				}
			}

		//	set_transient('b2bking_'.$rule_type.'_user_applicable_rules_'.$user_id,$user_applicable_rules);
			b2bking()->set_global_data('b2bking_'.$rule_type.'_user_applicable_rules',$user_applicable_rules, false, $user_id);
		}

		// if no applicable user rules, skip
		if (empty($user_applicable_rules)){
			return 'norules';
		}

		/*

		If a small number of user rules, it is fastest to check those specific rules
		But if not small, then calculating product rules makes sense, and since it is general for the product,
		it also helps things load faster for other users

		*/

		$skip_calc_rules_apply_product = 'no';
		if (count($user_applicable_rules) < apply_filters('b2bking_user_applicable_rules_threshold', 50)){
			$skip_calc_rules_apply_product = 'yes';
		}

		// 2. If not a small number of user rules, get all fixed price product rules
		//	$rules_that_apply_to_product = get_transient('b2bking_'.$rule_type.'_rules_apply_'.$current_product_id);
		$rules_that_apply_to_product = b2bking()->get_global_data('b2bking_'.$rule_type.'_rules_apply', $current_product_id);

		if (!$rules_that_apply_to_product){

			if ($skip_calc_rules_apply_product === 'no'){

				$rules_that_apply = array();
				$ruletype_rules_option = get_option('b2bking_have_'.$rule_type.'_rules_list_ids', '');
				if (!empty($ruletype_rules_option)){
					$ruletype_rules_v2_ids = explode(',',$ruletype_rules_option);
				} else {
					$ruletype_rules_v2_ids = array();
				}

				foreach ($ruletype_rules_v2_ids as $rule_id){
					$applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
					if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
						array_push($rules_that_apply, $rule_id);
					} else if ($applies === 'multiple_options'){
						$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
						$multiple_options_array = explode(',', $multiple_options);
						if (in_array('product_'.$current_product_id, $multiple_options_array)){
							array_push($rules_that_apply, $rule_id);
						} else {

							$taxonomies = b2bking()->get_taxonomies();
							foreach ($taxonomies as $tax_nickname => $tax_name){
								// try categories
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
								$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

								foreach ($current_product_belongsto_array as $item_category){
									if (in_array($item_category, $multiple_options_array)){
										array_push($rules_that_apply, $rule_id);
										break;
									}
								}
							}

							
						}
						
					} else if (explode('_', $applies)[0] === 'category'){
						// check category
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, 'product_cat' );
						$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
						if (in_array($applies, $current_product_belongsto_array)){
							array_push($rules_that_apply, $rule_id);
						}
					} else if (explode('_', $applies)[0] === 'tag'){
						// check category
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
						$current_product_belongsto_array = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
						if (in_array($applies, $current_product_belongsto_array)){
							array_push($rules_that_apply, $rule_id);
						}
					} else if ($applies === 'excluding_multiple_options'){
						// check that current product is not in list
						$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
						$multiple_options_array = explode(',', $multiple_options);

						$product_is_excluded = 'no';

						$variation_parent_id = wp_get_post_parent_id($current_product_id);
						if (in_array('product_'.$current_product_id, $multiple_options_array) || in_array('product_'.$variation_parent_id, $multiple_options_array)){
							$product_is_excluded = 'yes';
						} else {
							// try categories
							$taxonomies = b2bking()->get_taxonomies();
							foreach ($taxonomies as $tax_nickname => $tax_name){

								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
								$parent_product_categories = b2bking()->get_all_product_categories_taxonomies( $variation_parent_id, $tax_name );
								$current_product_categories = array_merge($current_product_categories, $parent_product_categories);

								$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

								foreach ($current_product_belongsto_array as $item_category){
									if (in_array($item_category, $multiple_options_array)){
										$product_is_excluded = 'yes';
										break;
									}
								}
							}
						}
						// check if product has any variations that are excluded. If yes, then exclude product too, so variations rule can apply.
						// Get children product variation IDs in an array
						$productobjj = wc_get_product($current_product_id);
						$children_ids = $productobjj->get_children();
						foreach ($children_ids as $child_id){
							if (in_array('product_'.$child_id, $multiple_options_array)){
								$product_is_excluded = 'yes';
								break;
							}
						}

						if ($product_is_excluded === 'no'){
							// product is not excluded, therefore rule applies
							array_push($rules_that_apply, $rule_id);
						}

					}
				}

				// set_transient('b2bking_'.$rule_type.'_rules_apply_'.$current_product_id,$rules_that_apply);
				b2bking()->set_global_data('b2bking_'.$rule_type.'_rules_apply', $rules_that_apply, $current_product_id);

				$rules_that_apply_to_product = $rules_that_apply;
			}
		}

		/* 3. Calculate user applicable rules, by either intersecting product/user rules, OR starting with user rules
		and checking each rule for the product */
		
		//if (!get_transient('b2bking_'.$rule_type.'_'.$current_product_id.'_'.get_current_user_id())){
		if (!b2bking()->get_global_data('b2bking_'.$rule_type, $current_product_id, get_current_user_id())){
			// if we have the info about which rules apply to the product, use it, else calculate
			if ($rules_that_apply_to_product){
				// we have the info, simply intersect product rules with user rules
				$final_rules = array_intersect($rules_that_apply_to_product, $user_applicable_rules);
			} else {
				$final_rules = array();
				// for each user rule, check which rules apply to product
				foreach ($user_applicable_rules as $rule_id){

					$applies = get_post_meta($rule_id,'b2bking_rule_applies', true);

					if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
						array_push($final_rules, $rule_id);
					} else if ($applies === 'multiple_options'){
						$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
						$multiple_options_array = explode(',', $multiple_options);
						if (in_array('product_'.$current_product_id, $multiple_options_array)){
							array_push($final_rules, $rule_id);
						} else {
							// try categories
							$taxonomies = b2bking()->get_taxonomies();
							foreach ($taxonomies as $tax_nickname => $tax_name){
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
								if (empty($current_product_categories)){
									// if no categories, this may be a variation, check parent categories
									$possible_parent_id = wp_get_post_parent_id($current_product_id);
									if ($possible_parent_id !== 0){
										// if product has parent
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, $tax_name );
									}
								}
								$current_product_belongsto_array = array_map(function($value) use ($tax_nickname){ return $tax_nickname.'_'.$value; }, $current_product_categories);

								foreach ($current_product_belongsto_array as $item_category){
									if (in_array($item_category, $multiple_options_array)){
										array_push($final_rules, $rule_id);
										break;
									}
								}
							}
						}
						
					} else if (explode('_', $applies)[0] === 'category'){
						// check category
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, 'product_cat' );
						if (empty($current_product_categories)){
							// if no categories, this may be a variation, check parent categories
							$possible_parent_id = wp_get_post_parent_id($current_product_id);
							if ($possible_parent_id !== 0){
								// if product has parent
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, 'product_cat' );
							}
						}
						$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
						if (in_array($applies, $current_product_belongsto_array)){
							array_push($final_rules, $rule_id);
						}
					} else if (explode('_', $applies)[0] === 'tag'){
						// check category
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
						if (empty($current_product_categories)){
							// if no categories, this may be a variation, check parent categories
							$possible_parent_id = wp_get_post_parent_id($current_product_id);
							if ($possible_parent_id !== 0){
								// if product has parent
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
							}
						}
						$current_product_belongsto_array = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
						if (in_array($applies, $current_product_belongsto_array)){
							array_push($final_rules, $rule_id);
						}
					} else if ($applies === 'excluding_multiple_options'){

						// check that current product is not in list
						$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
						$multiple_options_array = explode(',', $multiple_options);

						$product_is_excluded = 'no';

						$variation_parent_id = wp_get_post_parent_id($current_product_id);
						if (in_array('product_'.$current_product_id, $multiple_options_array) || in_array('product_'.$variation_parent_id, $multiple_options_array)){
							$product_is_excluded = 'yes';
						} else {
							// try categories
							$taxonomies = b2bking()->get_taxonomies();
							foreach ($taxonomies as $tax_nickname => $tax_name){
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
								$parent_product_categories = b2bking()->get_all_product_categories_taxonomies( $variation_parent_id, $tax_name );
								$current_product_categories = array_merge($current_product_categories, $parent_product_categories);

								$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

								foreach ($current_product_belongsto_array as $item_category){
									if (in_array($item_category, $multiple_options_array)){
										$product_is_excluded = 'yes';
										break;
									}
								}
							}
						}
						// check if product has any variations that are excluded. If yes, then exclude product too, so variations rule can apply.
						// Get children product variation IDs in an array
						$productobjj = wc_get_product($current_product_id);
						$children_ids = $productobjj->get_children();
						foreach ($children_ids as $child_id){
							if (in_array('product_'.$child_id, $multiple_options_array)){
								$product_is_excluded = 'yes';
								break;
							}
						}
						if ($product_is_excluded === 'no'){
							// product is not excluded, therefore rule applies
							array_push($final_rules, $rule_id);
						}
					}

				}
			}

			//set_transient('b2bking_'.$rule_type.'_'.$current_product_id.'_'.get_current_user_id(), $final_rules);
			b2bking()->set_global_data('b2bking_'.$rule_type, $final_rules, $current_product_id, get_current_user_id());
		}


		// 4. If there are no rules that apply to the product, check if this product is a variation and if 
		// there are any parent rules

		// if (!get_transient('b2bking_'.$rule_type.'_'.$current_product_id.'_'.get_current_user_id())){
		if (!b2bking()->get_global_data('b2bking_'.$rule_type, $current_product_id, get_current_user_id())){
			$post_parent_id = wp_get_post_parent_id($current_product_id);
			if ($post_parent_id !== 0){
				// check if there are parent rules
				$current_product_id = $post_parent_id;

				// based on code above
				// 1) Get all rules and check if any rules apply to the product
				// $rules_that_apply_to_product = get_transient('b2bking_'.$rule_type.'_parent_rules_apply_'.$current_product_id);
				$rules_that_apply_to_product = b2bking()->get_global_data('b2bking_'.$rule_type.'_parent_rules_apply',$current_product_id);

				if (!$rules_that_apply_to_product){

					if ($skip_calc_rules_apply_product === 'no'){

						$rules_that_apply = array();
						$ruletype_rules_option = get_option('b2bking_have_'.$rule_type.'_rules_list_ids', '');
						if (!empty($ruletype_rules_option)){
							$ruletype_rules_v2_ids = explode(',',$ruletype_rules_option);
						} else {
							$ruletype_rules_v2_ids = array();
						}

						foreach ($ruletype_rules_v2_ids as $rule_id){
							$applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
							if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
								array_push($rules_that_apply, $rule_id);
							} else if ($applies === 'multiple_options'){
								$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
								$multiple_options_array = explode(',', $multiple_options);
								if (in_array('product_'.$current_product_id, $multiple_options_array)){
									array_push($rules_that_apply, $rule_id);
								} else {
									// try categories
									$taxonomies = b2bking()->get_taxonomies();
									foreach ($taxonomies as $tax_nickname => $tax_name){
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
										if (empty($current_product_categories)){
											// if no categories, this may be a variation, check parent categories
											$possible_parent_id = wp_get_post_parent_id($current_product_id);
											if ($possible_parent_id !== 0){
												// if product has parent
												$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, $tax_name );
											}
										}
										$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

										foreach ($current_product_belongsto_array as $item_category){
											if (in_array($item_category, $multiple_options_array)){
												array_push($rules_that_apply, $rule_id);
												break;
											}
										}
									}
								}
								
							} else if (explode('_', $applies)[0] === 'category'){
								// check category
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, 'product_cat' );
								if (empty($current_product_categories)){
									// if no categories, this may be a variation, check parent categories
									$possible_parent_id = wp_get_post_parent_id($current_product_id);
									if ($possible_parent_id !== 0){
										// if product has parent
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, 'product_cat' );
									}
								}
								$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
								if (in_array($applies, $current_product_belongsto_array)){
									array_push($rules_that_apply, $rule_id);
								}
							} else if (explode('_', $applies)[0] === 'tag'){
								// check category
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
								if (empty($current_product_categories)){
									// if no categories, this may be a variation, check parent categories
									$possible_parent_id = wp_get_post_parent_id($current_product_id);
									if ($possible_parent_id !== 0){
										// if product has parent
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
									}
								}
								$current_product_belongsto_array = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
								if (in_array($applies, $current_product_belongsto_array)){
									array_push($rules_that_apply, $rule_id);
								}
							} else if ($applies === 'excluding_multiple_options'){
								// check that current product is not in list
								$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
								$multiple_options_array = explode(',', $multiple_options);

								$product_is_excluded = 'no';

								$variation_parent_id = wp_get_post_parent_id($current_product_id);
								if (in_array('product_'.$current_product_id, $multiple_options_array) || in_array('product_'.$variation_parent_id, $multiple_options_array)){
									$product_is_excluded = 'yes';
								} else {
									// try categories
									$taxonomies = b2bking()->get_taxonomies();
									foreach ($taxonomies as $tax_nickname => $tax_name){
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
										$parent_product_categories = b2bking()->get_all_product_categories_taxonomies( $variation_parent_id, $tax_name );
										$current_product_categories = array_merge($current_product_categories, $parent_product_categories);

										$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

										foreach ($current_product_belongsto_array as $item_category){
											if (in_array($item_category, $multiple_options_array)){
												$product_is_excluded = 'yes';
												break;
											}
										}
									}
								}
								// check if product has any variations that are excluded. If yes, then exclude product too, so variations rule can apply.
								// Get children product variation IDs in an array
								$productobjj = wc_get_product($current_product_id);
								$children_ids = $productobjj->get_children();
								foreach ($children_ids as $child_id){
									if (in_array('product_'.$child_id, $multiple_options_array)){
										$product_is_excluded = 'yes';
										break;
									}
								}
								if ($product_is_excluded === 'no'){
									// product is not excluded, therefore rule applies
									array_push($rules_that_apply, $rule_id);
								}
							}
						}

						//set_transient('b2bking_'.$rule_type.'_parent_rules_apply_'.$current_product_id,$rules_that_apply);
						b2bking()->set_global_data('b2bking_'.$rule_type.'_parent_rules_apply',$rules_that_apply, $current_product_id);
						$rules_that_apply_to_product = $rules_that_apply;
					}
				}

				// if transient does not already exist
				//if (!get_transient('b2bking_'.$rule_type.'_parent_'.$current_product_id.'_'.get_current_user_id())){
				if (!b2bking()->get_global_data('b2bking_'.$rule_type.'_parent', $current_product_id, get_current_user_id())){
					// if we have the info about which rules apply to the product, use it, else calculate
					if ($rules_that_apply_to_product){
						// we have the info, simply intersect product rules with user rules
						$final_rules = array_intersect($rules_that_apply_to_product, $user_applicable_rules);
					} else {
						$final_rules = array();
						// for each user rule, check which rules apply to product
						foreach ($user_applicable_rules as $rule_id){
							$applies = get_post_meta($rule_id,'b2bking_rule_applies', true);
							if ($applies === 'cart_total' || $applies === 'product_'.$current_product_id){
								array_push($final_rules, $rule_id);
							} else if ($applies === 'multiple_options'){
								$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
								$multiple_options_array = explode(',', $multiple_options);
								if (in_array('product_'.$current_product_id, $multiple_options_array)){
									array_push($final_rules, $rule_id);
								} else {
									// try categories
									$taxonomies = b2bking()->get_taxonomies();
									foreach ($taxonomies as $tax_nickname => $tax_name){
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
										if (empty($current_product_categories)){
											// if no categories, this may be a variation, check parent categories
											$possible_parent_id = wp_get_post_parent_id($current_product_id);
											if ($possible_parent_id !== 0){
												// if product has parent
												$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, $tax_name );
											}
										}
										$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

										foreach ($current_product_belongsto_array as $item_category){
											if (in_array($item_category, $multiple_options_array)){
												array_push($final_rules, $rule_id);
												break;
											}
										}
									}

								}
								
							} else if (explode('_', $applies)[0] === 'category'){
								// check category
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, 'product_cat' );
								if (empty($current_product_categories)){
									// if no categories, this may be a variation, check parent categories
									$possible_parent_id = wp_get_post_parent_id($current_product_id);
									if ($possible_parent_id !== 0){
										// if product has parent
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, 'product_cat' );
									}
								}
								$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
								if (in_array($applies, $current_product_belongsto_array)){
									array_push($final_rules, $rule_id);
								}
							} else if (explode('_', $applies)[0] === 'tag'){
								// check category
								$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
								if (empty($current_product_categories)){
									// if no categories, this may be a variation, check parent categories
									$possible_parent_id = wp_get_post_parent_id($current_product_id);
									if ($possible_parent_id !== 0){
										// if product has parent
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
									}
								}
								$current_product_belongsto_array = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
								if (in_array($applies, $current_product_belongsto_array)){
									array_push($final_rules, $rule_id);
								}
							} else if ($applies === 'excluding_multiple_options'){
								// check that current product is not in list
								$multiple_options = get_post_meta($rule_id,'b2bking_rule_applies_multiple_options', true);
								$multiple_options_array = explode(',', $multiple_options);

								$product_is_excluded = 'no';

								$variation_parent_id = wp_get_post_parent_id($current_product_id);
								if (in_array('product_'.$current_product_id, $multiple_options_array) || in_array('product_'.$variation_parent_id, $multiple_options_array)){
									$product_is_excluded = 'yes';
								} else {
									// try categories
									$taxonomies = b2bking()->get_taxonomies();
									foreach ($taxonomies as $tax_nickname => $tax_name){
										$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, $tax_name );
										$parent_product_categories = b2bking()->get_all_product_categories_taxonomies( $variation_parent_id, $tax_name );
										$current_product_categories = array_merge($current_product_categories, $parent_product_categories);

										$current_product_belongsto_array = array_map(function($value) use ($tax_nickname) { return $tax_nickname.'_'.$value; }, $current_product_categories);

										foreach ($current_product_belongsto_array as $item_category){
											if (in_array($item_category, $multiple_options_array)){
												$product_is_excluded = 'yes';
												break;
											}
										}
									}
								}
								// check if product has any variations that are excluded. If yes, then exclude product too, so variations rule can apply.
								// Get children product variation IDs in an array
								$productobjj = wc_get_product($current_product_id);
								$children_ids = $productobjj->get_children();
								foreach ($children_ids as $child_id){
									if (in_array('product_'.$child_id, $multiple_options_array)){
										$product_is_excluded = 'yes';
										break;
									}
								}
								if ($product_is_excluded === 'no'){
									// product is not excluded, therefore rule applies
									array_push($final_rules, $rule_id);
								}
							}

						}
					}
				//	set_transient('b2bking_'.$rule_type.'_parent_'.$current_product_id.'_'.get_current_user_id(), $final_rules);
					b2bking()->set_global_data('b2bking_'.$rule_type.'_parent',$final_rules, $current_product_id, get_current_user_id());
				}

			}	
		}
		
		//$ruletype_rules = get_transient('b2bking_'.$rule_type.'_'.$current_product_id.'_'.get_current_user_id());
		$ruletype_rules = b2bking()->get_global_data('b2bking_'.$rule_type, $current_product_id, get_current_user_id());
		//$ruletype_parent_rules = get_transient('b2bking_'.$rule_type.'_parent_'.$current_product_id.'_'.get_current_user_id());
		$ruletype_parent_rules = b2bking()->get_global_data('b2bking_'.$rule_type.'_parent', $current_product_id, get_current_user_id());

		if (empty($ruletype_rules)){

			$ruletype_rules = $ruletype_parent_rules;
			$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $post_parent_id, 'product_cat' );
			if (empty($current_product_categories)){
				// if no categories, this may be a variation, check parent categories
				$possible_parent_id = wp_get_post_parent_id($current_product_id);
				if ($possible_parent_id !== 0){
					// if product has parent
					$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, 'product_cat' );
				}
			}
			$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
			// add the product to the array to search for all relevant rules
			array_push($current_product_belongsto_array, 'product_'.$post_parent_id);

			// new: add tags support
			if (apply_filters('b2bking_dynamic_rules_show_tags', true)){
				$ruletype_rules = $ruletype_parent_rules;
				$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $post_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
				if (empty($current_product_categories)){
					// if no categories, this may be a variation, check parent categories
					$possible_parent_id = wp_get_post_parent_id($current_product_id);
					if ($possible_parent_id !== 0){
						// if product has parent
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
					}
				}
				$current_product_belongsto_array_tags = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
				// merge old and new
				$current_product_belongsto_array = array_merge($current_product_belongsto_array, $current_product_belongsto_array_tags);
			}

		} else {
			$ruletype_rules = $ruletype_rules;
			$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, 'product_cat' );
			if (empty($current_product_categories)){
				// if no categories, this may be a variation, check parent categories
				$possible_parent_id = wp_get_post_parent_id($current_product_id);
				if ($possible_parent_id !== 0){
					// if product has parent
					$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, 'product_cat' );
				}
			}
			$current_product_belongsto_array = array_map(function($value) { return 'category_'.$value; }, $current_product_categories);
			// add the product to the array to search for all relevant rules
			array_push($current_product_belongsto_array, 'product_'.$current_product_id);

			// new: add tags support
			if (apply_filters('b2bking_dynamic_rules_show_tags', true)){
				$ruletype_rules = $ruletype_rules;
				$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $current_product_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
				if (empty($current_product_categories)){
					// if no categories, this may be a variation, check parent categories
					$possible_parent_id = wp_get_post_parent_id($current_product_id);
					if ($possible_parent_id !== 0){
						// if product has parent
						$current_product_categories = b2bking()->get_all_product_categories_taxonomies( $possible_parent_id, apply_filters('b2bking_dynamic_rules_taxonomy_option', 'product_tag') );
					}
				}
				$current_product_belongsto_array_tags = array_map(function($value) { return 'tag_'.$value; }, $current_product_categories);
				// merge old and new
				$current_product_belongsto_array = array_merge($current_product_belongsto_array, $current_product_belongsto_array_tags);
			}
		}

		if (empty($ruletype_rules)){
			$ruletype_rules = array();
		}

		return apply_filters('b2bking_applicable_rules_products', array($ruletype_rules, $current_product_belongsto_array), $rule_type, $current_product_id, get_current_user_id(), $current_product_belongsto_array);	



	}

}