<?php

class Messagingapi_Multivendor_WooCommerce_Product_Vendors_Manager extends Abstract_Messagingsms_Multivendor {
	public function __construct( Messagingsms_WooCoommerce_Logger $log = null ) {
		parent::__construct( $log );
	}

	public function setup_mobile_number_setting_field( $user ) {
		?>
        <h3 class="heading"><?php echo ZMPLGXX_NAME; ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="messagingsms_phone_field">Phone</label></th>
                <td>
                    <input type="text" class="input-text" id="messagingsms_phone_field" name="messagingsms_phone_field"
                           value="<?php echo esc_attr( get_the_author_meta( 'messagingsms_phone', $user->ID ) ) ?>"/>
                    <p class="description">Fill this field to enable Whatsapp feature for vendor</p>
                </td>
            </tr>
        </table>
		<?php
	}

	public function save_mobile_number_setting( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$messagingsms_phone_field = sanitize_text_field( $_POST['messagingsms_phone_field'] );

		update_user_meta( $user_id, 'messagingsms_phone', $messagingsms_phone_field );
	}

	public function get_vendor_mobile_number_from_vendor_data( $vendor_data ) {
		return get_user_meta( $this->get_vendor_id_from_item( $vendor_data['item'] ), 'messagingsms_phone', true );
	}

	public function get_vendor_country_from_vendor_data($vendor_data){
		$selected_country_code        = messagingsms_get_options( 'messagingsms_woocommerce_country_code', 'messagingsms_setting', '' );//Get default country v1.1.17
		return $selected_country_code;
	}

	public function get_vendor_shop_name_from_vendor_data( $vendor_data ) {
		return $vendor_data['vendor_profile']['name'];
	}

	public function get_vendor_id_from_item( WC_Order_Item $item ) {
		$adminId = 0;
		$admin = WC_Product_Vendors_Utils::get_vendor_data_by_id( WC_Product_Vendors_Utils::get_vendor_id_from_product( $item->get_product_id() ) )['admins'];
		if (isset($admin[0])) $adminId = $admin[0];
		return $adminId;
	}

	public function get_vendor_profile_from_item( WC_Order_Item $item ) {
		return WC_Product_Vendors_Utils::get_vendor_data_by_id( WC_Product_Vendors_Utils::get_vendor_id_from_product( $item->get_product_id() ) );
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
