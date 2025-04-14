<?php

class MessagingLatePoint implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: LatePoint - Appointment Booking & Reservation plugin for WordPress
    Plugin Link: https://codecanyon.net/item/latepoint-appointment-booking-reservation-plugin-for-wordpress/22792692
    */

    public static $plugin_identifier = 'latepoint';
    private $log;
    private $plugin_name;
    private $option_id;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'LatePoint Appointment Booking and Reservation';
    }

    public function register()
    {
        add_action( 'latepoint_booking_updated', array($this, 'send_sms_on'), 10, 2 );
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public static function plugin_activated()
    {
        $log = new Messagingsms_WooCoommerce_Logger();
        if (is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier))) {
            try {
                require_once( LATEPOINT_ABSPATH . 'lib/models/service_model.php' );
                require_once( LATEPOINT_ABSPATH . 'lib/models/booking_model.php' );
                require_once( LATEPOINT_ABSPATH . 'lib/models/customer_model.php' );
                require_once( LATEPOINT_ABSPATH . 'lib/models/agent_model.php' );

                return true;
            } catch (Exception $e) {
                $log->add(ZMPLGXX_NAME, "Failed to import model files from LATEPOINT");
                $log->add(ZMPLGXX_NAME, print_r($e, true));
                return false;
            }
        }

        return false;
    }

    public function get_setting_section_data()
    {
        return array(
            'id'    => $this->get_option_id(),
            'title' => __( $this->plugin_name, MESSAGINGSMS_TEXT_DOMAIN ),
        );
    }

    public function get_setting_field_data()
    {
        $setting_fields = array(
			$this->get_enable_notification_fields(),
			$this->get_send_on_fields(),
		);
        foreach($this->get_sms_template_fields() as $sms_reminder_templates) {
            $setting_fields[] = $sms_reminder_templates;
        }
        return $setting_fields;
    }

    private function get_enable_notification_fields() {
        return array(
            'name'    => 'messagingsms_automation_enable_notification',
            'label'   => __( 'Enable notifications', MESSAGINGSMS_TEXT_DOMAIN ),
            'desc'    => ' ' . __( 'Enable', MESSAGINGSMS_TEXT_DOMAIN ),
            'type'    => 'checkbox',
            'default' => 'off'
        );
    }

    private function get_send_on_fields() {
        return array(
            'name'    => 'messagingsms_automation_send_on',
            'label'   => __( 'Send notification on', MESSAGINGSMS_TEXT_DOMAIN ),
            'desc'    => __( 'Choose when to send a Whatsapp notification message to your customer', MESSAGINGSMS_TEXT_DOMAIN ),
            'type'    => 'multicheck',
            'options' => array(
                'approved'         => 'Approved',
                'pending'          => 'Pending approval',
                'payment_pending'  => 'Payment pending',
                'cancelled'        => 'Cancelled',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_approved',
                'label'   => __( 'Approved Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="approved" data-attr-target="%1$s[messagingsms_automation_sms_template_approved]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [cust_first_name], your appointment for [service_name] on [booking_start_date] [booking_start_time] is [booking_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_pending',
                'label'   => __( 'Pending approval Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [cust_first_name], your appointment for [service_name] on [booking_start_date] [booking_start_time] is [booking_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_payment_pending',
                'label'   => __( 'Payment pending Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_payment_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [cust_first_name], your appointment for [service_name] on [booking_start_date] [booking_start_time] is [booking_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_cancelled',
                'label'   => __( 'Cancelled Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_cancelled]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [cust_first_name], your appointment for [service_name] on [booking_start_date] [booking_start_time] is [booking_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "messagingsms_automation_enable_notification"           => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                       => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_sms_template_approved"         => messagingsms_get_options("messagingsms_automation_sms_template_approved", $this->get_option_id()),
            "messagingsms_automation_sms_template_pending"          => messagingsms_get_options("messagingsms_automation_sms_template_pending", $this->get_option_id()),
            "messagingsms_automation_sms_template_payment_pending"  => messagingsms_get_options("messagingsms_automation_sms_template_payment_pending", $this->get_option_id()),
            "messagingsms_automation_sms_template_cancelled"        => messagingsms_get_options("messagingsms_automation_sms_template_cancelled", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
    }

    public function get_keywords_field()
    {
        return array(
            'booking' => array(
                'booking_id',
                'booking_start_date',
                'booking_end_date',
                'booking_start_time',
                'booking_end_time',
                'booking_status',
                'booking_duration',
                'booking_price',
            ),
            'customer' => array(
                'cust_first_name',
                'cust_last_name',
                'cust_email',
                'cust_phone',
            ),
            'service' => array(
                'service_name',
                'service_short_description',
                'service_price_min',
                'service_price_max',
                'service_charge_amount',
                'service_deposit_amount',
                'service_duration',
                'service_buffer_before',
                'service_buffer_after',
            ),
            'agent' => array(
                'agent_first_name',
                'agent_last_name',
                'agent_display_name',
                'agent_email',
                'agent_phone',
            ),
        );

    }

    public function send_sms_on($booking, $old_status)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];

        $this->log->add(ZMPLGXX_NAME, "booking id: {$booking->id}");
        $this->log->add(ZMPLGXX_NAME, "booking status: {$booking->status}");

        $status = $booking->status;

        if($enable_notifications === "on"){
            $this->log->add(ZMPLGXX_NAME, "Enable notifications: {$enable_notifications}");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add(ZMPLGXX_NAME, "Enable {$status} notifications: true");
                    $function_to_be_called = "send_sms_on_status_{$status}";
                    $this->$function_to_be_called($booking);
                }
            }
        }

        return $booking;
    }

    public function send_sms_on_status_pending($booking) {
		$this->send_customer_notification( $booking, "pending" );
	}

    public function send_sms_on_status_approved($booking) {
		$this->send_customer_notification( $booking, "approved" );
	}

    public function send_sms_on_status_payment_pending($booking) {
		$this->send_customer_notification( $booking, "payment_pending" );
	}

    public function send_sms_on_status_cancelled($booking) {
		$this->send_customer_notification( $booking, "cancelled" );
	}

    public function send_customer_notification($booking, $status)
    {
        $settings = $this->get_plugin_settings();

        // get number from customer
        $customer = new OsCustomerModel($booking->customer_id);
        $phone_no = $customer->phone;

        if(empty($phone_no)) {
            // check if it is associated with a wordpress user
            // and get the number from there
            $this->log->add(ZMPLGXX_NAME, "customer phone number is EMPTY or NULL");
            $this->log->add(ZMPLGXX_NAME, "Checking whether customer is associated with a wordpress user");
            if(empty($customer->wordpress_user_id)) {
                $this->log->add(ZMPLGXX_NAME, "Customer not associated with any wordpress user");
                $this->log->add(ZMPLGXX_NAME, "Nothing else to do here, abort");
                return;
            }
            // if it is associated with a WP_User
            $user = new WP_User($customer->wordpress_user_id);
            $phone_no = $user->phone;
        }

        $this->log->add(ZMPLGXX_NAME, "customer phone no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["messagingsms_automation_sms_template_{$status}"];

        $this->log->add(ZMPLGXX_NAME, "Message template: {$msg_template}");

        $message = $this->replace_keywords_with_value($booking, $msg_template);
        MessagingSMS_SendSMS_Sms::send_sms($phone_no, $message);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($booking, $message)
    {
        // use regex to match all [stuff_inside]
        // replace and match it with rtbBooking (booking) object
        // return the message
        $service = new OsServiceModel($booking->service_id);
        $customer = new OsCustomerModel($booking->customer_id);
        $agent = new OsAgentModel($booking->agent_id);
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if($keywords) {
            foreach($keywords[1] as $keyword) {
                if(substr($keyword, 0, strlen('booking_')) === 'booking_') {
                    $message = str_replace("[{$keyword}]", $this->keyword_mapper($booking, $keyword), $message);
                }
                else if(substr($keyword, 0, strlen('cust_')) === 'cust_') {
                    $message = str_replace("[{$keyword}]", $this->keyword_mapper($customer, $keyword), $message);
                }
                else if(substr($keyword, 0, strlen('service_')) === 'service_') {
                    $message = str_replace("[{$keyword}]", $this->keyword_mapper($service, $keyword), $message);
                }
                else if(substr($keyword, 0, strlen('agent_')) === 'agent_') {
                    $message = str_replace("[{$keyword}]", $this->keyword_mapper($agent, $keyword), $message);
                }
                // the keyword not a property in $booking object
                // so we just replace with empty string
                else {
                    $message = str_replace("[{$keyword}]", "", $message);
                }
            }
        }
        return $message;
    }

    private function keyword_mapper($model, $key) {
        $date_format = !empty(messagingsms_get_options('messagingsms_woocommerce_dateformat', 'messagingsms_setting', '' )) ? messagingsms_get_options('messagingsms_woocommerce_dateformat', 'messagingsms_setting', '' ) : "Y-m-d";

        $kw_mappers = array(
            'booking_id'                => $model->id,
            'booking_start_date'        => date($date_format, strtotime($model->start_date)),
            'booking_end_date'          => date($date_format, strtotime($model->end_date)),
            'booking_start_time'        => date("h:i A", mktime(0, $model->start_time)),
            'booking_end_time'          => date("h:i A", mktime(0, $model->end_time)),
            'booking_status'            => $model->status,
            'booking_duration'          => $model->duration,
            'booking_price'             => $model->price,
            'cust_first_name'           => $model->first_name,
            'cust_last_name'            => $model->last_name,
            'cust_email'                => $model->email,
            'cust_phone'                => $model->phone,
            'service_name'              => $model->name,
            'service_short_description' => $model->short_description,
            'service_price_min'         => $model->price_min,
            'service_price_max'         => $model->price_max,
            'service_charge_amount'     => $model->charge_amount,
            'service_deposit_amount'    => $model->deposit_amount,
            'service_duration'          => $model->duration,
            'service_buffer_before'     => $model->buffer_before,
            'service_buffer_after'      => $model->buffer_after,
            'agent_first_name'          => $model->first_name,
            'agent_last_name'           => $model->last_name,
            'agent_display_name'        => $model->display_name,
            'agent_email'               => $model->email,
            'agent_phone'               => $model->phone,
        );

        if(! array_key_exists($key, $kw_mappers)) { return ''; }
        return $kw_mappers[$key];
    }

}
