<?php
$wpcf7 = WPCF7_ContactForm::get_current();
if (empty($wpcf7->id())) {
    echo '<h3>';
    esc_html_e('Please save your contact form 7 once.', MESSAGINGSMS_TEXT_DOMAIN);
    echo '</h3>';
} else {
    $contact_form = WPCF7_ContactForm::get_instance($wpcf7->id());
    $form_fields  = $contact_form->scan_form_tags();

    $visitor_notification = $data['visitor_notification'];
    $visitor_mobile_field = $data['visitor_mobile_field'];
    $visitor_sms_template =$data['visitor_sms_template'];

    $admin_notification = $data['admin_notification'];
    $admin_mobile_numbers = $data['admin_mobile_numbers'];
    $admin_sms_template = $data['admin_sms_template'];
?>
    <h2>Visitor settings</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[visitor_notification]">Whatsapp notification</label></th>
                <td>
                    <fieldset>
                        <label for="wpcf7messagingapi-settings[visitor_notification]">
                            <input type="hidden" name="wpcf7messagingapi-settings[visitor_notification]" value="off">
                            <input
                                type="checkbox"
                                class="checkbox"
                                id="wpcf7messagingapi-settings[visitor_notification]"
                                name="wpcf7messagingapi-settings[visitor_notification]"
                                value="on"
                                <?php echo ($visitor_notification==="on") ? "checked" : "" ?>
                            >
                            Enable</label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[visitor_mobile_field]">Mobile number field</label></th>
                <td>
                    <select class="regular" name="wpcf7messagingapi-settings[visitor_mobile_field]" id="wpcf7messagingapi-settings[visitor_mobile_field]">
                        <option value="">Select mobile field</option>
                        <?php
                        if (!empty($form_fields)) {
                            foreach ($form_fields as $form_field) {
                                $field = json_decode(wp_json_encode($form_field), true);
                                if ($field['name'] !== '') {
                                    $text = '';
                                    if($visitor_mobile_field === $field['name']) {
                                        $text = sprintf('<option value="%1$s" selected>%1$s</option>', $field['name']);
                                    } else {
                                        $text = sprintf('<option value="%1$s">%1$s</option>', $field['name']);
                                    }
                                    echo $text;
                                }
                            }
                        }
                        ?>

                    </select>
                    <!-- <p class="description">Selected country will be use as default country info for mobile number when country info is not provided. </p> -->
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[visitor_sms_template]">Whatsapp message</label></th>
                <td>
                    <textarea
                        rows="8"
                        cols="500"
                        class="regular-text"
                        style="min-width:350px;;"
                        id="wpcf7messagingapi-settings[visitor_sms_template]"
                        name="wpcf7messagingapi-settings[visitor_sms_template]"
                    ><?php echo $visitor_sms_template; ?></textarea>
                    <p class="description">Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword[dummy]" data-attr-type="admin" data-attr-target="wpcf7messagingapi-settings[visitor_sms_template]" class="button button-secondary">Keywords</button></p>
                </td>
            </tr>
        </tbody>
    </table>

    <h2>Admin settings</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[admin_notification]">Whatsapp notification</label></th>
                <td>
                    <fieldset>
                        <label for="wpcf7messagingapi-settings[admin_notification]">
                            <input type="hidden" name="wpcf7messagingapi-settings[admin_notification]" value="off">
                            <input
                                type="checkbox"
                                class="checkbox"
                                id="wpcf7messagingapi-settings[admin_notification]"
                                name="wpcf7messagingapi-settings[admin_notification]"
                                value="on"
                                <?php echo ($admin_notification==="on") ? "checked" : "" ?>
                            >
                            Enable</label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[admin_mobile_numbers]">Mobile number</label></th>
                <td>
                    <input
                        type="text"
                        class="regular-text"
                        id="wpcf7messagingapi-settings[admin_mobile_numbers]"
                        name="wpcf7messagingapi-settings[admin_mobile_numbers]"
                        placeholder="60123456789,60123344556"
                        value="<?php echo $admin_mobile_numbers; ?>"
                    >
                    <p class="description">Comma separated mobile numbers including country code. Eg: 60123456789,60123344556</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7messagingapi-settings[admin_sms_template]">Whatsapp message</label></th>
                <td>
                    <textarea
                        rows="8"
                        cols="500"
                        class="regular-text"
                        style="min-width:350px;;"
                        id="wpcf7messagingapi-settings[admin_sms_template]"
                        name="wpcf7messagingapi-settings[admin_sms_template]"
                    ><?php echo $admin_sms_template ?></textarea>
                    <p class="description">Customize your Whatsapp with <button type="button" id="messagingsms-open-keyword[dummy]" data-attr-type="default" data-attr-target="wpcf7messagingapi-settings[admin_sms_template]" class="button button-secondary">Keywords</button></p>,
                </td>
            </tr>
        </tbody>
    </table>

    <script>
        <?php
        $keywordArr = [];
        $keywordArr['Contact Form 7'] = [];
        if (!empty($form_fields)) {
            foreach ($form_fields as $form_field) {
                $field = json_decode(wp_json_encode($form_field), true);
                if ($field['name'] !== '') {
                    $keywordArr['Contact Form 7'][] = $field['name'];
                }
            }
        }

        ?>
        let keywordArr = <?php echo json_encode($keywordArr) ?>;
        jQuery(function($) {

            var $div = $('<div />').appendTo('body');
            $div.attr('id', `keyword-modal`);
            $div.attr('class', "modal");
            $div.attr('style', "display: none;");
            $(`#messagingsms-open-keyword\\[dummy\\]`).click(function(e) {

                const target = $(e.target).attr('data-attr-target');

                pointerPosition = $(target).prop('selectionStart');

                const buildTable = function(keywords) {
                    const chunkedKeywords = keywords.array_chunk(3);

                    let tableCode = '';
                    chunkedKeywords.forEach(function(row, rowIndex) {
                        if (rowIndex === 0) {
                            tableCode += '<table class="widefat fixed striped"><tbody>';
                        }

                        tableCode += '<tr>';
                        row.forEach(function(col) {
                            tableCode += `<td class="column"><button class="button-link" onclick="messagingsms_bind_text_to_field('${target}', '[${col}]')">[${col}]</button></td>`;
                        });
                        tableCode += '</tr>';

                        if (rowIndex === chunkedKeywords.length - 1) {
                            tableCode += '</tbody></table>';
                        }
                    });

                    return tableCode;
                };

                $(`#keyword-modal`).off();
                $(`#keyword-modal`).on($.modal.AFTER_CLOSE, function() {
                    document.getElementById(target).focus();
                    document.getElementById(target).setSelectionRange(pointerPosition, pointerPosition);
                });

                let mainTable = '';
                for (let [key, value] of Object.entries(keywordArr)) {
                    mainTable += `<h3>${key}</h3>`;
                    mainTable += buildTable(value);
                }

                mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to Whatsapp template</small></div>';

                $(`#keyword-modal`).html(mainTable);
                $(`#keyword-modal`).modal();
            });

            $(".button-link").click(function(e) {
                console.log("Click")
                console.log(e)
            })

        })

        function insertAtCaret(e, t) {
            var s = document.getElementById(t);
            if (document.all)
                if (s.createTextRange && s.caretPos) {
                    var i = s.caretPos;
                    i.text = " " == i.text.charAt(i.text.length - 1) ? e + " " : e
                } else s.value = s.value + e;
            else if (s.setSelectionRange) {
                var r = s.selectionStart,
                    o = s.selectionEnd,
                    n = s.value.substring(0, r),
                    l = s.value.substring(o);
                s.value = n + e + l
            } else alert("This version of Mozilla based browser does not support setSelectionRange")
        }

        var adminnumber = "<?php echo esc_attr($data['phoneno']); ?>";
        var tagInput1 = new TagsInput({
            selector: 'wpcf7smsalert-settings[phoneno]',
            duplicate: false,
            max: 10,
        });
        var number = (adminnumber != '') ? adminnumber.split(",") : [];
        if (number.length > 0) {
            tagInput1.addData(number);
        }
    </script>
<?php } ?>