/**
 *  @author    Shipmondo
 *  @copyright 2023 Shipmondo
 *  @license   All rights reserved
 */
$('body').on('change', '.live', function () {
    $('.delivery-option input:checked').trigger('click');
});