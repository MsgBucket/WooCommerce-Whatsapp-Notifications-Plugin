<?php

class Messagingapi_Multivendor_WC_Marketplace_Manager extends Abstract_Messagingsms_Multivendor {
	public function __construct( Messagingsms_WooCoommerce_Logger $log = null ) {
		parent::__construct( $log );
	}

	public function setup_mobile_number_setting_field( $user ) {
		//not supported due to default available
	}

	public function save_mobile_number_setting( $user_id ) {
		//not supported due to default available
	}

	public function get_vendor_mobile_number_from_vendor_data( $vendor_data ) {
		return $vendor_data['vendor_profile']['_vendor_phone'][0];
	}

	public function get_vendor_country_from_vendor_data($vendor_data){
		return $vendor_data['vendor_profile']['_vendor_country_code'][0];
	}

	public function get_vendor_shop_name_from_vendor_data( $vendor_data ) {
		return $vendor_data['vendor_profile']['_vendor_page_title'][0];
	}

	public function get_vendor_id_from_item( WC_Order_Item $item ) {
		return $item->get_meta( '_vendor_id' );
	}

	public function get_vendor_profile_from_item( WC_Order_Item $item ) {
		return get_user_meta( $this->get_vendor_id_from_item( $item ) );
	}

	public function get_vendor_data_list_from_order( $order_id ) {
		$order = wc_get_order( $order_id );
		$items = $order->get_items();

		$vendor_data_list = array();

		foreach ( $items as $item ) {
			$vendor_data_list[] = array(
				'item'           => $item,
				'vendor_user_id' => $this->get_vendor_id_from_item( $item ),
				'vendor_profile' => $this->get_vendor_profile_from_item( $item )
			);
		}

		$this->log->add(ZMPLGXX_NAME, 'Raw data: ' . json_encode( $vendor_data_list ) );

		return $this->perform_grouping( $vendor_data_list );
	}
}
