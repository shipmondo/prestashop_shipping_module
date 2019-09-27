<div class="shipmondo-modal-content error">
    {*
    <!--<?php \ShipmondoForWooCommerce\Plugin\Plugin::getTemplate('pickup-point-selection.modal.partials.close-button'); ?>-->
    <!--<p><?php echo $error; ?></p>-->


    {$error}
    *}
    {include file='module:shipmondo/views/templates/front/popup/partials/close_button.tpl'}

    <button class="shipmondo-modal-close-button button alt">{l s='Close' mod='shipmondo'}</button>
</div>