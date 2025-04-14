<?php

use MessagingAPI_WC\Migrations\MigrateSendSMSPlugin;
use MessagingAPI_WC\Migrations\MigrateWoocommercePlugin;

class Messagingsms_WooCommerce_Setting implements Messagingsms_Register_Interface {

	private $settings_api;
    private $log;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
        $this->log = new Messagingsms_WooCoommerce_Logger();
	}

	public function register() {
        // if ( class_exists( 'woocommerce' ) ) {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'messagingsms_setting_fields_custom_html', array( $this, 'messagingsms_wc_not_activated' ), 10, 1 );
            add_filter( 'messagingsms_setting_fields', array( $this, 'add_custom_order_status' ) );

        // } else {
        //     add_action( 'admin_menu', array( $this, 'woocommerce_not_activated_menu_view' ) );
        // }
	}

	function admin_init() {

		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );

		//initialize settings
		$this->settings_api->admin_init();
	}

	function admin_menu() {
		add_options_page( ZMPLGXX_NAME, ZMPLGXX_NAME, 'manage_options', 'messagingsms-woocoommerce-setting',
            array($this, 'plugin_page')
        );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'messagingsms_setting',
				'title' => __( 'API Settings', MESSAGINGSMS_TEXT_DOMAIN )
			),
			array(
				'id'    => 'messagingsms_admin_setting',
				'title' => __( 'Admin Settings', MESSAGINGSMS_TEXT_DOMAIN ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			),
			array(
                'id'    => 'messagingsms_customer_setting',
				'title' => __( 'Customer Settings', MESSAGINGSMS_TEXT_DOMAIN ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			)
		);

		$sections = apply_filters( 'messagingsms_setting_section', $sections );

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	function get_settings_fields() {
		//WooCommerce Country
		global $woocommerce;
        // $countries_obj = $this->get_countries();
    	// $countries_obj   = new WC_Countries();
		// $countries   = $countries_obj->__get('countries');
        $countries =  $this->get_countries();

		$additional_billing_fields       = '';
		$additional_billing_fields_desc  = '';
		$additional_billing_fields_array = $this->get_additional_billing_fields();
		foreach ( $additional_billing_fields_array as $field ) {
			$additional_billing_fields .= ', [' . $field . ']';
		}
		if ( $additional_billing_fields ) {
			$additional_billing_fields_desc = '<br />Custom tags: ' . substr( $additional_billing_fields, 2 );
		}

		$settings_fields = array(
			'messagingsms_setting' => array(
                array(
                    'name'  => 'messagingsms_woocommerce_api_key',
                    'label' => __('API Key', MESSAGINGSMS_TEXT_DOMAIN),
                    'desc'  => __('Your ' . ZMPLGXX_SITENAME . ' API key (<a href="' . ZMPLGXX_URL . '" target="blank">Create API Key</a>).', MESSAGINGSMS_TEXT_DOMAIN),
                    'type'  => 'text',
                ),
                
				
                
                
                
                array(
                    'name'  => 'messagingsms_woocommerce_dateformat',
                    'label' => __('Date Format', MESSAGINGSMS_TEXT_DOMAIN),
                    'desc'  => 'Date format for the shortcodes. Please use this format guide: <a href="https://www.w3schools.com/php/func_date_date.asp" target="_blank">PHP Date Format</a>.',
                    'type'  => 'text',
                ),
                array(
                    'name'          => 'messagingsms_woocommerce_country_code',
                    'label'         => __('Default Country', MESSAGINGSMS_TEXT_DOMAIN),
                    'class'         => array('chzn-drop'),
                    'desc'          => 'Selected country will be use as default country info for mobile numbers when country info is not provided by the user.',
                    'type'          => 'select',
                    'options'       => $countries
                ),
                array(
                    'name'  => 'export_messagingsms_log',
                    'label' => 'Export Log',
                    'desc'  => '<a href="' . admin_url( 'admin.php?page=messagingsms-download-file&file=' . ZMPLGXX_NAME ) . '" class="button button-secondary">Export</a><div id="messaging_sms[keyword-modal]" class="modal"></div>',
                    'type'  => 'html'
                )
			),
			'messagingsms_admin_setting'     => array(
				array(
					'name'    => 'messagingsms_woocommerce_admin_suborders_send_sms',
					'label'   => __( 'Enable Suborders Whatsapp Notifications', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => ' ' . __( 'Enable', MESSAGINGSMS_TEXT_DOMAIN ),
					'type'    => 'checkbox',
					'default' => 'off'
				),
				array(
					'name'    => 'messagingsms_woocommerce_admin_send_sms_on',
					'label'   => __( '	Send notification on', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => __( 'Choose when to send a status notification message to your admin <br> Set <strong>low stock threshold</strong> for each product under <strong>WooCommerce Product -> Product Data -> Inventory -> Low Stock Threshold</strong>', MESSAGINGSMS_TEXT_DOMAIN ),
					'type'    => 'multicheck',
					'default' => array(
						'on-hold'    => 'on-hold',
						'processing' => 'processing'
					),
					'options' => array(
						'pending'           => ' Pending',
						'on-hold'           => ' On-hold',
						'processing'        => ' Processing',
						'completed'         => ' Completed',
						'cancelled'         => ' Cancelled',
						'refunded'          => ' Refunded',
						'failed'            => ' Failed',
						'low_stock_product' => ' Low stock product ',
					)
				),
				array(
					'name'  => 'messagingsms_woocommerce_admin_sms_recipients',
					'label' => __( 'Mobile Number', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'  => __( 'Mobile number to receive new order Whatsapp notification. To send to multiple receivers, separate each entry with comma such as 0123456789, 0167888945', MESSAGINGSMS_TEXT_DOMAIN ),
					'type'  => 'text',
				),
				array(
					'name'    => 'messagingsms_woocommerce_admin_sms_template',
					'label'   => __( 'Admin Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="admin" data-attr-target="messagingsms_admin_setting[messagingsms_woocommerce_admin_sms_template]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : You have a new order with order ID [order_id] and order amount [order_currency] [order_amount]. The order is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
                ),
				array(
					'name'    => 'messagingsms_woocommerce_admin_sms_template_low_stock_product',
					'label'   => __( 'Low Stock Product Admin Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords-low-product-stock]" data-attr-type="admin" data-attr-target="messagingsms_admin_setting[messagingsms_woocommerce_admin_sms_template_low_stock_product]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Your product [product_name] has low stock. Current quantity: [product_stock_quantity]. Please restock soon.', MESSAGINGSMS_TEXT_DOMAIN )
                ),
			),
			'messagingsms_customer_setting'  => array(
				array(
					'name'    => 'messagingsms_woocommerce_suborders_send_sms',
					'label'   => __( 'Enable Suborders Whatsapp Notifications', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => ' ' . __( 'Enable', MESSAGINGSMS_TEXT_DOMAIN ),
					'type'    => 'checkbox',
					'default' => 'off'
				),
				array(
					'name'    => 'messagingsms_woocommerce_send_sms',
					'label'   => __( '	Send notification on', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => __( 'Choose when to send a status notification message to your customer', MESSAGINGSMS_TEXT_DOMAIN ),
					'type'    => 'multicheck',
                    'default' => array(
						'on-hold'    => 'on-hold',
						'processing' => 'processing',
						'completed'  => 'completed',
					),
					'options' => array(
						'pending'    => ' Pending',
						'on-hold'    => ' On-hold',
						'processing' => ' Processing',
						'completed'  => ' Completed',
						'cancelled'  => ' Cancelled',
						'refunded'   => ' Refunded',
						'failed'     => ' Failed'
					)
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_default',
					'label'   => __( 'Default Customer Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="default" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_default]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_pending',
					'label'   => __( 'Pending Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="pending" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_pending]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_on-hold',
					'label'   => __( 'On-hold Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="on_hold" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_on-hold]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_processing',
					'label'   => __( 'Processing Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="processing" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_processing]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_completed',
					'label'   => __( 'Completed Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="completed" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_completed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_cancelled',
					'label'   => __( 'Cancelled Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="cancelled" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_cancelled]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_refunded',
					'label'   => __( 'Refunded Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="refunded" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_refunded]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'messagingsms_woocommerce_sms_template_failed',
					'label'   => __( 'Failed Whatsapp Message', MESSAGINGSMS_TEXT_DOMAIN ),
					'desc'    => 'Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="failed" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_failed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', MESSAGINGSMS_TEXT_DOMAIN )
				)
			)
		);

        if(!class_exists('woocommerce')) {
            unset($settings_fields['messagingsms_admin_setting']);
            unset($settings_fields['messagingsms_customer_setting']);
        }

		$settings_fields = apply_filters( 'messagingsms_setting_fields', $settings_fields );

		return $settings_fields;
	}

    public function add_custom_order_status($setting_fields)
    {
        $log = new Messagingsms_WooCoommerce_Logger();
        // $log->add(ZMPLGXX_NAME, print_r($custom_wc_statuses, 1));
        $default_statuses = [
            'wc-pending',
            'wc-processing',
            'wc-on-hold',
            'wc-completed',
            'wc-cancelled',
            'wc-refunded',
            'wc-failed',
            'wc-checkout-draft'
        ];

        $fields_to_iterate = ['messagingsms_admin_setting', 'messagingsms_customer_setting', 'messagingsms_multivendor_setting'];

        $all_wc_statuses = function_exists("wc_get_order_statuses") ? wc_get_order_statuses() : [];

        $custom_wc_statuses = array_diff_key($all_wc_statuses, array_flip($default_statuses));

        $processed_wc_statuses = [];

        foreach($custom_wc_statuses as $key => $value) {
            $trimmed_key = ltrim($key, 'wc-');
            $processed_wc_statuses[$trimmed_key] = $value;
        }

        foreach($fields_to_iterate as $field) {
            if(array_key_exists($field, $setting_fields)) {
                for( $i=0; $i<count($setting_fields[$field]); $i++ ) {
                    if(array_key_exists('options', $setting_fields[$field][$i])) {
                        foreach($processed_wc_statuses as $processed_key => $processed_value) {
                            if( ! array_key_exists($processed_key, $setting_fields[$field][$i]['options']) ) {
                                $setting_fields[$field][$i]['options'][$processed_key] = " {$processed_value}";
                                if($field == 'messagingsms_customer_setting') {
                                    $setting_fields[$field][] = array(
                                        'name'    => "messagingsms_woocommerce_sms_template_{$processed_key}",
                                        'label'   => __( "{$processed_value} Customer Whatsapp Message", MESSAGINGSMS_TEXT_DOMAIN ),
                                        'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messaging_sms[open-keywords]" data-attr-type="default" data-attr-target="messagingsms_customer_setting[messagingsms_woocommerce_sms_template_%s]" class="button button-secondary">Keywords</button>', $processed_key),
                                        'type'    => 'textarea',
                                        'rows'    => '8',
                                        'cols'    => '500',
                                        'css'     => 'min-width:350px;',
                                        'default' => __( "Your {$processed_value} Whatsapp template", MESSAGINGSMS_TEXT_DOMAIN )
                                    );
                                }
                            }
                        }
                        break;
                    }
                }

                continue;
            }
        }

        return $setting_fields;
    }

	function plugin_page() {

		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();
		echo '<input type="hidden" value="' . join(",", $this->get_additional_billing_fields()) . '" id="messagingsms_new_billing_field" />';

		echo '</div>';
	}

	/**
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
	function get_pages() {
		$pages         = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[ $page->ID ] = $page->post_title;
			}
		}

		return $pages_options;
	}

	function get_additional_billing_fields() {
		$default_billing_fields   = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_country',
			'billing_postcode',
			'billing_phone',
			'billing_email'
		);
		$additional_billing_field = array();
		$billing_fields           = array_filter( get_option( 'wc_fields_billing', array() ) );
		foreach ( $billing_fields as $field_key => $field_info ) {
			if ( ! in_array( $field_key, $default_billing_fields ) && $field_info['enabled'] ) {
				array_push( $additional_billing_field, $field_key );
			}
		}

		return $additional_billing_field;
	}

    public function messagingsms_wc_not_activated($form_id)
    {
        if(class_exists('woocommerce')) { return; }
        if(!($form_id === 'messagingsms_admin_setting' || $form_id === 'messagingsms_customer_setting')) { return; }
        ?>
        <div class="wrap">
            <h1>MessagingAPI Woocommerce Order Notification</h1>
            <p>This feature requires WooCommerce to be activated</p>
        </div>
        <?php
    }

    public function get_countries()
    {
        return array(
            "AF" => "Afghanistan",
            "AL" => "Albania",
            "DZ" => "Algeria",
            "AS" => "American Samoa",
            "AD" => "Andorra",
            "AO" => "Angola",
            "AI" => "Anguilla",
            "AQ" => "Antarctica",
            "AG" => "Antigua and Barbuda",
            "AR" => "Argentina",
            "AM" => "Armenia",
            "AW" => "Aruba",
            "AU" => "Australia",
            "AT" => "Austria",
            "AZ" => "Azerbaijan",
            "BS" => "Bahamas",
            "BH" => "Bahrain",
            "BD" => "Bangladesh",
            "BB" => "Barbados",
            "BY" => "Belarus",
            "BE" => "Belgium",
            "BZ" => "Belize",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BT" => "Bhutan",
            "BO" => "Bolivia",
            "BA" => "Bosnia and Herzegovina",
            "BW" => "Botswana",
            "BV" => "Bouvet Island",
            "BR" => "Brazil",
            "IO" => "British Indian Ocean Territory",
            "BN" => "Brunei Darussalam",
            "BG" => "Bulgaria",
            "BF" => "Burkina Faso",
            "BI" => "Burundi",
            "KH" => "Cambodia",
            "CM" => "Cameroon",
            "CA" => "Canada",
            "CV" => "Cape Verde",
            "KY" => "Cayman Islands",
            "CF" => "Central African Republic",
            "TD" => "Chad",
            "CL" => "Chile",
            "CN" => "China",
            "CX" => "Christmas Island",
            "CC" => "Cocos (Keeling) Islands",
            "CO" => "Colombia",
            "KM" => "Comoros",
            "CG" => "Congo",
            "CD" => "Congo, the Democratic Republic of the",
            "CK" => "Cook Islands",
            "CR" => "Costa Rica",
            "CI" => "Cote D'Ivoire",
            "HR" => "Croatia",
            "CU" => "Cuba",
            "CY" => "Cyprus",
            "CZ" => "Czech Republic",
            "DK" => "Denmark",
            "DJ" => "Djibouti",
            "DM" => "Dominica",
            "DO" => "Dominican Republic",
            "EC" => "Ecuador",
            "EG" => "Egypt",
            "SV" => "El Salvador",
            "GQ" => "Equatorial Guinea",
            "ER" => "Eritrea",
            "EE" => "Estonia",
            "ET" => "Ethiopia",
            "FK" => "Falkland Islands (Malvinas)",
            "FO" => "Faroe Islands",
            "FJ" => "Fiji",
            "FI" => "Finland",
            "FR" => "France",
            "GF" => "French Guiana",
            "PF" => "French Polynesia",
            "TF" => "French Southern Territories",
            "GA" => "Gabon",
            "GM" => "Gambia",
            "GE" => "Georgia",
            "DE" => "Germany",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GR" => "Greece",
            "GL" => "Greenland",
            "GD" => "Grenada",
            "GP" => "Guadeloupe",
            "GU" => "Guam",
            "GT" => "Guatemala",
            "GN" => "Guinea",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HT" => "Haiti",
            "HM" => "Heard Island and Mcdonald Islands",
            "VA" => "Holy See (Vatican City State)",
            "HN" => "Honduras",
            "HK" => "Hong Kong",
            "HU" => "Hungary",
            "IS" => "Iceland",
            "IN" => "India",
            "ID" => "Indonesia",
            "IR" => "Iran, Islamic Republic of",
            "IQ" => "Iraq",
            "IE" => "Ireland",
            "IL" => "Israel",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JP" => "Japan",
            "JO" => "Jordan",
            "KZ" => "Kazakhstan",
            "KE" => "Kenya",
            "KI" => "Kiribati",
            "KP" => "Korea, Democratic People's Republic of",
            "KR" => "Korea, Republic of",
            "KW" => "Kuwait",
            "KG" => "Kyrgyzstan",
            "LA" => "Lao People's Democratic Republic",
            "LV" => "Latvia",
            "LB" => "Lebanon",
            "LS" => "Lesotho",
            "LR" => "Liberia",
            "LY" => "Libyan Arab Jamahiriya",
            "LI" => "Liechtenstein",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "MO" => "Macao",
            "MK" => "Macedonia, the Former Yugoslav Republic of",
            "MG" => "Madagascar",
            "MW" => "Malawi",
            "MY" => "Malaysia",
            "MV" => "Maldives",
            "ML" => "Mali",
            "MT" => "Malta",
            "MH" => "Marshall Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MU" => "Mauritius",
            "YT" => "Mayotte",
            "MX" => "Mexico",
            "FM" => "Micronesia, Federated States of",
            "MD" => "Moldova, Republic of",
            "MC" => "Monaco",
            "MN" => "Mongolia",
            "MS" => "Montserrat",
            "MA" => "Morocco",
            "MZ" => "Mozambique",
            "MM" => "Myanmar",
            "NA" => "Namibia",
            "NR" => "Nauru",
            "NP" => "Nepal",
            "NL" => "Netherlands",
            "AN" => "Netherlands Antilles",
            "NC" => "New Caledonia",
            "NZ" => "New Zealand",
            "NI" => "Nicaragua",
            "NE" => "Niger",
            "NG" => "Nigeria",
            "NU" => "Niue",
            "NF" => "Norfolk Island",
            "MP" => "Northern Mariana Islands",
            "NO" => "Norway",
            "OM" => "Oman",
            "PK" => "Pakistan",
            "PW" => "Palau",
            "PS" => "Palestinian Territory, Occupied",
            "PA" => "Panama",
            "PG" => "Papua New Guinea",
            "PY" => "Paraguay",
            "PE" => "Peru",
            "PH" => "Philippines",
            "PN" => "Pitcairn",
            "PL" => "Poland",
            "PT" => "Portugal",
            "PR" => "Puerto Rico",
            "QA" => "Qatar",
            "RE" => "Reunion",
            "RO" => "Romania",
            "RU" => "Russian Federation",
            "RW" => "Rwanda",
            "SH" => "Saint Helena",
            "KN" => "Saint Kitts and Nevis",
            "LC" => "Saint Lucia",
            "PM" => "Saint Pierre and Miquelon",
            "VC" => "Saint Vincent and the Grenadines",
            "WS" => "Samoa",
            "SM" => "San Marino",
            "ST" => "Sao Tome and Principe",
            "SA" => "Saudi Arabia",
            "SN" => "Senegal",
            "CS" => "Serbia and Montenegro",
            "SC" => "Seychelles",
            "SL" => "Sierra Leone",
            "SG" => "Singapore",
            "SK" => "Slovakia",
            "SI" => "Slovenia",
            "SB" => "Solomon Islands",
            "SO" => "Somalia",
            "ZA" => "South Africa",
            "GS" => "South Georgia and the South Sandwich Islands",
            "ES" => "Spain",
            "LK" => "Sri Lanka",
            "SD" => "Sudan",
            "SR" => "Suriname",
            "SJ" => "Svalbard and Jan Mayen",
            "SZ" => "Swaziland",
            "SE" => "Sweden",
            "CH" => "Switzerland",
            "SY" => "Syrian Arab Republic",
            "TW" => "Taiwan, Province of China",
            "TJ" => "Tajikistan",
            "TZ" => "Tanzania, United Republic of",
            "TH" => "Thailand",
            "TL" => "Timor-Leste",
            "TG" => "Togo",
            "TK" => "Tokelau",
            "TO" => "Tonga",
            "TT" => "Trinidad and Tobago",
            "TN" => "Tunisia",
            "TR" => "Turkey",
            "TM" => "Turkmenistan",
            "TC" => "Turks and Caicos Islands",
            "TV" => "Tuvalu",
            "UG" => "Uganda",
            "UA" => "Ukraine",
            "AE" => "United Arab Emirates",
            "GB" => "United Kingdom",
            "US" => "United States",
            "UM" => "United States Minor Outlying Islands",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VU" => "Vanuatu",
            "VE" => "Venezuela",
            "VN" => "Viet Nam",
            "VG" => "Virgin Islands, British",
            "VI" => "Virgin Islands, U.s.",
            "WF" => "Wallis and Futuna",
            "EH" => "Western Sahara",
            "YE" => "Yemen",
            "ZM" => "Zambia",
            "ZW" => "Zimbabwe"
        );
    }
}

?>
