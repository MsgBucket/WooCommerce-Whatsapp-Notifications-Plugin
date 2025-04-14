<?php
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingS2Member.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingARMemberLite.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingARMemberPremium.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingMemberPress.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingMemberMouse.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingSimpleMembership.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingRestaurantReservation.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingQuickRestaurantReservation.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingBookIt.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingLatePoint.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingFATService.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingWpERP.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingJetpackCRM.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingFluentCRM.php';
require_once MESSAGINGSMS_PLUGIN_DIR . 'includes/plugins/MessagingGroundhoggCRM.php';

class MessagingSupportedPlugin {

    public function __construct() {}

    public static function get_activated_plugins()
    {
        $supported_plugins = array();
        
        if(MessagingS2Member::plugin_activated())
            $supported_plugins[] = MessagingS2Member::class;
        if(MessagingARMemberLite::plugin_activated())
            $supported_plugins[] = MessagingARMemberLite::class;
        if(MessagingARMemberPremium::plugin_activated())
            $supported_plugins[] = MessagingARMemberPremium::class;
        if(MessagingMemberPress::plugin_activated())
            $supported_plugins[] = MessagingMemberPress::class;
        if(MessagingMemberMouse::plugin_activated())
            $supported_plugins[] = MessagingMemberMouse::class;
        if(MessagingSimpleMembership::plugin_activated())
            $supported_plugins[] = MessagingSimpleMembership::class;

        if(MessagingRestaurantReservation::plugin_activated())
            $supported_plugins[] = MessagingRestaurantReservation::class;
        if(MessagingQuickRestaurantReservation::plugin_activated())
            $supported_plugins[] = MessagingQuickRestaurantReservation::class;
        if(MessagingBookIt::plugin_activated())
            $supported_plugins[] = MessagingBookIt::class;
        if(MessagingLatePoint::plugin_activated())
            $supported_plugins[] = MessagingLatePoint::class;
        if(MessagingFATService::plugin_activated())
            $supported_plugins[] = MessagingFATService::class;

        if(MessagingWpERP::plugin_activated())
            $supported_plugins[] = MessagingWpERP::class;
        if(MessagingJetpackCRM::plugin_activated())
            $supported_plugins[] = MessagingJetpackCRM::class;
        if(MessagingFluentCRM::plugin_activated())
            $supported_plugins[] = MessagingFluentCRM::class;
        if(MessagingGroundhoggCRM::plugin_activated())
            $supported_plugins[] = MessagingGroundhoggCRM::class;

        return $supported_plugins;
    }


}
