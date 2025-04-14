<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class MessagingSMS_SendSMS_Sms {

	public static function send_sms($phone_no, $message) {
        if(empty($phone_no)) {
            return;
        }

	    $log = new Messagingsms_WooCoommerce_Logger();

        $api_key = messagingsms_get_options("messagingsms_woocommerce_api_key", 'messagingsms_setting');
        $service = messagingsms_get_options("messagingsms_woocommerce_service", 'messagingsms_setting');
        $whatsapp = messagingsms_get_options("messagingsms_woocommerce_whatsapp", 'messagingsms_setting');
        $device = messagingsms_get_options("messagingsms_woocommerce_device", 'messagingsms_setting');
        $gateway = messagingsms_get_options("messagingsms_woocommerce_gateway", 'messagingsms_setting');
        $sim = messagingsms_get_options("messagingsms_woocommerce_sim", 'messagingsms_setting');

	    if($api_key == '') return;

	    $log->add(ZMPLGXX_NAME, 'Sending message to '.$phone_no.', message: '.$message);

	    try {
	        $messagingsms_rest = new MessagingSMS($api_key, $service, $whatsapp, $device, $gateway, $sim);
	        $rest_response = $messagingsms_rest->sendSMS($phone_no, $message);

	        $log->add(ZMPLGXX_NAME, 'Response from gateway: ' .$rest_response);

	  		return 'true';

	    } catch (Exception $e) {
	        $log->add(ZMPLGXX_NAME, 'Failed sent Whatsapp: ' . $e->getMessage());
	    }

	}

	public static function getPhoneNumber($message_to, $customer, $phone, $country, $filters='', $criteria=''){
        // Validate phone numbers here

		switch($message_to) {
		    case "customer_all":
                $numbers = self::getValidatedPhoneNumbers(get_users());
		    	#$numbers = self::getAllUsersPhones();
		    	break;
		    case "customer":
		    	$numbers = self::getValidatedPhoneNumbers($customer);
		    	// $numbers = self::getSpecificCustomerPhones($customer);
		    	break;
		    case "spec_group_ppl":
		    	$numbers = self::getFilteredUsers($filters, $criteria);
		    	// $numbers = self::getSpecificCustomerPhones($customer);
		    	break;
		    case "phones":
		    	$numbers = self::getUsersPhones($phone);
		    	break;
		    default: break;
		}

		return $numbers;
	}

    public static function getFilteredUsers($filters, $criteria) {

        $filtered_users = array();

        // get all users
        // filter them using filters and criteria
        if($filters == 'roles') {

            $args = array(
                'role__in' => $criteria,
            );

            $filtered_users = get_users($args);

        }

        if($filters == 'country') {

            $args = array(
                'meta_key' => 'country',
                'meta_value' => $criteria,
            );

            $filtered_users = get_users($args);

        }

        if ($filters == 'status') {
            $args = array(
                'meta_key' => 'account_status',
                'meta_value' => $criteria,
            );

            $filtered_users = get_users($args);
        }

        if ($filters == 'membership_level') {
            global $wpdb;
            #$wpdb->prepare($sql_query, implode(', ', $criteria));
            $sql_query = ' SELECT user_id FROM wp_pmpro_memberships_users WHERE membership_id IN (%s) ';
            $results = $wpdb->get_results($wpdb->prepare($sql_query, implode(', ', $criteria)));

            foreach($results as $result) {
                $filtered_users[] = get_user_by("ID", $result->user_id);
            }

        }

        return self::getValidatedPhoneNumbers($filtered_users);
    }

    public static function getValidatedPhoneNumbers($users) {
        $validatedUsers = array();
        if($users) {
            if(is_array($users)) {
                foreach ($users as $user) {
                    if(!($user instanceof WP_User)) {
                        $user = get_user_by('ID', $user);
                    }

                    $phone = self::get_formatted_number($user->phone, $user->country);

                    if ($phone) {
                        $user->phone = $phone;
                        array_push($validatedUsers, $user);
                    }
                }
            }
            else {
                $phone = self::get_formatted_number($users->phone, $users->country);

                if($phone) {
                    $users->phone = $phone;
                    return $users;
                }
            }
        }

        return $validatedUsers;
    }

    public static function get_formatted_number($phone, $country = '') {
        $log = new Messagingsms_WooCoommerce_Logger();
        $settings_country = !empty(messagingsms_get_options('messagingsms_woocommerce_country_code', 'messagingsms_setting', '' )) ? messagingsms_get_options('messagingsms_woocommerce_country_code', 'messagingsms_setting', '' ) : "US";
        $country = !empty($country) ? strtoupper($country) : strtoupper($settings_country);

        $request_url = ZMPLGXX_URL . "/plugin?name=woocommerce-integration&action=mobile&phone={$phone}&country={$country}";

        $response = wp_remote_get($request_url, array( 'sslverify' => false ));

        if (is_array($response)) {
            $customer_phone_no = json_decode(wp_remote_retrieve_body($response), true);

            if ($customer_phone_no["status"] == 200) {
                return $customer_phone_no["data"]["phone"];
            }

            $log->add(ZMPLGXX_NAME, 'check number api err response:' . json_encode($customer_phone_no));

            return false;
        }

        $log->add( ZMPLGXX_NAME, 'check number api failed' );

        return false;
    }

	private static function getUsersPhones($phone_number)
	{
		$phone_number = explode(",", $phone_number);
		$phones = array();
		foreach ($phone_number as $phone) {
		 	$phones[] = $phone;
		}
		return $phones;
	}
}