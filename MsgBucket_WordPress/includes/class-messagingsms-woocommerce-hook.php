<?php

class Messagingsms_WooCommerce_Hook implements Messagingsms_Register_Interface {
	protected $notification_ins;

	public function __construct( Messagingsms_WooCommerce_Notification $notification_ins ) {
		$this->notification_ins = $notification_ins;
	}

	public function register() {
		messagingsms_add_actions( $this->get_core_actions() );
	}

	protected function get_core_actions() {
		$hook_actions   = array();
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_pending',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_pending' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_failed',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_failed' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_on-hold',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_on_hold' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_processing',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_processing' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_completed',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_completed' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_refunded',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_refunded' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_cancelled',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_order_status_cancelled' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_low_stock',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_low_stock_product' ),
		);
		$hook_actions[] = array(
			'hook'                  => 'woocommerce_order_status_changed',
			'function_to_be_called' => array( $this->notification_ins, 'send_sms_woocommerce_custom_order_status' ),
            'priority'              => 10,
            'args_count'            => 3,
		);
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_pending_to_on-hold',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_pending_to_processing',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_pending_to_completed',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_pending_to_failed',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_pending_to_cancelled',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_failed_to_on-hold',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_failed_to_processing',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );
		// $hook_actions[] = array(
		// 	'hook'                  => 'woocommerce_order_status_failed_to_completed',
		// 	'function_to_be_called' => array( $this->notification_ins, 'send_admin_notification' ),
		// );

		return $hook_actions;
	}
}
