<?php

class MessagingSMS_SendSMS_View implements Messagingsms_Register_Interface {

	private $settings_api;
    private $log;

	function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'messagingsms_setting_section',     array($this, 'set_sendsms_setting_section' ) );
		add_filter( 'messagingsms_setting_fields',      array($this, 'set_sendsms_setting_field' ) );
		add_action( 'messagingsms_load_javascripts',    array($this, 'load_scripts' ) );
        add_action( 'register_form',                 array($this, 'mapi_display_phone_field'));
        add_action( 'register_form',                 array($this, 'mapi_display_country_field'));
        add_action( 'register_post',                 array($this, 'mapi_validate_fields'),10,3);
        add_action( 'user_register',                 array($this, 'mapi_register_additional_fields'));
        add_action( 'show_user_profile',             array($this, 'mapi_show_additional_profile_fields') );
        add_action( 'edit_user_profile',             array($this, 'mapi_show_additional_profile_fields') );
        add_action( 'personal_options_update',       array($this, 'mapi_save_additional_profile_fields') );
        add_action( 'edit_user_profile_update',      array($this, 'mapi_save_additional_profile_fields') );
        add_action( 'user_profile_update_errors',    array($this, 'validate_additional_fields'), 10, 3 );
		add_action( 'admin_post_messagingsms_sms_form', array($this, 'mapi_send_sms' ) );
        add_action( 'admin_notices',                 array($this, 'display_send_sms_success') );
        add_filter( 'removable_query_args',          array($this, 'add_removable_arg') );
	}

    public function mapi_send_sms()
    {
        $message_to='';$message='';$users='';$recipients='';$country='';$roles=[];$criteria=[];$filters=[];
        $post_data = $_POST['messagingsms_sendsms_setting'];
        if(isset($post_data['messagingsms_sendsms_message_to']))
            $message_to = sanitize_text_field($post_data['messagingsms_sendsms_message_to']);
            //$message_to = esc_attr($message_to);
        if(isset($post_data['messagingsms_sendsms_message']))
            $message = sanitize_text_field($post_data['messagingsms_sendsms_message']);
            //$message = esc_textarea($message);
        if(isset($post_data['messagingsms_sendsms_users'])){
            $users = array();
            foreach ($post_data['messagingsms_sendsms_users'] as $value) {
                $users[] = sanitize_text_field($value);
            }
        }
            //$users = array_map( 'esc_attr', $users );
        if(isset($post_data['messagingsms_sendsms_recipients']))
            $recipients = sanitize_text_field($post_data['messagingsms_sendsms_recipients']);
            //$recipients = esc_textarea($recipients);
        // if(isset($post_data['messagingsms_sendsms_country']))
        //     $country = sanitize_text_field($post_data['messagingsms_sendsms_country']);

        if(isset($post_data['messagingsms_sendsms_filters']))
            $filters = sanitize_text_field($post_data['messagingsms_sendsms_filters']);
        if(isset($post_data['messagingsms_sendsms_criteria'])) {
            $criteria = sanitize_text_field($post_data['messagingsms_sendsms_criteria']);
        }

        $numbers = MessagingSMS_SendSMS_Sms::getPhoneNumber($message_to, $users, $recipients, $country, $filters, $criteria);
        // write_log('numbers :' . json_encode($numbers));

        $medium = 'wp_wordpress';

        if($numbers){
            if(is_array($numbers)){
                foreach($numbers as $number){
                    if($number instanceof WP_User) {
                        $user = $number;
                        $send_sms = MessagingSMS_SendSMS_Sms::send_sms($user->phone, $message, $medium);
                    }
                    else {
                        $send_sms = MessagingSMS_SendSMS_Sms::send_sms($number, $message, $medium);
                    }
                }
            }else{
                if($numbers instanceof WP_User) {
                    $user = $numbers;
                    $send_sms = MessagingSMS_SendSMS_Sms::send_sms($user->phone, $message, $medium);
                }
                else {
                    $send_sms = MessagingSMS_SendSMS_Sms::send_sms($numbers, $message, $medium);
                }
            }
        }
        wp_redirect(admin_url('options-general.php?page=messagingsms-woocoommerce-setting&sms_sent='.$send_sms)); exit;
    }

	public function set_sendsms_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'messagingsms_sendsms_setting',
            'title'            => __( 'Send Message', MESSAGINGSMS_TEXT_DOMAIN ),
            'submit_button'    => get_submit_button('Send Message', 'primary large', 'sendMessage', true ,array('id' => 'sendMessage')),
            'action'           => 'messagingsms_sms_form',
            'action_url'       => admin_url('admin-post.php'),
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_sendsms_setting_field( $setting_fields ) {

        $users = get_users();
        $filtered_user = array();

        foreach($users as $user) {
            $filtered_user[$user->ID] = $user->user_login;
        }

		$setting_fields['messagingsms_sendsms_setting'] = array(
			array(
				'name'    => 'messagingsms_sendsms_message_to',
				'label'   => __( 'To', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => 'Select the recipients you wish to broadcast your message',
				'type'    => 'select',
				'default' => 'customer_all',
				'options' => array(
					'customer_all'     => 'All users',
					'customer'         => 'Specific users',
					'phones'           => 'Specific phone number',
					'spec_group_ppl'   => 'Specific Group of People',
				)
			),
			array(
				'name'    => 'messagingsms_sendsms_users',
				'label'   => __( 'Users', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => 'Note: Please ensure <b>Mobile Number</b> field at <b>Additional profile information</b> is not empty for selected users.<br />',
				'type'    => 'selectm',
				'default' => 'auto',
				'options' => $filtered_user
			),

			array(
				'name'    => 'messagingsms_sendsms_recipients',
				'label'   => __( 'Recipients', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => '(Please insert country code along with mobile numbers,<br>e.g. 60124512978,60198745123,60161237841)',
				'type'    => 'textarea',
				'rows'    => '8',
				'cols'    => '500',
				'css'     => 'min-width:350px',
			),
            array(
				'name'    => 'messagingsms_sendsms_filters',
				'label'   => __( 'Filter By', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => 'Select the recipients you wish to filter by<br />',
				'type'    => 'select',
				'default' => '-1',
				'options' => array(
                    '-1'          => "Select Filter",
                    'roles'       => "roles",
                    'country'     => "country",
                )
			),
            array(
				'name'    => 'messagingsms_sendsms_criteria',
				'label'   => __( 'Criteria', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => 'Select the criteria you wish to filter by<br />',
				'type'    => 'select',
                // 'css'     => 'min-width:350px;',
				'default' => '-1',
				'options' => array(),

			),
			array(
				'name'    => 'messagingsms_sendsms_message',
				'label'   => __( 'Message', MESSAGINGSMS_TEXT_DOMAIN ),
				'desc'    => 'Compose your message here',
				'type'    => 'textarea',
				'rows'    => '8',
				'cols'    => '500',
				'css'     => 'min-width:350px;',
            ),

		);

		return $setting_fields;
	}

    public function mapi_getCountryList(){
        $countries = array();
        $countries[] = array("code"=>"AF","name"=>"Afghanistan","d_code"=>"+93");
        $countries[] = array("code"=>"AL","name"=>"Albania","d_code"=>"+355");
        $countries[] = array("code"=>"DZ","name"=>"Algeria","d_code"=>"+213");
        $countries[] = array("code"=>"AS","name"=>"American Samoa","d_code"=>"+1");
        $countries[] = array("code"=>"AD","name"=>"Andorra","d_code"=>"+376");
        $countries[] = array("code"=>"AO","name"=>"Angola","d_code"=>"+244");
        $countries[] = array("code"=>"AI","name"=>"Anguilla","d_code"=>"+1");
        $countries[] = array("code"=>"AG","name"=>"Antigua","d_code"=>"+1");
        $countries[] = array("code"=>"AR","name"=>"Argentina","d_code"=>"+54");
        $countries[] = array("code"=>"AM","name"=>"Armenia","d_code"=>"+374");
        $countries[] = array("code"=>"AW","name"=>"Aruba","d_code"=>"+297");
        $countries[] = array("code"=>"AU","name"=>"Australia","d_code"=>"+61");
        $countries[] = array("code"=>"AT","name"=>"Austria","d_code"=>"+43");
        $countries[] = array("code"=>"AZ","name"=>"Azerbaijan","d_code"=>"+994");
        $countries[] = array("code"=>"BH","name"=>"Bahrain","d_code"=>"+973");
        $countries[] = array("code"=>"BD","name"=>"Bangladesh","d_code"=>"+880");
        $countries[] = array("code"=>"BB","name"=>"Barbados","d_code"=>"+1");
        $countries[] = array("code"=>"BY","name"=>"Belarus","d_code"=>"+375");
        $countries[] = array("code"=>"BE","name"=>"Belgium","d_code"=>"+32");
        $countries[] = array("code"=>"BZ","name"=>"Belize","d_code"=>"+501");
        $countries[] = array("code"=>"BJ","name"=>"Benin","d_code"=>"+229");
        $countries[] = array("code"=>"BM","name"=>"Bermuda","d_code"=>"+1");
        $countries[] = array("code"=>"BT","name"=>"Bhutan","d_code"=>"+975");
        $countries[] = array("code"=>"BO","name"=>"Bolivia","d_code"=>"+591");
        $countries[] = array("code"=>"BA","name"=>"Bosnia and Herzegovina","d_code"=>"+387");
        $countries[] = array("code"=>"BW","name"=>"Botswana","d_code"=>"+267");
        $countries[] = array("code"=>"BR","name"=>"Brazil","d_code"=>"+55");
        $countries[] = array("code"=>"IO","name"=>"British Indian Ocean Territory","d_code"=>"+246");
        $countries[] = array("code"=>"VG","name"=>"British Virgin Islands","d_code"=>"+1");
        $countries[] = array("code"=>"BN","name"=>"Brunei","d_code"=>"+673");
        $countries[] = array("code"=>"BG","name"=>"Bulgaria","d_code"=>"+359");
        $countries[] = array("code"=>"BF","name"=>"Burkina Faso","d_code"=>"+226");
        $countries[] = array("code"=>"MM","name"=>"Burma Myanmar" ,"d_code"=>"+95");
        $countries[] = array("code"=>"BI","name"=>"Burundi","d_code"=>"+257");
        $countries[] = array("code"=>"KH","name"=>"Cambodia","d_code"=>"+855");
        $countries[] = array("code"=>"CM","name"=>"Cameroon","d_code"=>"+237");
        $countries[] = array("code"=>"CA","name"=>"Canada","d_code"=>"+1");
        $countries[] = array("code"=>"CV","name"=>"Cape Verde","d_code"=>"+238");
        $countries[] = array("code"=>"KY","name"=>"Cayman Islands","d_code"=>"+1");
        $countries[] = array("code"=>"CF","name"=>"Central African Republic","d_code"=>"+236");
        $countries[] = array("code"=>"TD","name"=>"Chad","d_code"=>"+235");
        $countries[] = array("code"=>"CL","name"=>"Chile","d_code"=>"+56");
        $countries[] = array("code"=>"CN","name"=>"China","d_code"=>"+86");
        $countries[] = array("code"=>"CO","name"=>"Colombia","d_code"=>"+57");
        $countries[] = array("code"=>"KM","name"=>"Comoros","d_code"=>"+269");
        $countries[] = array("code"=>"CK","name"=>"Cook Islands","d_code"=>"+682");
        $countries[] = array("code"=>"CR","name"=>"Costa Rica","d_code"=>"+506");
        $countries[] = array("code"=>"CI","name"=>"Côte d'Ivoire" ,"d_code"=>"+225");
        $countries[] = array("code"=>"HR","name"=>"Croatia","d_code"=>"+385");
        $countries[] = array("code"=>"CU","name"=>"Cuba","d_code"=>"+53");
        $countries[] = array("code"=>"CY","name"=>"Cyprus","d_code"=>"+357");
        $countries[] = array("code"=>"CZ","name"=>"Czech Republic","d_code"=>"+420");
        $countries[] = array("code"=>"CD","name"=>"Democratic Republic of Congo","d_code"=>"+243");
        $countries[] = array("code"=>"DK","name"=>"Denmark","d_code"=>"+45");
        $countries[] = array("code"=>"DJ","name"=>"Djibouti","d_code"=>"+253");
        $countries[] = array("code"=>"DM","name"=>"Dominica","d_code"=>"+1");
        $countries[] = array("code"=>"DO","name"=>"Dominican Republic","d_code"=>"+1");
        $countries[] = array("code"=>"EC","name"=>"Ecuador","d_code"=>"+593");
        $countries[] = array("code"=>"EG","name"=>"Egypt","d_code"=>"+20");
        $countries[] = array("code"=>"SV","name"=>"El Salvador","d_code"=>"+503");
        $countries[] = array("code"=>"GQ","name"=>"Equatorial Guinea","d_code"=>"+240");
        $countries[] = array("code"=>"ER","name"=>"Eritrea","d_code"=>"+291");
        $countries[] = array("code"=>"EE","name"=>"Estonia","d_code"=>"+372");
        $countries[] = array("code"=>"ET","name"=>"Ethiopia","d_code"=>"+251");
        $countries[] = array("code"=>"FK","name"=>"Falkland Islands","d_code"=>"+500");
        $countries[] = array("code"=>"FO","name"=>"Faroe Islands","d_code"=>"+298");
        $countries[] = array("code"=>"FM","name"=>"Federated States of Micronesia","d_code"=>"+691");
        $countries[] = array("code"=>"FJ","name"=>"Fiji","d_code"=>"+679");
        $countries[] = array("code"=>"FI","name"=>"Finland","d_code"=>"+358");
        $countries[] = array("code"=>"FR","name"=>"France","d_code"=>"+33");
        $countries[] = array("code"=>"GF","name"=>"French Guiana","d_code"=>"+594");
        $countries[] = array("code"=>"PF","name"=>"French Polynesia","d_code"=>"+689");
        $countries[] = array("code"=>"GA","name"=>"Gabon","d_code"=>"+241");
        $countries[] = array("code"=>"GE","name"=>"Georgia","d_code"=>"+995");
        $countries[] = array("code"=>"DE","name"=>"Germany","d_code"=>"+49");
        $countries[] = array("code"=>"GH","name"=>"Ghana","d_code"=>"+233");
        $countries[] = array("code"=>"GI","name"=>"Gibraltar","d_code"=>"+350");
        $countries[] = array("code"=>"GR","name"=>"Greece","d_code"=>"+30");
        $countries[] = array("code"=>"GL","name"=>"Greenland","d_code"=>"+299");
        $countries[] = array("code"=>"GD","name"=>"Grenada","d_code"=>"+1");
        $countries[] = array("code"=>"GP","name"=>"Guadeloupe","d_code"=>"+590");
        $countries[] = array("code"=>"GU","name"=>"Guam","d_code"=>"+1");
        $countries[] = array("code"=>"GT","name"=>"Guatemala","d_code"=>"+502");
        $countries[] = array("code"=>"GN","name"=>"Guinea","d_code"=>"+224");
        $countries[] = array("code"=>"GW","name"=>"Guinea-Bissau","d_code"=>"+245");
        $countries[] = array("code"=>"GY","name"=>"Guyana","d_code"=>"+592");
        $countries[] = array("code"=>"HT","name"=>"Haiti","d_code"=>"+509");
        $countries[] = array("code"=>"HN","name"=>"Honduras","d_code"=>"+504");
        $countries[] = array("code"=>"HK","name"=>"Hong Kong","d_code"=>"+852");
        $countries[] = array("code"=>"HU","name"=>"Hungary","d_code"=>"+36");
        $countries[] = array("code"=>"IS","name"=>"Iceland","d_code"=>"+354");
        $countries[] = array("code"=>"IN","name"=>"India","d_code"=>"+91");
        $countries[] = array("code"=>"ID","name"=>"Indonesia","d_code"=>"+62");
        $countries[] = array("code"=>"IR","name"=>"Iran","d_code"=>"+98");
        $countries[] = array("code"=>"IQ","name"=>"Iraq","d_code"=>"+964");
        $countries[] = array("code"=>"IE","name"=>"Ireland","d_code"=>"+353");
        $countries[] = array("code"=>"IL","name"=>"Israel","d_code"=>"+972");
        $countries[] = array("code"=>"IT","name"=>"Italy","d_code"=>"+39");
        $countries[] = array("code"=>"JM","name"=>"Jamaica","d_code"=>"+1");
        $countries[] = array("code"=>"JP","name"=>"Japan","d_code"=>"+81");
        $countries[] = array("code"=>"JO","name"=>"Jordan","d_code"=>"+962");
        $countries[] = array("code"=>"KZ","name"=>"Kazakhstan","d_code"=>"+7");
        $countries[] = array("code"=>"KE","name"=>"Kenya","d_code"=>"+254");
        $countries[] = array("code"=>"KI","name"=>"Kiribati","d_code"=>"+686");
        //$countries[] = array("code"=>"XK","name"=>"Kosovo","d_code"=>"+381");
        $countries[] = array("code"=>"KW","name"=>"Kuwait","d_code"=>"+965");
        $countries[] = array("code"=>"KG","name"=>"Kyrgyzstan","d_code"=>"+996");
        $countries[] = array("code"=>"LA","name"=>"Laos","d_code"=>"+856");
        $countries[] = array("code"=>"LV","name"=>"Latvia","d_code"=>"+371");
        $countries[] = array("code"=>"LB","name"=>"Lebanon","d_code"=>"+961");
        $countries[] = array("code"=>"LS","name"=>"Lesotho","d_code"=>"+266");
        $countries[] = array("code"=>"LR","name"=>"Liberia","d_code"=>"+231");
        $countries[] = array("code"=>"LY","name"=>"Libya","d_code"=>"+218");
        $countries[] = array("code"=>"LI","name"=>"Liechtenstein","d_code"=>"+423");
        $countries[] = array("code"=>"LT","name"=>"Lithuania","d_code"=>"+370");
        $countries[] = array("code"=>"LU","name"=>"Luxembourg","d_code"=>"+352");
        $countries[] = array("code"=>"MO","name"=>"Macau","d_code"=>"+853");
        $countries[] = array("code"=>"MK","name"=>"Macedonia","d_code"=>"+389");
        $countries[] = array("code"=>"MG","name"=>"Madagascar","d_code"=>"+261");
        $countries[] = array("code"=>"MW","name"=>"Malawi","d_code"=>"+265");
        $countries[] = array("code"=>"MY","name"=>"Malaysia","d_code"=>"+60");
        $countries[] = array("code"=>"MV","name"=>"Maldives","d_code"=>"+960");
        $countries[] = array("code"=>"ML","name"=>"Mali","d_code"=>"+223");
        $countries[] = array("code"=>"MT","name"=>"Malta","d_code"=>"+356");
        $countries[] = array("code"=>"MH","name"=>"Marshall Islands","d_code"=>"+692");
        $countries[] = array("code"=>"MQ","name"=>"Martinique","d_code"=>"+596");
        $countries[] = array("code"=>"MR","name"=>"Mauritania","d_code"=>"+222");
        $countries[] = array("code"=>"MU","name"=>"Mauritius","d_code"=>"+230");
        $countries[] = array("code"=>"YT","name"=>"Mayotte","d_code"=>"+262");
        $countries[] = array("code"=>"MX","name"=>"Mexico","d_code"=>"+52");
        $countries[] = array("code"=>"MD","name"=>"Moldova","d_code"=>"+373");
        $countries[] = array("code"=>"MC","name"=>"Monaco","d_code"=>"+377");
        $countries[] = array("code"=>"MN","name"=>"Mongolia","d_code"=>"+976");
        $countries[] = array("code"=>"ME","name"=>"Montenegro","d_code"=>"+382");
        $countries[] = array("code"=>"MS","name"=>"Montserrat","d_code"=>"+1");
        $countries[] = array("code"=>"MA","name"=>"Morocco","d_code"=>"+212");
        $countries[] = array("code"=>"MZ","name"=>"Mozambique","d_code"=>"+258");
        $countries[] = array("code"=>"NA","name"=>"Namibia","d_code"=>"+264");
        $countries[] = array("code"=>"NR","name"=>"Nauru","d_code"=>"+674");
        $countries[] = array("code"=>"NP","name"=>"Nepal","d_code"=>"+977");
        $countries[] = array("code"=>"NL","name"=>"Netherlands","d_code"=>"+31");
        $countries[] = array("code"=>"AN","name"=>"Netherlands Antilles","d_code"=>"+599");
        $countries[] = array("code"=>"NC","name"=>"New Caledonia","d_code"=>"+687");
        $countries[] = array("code"=>"NZ","name"=>"New Zealand","d_code"=>"+64");
        $countries[] = array("code"=>"NI","name"=>"Nicaragua","d_code"=>"+505");
        $countries[] = array("code"=>"NE","name"=>"Niger","d_code"=>"+227");
        $countries[] = array("code"=>"NG","name"=>"Nigeria","d_code"=>"+234");
        $countries[] = array("code"=>"NU","name"=>"Niue","d_code"=>"+683");
        $countries[] = array("code"=>"NF","name"=>"Norfolk Island","d_code"=>"+672");
        $countries[] = array("code"=>"KP","name"=>"North Korea","d_code"=>"+850");
        $countries[] = array("code"=>"MP","name"=>"Northern Mariana Islands","d_code"=>"+1");
        $countries[] = array("code"=>"NO","name"=>"Norway","d_code"=>"+47");
        $countries[] = array("code"=>"OM","name"=>"Oman","d_code"=>"+968");
        $countries[] = array("code"=>"PK","name"=>"Pakistan","d_code"=>"+92");
        $countries[] = array("code"=>"PW","name"=>"Palau","d_code"=>"+680");
        $countries[] = array("code"=>"PS","name"=>"Palestine","d_code"=>"+970");
        $countries[] = array("code"=>"PA","name"=>"Panama","d_code"=>"+507");
        $countries[] = array("code"=>"PG","name"=>"Papua New Guinea","d_code"=>"+675");
        $countries[] = array("code"=>"PY","name"=>"Paraguay","d_code"=>"+595");
        $countries[] = array("code"=>"PE","name"=>"Peru","d_code"=>"+51");
        $countries[] = array("code"=>"PH","name"=>"Philippines","d_code"=>"+63");
        $countries[] = array("code"=>"PL","name"=>"Poland","d_code"=>"+48");
        $countries[] = array("code"=>"PT","name"=>"Portugal","d_code"=>"+351");
        $countries[] = array("code"=>"PR","name"=>"Puerto Rico","d_code"=>"+1");
        $countries[] = array("code"=>"QA","name"=>"Qatar","d_code"=>"+974");
        $countries[] = array("code"=>"CG","name"=>"Republic of the Congo","d_code"=>"+242");
        $countries[] = array("code"=>"RE","name"=>"Réunion" ,"d_code"=>"+262");
        $countries[] = array("code"=>"RO","name"=>"Romania","d_code"=>"+40");
        $countries[] = array("code"=>"RU","name"=>"Russia","d_code"=>"+7");
        $countries[] = array("code"=>"RW","name"=>"Rwanda","d_code"=>"+250");
        //$countries[] = array("code"=>"BL","name"=>"Saint Barthélemy" ,"d_code"=>"+590");
        $countries[] = array("code"=>"SH","name"=>"Saint Helena","d_code"=>"+290");
        $countries[] = array("code"=>"KN","name"=>"Saint Kitts and Nevis","d_code"=>"+1");
        //$countries[] = array("code"=>"MF","name"=>"Saint Martin","d_code"=>"+590");
        $countries[] = array("code"=>"PM","name"=>"Saint Pierre and Miquelon","d_code"=>"+508");
        $countries[] = array("code"=>"VC","name"=>"Saint Vincent and the Grenadines","d_code"=>"+1");
        $countries[] = array("code"=>"WS","name"=>"Samoa","d_code"=>"+685");
        $countries[] = array("code"=>"SM","name"=>"San Marino","d_code"=>"+378");
        $countries[] = array("code"=>"ST","name"=>"São Tomé and Príncipe" ,"d_code"=>"+239");
        $countries[] = array("code"=>"SA","name"=>"Saudi Arabia","d_code"=>"+966");
        $countries[] = array("code"=>"SN","name"=>"Senegal","d_code"=>"+221");
        $countries[] = array("code"=>"RS","name"=>"Serbia","d_code"=>"+381");
        $countries[] = array("code"=>"SC","name"=>"Seychelles","d_code"=>"+248");
        $countries[] = array("code"=>"SL","name"=>"Sierra Leone","d_code"=>"+232");
        $countries[] = array("code"=>"SG","name"=>"Singapore","d_code"=>"+65");
        $countries[] = array("code"=>"SK","name"=>"Slovakia","d_code"=>"+421");
        $countries[] = array("code"=>"SI","name"=>"Slovenia","d_code"=>"+386");
        $countries[] = array("code"=>"SB","name"=>"Solomon Islands","d_code"=>"+677");
        $countries[] = array("code"=>"SO","name"=>"Somalia","d_code"=>"+252");
        $countries[] = array("code"=>"ZA","name"=>"South Africa","d_code"=>"+27");
        $countries[] = array("code"=>"KR","name"=>"South Korea","d_code"=>"+82");
        $countries[] = array("code"=>"ES","name"=>"Spain","d_code"=>"+34");
        $countries[] = array("code"=>"LK","name"=>"Sri Lanka","d_code"=>"+94");
        $countries[] = array("code"=>"LC","name"=>"St. Lucia","d_code"=>"+1");
        $countries[] = array("code"=>"SD","name"=>"Sudan","d_code"=>"+249");
        $countries[] = array("code"=>"SR","name"=>"Suriname","d_code"=>"+597");
        $countries[] = array("code"=>"SZ","name"=>"Swaziland","d_code"=>"+268");
        $countries[] = array("code"=>"SE","name"=>"Sweden","d_code"=>"+46");
        $countries[] = array("code"=>"CH","name"=>"Switzerland","d_code"=>"+41");
        $countries[] = array("code"=>"SY","name"=>"Syria","d_code"=>"+963");
        $countries[] = array("code"=>"TW","name"=>"Taiwan","d_code"=>"+886");
        $countries[] = array("code"=>"TJ","name"=>"Tajikistan","d_code"=>"+992");
        $countries[] = array("code"=>"TZ","name"=>"Tanzania","d_code"=>"+255");
        $countries[] = array("code"=>"TH","name"=>"Thailand","d_code"=>"+66");
        $countries[] = array("code"=>"BS","name"=>"The Bahamas","d_code"=>"+1");
        $countries[] = array("code"=>"GM","name"=>"The Gambia","d_code"=>"+220");
        $countries[] = array("code"=>"TL","name"=>"Timor-Leste","d_code"=>"+670");
        $countries[] = array("code"=>"TG","name"=>"Togo","d_code"=>"+228");
        $countries[] = array("code"=>"TK","name"=>"Tokelau","d_code"=>"+690");
        $countries[] = array("code"=>"TO","name"=>"Tonga","d_code"=>"+676");
        $countries[] = array("code"=>"TT","name"=>"Trinidad and Tobago","d_code"=>"+1");
        $countries[] = array("code"=>"TN","name"=>"Tunisia","d_code"=>"+216");
        $countries[] = array("code"=>"TR","name"=>"Turkey","d_code"=>"+90");
        $countries[] = array("code"=>"TM","name"=>"Turkmenistan","d_code"=>"+993");
        $countries[] = array("code"=>"TC","name"=>"Turks and Caicos Islands","d_code"=>"+1");
        $countries[] = array("code"=>"TV","name"=>"Tuvalu","d_code"=>"+688");
        $countries[] = array("code"=>"UG","name"=>"Uganda","d_code"=>"+256");
        $countries[] = array("code"=>"UA","name"=>"Ukraine","d_code"=>"+380");
        $countries[] = array("code"=>"AE","name"=>"United Arab Emirates","d_code"=>"+971");
        $countries[] = array("code"=>"GB","name"=>"United Kingdom","d_code"=>"+44");
        $countries[] = array("code"=>"US","name"=>"United States","d_code"=>"+1");
        $countries[] = array("code"=>"UY","name"=>"Uruguay","d_code"=>"+598");
        $countries[] = array("code"=>"VI","name"=>"US Virgin Islands","d_code"=>"+1");
        $countries[] = array("code"=>"UZ","name"=>"Uzbekistan","d_code"=>"+998");
        $countries[] = array("code"=>"VU","name"=>"Vanuatu","d_code"=>"+678");
        $countries[] = array("code"=>"VA","name"=>"Vatican City","d_code"=>"+39");
        $countries[] = array("code"=>"VE","name"=>"Venezuela","d_code"=>"+58");
        $countries[] = array("code"=>"VN","name"=>"Vietnam","d_code"=>"+84");
        $countries[] = array("code"=>"WF","name"=>"Wallis and Futuna","d_code"=>"+681");
        $countries[] = array("code"=>"YE","name"=>"Yemen","d_code"=>"+967");
        $countries[] = array("code"=>"ZM","name"=>"Zambia","d_code"=>"+260");
        $countries[] = array("code"=>"ZW","name"=>"Zimbabwe","d_code"=>"+263");

        return $countries;
    }

    /**
    * Add additional custom field
    */

    public function mapi_show_additional_profile_fields ( $user )
    {
    ?>
        <h3>Additional profile information</h3>
        <table class="form-table">
            <tr>
                <th><label for="phone">Mobile number</label></th>
                <td>
                    <input type="text" name="phone" placeholder="1234567890" id="phone" value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description">Please enter your phone number.</span>
                </td>
            </tr>
            <tr>
                <th><label for="country">Country</label></th>
                <td>
                    <select name="country" class="specific_number_prefix">
                        <?php foreach ($this->mapi_getCountryList() as $country) { ?>
                        <option data-country-code="<?php echo strtolower($country['d_code']); ?>"
                                value="<?php echo esc_attr(strtolower($country['code'])); ?>"
                                <?php
                                    if(!empty(get_the_author_meta('country', $user->ID))) {
                                        echo (
                                            strtolower($country['code'])==get_the_author_meta('country', $user->ID)
                                            ) ? esc_attr('selected=selected') : '';
                                    }
                                    else {
                                        echo (!empty($country['code']) && $country['code'] == 'US') ? esc_attr('selected=selected') : '';
                                    }
                                ?> >
                                <?php echo esc_attr($country['name']) ?>
                        </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </table>
    <?php
    }


    public function mapi_save_additional_profile_fields( $user_id )
    {
        if ( !current_user_can( 'edit_user', $user_id ) )
            return false;
        /* Copy and paste this line for additional fields. Make sure to change 'phone' to the field ID. */
        if(isset($_POST['phone']) && isset($_POST['country'])) {
            $post_phone = sanitize_text_field($_POST['phone']);
            $post_country = sanitize_text_field($_POST['country']);

            if(!empty($post_phone) && ctype_digit($post_phone))
                update_user_meta( $user_id, 'phone', $post_phone );

            if(!empty($post_country))
                update_user_meta( $user_id, 'country', $post_country );
        }
    }


    public function validate_additional_fields($errors,$update,$user){

        // Check if user has authority to change this
        if(!current_user_can('edit_user',$user->ID)){
            $errors->add("permission_denied","You do not have permission to update this page");
        }

        // Validate Phone Number
        if(isset($_POST['phone'])) {
            if(empty($_POST['phone'])){
                $errors->add("phone","Mobile number - Cannot be empty");
            }
            else if (!ctype_digit($_POST['phone']))
                $errors->add("phone", "Mobile Number - Please enter digits only");

            }

        if(isset($_POST['country'])) {
            if(empty($_POST['country']))
                $errors->add("country", "Country - Please select country");
        }

    }

    public function mapi_display_phone_field()
    {
    ?>
        <p>
        <label>Mobile number<br/>
        <input id="phone" type="text" placeholder="1234567890" tabindex="30" size="25" name="phone" />
        </label>
        </p>
    <?php
    }

    public function mapi_display_country_field() {
    ?>
        <p>
        <label>Country<br/>
            <select name="country" class="specific_number_prefix">
                <?php foreach ($this->mapi_getCountryList() as $country) { ?>
                <option data-country-code="<?php echo esc_attr(strtolower($country['d_code'])); ?>"
                        value="<?php echo esc_attr(strtolower($country['code'])); ?>"
                        <?php
                            if(!empty(messagingsms_get_options('messagingsms_woocommerce_country_code', 'messagingsms_setting', '' ))) {
                                echo (
                                    strtolower($country['code'])==messagingsms_get_options('messagingsms_woocommerce_country_code', 'messagingsms_setting', '' )
                                    ) ? esc_attr('selected=selected') : '';
                            }
                            else {
                                echo (!empty($country['code']) && $country['code'] == 'US') ? esc_attr('selected=selected') : '';
                            }
                        ?> >
                        <?php echo $country['name'] ?>
                </option>
                <?php } ?>
            </select>
        </label>
        </p>
    <?php
    }

    public function mapi_validate_fields ( $login, $email, $errors )
    {
        global $phone;
        if(isset($_POST['phone'])){

            $post_phone = sanitize_text_field($_POST['phone']);
            if ( $post_phone == '' )
            {
                $errors->add( 'empty_realname', "<strong>ERROR</strong>: Please Enter your phone number" );
            }
            else
            {
                $phone = $post_phone;
            }
        }
    }

    public function mapi_register_additional_fields ( $user_id, $password = "", $meta = array() )
    {
        $post_phone = sanitize_text_field($_POST['phone']);
        update_user_meta( $user_id, 'phone', $post_phone );
        $post_country = sanitize_text_field($_POST['country']);
        update_user_meta( $user_id, 'country', $post_country );
    }

    public function display_send_sms_success()
    {
        if( !isset($_GET['sms_sent']) ) { return; }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Whatsapp Sent!', MESSAGINGSMS_TEXT_DOMAIN ); ?></p>
        </div>
        <?php
    }

    public function add_removable_arg($args)
    {
        array_push($args, 'sms_sent');
        return $args;
    }

    public function load_scripts()
    {
        ?>
        <script src="https://cdn.rawgit.com/Codesleuth/split-Whatsapp/0.1.7/dist/split-Whatsapp.min.js"></script>
        <script>

            <?php
                $roles_arr = array();
                $um_arr = array();
                $pmpro_arr = array();
                $country_arr = array();

                $available_filters = array("roles", "country");

                // populate roles
                foreach (get_editable_roles() as $key => $value) {
                    $roles_arr[$key] = $value['name'];
                }

                foreach ($this->mapi_getCountryList() as $country) {
                    $country_arr[$country['code']] = $country['name'];
                }

                // populate ultimate members status
                if(function_exists('is_ultimatemember')) {
                    $available_filters[] = "status";
                    global $wpdb;
                    $results = $wpdb->get_results("SELECT DISTINCT meta_value FROM wp_usermeta WHERE meta_key = 'account_status' ");

                    foreach ($results as $result) {
                        $um_arr[$result->meta_value] = $result->meta_value;
                    }
                }

                // populate PM Pro
                if(function_exists('pmpro_hasMembershipLevel')) {
                    $available_filters[] = "membership_level";
                    global $wpdb;
                    $results = $wpdb->get_results ( "SELECT id, name FROM wp_pmpro_membership_levels" );
                    foreach ($results as $result) {
                        $pmpro_arr[$result->id] = $result->name;
                    }
                }

            ?>

            var filter_by_arr = <?php echo json_encode($available_filters) ?>;

            var criteria_array = new Object;

            if(filter_by_arr.includes("roles")) {
                criteria_array['roles'] = <?php echo json_encode($roles_arr); ?>;
            }
            if(filter_by_arr.includes("country")) {
                criteria_array['country'] = <?php echo json_encode($country_arr); ?>;
            }
            if(filter_by_arr.includes("status")) {
                criteria_array['status'] = <?php echo json_encode($um_arr); ?>;
            }
            if(filter_by_arr.includes("membership_level")) {
                criteria_array['membership_level'] = <?php echo json_encode($pmpro_arr); ?>;
            }


            function populatedSecondaryFields(filteredField, stateElementId) {

                var selectedFilter = document.getElementById(filteredField).value; // roles

                var criteriaElement = document.getElementById(stateElementId);

                criteriaElement.length = 0;
                criteriaElement.selectedIndex = 0;

                var crit_arr = criteria_array[selectedFilter];

                for (let [key, value] of Object.entries(crit_arr)) {
                    criteriaElement.options[criteriaElement.length] = new Option(value.replace('_', ' '), key);
                }

            }

            function populateFilters(filterElementId, filterValueElementId) {
                // given the id of the <select> tag as function argument, it inserts <option> tags
                var filterElement = document.getElementById(filterElementId);
                filterElement.length = 0;
                filterElement.options[0] = new Option('Select Filter', '-1');
                filterElement.selectedIndex = 0;
                for (var i = 0; i < filter_by_arr.length; i++) {
                    filterElement.options[filterElement.length] = new Option(filter_by_arr[i].replace('_', ' '), filter_by_arr[i]);
                }

                if (filterElementId) {
                    filterElement.onchange = function () {
                        populatedSecondaryFields(filterElementId, filterValueElementId);
                    };
                }
            }

            populateFilters("messagingsms_sendsms_setting[messagingsms_sendsms_filters]", "messagingsms_sendsms_setting[messagingsms_sendsms_criteria]");

            jQuery(function ($) {

                countCharactersAndSMS('textarea#messagingsms_sendsms_setting\\[messagingsms_sendsms_message\\]', 'text-bulksms-characters', 'text-bulksms-Whatsapp');

                function countCharactersAndSMS(selector, charCounter, smsCounter){

                    var box = $(selector+'');

                    $(selector+'').keyup(function(e) {
                        var info = window.splitter.split(box.val());
                        //this can read the bytes of last Whatsapp
                        countBytes = JSON.stringify(info.parts[info.parts.length-1].bytes);
                        totalBytes = JSON.stringify(info.bytes);
                        remainingChar = JSON.stringify(info.remainingInPart);
                        characterSet = JSON.stringify(info.characterSet);
                        $('#'+charCounter+'').html(countBytes+' / '+remainingChar);
                        //this can read the total number of Whatsapp
                        $('#'+smsCounter+'').html(info.parts.length);

                    });
                    $('#'+selector+'').trigger('keyup');
                }

                $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").hide();
                $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").hide();
                $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").hide();
                $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").hide();

                $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_message_to\\]').on('change', function() {
                    if($(this).val()=="customer_all") {
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").hide();
                    }
                    else if($(this).val()=="customer") {
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").show();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").hide();
                    }

                    else if($(this).val()=="phones") {
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").show();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").hide();
                    }
                    else if($(this).val()=="spec_group_ppl") {
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").show();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").show();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").hide();
                    } else {
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_filters\\]').closest("tr").hide();
                        $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_criteria\\]').closest("tr").hide();
                    }
                });

                $('select[name="type"]').on('change', function() {
                    if($(this).val()=="mms") {
                        $('.media_upload').show();
                    } else {
                        $('.media_upload').hide();
                    }
                });

                var error = '';
                var validate = function () {
                    if(!($('#messagingsms_sendsms_setting\\[messagingsms_sendsms_users\\]').val())
                        && $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_message_to\\]').val() == 'customer') {
                        error = "users";
                        return false;
                    }

                    if(!($('#messagingsms_sendsms_setting\\[messagingsms_sendsms_recipients\\]').val())
                        && $('#messagingsms_sendsms_setting\\[messagingsms_sendsms_message_to\\]').val() == 'phones') {
                        error = "recipients";
                        return false;
                    }

                    if(!($('#messagingsms_sendsms_setting\\[messagingsms_sendsms_message\\]').val())) {
                        error = "message";
                        return false;
                    }
                    return true;
                };

                $('#sendMessage').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if(validate()) {
                        $("#messagingsms_sendsms_setting form").submit();
                    } else {
                        if(error == 'from' ){
                            alert("Please enter sender!");
                        }else if(error == 'users'){
                            alert("Please enter user you wish to send to!");
                        }else if(error == 'recipients'){
                            alert("Please enter your recipients!");
                        }else if(error == 'message'){
                            alert("Please enter message!");
                        }
                    }
                });

                $("#count_me").characterCounter({
                    counterFormat: '%1 written characters.',
                    counterWrapper: 'div',
                    counterCssClass: 'message_counter'
                });

                $('#recipients').keypress(function(e) {
                    var a = [];
                    var k = e.which;

                    if($('#recipients').val() !== '')
                        a.push(44);

                    for (i = 48; i < 58; i++)
                        a.push(i);

                    if (!(a.indexOf(k)>=0))
                        e.preventDefault();

                    // $('span').text('KeyCode: '+k);
                });

            });

        function sendCheck(phone) {

            <?php if( get_option('smsbump_PhoneNumberPrefix')=='yes') { ?>
                phone.replace(' ','');
                phone.replace('-','');
                phone.replace('(','');
                phone.replace(')','');
                var numberCheck = phone.replace(/^(\+|0)+/, '');
                var prefixCheck = '<?php get_option('smsbump_StrictNumberPrefix'); ?>'.replace(/^\++/, '');
                var formattedNumber = '';
                if(!isNaN(phone)){
                    if((phone.indexOf('+') === 0 || phone.indexOf('00') === 0) && numberCheck.indexOf(prefixCheck) === 0 ){
                        formatedNumber = '+' + numberCheck;
                    } else if ((phone.indexOf('+') === 0 || phone.indexOf('00') === 0) && numberCheck.indexOf(prefixCheck) !== 0){
                        formattedNumber = false;
                    } else if (numberCheck.indexOf(prefixCheck) === -1){
                        formattedNumber = prefix + numberCheck;
                    } else if (phone.indexOf('+') !== 0 && phone.indexOf('00') !== 0 && numberCheck.indexOf(prefixCheck) === 0 ){
                        formattedNumber = '+'+phone;
                    } else {
                        formattedNumber = false;
                    }

                }
                return formattedNumber;
            <?php } ?>
            return phone;
        }

        // function getAllUsersPhones() {
        // 	var phones = [];
        // 	<?php
        // 	$users = get_users();

        // 	foreach ($users as $user ) {
        // 		if($user->roles[0] != 'administrator'){
        // 			$phone = get_user_meta($user->ID, "phone", true);
        // 		}
        // 	?>
        // 		phones.push('<?php //echo $phone; ?>');
        // 	<?php //} ?>

        // 	return phones;
        // }

        </script>
        <?php
    }

}

?>
