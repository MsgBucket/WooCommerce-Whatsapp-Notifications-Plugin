<?php

class MessagingSMS_Logs_View implements Messagingsms_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'messagingsms_setting_section', array($this, 'set_logs_setting_section' ) );
		add_filter( 'messagingsms_setting_fields',  array($this, 'set_logs_setting_field' ) );
		add_action( 'messagingsms_setting_fields_custom_html', array($this, 'display_logs_page'), 10, 1);
	}

	public function set_logs_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'messagingsms_logs_setting',
            'title'            => __( 'Usage Logs', MESSAGINGSMS_TEXT_DOMAIN ),
            'submit_button'    => '',
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_logs_setting_field( $setting_fields ) {

		return $setting_fields;
	}

    public function display_logs_page($form_id) {
        if($form_id !== 'messagingsms_logs_setting') { return; }
        $logger = new Messagingsms_WooCoommerce_Logger();
        $customer_logs = $logger->get_log_file(ZMPLGXX_NAME);
    ?>
        <div class="bootstrap-wrapper">
            <div id="setting-error-settings_updated" class="border border-primary" style="padding:4px;width:1200px;height:600px;overflow:auto">
                <pre><strong><?php echo esc_html($customer_logs); ?></strong></pre>
            </div>
        </div>

    <?php
    }


}

?>
