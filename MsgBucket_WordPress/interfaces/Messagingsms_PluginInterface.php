<?php

interface Messagingsms_PluginInterface {
    public static function plugin_activated();
    public function get_option_id();
    public function get_plugin_settings($with_identifier = false);
}