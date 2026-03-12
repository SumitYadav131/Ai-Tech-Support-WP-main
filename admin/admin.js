jQuery(document).ready(function ($) {

    $('#add-support-option').click(function () {

        $('#support-options-table tbody').append(
            `<tr>
                <td><input type="text" name="support_type[]" placeholder ="Support Type"></td>
                <td><input type="text" name="support_label[]" class="regular-text" placeholder="Support Label"></td>
                <td><input type="text" name="support_default_msg[]" class="regular-text" placeholder="Default Message"></td>
                <td><button type="button" class="button remove-option">Remove</button></td>
            </tr>`
        );

    });

    $(document).on('click', '.remove-option', function () {
        $(this).closest('tr').remove();
    });

});