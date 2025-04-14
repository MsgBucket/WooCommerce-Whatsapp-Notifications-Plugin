<?php

class Messagingsms_WooCommerce_Frontend_Scripts implements Messagingsms_Register_Interface {
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'msmswc_admin_enqueue_scripts' ) );
        add_action( 'init', array($this, 'load_bootstrap'));
	}

	public function msmswc_admin_enqueue_scripts() {
		wp_enqueue_script( 'admin-messagingsms-scripts', plugins_url( 'js/admin.js?v=202012071500', __DIR__ ), array( 'jquery' ), '1.1.5', true );
		wp_enqueue_script( 'admin-messagingsms-sendsms', plugins_url( 'js/sendsms.js', __DIR__ ), array(), false, true);
		wp_enqueue_script( 'admin-messagingsms-charcounter', plugins_url( 'js/charactercounter.js', __DIR__ ), array(), false, true );

		//jquery modal
		wp_enqueue_style( 'admin-messagingsms-css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css', array(), '0.9.1' );
		wp_enqueue_script( 'Jquery Modal', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js', array( 'jquery' ), '0.9.1', true );
	}

    // only load bootstrap 5 in  our plugin page
    public function load_bootstrap()
    {
        if ( isset($_GET['page']) ) {
            $page = sanitize_text_field($_GET['page']);
            global $pagenow;
            if ($pagenow === 'options-general.php' && $this->str_contains($page, 'messagingsms-woocoommerce-setting')) {
                wp_enqueue_style ( 'admin-messagingsms-bootstrap', plugins_url( 'css/bootstrap.css', __DIR__));
                wp_enqueue_style ( 'admin-messagingsms-wpfooter-fix', plugins_url( 'css/wpfooter-fix.css', __DIR__));
            }
        }
    }

    private function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
