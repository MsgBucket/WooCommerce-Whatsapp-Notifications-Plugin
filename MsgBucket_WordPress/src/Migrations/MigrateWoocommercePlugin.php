<?php

namespace MessagingAPI_WC\Migrations;

class MigrateWoocommercePlugin {
    public static function migrate()
    {
        $setting_ids_to_iterate = ["messagingsms_setting", "messagingsms_admin_setting", "messagingsms_customer_setting", "messagingsms_multivendor_setting"];

        foreach($setting_ids_to_iterate as $setting_id) {
            // check if order notifciation plugin setting is set
            $setting = get_option($setting_id);
            if(empty($setting)) {
                // check messagingapi-sendsms
                $sendsms_setting_id = preg_replace("/messagingsms_/", "messagingapi_", $setting_id, 1);
                $sendsms_setting = get_option($sendsms_setting_id);
                if(!empty($sendsms_setting)) {
                    // if user have messagingapi-sendsms setting, we overwrite it to order notification
                    $new_option = [];
                    foreach($sendsms_setting as $key => $value) {
                        $new_key = preg_replace("/messagingapi_/", "messagingsms_woocommerce_", $key, 1);
                        $new_option[$new_key] = $value;

                    }
                    update_option($setting_id, $new_option);
                }
            }
        }


    }
}