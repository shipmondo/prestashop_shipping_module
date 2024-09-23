{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
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