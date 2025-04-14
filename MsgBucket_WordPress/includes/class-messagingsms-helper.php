<?php

if ( ! function_exists( 'messagingsms_get_options' ) ) {
	/**
	 * @param $option
	 * @param $section
	 * @param array|string $default
	 *
	 * @return mixed
	 */
	function messagingsms_get_options( $option, $section, $default = '' ) {
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}
}

if ( ! function_exists( 'messagingsms_add_actions' ) ) {
	/**
	 * @param array $hook_actions
	 */
	function messagingsms_add_actions( $hook_actions ) {
		foreach ( $hook_actions as $hook ) {
            $priority = isset($hook['priority']) ? $hook['priority'] : 10;
            $args_count = isset($hook['args_count']) ? $hook['args_count'] : 1;
			add_action( $hook['hook'], $hook['function_to_be_called'], $priority, $args_count);
		}
	}
}
