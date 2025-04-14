<?php

class MessagingRestaurantReservation implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: Five Star Restaurant Reservations - WordPress Booking Plugin
    Plugin Link: https://wordpress.org/plugins/restaurant-reservations/
    */

    // private $section_id;
    public static $plugin_identifier = 'restaurant-reservations';
    private $log;
    private $plugin_name;
    private $hook_action;
    private $option_id;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'Five Star Restaurant Reservations';
        $this->hook_action = "messagingsms_send_reminder_{$this::$plugin_identifier}";
    }

    public function register()
    {
        add_filter( 'rtb_insert_booking', array($this, 'send_sms_on') );
        add_filter( 'rtb_update_booking', array($this, 'send_sms_on') );
        add_action( $this->hook_action, array($this, 'send_sms_reminder'), 10, 2);
    }

    public static function plugin_activated()
    {
        return is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier));
    }

    public function get_option_id()
    {
        return $this->option_id;
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
        foreach($this->get_reminder_fields() as $reminder) {
            $setting_fields[] = $reminder;
        }
        foreach($this->get_sms_reminder_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
        }
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
                'pending'    => 'Pending',
                'confirmed'  => 'Confirmed',
                'closed'     => 'Closed',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_pending',
                'label'   => __( 'Pending Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_confirmed',
                'label'   => __( 'Confirmed Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_confirmed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_closed',
                'label'   => __( 'Closed Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_closed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    private function get_reminder_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_reminder',
                'label'   => __( 'Send reminder to confirmed customer reservation', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => __( '', MESSAGINGSMS_TEXT_DOMAIN ),
                'type'    => 'multicheck',
                'options' => array(
                    'rem_1'    => '15 minutes before reservation',
                    'rem_2'    => '30 minutes before reservation',
                    'rem_3'    => '60 minutes before reservation',
                    'custom'   => 'Custom time before reservation',
                )
            ),
            array(
                'name'  => 'messagingsms_automation_reminder_custom_time',
                'label' => __( '', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'  => __( 'Enter the custom time you want to remind your customer before reservation in (minutes) <br> Choose when to send a Whatsapp reminder message to your customer <br> Please set your timezone in <a href="' . admin_url('options-general.php') . '">settings</a> <br> You must setup cronjob <a href="https://github.com/MessagingAPI/wordpress">here</a> ', MESSAGINGSMS_TEXT_DOMAIN ),
                'type'  => 'number',
            ),
        );
    }

    private function get_sms_reminder_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_1',
                'label'   => __( '15 minutes reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_1]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_2',
                'label'   => __( '30 minutes reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_3',
                'label'   => __( '60 minutes reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Greetings [name], your table reservation for [party] people on [date] is [post_status] - custom', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "messagingsms_automation_enable_notification"    => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_reminder"               => messagingsms_get_options("messagingsms_automation_reminder", $this->get_option_id()),
            "messagingsms_automation_reminder_custom_time"   => messagingsms_get_options("messagingsms_automation_reminder_custom_time", $this->get_option_id()),
            "messagingsms_automation_sms_template_pending"   => messagingsms_get_options("messagingsms_automation_sms_template_pending", $this->get_option_id()),
            "messagingsms_automation_sms_template_confirmed" => messagingsms_get_options("messagingsms_automation_sms_template_confirmed", $this->get_option_id()),
            "messagingsms_automation_sms_template_closed"    => messagingsms_get_options("messagingsms_automation_sms_template_closed", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_1"     => messagingsms_get_options("messagingsms_automation_sms_template_rem_1", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_2"     => messagingsms_get_options("messagingsms_automation_sms_template_rem_2", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_3"     => messagingsms_get_options("messagingsms_automation_sms_template_rem_3", $this->get_option_id()),
            "messagingsms_automation_sms_template_custom"    => messagingsms_get_options("messagingsms_automation_sms_template_custom", $this->get_option_id()),
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
                'ID',
                'name',
                'date',
                'party',
                'email',
                'phone',
                'post_status',
            ),
            'messagingsms' => array(
                'reminder_custom_time',
            ),
        );

    }

    private function schedule_reminders($booking, $status) {
        $send_sms_reminder_flag = true;
        $settings = $this->get_plugin_settings();

        // do our reminder stuff
        $as_group = self::$plugin_identifier . "_" . $booking->ID;
        $format = 'Y-m-d H:i:s T';
        // UTC booking date
        $booking_date = $booking->date;

        // Direct convert to local timezone
        $local_booking_date = DateTime::createFromFormat('Y-m-d H:i:s', $booking_date, wp_timezone());
        $reminder_booking_date_15 = DateTime::createFromFormat('Y-m-d H:i:s', $booking_date, wp_timezone());
        $reminder_booking_date_30 = DateTime::createFromFormat('Y-m-d H:i:s', $booking_date, wp_timezone());
        $reminder_booking_date_60 = DateTime::createFromFormat('Y-m-d H:i:s', $booking_date, wp_timezone());

        // current local time
        $current_time = date_i18n('Y-m-d H:i:s O');
        $now_date = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->format($format);
        $now_timestamp = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->getTimestamp();
        // $now_timestamp = strtotime("+1 minute", $now_timestamp);

        $this->log->add(ZMPLGXX_NAME, "Booking date: {$booking_date}");
        $this->log->add(ZMPLGXX_NAME, "Current Local Date: {$now_date}");
        $this->log->add(ZMPLGXX_NAME, "Current Local Timestamp: {$now_timestamp}");
        $this->log->add(ZMPLGXX_NAME, "Booking date to Local time: {$local_booking_date->format($format)}");

        $custom_reminder_time = $settings['messagingsms_automation_reminder_custom_time'];
        if(!ctype_digit($custom_reminder_time)) {
            $this->log->add(ZMPLGXX_NAME, "reminder time (in minutes) is not digit");
            $send_sms_reminder_flag = false;
        }

        $reminder_date_15 = $reminder_booking_date_15->modify("-15 minutes")->getTimestamp();
        $reminder_date_30 = $reminder_booking_date_30->modify("-30 minutes")->getTimestamp();
        $reminder_date_60 = $reminder_booking_date_60->modify("-60 minutes")->getTimestamp();

        $this->log->add(ZMPLGXX_NAME, "15 mins Reminder timestamp: {$reminder_date_15}");
        $this->log->add(ZMPLGXX_NAME, "30 mins Reminder timestamp: {$reminder_date_30}");
        $this->log->add(ZMPLGXX_NAME, "60 mins Reminder timestamp: {$reminder_date_60}");

        $this->log->add(ZMPLGXX_NAME, "Unscheduling all Whatsapp reminders for Group: {$as_group}");
        as_unschedule_all_actions('', array(), $as_group);
        $action_id_15 = as_schedule_single_action($reminder_date_15, $this->hook_action, array($booking, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_30, $this->hook_action, array($booking, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_60, $this->hook_action, array($booking, 'rem_3'), $as_group );
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_60 = {$action_id_60}");

        if($send_sms_reminder_flag) {
            $reminder_date_custom = $local_booking_date->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add(ZMPLGXX_NAME, "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($booking, 'custom'), $as_group );
            $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($booking, $status)
    {
        $booking = (object) $booking;
        $this->log->add(ZMPLGXX_NAME, "Booking status: {$booking->post_status}");
        $this->log->add(ZMPLGXX_NAME, "Status: {$status}");
        if($booking->post_status !== 'confirmed') { return; }
        $settings = $this->get_plugin_settings();

        $enable_notifications = $settings['messagingsms_automation_enable_notification'];
        $reminder = $settings['messagingsms_automation_reminder'];

        $this->log->add(ZMPLGXX_NAME, "Successfully retrieved plugin settings");

        if($enable_notifications === "on"){
            $this->log->add(ZMPLGXX_NAME, "enable_notifications: {$enable_notifications}");
            if(!empty($reminder) && is_array($reminder)) {
                if(array_key_exists($status, $reminder)) {
                    $this->log->add(ZMPLGXX_NAME, "Sending reminder now");
                    $this->send_customer_notification($booking, $status);
                }
            }
        }
    }

    public function send_sms_on($booking)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];

        $status = $booking->post_status;

        if($enable_notifications === "on"){
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $function_to_be_called = "send_sms_on_status_{$status}";
                    $this->$function_to_be_called($booking);
                }
            }
        }

        return $booking;
    }

    public function send_sms_on_status_pending($booking) {
        $as_group = self::$plugin_identifier . "_" . $booking->ID;
        as_unschedule_all_actions('', array(), $as_group);
		$this->send_customer_notification( $booking, "pending" );
	}

    public function send_sms_on_status_confirmed($booking) {
        $status = 'confirmed';
        $this->schedule_reminders($booking, $status);
		$this->send_customer_notification( $booking, $status );
	}

    public function send_sms_on_status_closed($booking) {
        $as_group = "{$this::$plugin_identifier}_{$booking->ID}";
        as_unschedule_all_actions('', array(), $as_group);
		$this->send_customer_notification( $booking, "closed" );
	}

    public function send_customer_notification($booking, $status)
    {
        $settings = $this->get_plugin_settings();

        // get number from booking
        $phone_no = $booking->phone;
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
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if($keywords) {
            foreach($keywords[1] as $keyword) {
                if(property_exists($booking, $keyword)) {
                    $message = str_replace("[{$keyword}]", $booking->$keyword, $message);
                }
                else if($keyword == 'reminder_custom_time') {
                    $settings = $this->get_plugin_settings();
                    $reminder_time = $settings['messagingsms_automation_reminder_custom_time'];
                    $message = str_replace("[{$keyword}]", $this->seconds_to_days($reminder_time), $message);
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

    private function seconds_to_days($seconds) {

        if(!ctype_digit($seconds)) {
            $this->log->add(ZMPLGXX_NAME, 'seconds_to_days: $seconds is not a valid digit');
            return '';
        }

        $ret = "";

        $days = intval(intval($seconds) / (3600*24));
        if($days> 0)
        {
            $ret .= "{$days}";
        }

        return $ret;
    }


}
