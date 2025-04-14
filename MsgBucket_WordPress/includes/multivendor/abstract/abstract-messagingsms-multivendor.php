<?php

require_once __DIR__ . '/../contracts/class-messagingsms-multivendor-interface.php';

abstract class Abstract_Messagingsms_Multivendor implements Messagingsms_Multivendor_Interface {
	protected $log;

	protected function __construct( Messagingsms_WooCoommerce_Logger $log = null ) {
		if ( $log === null ) {
			$log = new Messagingsms_WooCoommerce_Logger();
		}

		$this->log = $log;

		if ( ! empty( $_GET['user_id'] ) && is_numeric( $_GET['user_id'] ) ) {
			$user_id = $_GET['user_id'];
		} else {
			$user_id = get_current_user_id();
		}

		$user = get_user_by( 'id', $user_id );

		//register mobile field setting
		if ($user && !empty($user->roles) && in_array('customer', (array)$user->roles)) {
			add_action( 'show_user_profile', array( $this, 'setup_mobile_number_setting_field' ) );
			add_action( 'edit_user_profile', array( $this, 'setup_mobile_number_setting_field' ) );

			add_action( 'personal_options_update', array( $this, 'save_mobile_number_setting' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_mobile_number_setting' ) );
		}
	}

	protected function perform_grouping( $vendor_data_list ) {
		$group_vendor_datas = array();
		//perform grouping
		foreach ( $vendor_data_list as $vendor_data ) {
			//only send to vendor with phone number
			if ( $this->get_vendor_mobile_number_from_vendor_data( $vendor_data ) && $this->get_vendor_mobile_number_from_vendor_data( $vendor_data ) !== '' ) {
				$group_vendor_datas[ $this->get_vendor_mobile_number_from_vendor_data( $vendor_data ) ][] = $vendor_data;
			} else {
				$this->log->add(ZMPLGXX_NAME, 'phone not set for vendor id (' . $vendor_data['vendor_user_id'] . ')' );
			}
		}

		$new_group_vendor_datas = array();
		foreach ( $group_vendor_datas as $phone_number => $vendor_datas ) {
			$product_name     = '';
			$total            = 0;
			$product_with_qty = '';
			foreach ( $vendor_datas as $vendor_data ) {
				$product_name     .= ', ' . $vendor_data['item']->get_name();
				$product_with_qty .= ', ' . $vendor_data['item']->get_name() . ' X ' . $vendor_data['item']->get_quantity();
				$total            += $vendor_data['item']->get_total();

				$this->log->add(ZMPLGXX_NAME, 'item data for vendor id (' . $vendor_data['vendor_user_id'] . ') : ' . json_encode( $vendor_data['item']->get_data() ) );
			}

			if ( $product_name ) {
				$product_name     = substr( $product_name, 2 );
				$product_with_qty = substr( $product_with_qty, 2 );
			}
			$new_group_vendor_datas[ $phone_number ]['item']                    = $product_name;
			$new_group_vendor_datas[ $phone_number ]['product_with_qty']        = $product_with_qty;
			$new_group_vendor_datas[ $phone_number ]['total_amount_for_vendor'] = $total;
			$new_group_vendor_datas[ $phone_number ]['vendor_user_id']          = $vendor_datas[0]['vendor_user_id'];
			$new_group_vendor_datas[ $phone_number ]['vendor_profile']          = $vendor_datas[0]['vendor_profile'];
		}

		$this->log->add(ZMPLGXX_NAME, 'processed data: ' . json_encode( $new_group_vendor_datas ) );

		return $new_group_vendor_datas;
	}

	abstract public function setup_mobile_number_setting_field( $user );

	abstract public function save_mobile_number_setting( $user_id );

	abstract public function get_vendor_mobile_number_from_vendor_data( $vendor_data );

	abstract public function get_vendor_country_from_vendor_data( $vendor_data );

	abstract public function get_vendor_shop_name_from_vendor_data( $vendor_data );

	abstract public function get_vendor_id_from_item( WC_Order_Item $item );

	abstract public function get_vendor_profile_from_item( WC_Order_Item $item );
}
