<?php

class MessagingFluentCRM implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: Email Marketing Automation, Newsletter and CRM Plugin for WordPress by FluentCRM
    Plugin Link: https://wordpress.org/plugins/fluent-crm/
    */

    public static $plugin_identifier = 'fluent-crm';
    private $plugin_name;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'Fluent CRM';
    }

    public static function plugin_activated()
    {
        $log = new Messagingsms_WooCoommerce_Logger();
        if( ! is_plugin_active(sprintf('%1$s/%1$s.php', self::$plugin_identifier ))) { return false; }
        return true;
    }

    public function register()
    {
        add_action( 'fluentcrm_subscriber_status_to_unsubscribed', array( $this, 'send_sms_on'), 10, 2);
        add_action( 'fluentcrm_subscriber_status_to_subscribed'  , array( $this, 'send_sms_on'), 10, 2);
        add_action( 'fluentcrm_subscriber_status_to_pending'     , array( $this, 'send_sms_on'), 10, 2);
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
        foreach($this->get_sms_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
        }
        return $setting_fields;
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "messagingsms_automation_enable_notification"        => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                    => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_sms_template_subscribed"    => messagingsms_get_options("messagingsms_automation_sms_template_subscribed", $this->get_option_id()),
            "messagingsms_automation_sms_template_unsubscribed"  => messagingsms_get_options("messagingsms_automation_sms_template_unsubscribed", $this->get_option_id()),
            "messagingsms_automation_sms_template_pending"       => messagingsms_get_options("messagingsms_automation_sms_template_pending", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
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
            'desc'    => __( 'Choose when to send a Whatsapp notification message', MESSAGINGSMS_TEXT_DOMAIN ),
            'type'    => 'multicheck',
            'options' => array(
                'subscribed'    => 'Subscribed',
                'unsubscribed'  => 'Unsubscribed',
                'pending'       => 'Pending',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_subscribed',
                'label'   => __( 'Subscribed status Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="customer" data-attr-target="%1$s[messagingsms_automation_sms_template_subscribed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for subscribing, trending contents will be delivered to you.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_unsubscribed',
                'label'   => __( 'Unsubscribed status Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="lead" data-attr-target="%1$s[messagingsms_automation_sms_template_unsubscribed]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], you have unsubscribed and will no longer receive trending contents.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_pending',
                'label'   => __( 'Pending status Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="refused" data-attr-target="%1$s[messagingsms_automation_sms_template_pending]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], would you like to opt-in to our newsletter and get trending contents delivered to you ? We promise we will not spam you.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_keywords_field()
    {
        return array(
            'customer' => array(
                'prefix',
                'first_name',
                'last_name',
                'email',
                'status',
                'contact_type',
                'address_line_1',
                'address_line_2',
                'postal_code',
                'city',
                'state',
                'country',
                'phone',
                'date_of_birth',
            ),
        );

    }

    public function send_sms_on($contact, $old_status)
    {
        if($old_status == $contact->status) {
            $this->log->add(ZMPLGXX_NAME, "old status and new status is the same, aborting.");
            return;
        }

        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];

        // status = pending / subscribed / bounced / unsubscribed / complained
        $status = $contact->status;

        $this->log->add(ZMPLGXX_NAME, "status: {$status}");

        if($enable_notifications === "on") {
            $this->log->add(ZMPLGXX_NAME, "enable notifications: on");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add(ZMPLGXX_NAME, "enable {$status} notifications: on");
                    $this->send_customer_notification($contact, $status);
                }
            }
        }

        return false;
    }

    public function send_customer_notification($contact, $status)
    {
        $this->log->add(ZMPLGXX_NAME, "send_customer_notification status: {$status}");
        $settings = $this->get_plugin_settings();

        // get number from args
        $phone_no = $contact->phone;
        if( !ctype_digit($phone_no) ) {
            $this->log->add(ZMPLGXX_NAME, "phone_no is not a digit: {$phone_no}. Aborting...");
            return;
        }
        if( !empty($contact->country) ) {
            $country = $contact->country;
            $phone_no = MessagingSMS_SendSMS_Sms::get_formatted_number($phone_no, $country);
        }

        $this->log->add(ZMPLGXX_NAME, "phone_no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["messagingsms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($contact, $msg_template);

        MessagingSMS_SendSMS_Sms::send_sms($phone_no, $message);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($contact, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        // preg_match_all('/\[(.*?)\]/', $message, $keywords);
        $messaging_setting = new Messagingsms_WooCommerce_Setting();

        $keywords = array(
            '[prefix]'         => !empty($contact->prefix) ? $contact->prefix : '',
            '[first_name]'     => !empty($contact->first_name) ? $contact->first_name : '',
            '[last_name]'      => !empty($contact->last_name) ? $contact->last_name : '',
            '[email]'          => !empty($contact->email) ? $contact->email : '',
            '[status]'         => !empty($contact->status) ? $contact->status : '',
            '[contact_type]'   => !empty($contact->contact_type) ? $contact->contact_type : '',
            '[address_line_1]' => !empty($contact->address_line_1) ? $contact->address_line_1 : '',
            '[address_line_2]' => !empty($contact->address_line_2) ? $contact->address_line_2 : '',
            '[postal_code]'    => !empty($contact->postal_code) ? $contact->postal_code : '',
            '[city]'           => !empty($contact->city) ? $contact->city : '',
            '[state]'          => !empty($contact->state) ? $contact->state : '',
            '[country]'        => !empty($contact->country) ? $contact->country : '',
            '[phone]'          => !empty($contact->phone) ? $contact->phone : '',
            '[date_of_birth]'  => !empty($contact->date_of_birth) ? $contact->date_of_birth : '',
        );

        return str_replace(array_keys($keywords), array_values($keywords), $message);

    }
}
