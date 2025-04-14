<?php

class MessagingSMS_Automation_View implements Messagingsms_Register_Interface {

	private $settings_api;
    private $activated_plugins;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'messagingsms_setting_section', array( $this, 'set_automation_setting_section' ) );
		add_filter( 'messagingsms_setting_fields', array( $this, 'set_automation_setting_field' ) );
        add_action( 'messagingsms_load_javascripts', array($this, 'load_scripts') );
        // loop through all activated plugins and register their hooks / filters
        $this->activated_plugins = MessagingSupportedPlugin::get_activated_plugins();
        foreach ($this->activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $plugin->register();
        }

	}

	public function set_automation_setting_section( $sections ) {
        $children = array();
        $activated_plugins = $this->activated_plugins;
        foreach ($this->activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $children[] = $plugin->get_setting_section_data();
        }
		$sections[] = array(
			'id'             => 'messagingsms_automation_setting',
			'title'          => __( 'Automation', MESSAGINGSMS_TEXT_DOMAIN ),
            'submit_button'  => '',
            'children'       => $children,
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_automation_setting_field( $setting_fields ) {

        $activated_plugins = $this->activated_plugins;
        foreach ($activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $setting_fields[$plugin->get_option_id()] = $plugin->get_setting_field_data();
        }

		return $setting_fields;
	}

    public function load_scripts() {

        $activated_plugins = $this->activated_plugins;
        $plugins = array();
        foreach ($activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $plugins[$plugin->get_option_id()] = $plugin->get_keywords_field();
        }

    ?>
        <script>
            var pointerPosition = 0;
            var plugins = <?php echo json_encode($plugins); ?>;

            jQuery(function ($) {
                for (let [option_id, plugin_keywords] of Object.entries(plugins)) {
                    // create div element for each plugins
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', `keyword-modal-${option_id}`);
                    $div.attr('class', "modal");
                    $div.attr('style', "display: none;");


                    $(`#messagingsms-open-keyword-${option_id}-\\[dummy\\]`).click(function (e) {
                        const type = $(e.target).attr('data-attr-type');
                        const target = $(e.target).attr('data-attr-target');

                        pointerPosition = document.getElementById(target).selectionStart;

                        const buildTable = function (keywords) {
                            const chunkedKeywords = keywords.array_chunk(3);

                            let tableCode = '';
                            chunkedKeywords.forEach(function (row, rowIndex) {
                                if (rowIndex === 0) {
                                    tableCode += '<table class="widefat fixed striped"><tbody>';
                                }

                                tableCode += '<tr>';
                                row.forEach(function (col) {
                                    tableCode += `<td class="column"><button class="button-link" onclick="messagingsms_bind_text_to_field_automation('${target}', '[${col}]')">[${col}]</button></td>`;
                                });
                                tableCode += '</tr>';

                                if (rowIndex === chunkedKeywords.length - 1) {
                                    tableCode += '</tbody></table>';
                                }
                            });

                            return tableCode;
                        };

                        $(`#keyword-modal-${option_id}`).off();
                        $(`#keyword-modal-${option_id}`).on($.modal.AFTER_CLOSE, function () {
                            document.getElementById(target).focus();
                            document.getElementById(target).setSelectionRange(pointerPosition, pointerPosition);
                        });

                        let mainTable = '';
                        for (let [key, value] of Object.entries(plugin_keywords)) {
                            mainTable += `<h3>${capitalize_first_letter(key.replaceAll('_', ' '))}</h3>`;
                            mainTable += buildTable(value);
                        }

                        mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to Whatsapp template</small></div>';

                        $(`#keyword-modal-${option_id}`).html(mainTable);
                        $(`#keyword-modal-${option_id}`).modal();
                    });
                }
            });
            function capitalize_first_letter (str) {
                return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
                    return $1.toUpperCase();
                });
            }
            function messagingsms_bind_text_to_field_automation(target, keyword) {
                const startStr = document.getElementById(target).value.substring(0, pointerPosition);
                const endStr = document.getElementById(target).value.substring(pointerPosition);
                document.getElementById(target).value = startStr + keyword + endStr;
                pointerPosition += keyword.length;
            }
        </script>
    <?php
    }

}

?>
