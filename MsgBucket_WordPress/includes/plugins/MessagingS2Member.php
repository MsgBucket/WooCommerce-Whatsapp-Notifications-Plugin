<?php

class MessagingS2Member implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: s2Member® Framework (Membership, Member Level Roles, Access Capabilities, PayPal Members)
    Plugin Link: https://wordpress.org/plugins/s2member/
    */

    public static $plugin_identifier = 's2member';
    private $option_id;
    private $plugin_name;
    private $hook_action;
    private $log;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'S2 Member';
        $this->hook_action = "messagingsms_send_reminder_{$this::$plugin_identifier}";
    }

    public function register()
    {
        add_action( 'init', array($this, 'send_sms_on') );
        add_action( $this->hook_action, array($this, 'send_sms_reminder'), 10, 3);
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public static function plugin_activated()
    {
        // special case for S2Member plugin
        // must check their relevant database for Notifications API URLs (won't work without this)
        if(! is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier)))
            return false;

        // if plugin is active

        return self::validate_notification_api_urls();
    }

    public static function validate_notification_api_urls()
    {
        $notification_urls = array(
            'signup_notification_urls' => self::build_notification_url('subscription'),
            // 'registration_notification_urls' => 'Registration Notification URL',
            'payment_notification_urls' => self::build_notification_url('payment'),
            'modification_notification_urls' => self::build_notification_url('modification'),
            // 'cancellation_notification_urls' => 'Cancellation Notification URL',
            'eot_del_notification_urls' => self::build_notification_url('end_of_term'),
            'ref_rev_notification_urls' => self::build_notification_url('refund_or_reversal'),

        );
        $s2_option = get_option('ws_plugin__s2member_options');
        foreach( $notification_urls as $key => $url ) {
            if(empty($s2_option[$key]))
                $s2_option[$key] = $url;
            else {
                $existing_urls = explode(PHP_EOL, $s2_option[$key]);
                if(! in_array($url, array_values($existing_urls))){
                    $existing_urls[] = $url;
                    $s2_option[$key] = implode(PHP_EOL, $existing_urls);
                }
            }
        }
        update_option('ws_plugin__s2member_options', $s2_option);

        return true;
    }

    private static function build_notification_url($notif_identifier)
    {
        $domain = $_SERVER['HTTP_HOST'];

        $url = "https://{$domain}/?";
        $url = "http://{$domain}/?";

        $url .= urldecode(http_build_query(self::__url_params($notif_identifier)));

        return $url;
    }

    private static function __url_params($notif_identifier, $only_keys = false)
    {
        $signup_notification_params = array(
            's2_signup_notification'    => 'yes',
            'payer_email'               => '%%payer_email%%',
            'subscr_id'                 => '%%subscr_id%%',
            'currency'                  => '%%currency%%',
            'currency_symbol'           => '%%currency_symbol%%',
            'initial'                   => '%%initial%%',
            'regular'                   => '%%regular%%',
            'recurring'                 => '%%recurring%%',
            'user_ip'                   => '%%user_ip%%',
            'item_number'               => '%%item_number%%',
            'item_name'                 => '%%item_name%%',
            'initial_term'              => '%%initial_term%%',
            'regular_term'              => '%%regular_term%%',
        );

        $payment_notification_params = array(
            's2_payment_notification'   => 'yes',
            'user_id'                   => '%%user_id%%',
            'subscr_id'                 => '%%subscr_id%%',
            'txn_id'                    => '%%txn_id%%',
            'currency'                  => '%%currency%%',
            'currency_symbol'           => '%%currency_symbol%%',
            'amount'                    => '%%amount%%',
            'user_ip'                   => '%%user_ip%%',
            'item_number'               => '%%item_number%%',
            'item_name'                 => '%%item_name%%',
        );

        $modification_notification_params = array(
            's2_modification_notification'  => 'yes',
            'user_id'                       => '%%user_id%%',
            'subscr_id'                     => '%%subscr_id%%',
            'currency'                      => '%%currency%%',
            'currency_symbol'               => '%%currency_symbol%%',
            'initial'                       => '%%initial%%',
            'regular'                       => '%%regular%%',
            'recurring'                     => '%%recurring%%',
            'item_number'                   => '%%item_number%%',
            'item_name'                     => '%%item_name%%',
            'initial_term'                  => '%%initial_term%%',
            'regular_term'                  => '%%regular_term%%',
            'user_ip'                       => '%%user_ip%%',
        );

        $eot_notification_params = array(
            's2_eot_notification'   => 'yes',
            'eot_del_type'          => '%%eot_del_type%%',
            'user_id'               => '%%user_id%%',
            'subscr_id'             => '%%subscr_id%%',
            'user_ip'               => '%%user_ip%%',
        );

        $ror_notification_params = array(
            's2_ror_notification'   => 'yes',
            'user_id'               => '%%user_id%%',
            'subscr_id'             => '%%subscr_id%%',
            'currency'              => '%%currency%%',
            'currency_symbol'       => '%%currency_symbol%%',
            '-amount'               => '%%-amount%%',
            '-fee'                  => '%%-fee%%',
            'payer_email'           => '%%payer_email%%',
            'item_number'           => '%%item_number%%',
            'item_name'             => '%%item_name%%',
            'user_ip'               => '%%user_ip%%',
        );

        $notification_urls = array(
            'subscription' => $signup_notification_params,
            'payment' => $payment_notification_params,
            'modification' => $modification_notification_params,
            'end_of_term' => $eot_notification_params,
            'refund_or_reversal' => $ror_notification_params,
        );

        if(! array_key_exists($notif_identifier, $notification_urls)) { throw new Exception("Invalid Notification Type - S2Member"); }

        if($only_keys) { return array_keys($notification_urls[$notif_identifier]); }

        return $notification_urls[$notif_identifier];

    }

    public function get_statuses()
    {
        return array(
            'subscription',
            'payment',
            'modification',
            'end_of_term',
            'refund_or_reversal',
        );

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
        foreach($this->get_sms_reminder_template_fields() as $sms_reminder) {
            $setting_fields[] = $sms_reminder;
        }
        foreach($this->get_sms_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
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
                'subscription'       => 'New subscription',
                'payment'            => 'Payment received',
                'modification'       => 'Payment modification',
                'end_of_term'        => 'End of term',
                'refund_or_reversal' => 'Refund or reversal',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_subscription',
                'label'   => __( 'Subscription Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="subscription" data-attr-target="%1$s[messagingsms_automation_sms_template_subscription]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for your subscription of [s2member_access_label]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_payment',
                'label'   => __( 'Payment received Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="payment" data-attr-target="%1$s[messagingsms_automation_sms_template_payment]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for your purchase of [s2member_access_label], [currency_symbol][regular] has been deducted from your account.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_modification',
                'label'   => __( 'Payment modification Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="modification" data-attr-target="%1$s[messagingsms_automation_sms_template_modification]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your membership has been modified, current membership is [s2member_access_label]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_end_of_term',
                'label'   => __( 'End of term Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="end_of_term" data-attr-target="%1$s[messagingsms_automation_sms_template_end_of_term]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], [s2member_access_label] has expired', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_refund_or_reversal',
                'label'   => __( 'Refund or reversal Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="refund_or_reversal" data-attr-target="%1$s[messagingsms_automation_sms_template_refund_or_reversal]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your payment of [currency_symbol][-amount] for [s2member_access_label] has been refunded.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    private function get_reminder_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_reminder',
                'label'   => __( 'Send reminder to renew membership', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => __( '', MESSAGINGSMS_TEXT_DOMAIN ),
                'type'    => 'multicheck',
                'options' => array(
                    'rem_1'    => '1 day before membership expires',
                    'rem_2'    => '2 days before membership expires',
                    'rem_3'    => '3 days before membership expires',
                    'custom'    => 'Custom time before membership expires',
                )
            ),
            array(
                'name'  => 'messagingsms_automation_reminder_custom_time',
                'label' => __( '', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'  => __( 'Enter the custom time you want to remind your customer before membership expires in (minutes) <br> Choose when to send a Whatsapp reminder message to your customer <br> Please set your timezone in <a href="' . admin_url('options-general.php') . '">settings</a> <br> You must setup cronjob <a href="https://github.com/MessagingAPI/wordpress">here</a> ', MESSAGINGSMS_TEXT_DOMAIN ),
                'type'  => 'number',
            ),
        );
    }

    private function get_sms_reminder_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_1',
                'label'   => __( '1 day reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_1]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [s2member_access_label] will expire in 1 Day, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_2',
                'label'   => __( '2 days reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [s2member_access_label] will expire in 2 Days, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_3',
                'label'   => __( '3 days reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [s2member_access_label] will expire in 3 Days, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [s2member_access_label] will expire in [reminder_custom_time] Days, renew now to keep access. - custom', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "messagingsms_automation_enable_notification"             => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                         => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_reminder"                        => messagingsms_get_options("messagingsms_automation_reminder", $this->get_option_id()),
            "messagingsms_automation_reminder_custom_time"            => messagingsms_get_options("messagingsms_automation_reminder_custom_time", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_1"              => messagingsms_get_options("messagingsms_automation_sms_template_rem_1", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_2"              => messagingsms_get_options("messagingsms_automation_sms_template_rem_2", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_3"              => messagingsms_get_options("messagingsms_automation_sms_template_rem_3", $this->get_option_id()),
            "messagingsms_automation_sms_template_custom"             => messagingsms_get_options("messagingsms_automation_sms_template_custom", $this->get_option_id()),
            "messagingsms_automation_sms_template_subscription"       => messagingsms_get_options("messagingsms_automation_sms_template_subscription", $this->get_option_id()),
            "messagingsms_automation_sms_template_payment"            => messagingsms_get_options("messagingsms_automation_sms_template_payment", $this->get_option_id()),
            "messagingsms_automation_sms_template_modification"       => messagingsms_get_options("messagingsms_automation_sms_template_modification", $this->get_option_id()),
            "messagingsms_automation_sms_template_end_of_term"        => messagingsms_get_options("messagingsms_automation_sms_template_end_of_term", $this->get_option_id()),
            "messagingsms_automation_sms_template_refund_or_reversal" => messagingsms_get_options("messagingsms_automation_sms_template_refund_or_reversal", $this->get_option_id()),
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

        if(function_exists('get_s2member_custom_fields'))
            $custom_fields = array_keys(get_s2member_custom_fields());
        $keywords = array(
            'user' => array(
                'email',
                'first_name',
                'last_name',
                'phone',
                'country',
            ),
            's2_member' => array(
                's2member_custom',
                's2member_subscr_id',
                's2member_subscr_gateway',
                's2member_access_label',
            ),
            'messagingsms' => array(
                'reminder_custom_time',
            ),
        );

        // if(in_array('subscription', $this->get_statuses())) {
        //     $keywords['s2_subscription'] = self::__url_params('subscription', true);
        // }
        // if(in_array('payment', $this->get_statuses())) {
        //     $keywords['s2_payment'] = self::__url_params('payment', true);
        // }
        // if(in_array('modification', $this->get_statuses())) {
        //     $keywords['s2_modification'] = self::__url_params('modification', true);
        // }
        // if(in_array('end_of_term', $this->get_statuses())) {
        //     $keywords['s2_end_of_term'] = self::__url_params('end_of_term', true);
        // }
        // if(in_array('refund_or_reversal', $this->get_statuses())) {
        //     $keywords['s2_refund_or_reversal'] = self::__url_params('refund_or_reversal', true);
        // }

        if(!empty($custom_fields)) {
            $keywords['your_s2_custom_fields'] = $custom_fields;
        }

        return $keywords;
    }

    private function schedule_reminders($user, $params) {
        $send_custom_reminder_flag = true;
        $settings = $this->get_plugin_settings();
        $this->log->add(ZMPLGXX_NAME, "schedule_reminders: successfully retrieved plugin settings");
        // do our reminder stuff
        $as_group = "{$this::$plugin_identifier}_{$user->ID}";
        $format = get_option("date_format");
        $s2_eot = s2member_eot($user->ID);
        $membership_expiry_timestamp = $s2_eot['time'];
        $membership_expiry_date = date_i18n($format, $membership_expiry_timestamp);

        // Create date from timestamp
        $reminder_booking_date_1 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_1->setTimezone(wp_timezone());

        $reminder_booking_date_2 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_2->setTimezone(wp_timezone());

        $reminder_booking_date_3 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_3->setTimezone(wp_timezone());

        $reminder_booking_date_custom = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_custom->setTimezone(wp_timezone());

        // current local time
        $current_time = date_i18n('Y-m-d H:i:s O');
        $now_date = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->format($format);
        $now_timestamp = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->getTimestamp();
        // $now_timestamp = strtotime("+1 minute", $now_timestamp);

        $this->log->add(ZMPLGXX_NAME, "Membership expiry date: {$membership_expiry_date}");
        $this->log->add(ZMPLGXX_NAME, "Current Local Date: {$now_date}");
        $this->log->add(ZMPLGXX_NAME, "Current Local Timestamp: {$now_timestamp}");

        $custom_reminder_time = $settings['messagingsms_automation_reminder_custom_time'];
        if(!ctype_digit($custom_reminder_time)) {
            $this->log->add(ZMPLGXX_NAME, "reminder time (in minutes) is not digit");
            $send_custom_reminder_flag = false;
        }

        $reminder_date_1 = $reminder_booking_date_1->modify("-1 day")->getTimestamp();
        $reminder_date_2 = $reminder_booking_date_2->modify("-2 days")->getTimestamp();
        $reminder_date_3 = $reminder_booking_date_3->modify("-3 days")->getTimestamp();

        $this->log->add(ZMPLGXX_NAME, "1 Day Reminder timestamp: {$reminder_date_1}");
        $this->log->add(ZMPLGXX_NAME, "2 Days Reminder timestamp: {$reminder_date_2}");
        $this->log->add(ZMPLGXX_NAME, "3 Days Reminder timestamp: {$reminder_date_3}");


        $this->log->add(ZMPLGXX_NAME, "Unscheduling all Whatsapp reminders for Group: {$as_group}");
        as_unschedule_all_actions('', array(), $as_group);
        $action_id_15 = as_schedule_single_action($reminder_date_1, $this->hook_action, array($user, $params, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_2, $this->hook_action, array($user, $params, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_3, $this->hook_action, array($user, $params, 'rem_3'), $as_group );
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_60 = {$action_id_60}");


        if($send_custom_reminder_flag) {
            $reminder_date_custom = $reminder_booking_date_custom->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add(ZMPLGXX_NAME, "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($user, $params, 'custom'), $as_group );
            $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($user, $params, $status)
    {
        if(! $user instanceof WP_User) {
            $this->log->add(ZMPLGXX_NAME, '$user not an instance of WP_User');
            $user = new WP_User($user['ID']);
        }
        $this->log->add(ZMPLGXX_NAME, 'Converted $user to an instance of WP_User');

        $this->log->add(ZMPLGXX_NAME, "User ID: {$user->ID}");
        $this->log->add(ZMPLGXX_NAME, "send_sms_reminder params: " . print_r($params, true));
        $this->log->add(ZMPLGXX_NAME, "Status: {$status}");

        // membership already expired
        if(s2member_eot($user->ID)['tense'] !== 'future') {
            $this->log->add(ZMPLGXX_NAME, "Membership already expire, exiting...");
            return;
        }

        $settings = $this->get_plugin_settings();

        $enable_notifications = $settings['messagingsms_automation_enable_notification'];
        $reminder = $settings['messagingsms_automation_reminder'];

        $this->log->add(ZMPLGXX_NAME, "Successfully retrieved plugin settings");

        if($enable_notifications === "on"){
            $this->log->add(ZMPLGXX_NAME, "enable_notifications: {$enable_notifications}");
            if(!empty($reminder) && is_array($reminder)) {
                if(array_key_exists($status, $reminder)) {
                    $this->log->add(ZMPLGXX_NAME, "Sending reminder now");
                    $this->send_customer_notification($user, $params, $status);
                }
            }
        }
    }

    public function send_sms_on()
    {
        $params = $_GET;
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];

        if($enable_notifications !== "on") { return false; }

        if(!empty($params['s2_signup_notification']) && $params['s2_signup_notification'] === 'yes') {
            if(!array_key_exists("subscription", $send_on) && empty($params['payer_email']))
                return false;

            $payer_email = sanitize_text_field($params['payer_email']);
            $user = get_user_by('email', $payer_email);
            if(empty($user))
                return false;
            $this->send_sms_on_status_subscription($user, $params);
        }

        if(!empty($params['s2_payment_notification']) && $params['s2_payment_notification'] === 'yes') {
            if( !array_key_exists("payment", $send_on)  && empty($params['user_id']))
                return false;
            $user_id = sanitize_text_field($params['user_id']);
            $user = new WP_User($user_id);
            if(empty($user))
                return false;
            $this->send_sms_on_status_payment($user, $params);
        }

        if(!empty($params['s2_eot_notification']) && $params['s2_eot_notification'] === 'yes') {
            if( ! array_key_exists("end_of_term", $send_on)  && empty($params['user_id']))
                return false;
            $user_id = sanitize_text_field($params['user_id']);
            $user = new WP_User($user_id);
            if(empty($user))
                return false;
            $this->send_sms_on_status_eot($user, $params);
        }

        if(!empty($params['s2_ror_notification']) && $params['s2_ror_notification'] === 'yes') {
            if( !array_key_exists("refund_or_reversal", $send_on)  && empty($params['user_id']))
                return false;
            $user_id = sanitize_text_field($params['user_id']);
            $user = new WP_User($user_id);
            if(empty($user))
                return false;
            $this->send_sms_on_status_ror($user, $params);
        }
    }

    public function send_sms_on_status_subscription($user, $params) {
        $this->schedule_reminders($user, $params);
        $this->send_customer_notification($user, $params, "subscription");
	}

    public function send_sms_on_status_payment($user, $params) {
        $this->schedule_reminders($user, $params);
        $this->send_customer_notification($user, $params, "payment" );
	}

    public function send_sms_on_status_eot($user, $params) {
        $as_group = "{$this::$plugin_identifier}_{$user->ID}";
        as_unschedule_all_actions('', array(), $as_group);
        $this->send_customer_notification($user, $params, "end_of_term" );
	}

    public function send_sms_on_status_ror($user, $params) {
        $as_group = "{$this::$plugin_identifier}_{$user->ID}";
        as_unschedule_all_actions('', array(), $as_group);
        $this->send_customer_notification($user, $params, "refund_or_reversal" );
	}

    public function send_customer_notification($user, $params, $status)
    {
        $this->log->add(ZMPLGXX_NAME, "send_cust_notification params: " . print_r($params, true));

        $settings = $this->get_plugin_settings();

        // get number from user
        $validated_user = MessagingSMS_SendSMS_Sms::getValidatedPhoneNumbers($user);
        if(empty($validated_user))
            return false;
        $phone_no = $validated_user->phone;

        // get message template from status
        $msg_template = $settings["messagingsms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($user, $params, $msg_template);

        MessagingSMS_SendSMS_Sms::send_sms($phone_no, $message);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($user, $params, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if(!empty($keywords)) {
            foreach($keywords[1] as $keyword) {
                if(get_user_field($keyword, $user->ID)) {
                    $message = str_replace("[{$keyword}]", get_user_field($keyword, $user->ID), $message);
                }
                else if(array_key_exists($keyword, $params)) {
                    // this is specific for refunds, S2Member will return a negative refunded value
                    if(is_numeric($params[$keyword]))
                        if($params[$keyword] < 0)
                            $params[$keyword] = abs($params[$keyword]);

                    $message = str_replace("[{$keyword}]", $params[$keyword], $message);
                }
                else if($keyword == 'reminder_custom_time') {
                    $settings = $this->get_plugin_settings();
                    $reminder_time = $settings['messagingsms_automation_reminder_custom_time'];
                    $message = str_replace("[{$keyword}]", $this->seconds_to_days($reminder_time), $message);
                }
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
