<?php
namespace MessagingAPI_WC\Helpers;

class Sanitization {
    /**
     * Sanitizes an array of values.
     *
     * @param array $arr Values to be sanitized.
     */
    static function messagingapi_sanitize_array( $arr ) {
        global $wp_version;
        $older_version = ( $wp_version < '4.7' ) ? true : false;
        if ( ! is_array( $arr ) ) {
            return ( ( $older_version ) ? stripcslashes( sanitize_text_field( $arr ) ) : stripcslashes( sanitize_textarea_field( $arr ) ) );
        }

        $result = array();
        foreach ( $arr as $key => $val ) {
            $result[ $key ] = is_array( $val ) ? Sanitization::messagingapi_sanitize_array( $val ) : ( ( $older_version ) ? stripcslashes( sanitize_text_field( $val ) ) : stripcslashes( sanitize_textarea_field( $val ) ) );
        }

        return $result;
    }

}
