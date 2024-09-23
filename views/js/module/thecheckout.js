/**
 *  @author    Shipmondo
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */
$('body').on('change', '.live', function () {
    $('.delivery-option input:checked').trigger('click');
});