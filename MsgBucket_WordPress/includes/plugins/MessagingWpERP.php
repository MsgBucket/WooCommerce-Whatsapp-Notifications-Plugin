<?php

class MessagingWpERP implements Messagingsms_PluginInterface, Messagingsms_Register_Interface {
    /*
    Plugin Name: WP ERP – Complete WordPress Business Manager with HR, CRM & Accounting Systems for Small Businesses
    Plugin Link: https://wordpress.org/plugins/erp/
    */

    public static $plugin_identifier = 'erp';
    private $plugin_name;
    private $hook_action;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Messagingsms_WooCoommerce_Logger();
        $this->option_id = "messagingsms_{$this::$plugin_identifier}";
        $this->plugin_name = 'WP ERP';
        $this->hook_action = "messagingsms_send_reminder_{$this::$plugin_identifier}";
    }

    public static function plugin_activated()
    {
        $log = new Messagingsms_WooCoommerce_Logger();
        if( ! is_plugin_active(sprintf("%s/wp-erp.php", self::$plugin_identifier))) { return false; }
        return true;
    }

    public function register()
    {
        add_action( 'erp_create_new_people', array( $this, 'send_sms_on'), 10, 3);
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
            "messagingsms_automation_enable_notification"          => messagingsms_get_options("messagingsms_automation_enable_notification", $this->get_option_id()),
            "messagingsms_automation_send_on"                      => messagingsms_get_options("messagingsms_automation_send_on", $this->get_option_id()),
            "messagingsms_automation_sms_template_new_customer"    => messagingsms_get_options("messagingsms_automation_sms_template_new_customer", $this->get_option_id()),
            "messagingsms_automation_sms_template_new_lead"        => messagingsms_get_options("messagingsms_automation_sms_template_new_lead", $this->get_option_id()),
            "messagingsms_automation_sms_template_new_opportunity" => messagingsms_get_options("messagingsms_automation_sms_template_new_opportunity", $this->get_option_id()),
            "messagingsms_automation_sms_template_new_subscriber"  => messagingsms_get_options("messagingsms_automation_sms_template_new_subscriber", $this->get_option_id()),
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
            'desc'    => __( 'Choose when to send a Whatsapp notification message to your customer', MESSAGINGSMS_TEXT_DOMAIN ),
            'type'    => 'multicheck',
            'options' => array(
                'new_customer'    => 'New customer',
                'new_lead'        => 'New lead',
                'new_opportunity' => 'New opportunity',
                'new_subscriber'  => 'New subscriber',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'messagingsms_automation_sms_template_new_customer',
                'label'   => __( 'New customer Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="new_leads" data-attr-target="%1$s[messagingsms_automation_sms_template_new_customer]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], we would like to personally thank you for using our services.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_new_lead',
                'label'   => __( 'New lead Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="new_leads" data-attr-target="%1$s[messagingsms_automation_sms_template_new_lead]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for showing interest in our services. Our sales representative will contact you shortly.', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_new_opportunity',
                'label'   => __( 'New opportunity Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="new_leads" data-attr-target="%1$s[messagingsms_automation_sms_template_new_opportunity]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], when would be a good time for our sales representative to schedule a call with you to discuss more on our service?', MESSAGINGSMS_TEXT_DOMAIN )
            ),
            array(
                'name'    => 'messagingsms_automation_sms_template_new_subscriber',
                'label'   => __( 'New subscriber Whatsapp message', MESSAGINGSMS_TEXT_DOMAIN ),
                'desc'    => sprintf('Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword-%1$s-[dummy]" data-attr-type="new_leads" data-attr-target="%1$s[messagingsms_automation_sms_template_new_subscriber]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], thank you for subscribing to us. We will notify you of HOT contents', MESSAGINGSMS_TEXT_DOMAIN )
            ),
        );
    }

    public function get_keywords_field()
    {
        return array(
            'customer' => array(
                'first_name',
                'last_name',
                'email',
                'phone',
                'life_stage',
                'date_of_birth',
                'age',
                'mobile',
                'website',
                'fax',
                'address_1',
                'address_2',
                'city',
                'country',
                'state',
                'postal_code',
                'content_source',
                'other',
                'notes',
                'facebook',
                'twitter',
                'googleplus',
                'linkedin',
            ),
            'contact_owner' => array(
                'cowner_first_name',
                'cowner_last_name',
                'cowner_email',
            ),
        );

    }

    public function send_sms_on($people_id, $args, $people_type)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['messagingsms_automation_enable_notification'];
        $send_on = $plugin_settings['messagingsms_automation_send_on'];

        $this->log->add(ZMPLGXX_NAME, "people_id: {$people_id}");

        $status = "new_{$args['life_stage']}";

        if($enable_notifications === "on") {
            $this->log->add(ZMPLGXX_NAME, "enable notifications: on");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add(ZMPLGXX_NAME, "enable {$status} notifications: on");
                    $this->send_customer_notification($args, $status);
                }
            }
        }

        return false;
    }

    public function send_customer_notification($args, $status)
    {
        $this->log->add(ZMPLGXX_NAME, "send_customer_notification status: {$status}");
        $settings = $this->get_plugin_settings();

        // get number from args
        $phone_no = $args['phone'];
        if( !ctype_digit($phone_no) ) {
            $this->log->add(ZMPLGXX_NAME, "phone_no is not a digit: {$phone_no}. Aborting...");
            return;
        }
        if( $args['country'] != '-1' ) {
            $phone_no = MessagingSMS_SendSMS_Sms::get_formatted_number($args['phone'], $args['country']);
        } else {
            $phone_no = $args['phone'];
        }

        $this->log->add(ZMPLGXX_NAME, "phone_no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["messagingsms_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($args, $msg_template);

        MessagingSMS_SendSMS_Sms::send_sms($phone_no, $message);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($args, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        // preg_match_all('/\[(.*?)\]/', $message, $keywords);

        $wp_user = new WP_User( intval($args['contact_owner']) );
        $keywords = array(
            '[first_name]'        => !empty($args['first_name'])     ? $args['first_name'] : '',
            '[last_name]'         => !empty($args['last_name'])      ? $args['last_name'] : '',
            '[email]'             => !empty($args['email'])          ? $args['email'] : '',
            '[phone]'             => !empty($args['phone'])          ? $args['phone'] : '',
            '[life_stage]'        => !empty($args['life_stage'])     ? $args['life_stage'] : '',
            '[date_of_birth]'     => !empty($args['date_of_birth'])  ? $args['date_of_birth'] : '',
            '[age]'               => !empty($args['contact_age'])    ? $args['contact_age'] : '',
            '[mobile]'            => !empty($args['mobile'])         ? $args['mobile'] : '',
            '[website]'           => !empty($args['website'])        ? $args['website'] : '',
            '[fax]'               => !empty($args['fax'])            ? $args['fax'] : '',
            '[address_1]'         => !empty($args['street_1'])       ? $args['street_1'] : '',
            '[address_2]'         => !empty($args['street_2'])       ? $args['street_2'] : '',
            '[city]'              => !empty($args['city'])           ? $args['city'] : '',
            '[country]'           => $args['country'] !== -1         ? $args['country'] : '',
            '[state]'             => !empty($args['state'])          ? $args['state'] : '',
            '[postal_code]'       => !empty($args['postal_code'])    ? $args['postal_code'] : '',
            '[content_source]'    => !empty($args['content_source']) ? $args['content_source'] : '',
            '[other]'             => !empty($args['other'])          ? $args['other'] : '',
            '[notes]'             => !empty($args['notes'])          ? $args['notes'] : '',
            '[facebook]'          => !empty($args['facebook'])       ? $args['facebook'] : '',
            '[twitter]'           => !empty($args['twitter'])        ? $args['twitter'] : '',
            '[googleplus]'        => !empty($args['googleplus'])     ? $args['googleplus'] : '',
            '[linkedin]'          => !empty($args['linkedin'])       ? $args['linkedin'] : '',
            '[cowner_first_name]' => $wp_user->first_name,
            '[cowner_last_name]'  => $wp_user->last_name,
            '[cowner_email]'      => $wp_user->email,
        );

        return str_replace(array_keys($keywords), array_values($keywords), $message);

    }
}
