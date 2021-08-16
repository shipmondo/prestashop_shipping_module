{*
*  @author    Shipmondo
*  @copyright 2021 Shipmondo
*  @license   All rights reserved
*}

<div class="shipmondo-modal shipmondo-hidden" id="shipmondo-modal" tabindex="-1" role="dialog" aria-labelledby="{l s='Service point modal' mod='shipmondo'}">
    <div class="shipmondo-modal-wrapper">
        <div class="shipmondo-loader-wrapper">
            <div class="shipmondo-loader"></div>
        </div>
        <div class="shipmondo-removable-content"></div>
        <div class="shipmondo-error">
            {*
            {include file='module:shipmondo/views/templates/front/popup/partials/close_button.tpl'}
            *}
            <p>{l s='Something went wrong, please try again' mod='shipmondo'}</p>
            <button class="shipmondo-modal-close-button button btn btn-primary">{l s='Close' mod='shipmondo'}</button>
        </div>
    </div>
</div>