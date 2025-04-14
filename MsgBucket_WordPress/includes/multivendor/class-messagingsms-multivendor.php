<?php

class Messagingsms_Multivendor implements Messagingsms_Register_Interface {
	public function register() {
		$this->required_files();
		//create notification instance
		$messagingsms_notification = new Messagingsms_Multivendor_Notification( 'Wordpress-Woocommerce-Multivendor-Extension-' . Messagingsms_Multivendor_Factory::$activatedPlugin );

		$registerInstance = new Messagingsms_WooCommerce_Register();
		$registerInstance->add( new Messagingsms_Multivendor_Hook( $messagingsms_notification ) )
		                 ->add( new Messagingsms_Multivendor_Setting() )
		                 ->load();
	}

	protected function required_files() {
		require_once __DIR__ . '/admin/class-messagingsms-multivendor-setting.php';
		require_once __DIR__ . '/abstract/abstract-messagingsms-multivendor.php';
		require_once __DIR__ . '/contracts/class-messagingsms-multivendor-interface.php';
		require_once __DIR__ . '/class-messagingsms-multivendor-factory.php';
		require_once __DIR__ . '/class-messagingsms-multivendor-hook.php';
		require_once __DIR__ . '/class-messagingsms-multivendor-notification.php';
	}
}
