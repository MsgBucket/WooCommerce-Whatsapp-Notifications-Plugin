<?php

class MessagingMemberPress implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: MemberPress
    Plugin Link: https://memberpress.com/
    */

    public static $plugin_identifier = 'memberpress';
    private $plugin_name;
    private $hook_action;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'MemberPress';
        $this->hook_action = "messagingsms_send_reminder_{$this::$plugin_identifier}";
    }

    public static function plugin_activated()
    {
        if (is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier))) {
            $log = new Messagingsms_WooCoommerce_Logger();
            try {
                require_once MEPR_MODELS_PATH . "/MeprSubscription.php";
            } catch (Exception $e) {
                $log->add(ZMPLGXX_NAME, "Failed to import MeprSubscription.php");
                $log->add(ZMPLGXX_NAME, "MEPR_MODELS_PATH defined: " . defined('MEPR_MODELS_PATH'));
                $log->add(ZMPLGXX_NAME, print_r($e, true));
            } finally {
                return true;
            }
        }
        return false;
    }

    public function register()
    {
        add_action( 'mepr-txn-transition-status',              array($this, 'messagingsms_mepr_notifications_transaction_transition'), 10, 3);
        add_action( 'mepr_subscription_transition_status',     array($this, 'messagingsms_mepr_notifications_subscription_transition'), 10, 3);
        add_action( 'mepr-event-transaction-completed',        array($this, 'messagingsms_mepr_notifications_transaction_completed'));
        add_action( 'mepr-event-transaction-expired',          array($this, 'messagingsms_mepr_notifications_transaction_expired'));
        add_action( 'mepr-event-transaction-refunded',         array($this, 'messagingsms_mepr_notifications_transaction_refunded'));
        add_action( 'mepr-event-recurring-transaction-failed', array($this, 'messagingsms_mepr_notifications_transaction_failed'));
        add_action( 'mepr-event-subscription-created',         array($this, 'messagingsms_mepr_notifications_subscription_created'));
        add_action( 'mepr-event-subscription-paused',          array($this, 'messagingsms_mepr_notifications_subscription_paused'));
        add_action( 'mepr-event-subscription-resumed',         array($this, 'messagingsms_mepr_notifications_subscription_resumed'));
        add_action( 'mepr-event-subscription-stopped',         array($this, 'messagingsms_mepr_notifications_subscription_stopped'));
        add_action( $this->hook_action,                        array($this, 'send_sms_reminder'), 10, 5);

    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public function get_statuses()
    {
        return array(
            'transaction_completed',
            'transaction_expired',
            'transaction_pending',
            'transaction_failed',
            'transaction_refunded',
            'subscription_paused',
            'subscription_resumed',
            'subscription_stopped',
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
                'transaction_completed' => 'Transaction completed',
                'transaction_expired'   => 'Transaction expired',
                'transaction_pending'   => 'Transaction pending',
                'transaction_failed'    => 'Transaction failed',
                'transaction_refunded'  => 'Transaction refunded',
                'subscription_paused'   => 'Subscription paused',
                'subscription_resumed'  => 'Subscription resumed',
                'subscription_stopped'  => 'Subscription stopped',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_transaction_completed',
                'label'   => __( 'Transaction completed Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_transaction_completed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for your purchase of [membership_post_title] at [trans_total]', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_transaction_expired',
                'label'   => __( 'Transaction expired Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_transaction_expired]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your recurring transaction of [trans_total] has expired', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_transaction_pending',
                'label'   => __( 'Transaction pending Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_transaction_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your transaction [trans_id] is pending', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_transaction_failed',
                'label'   => __( 'Transaction failed Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_transaction_failed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your recurring transaction of [trans_total] has failed', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_transaction_refunded',
                'label'   => __( 'Transaction refunded Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_transaction_refunded]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], we are sorry that you are not satisfied with our services, your payment of [trans_total] has been refunded', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_subscription_paused',
                'label'   => __( 'Subscription paused Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_subscription_paused]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription has been paused', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_subscription_resumed',
                'label'   => __( 'Subscription resumed Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_subscription_resumed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription has been resumed', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_subscription_stopped',
                'label'   => __( 'Subscription stopped Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_subscription_stopped]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription has stopped', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    private function get_reminder_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_reminder',
                'label'   => __( 'Send reminder to renew active subscription', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => __( '', MESSAGINGSMS_TEXT_DOMAIN ),
                'type'    => 'multicheck',
                'options' => array(
                    'rem_1'  => '1 day before subscription expiry',
                    'rem_2'  => '2 days before subscription expiry',
                    'rem_3'  => '3 days before subscription expiry',
                    'custom' => 'Custom time before subscription expiry',
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
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription will expire in 1 Day, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_2',
                'label'   => __( '2 days reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription will expire in 2 Days, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_rem_3',
                'label'   => __( '3 days reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription will expire in 3 Days, renew now to keep access.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[messagingsms_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_post_title] subscription will expire in [reminder_custom_time] Days, renew now to keep access. - custom', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "messagingsms_automation_enable_notification"                => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                            => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_reminder"                           => messagingsms_get_options("messagingsms_automation_reminder", $this->get_option_id()),
            "messagingsms_automation_reminder_custom_time"               => messagingsms_get_options("messagingsms_automation_reminder_custom_time", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_1"                 => messagingsms_get_options("messagingsms_automation_sms_template_rem_1", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_2"                 => messagingsms_get_options("messagingsms_automation_sms_template_rem_2", $this->get_option_id()),
            "messagingsms_automation_sms_template_rem_3"                 => messagingsms_get_options("messagingsms_automation_sms_template_rem_3", $this->get_option_id()),
            "messagingsms_automation_sms_template_custom"                => messagingsms_get_options("messagingsms_automation_sms_template_custom", $this->get_option_id()),
            "messagingsms_automation_sms_template_transaction_completed" => messagingsms_get_options("messagingsms_automation_sms_template_transaction_completed", $this->get_option_id()),
            "messagingsms_automation_sms_template_transaction_expired"   => messagingsms_get_options("messagingsms_automation_sms_template_transaction_expired", $this->get_option_id()),
            "messagingsms_automation_sms_template_transaction_pending"   => messagingsms_get_options("messagingsms_automation_sms_template_transaction_pending", $this->get_option_id()),
            "messagingsms_automation_sms_template_transaction_failed"    => messagingsms_get_options("messagingsms_automation_sms_template_transaction_failed", $this->get_option_id()),
            "messagingsms_automation_sms_template_transaction_refunded"  => messagingsms_get_options("messagingsms_automation_sms_template_transaction_refunded", $this->get_option_id()),
            "messagingsms_automation_sms_template_subscription_paused"   => messagingsms_get_options("messagingsms_automation_sms_template_subscription_paused", $this->get_option_id()),
            "messagingsms_automation_sms_template_subscription_resumed"  => messagingsms_get_options("messagingsms_automation_sms_template_subscription_resumed", $this->get_option_id()),
            "messagingsms_automation_sms_template_subscription_stopped"  => messagingsms_get_options("messagingsms_automation_sms_template_subscription_stopped", $this->get_option_id()),
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
            'user' => array(
                'email',
                'first_name',
                'last_name',
                'phone',
                'country',
            ),
            'subscription' => array(
                'sub_subscr_id',
                'sub_gateway',
                'sub_coupon_id',
                'sub_price',
                'sub_period',
                'sub_period_type',
                'sub_trial_days',
                'sub_trial_amount',
                'sub_trial_tax_amount',
                'sub_trial_total',
                'sub_status',
                'sub_created_at',
                'sub_total',
                'sub_tax_rate',
                'sub_tax_amount',
                'sub_tax_desc',
                'sub_tax_class',
            ),
            'transaction' => array(
                'trans_id',
                'trans_amount',
                'trans_total',
                'trans_tax_amount',
                'trans_tax_rate',
                'trans_tax_desc',
                'trans_tax_class',
                'trans_coupon_id',
                'trans_trans_num',
                'trans_status',
                'trans_txn_type',
                'trans_created_at',
            ),
            'membership' => array(
                'membership_post_title',
                'membership_post_desc',
            ),
            'messagingsms' => array(
                'reminder_custom_time',
            ),
        );

    }

    public function messagingsms_mepr_notifications_transaction_completed($event) {
        $this->send_sms_on($event, $status='transaction_completed');
    }
    public function messagingsms_mepr_notifications_transaction_expired($event) {
        $this->send_sms_on($event, $status='transaction_expired');
    }
    public function messagingsms_mepr_notifications_transaction_refunded($event) {
        $this->send_sms_on($event, $status='transaction_refunded');
    }
    public function messagingsms_mepr_notifications_transaction_failed($event) {
        $this->send_sms_on($event, $status='transaction_failed');
    }

    public function messagingsms_mepr_notifications_transaction_transition($old_status, $new_status, $txv) {
        if($old_status === $new_status) { return; }
        $this->send_sms_on($txv, $status='transaction_transition');
    }

    public function messagingsms_mepr_notifications_subscription_transition($old_status, $new_status, $sub) {
        if($old_status === $new_status) { return; }
        $this->send_sms_on($sub, $status='subscription_transition');
    }

    public function messagingsms_mepr_notifications_subscription_created($event) {
        $this->send_sms_on($event, $status='subscription_created');
    }
    public function messagingsms_mepr_notifications_subscription_paused($event) {
        $this->send_sms_on($event, $status='subscription_paused');
    }
    public function messagingsms_mepr_notifications_subscription_resumed($event) {
        $this->send_sms_on($event, $status='subscription_resumed');
    }
    public function messagingsms_mepr_notifications_subscription_stopped($event) {
        $this->send_sms_on($event, $status='subscription_stopped');
    }

    private function schedule_reminders($user, $transaction, $subscription, $product, $status) {
        $send_custom_reminder_flag = true;
        $settings = $this->get_plugin_settings();
        $this->log->add(ZMPLGXX_NAME, "schedule_reminders: successfully retrieved plugin settings");
        $this->log->add(ZMPLGXX_NAME, "User ID: {$user->ID}");
        $this->log->add(ZMPLGXX_NAME, "Subscription ID: {$subscription->id}");

        $membership_expiry_timestamp = $subscription->get_expires_at(strtotime($subscription->created_at));

        if(empty($membership_expiry_timestamp) || is_null($membership_expiry_timestamp)) {
            // maybe is lifetime account
            $this->log->add(ZMPLGXX_NAME, "membership expiry date is empty or null");
            return;
        }

        // do our reminder stuff
        $as_group = "{$this::$plugin_identifier}_{$user->ID}";
        $format = get_option("date_format");
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
        $subscription = (array) $subscription->rec;
        $action_id_15 = as_schedule_single_action($reminder_date_1, $this->hook_action, array($user, $transaction, $subscription, $product, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_2, $this->hook_action, array($user, $transaction, $subscription, $product, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_3, $this->hook_action, array($user, $transaction, $subscription, $product, 'rem_3'), $as_group );
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_60 = {$action_id_60}");

        if($send_custom_reminder_flag) {
            $reminder_date_custom = $reminder_booking_date_custom->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add(ZMPLGXX_NAME, "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($user, $transaction, $subscription, $product, 'custom'), $as_group );
            $this->log->add(ZMPLGXX_NAME, "Send Whatsapp Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($user, $transaction, $subscription, $product, $status)
    {
        if(! $user instanceof WP_User) {
            $this->log->add(ZMPLGXX_NAME, '$user not an instance of WP_User');
            $user = new WP_User($user['ID']);
        }
        $this->log->add(ZMPLGXX_NAME, 'Converted $user to an instance of WP_User');

        if(! $subscription instanceof MeprSubscription) {
            $this->log->add(ZMPLGXX_NAME, '$subscription not an instance of MeprSubscription');
            $subscription = new MeprSubscription($subscription['id']);
        }
        $this->log->add(ZMPLGXX_NAME, 'Converted $subscription to an instance of MeprSubscription');

        $this->log->add(ZMPLGXX_NAME, "User ID: {$user->ID}");
        $this->log->add(ZMPLGXX_NAME, "send_sms_reminder subscription id: {$subscription->id}");
        $this->log->add(ZMPLGXX_NAME, "Status: {$status}");

        // membership already expired
        $membership_expiry_timestamp = $subscription->get_expires_at(strtotime($subscription->created_at));
        $now_timestamp = current_datetime()->getTimestamp();

        // membership already expired
        if($now_timestamp >= $membership_expiry_timestamp) {
            $this->log->add(ZMPLGXX_NAME, "membership expiry date is in the past");
            return;
        }

        // subscription not active
        if($subscription->status != 'active') {
            $this->log->add(ZMPLGXX_NAME, "Subscription is not active");
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
                    $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
                }
            }
        }
    }

    public function send_sms_on($event, $status)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];
        if($enable_notifications === "on"){
            if(!empty($send_on) && is_array($send_on)) {
                if($event instanceof MeprTransaction) {
                    $transaction = $event;
                    $user = new WP_User($transaction->user()->ID);
                    $subscription = $transaction->subscription();
                    $product = $transaction->product();
                    if($transaction->status === 'complete')
                        $status = "transaction_completed";
                    else if($transaction->status === 'pending')
                        $status = "transaction_pending";
                    else if($transaction->status === 'failed')
                        $status = "transaction_failed";
                    else if($transaction->status === 'refunded')
                        $status = "transaction_refunded";

                    if( ! array_key_exists($status, $send_on)) { return false; }

                    return $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
                }

                else if($event instanceof MeprSubscription) {
                    $transaction = '';
                    $subscription = $event;
                    $user = new WP_User($subscription->user()->ID);
                    $product = $subscription->product();
                    $as_group = "{$this::$plugin_identifier}_{$user->ID}";
                    if($subscription->status === 'active') {
                        $this->schedule_reminders($user, $transaction, $subscription, $product, $status);
                        $status = "subscription_resumed";
                    }
                    else if($subscription->status === 'suspended') {
                        as_unschedule_all_actions('', array(), $as_group);
                        $status = "subscription_paused";
                    }
                    else if($subscription->status === 'cancelled') {
                        as_unschedule_all_actions('', array(), $as_group);
                        $status = "subscription_stopped";
                    }

                    if( ! array_key_exists($status, $send_on)) { return false; }

                    return $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
                }

                else {
                    $this->log->add(ZMPLGXX_NAME, '$event is neither an instance of MeprSubscription or MeprTransaction');
                    $this->log->add(ZMPLGXX_NAME, '$event object: ' . print_r($event, true));
                    return;
                }

                // if notification is on
                $func_to_call = "send_sms_on_status_{$status}";
                return $this->$func_to_call($event, $status);
            }
        }

        return false;

    }

    public function send_sms_on_status_transaction_completed($event, $status) {
        $transaction = $event->get_data();
        $subscription = $transaction->subscription();
        $user = new WP_User($transaction->user()->ID);
        $product = $transaction->product();
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_transaction_expired($event, $status) {
        $transaction = $event->get_data();
        $subscription = $transaction->subscription(); //This may return false if it's a one-time transaction that has expired
        $user = new WP_User($transaction->user()->ID);
        $product = $transaction->product();
        if($user->is_already_subscribed_to($transaction->product_id))
            return false;
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_transaction_failed($event, $status) {
        $transaction = $event->get_data();
        $subscription = $transaction->subscription(); //This may return false if it's a one-time transaction that has expired
        $user = new WP_User($transaction->user()->ID);
        $product = array();
        if($user->is_already_subscribed_to($transaction->product_id))
            return false;
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_transaction_refunded($event, $status) {
        $transaction = $event->get_data();
        $subscription = $transaction->subscription();
        $user = new WP_User($transaction->user()->ID);
        $product = $transaction->product();
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_subscription_created($event, $status) {
        $transaction = '';
        $subscription = $event->get_data();
        $user = new WP_User($subscription->user()->ID);
        $product = $subscription->product();
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_subscription_paused($event, $status) {
        $transaction = '';
        $subscription = $event->get_data();
        $user = new WP_User($subscription->user()->ID);
        $product = $subscription->product();
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_subscription_resumed($event, $status) {
        $transaction = '';
        $subscription = $event->get_data();
        $user = new WP_User($subscription->user()->ID);
        $product = $subscription->product();

        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_sms_on_status_subscription_stopped($event, $status) {
        $transaction = '';
        $subscription = $event->get_data();
        $user = new WP_User($subscription->user()->ID);
        $product = $subscription->product();
        $this->send_customer_notification($user, $transaction, $subscription, $product, $status);
    }

    public function send_customer_notification($user, $transaction, $subscription, $product, $status)
    {
        $settings = $this->get_plugin_settings();

        // get number from user
        $validated_user = MessagingSMS_SendSMS_Sms::getValidatedPhoneNumbers($user);
        if(empty($validated_user)) {
            return false;
        }

        $phone_no = $validated_user->phone;

        // get message template from status
        $msg_template = $settings["messagingsms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($user, $transaction, $subscription, $product, $msg_template);

        MessagingSMS_SendSMS_Sms::send_sms($phone_no, $message);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'John Doe'
    */
    protected function replace_keywords_with_value($user, $transaction, $subscription, $product, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if(!empty($keywords)) {
            foreach($keywords[1] as $keyword) {
                if($user->has_prop($keyword)) {
                    $message = str_replace("[{$keyword}]", $user->$keyword, $message);
                }

                else if (!empty($transaction)
                        && $transaction instanceof MeprTransaction
                        && substr($keyword, 0, strlen('trans_')) === 'trans_') {
                    // from trans_tax_id to tax_id
                    $trimmed_keyword = str_replace('trans_', '', $keyword);
                    if(property_exists($transaction->rec, $trimmed_keyword))
                        $message = str_replace("[{$keyword}]", $transaction->rec->$trimmed_keyword, $message);
                    else
                        $message = str_replace("[{$keyword}]", "", $message);
                }

                else if(!empty($subscription)
                        && $subscription instanceof MeprSubscription
                        && substr($keyword, 0, strlen('sub_')) === 'sub_') {
                        $trimmed_keyword = str_replace('sub_', '', $keyword);
                        if(property_exists($subscription->rec, $trimmed_keyword))
                            $message = str_replace("[{$keyword}]", $subscription->rec->$trimmed_keyword, $message);
                        else
                            $message = str_replace("[{$keyword}]", "", $message);
                }

                else if(!empty($product)
                        && $product instanceof MeprProduct
                        && substr($keyword, 0, strlen('membership_')) === 'membership_') {
                        $trimmed_keyword = str_replace('membership_', '', $keyword);
                        if(property_exists($product->rec, $trimmed_keyword))
                            $message = str_replace("[{$keyword}]", $product->rec->$trimmed_keyword, $message);
                        else
                            $message = str_replace("[{$keyword}]", "", $message);
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
