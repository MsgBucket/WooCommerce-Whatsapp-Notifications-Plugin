<?php

namespace MessagingAPI_WC;

use MessagingAPI_WC\Forms\Handlers\ContactForm7;
use MessagingAPI_WC\Migrations\MigrateSendSMSPlugin;
use MessagingAPI_WC\Migrations\MigrateWoocommercePlugin;

class Loader {

    public static function load()
    {
        new ContactForm7();

        // load Migrations
        MigrateWoocommercePlugin::migrate();
        MigrateSendSMSPlugin::migrate();
    }
}
