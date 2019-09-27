<div class="shipmondo-modal-content">
    {include file='module:shipmondo/views/templates/front/popup/partials/close_button.tpl'}
    <div class="shipmondo-modal-header">
        <h4>{l s='Choose pickup point' mod='shipmondo'}</h4>
        <p class="shipmondo-pickoup-point-counter" id="shipmondo-pickup-point-counter">
            {*
            <?php echo sprintf(_n('%s pickup point found', '%s pickup points found', $pickup_points_number,'pakkelabels-for-woocommerce'), $pickup_points_number); ?>
            *}
            {$service_points_count}
        </p>
    </div>
    <div class="shipmondo-modal-body">
        <div id="shipmondo-map-wrapper">
            <div id="shipmondo-map"></div>
            <input type="hidden" name="shipmondo_pickup_points_json" value='{$service_points_json nofilter}'>
            <script>
                jQuery(document).trigger('shipmondo_pickup_point_modal_loaded');
            </script>
        </div>
        <div id="shipmondo-list-wrapper">
            <ul class="shipmondo-shoplist-ul">
                {foreach $service_points as $service_point}

                <li class="shipmondo-shop-list" data-id="{$service_point->number nofilter}">
                    <div class="shipmondo-pickup-point-info">
                        {*
                        <!--<input type="hidden" class="input_shop_id" id="<?php echo 'shop_id_' . $pickup_point->number; ?>"-->
                        <!--name="<?php echo 'shop_id_' . $pickup_point->number; ?>"-->
                        <!--value="<?php echo 'ID: ' . strtoupper($pickup_point->agent) . '-' . trim($pickup_point->number); ?>">-->
                        <!--<input type="hidden" class="input_shop_name" id="<?php echo 'shop_name_' . $pickup_point->number; ?>"-->
                        <!--name="<?php echo 'shop_name_' . $pickup_point->number; ?>" value="<?php echo $pickup_point->company_name; ?>">-->
                        <!--<input type="hidden" class="input_shop_address" id="<?php echo 'shop_address_' . $pickup_point->number; ?>"-->
                        <!--name="<?php echo 'shop_address_' . $pickup_point->number; ?>" value="<?php echo $pickup_point->address; ?>">-->
                        <!--<input type="hidden" class="input_shop_zip" id="<?php echo 'shop_zip_' . $pickup_point->number; ?>"-->
                        <!--name="<?php echo 'shop_zip_' . $pickup_point->number; ?>" value="<?php echo $pickup_point->zipcode; ?>">-->
                        <!--<input type="hidden" class="input_shop_city" id="<?php echo 'shop_city_' . $pickup_point->number; ?>"-->
                        <!--name="<?php echo 'shop_city_' . $pickup_point->number; ?>" value="<?php echo $pickup_point->city; ?>">-->


                        <!--<div class="shipmondo-radio-button"></div>-->
                        <!--<div class="shipmondo-pickup-point-name"><?php echo $pickup_point->company_name; ?></div>-->
                        <!--<div class="shipmondo-pickup-point-address"><?php echo $pickup_point->address; ?></div>-->
                        <!--<div class="shipmondo-pickup-point-zipcode-city">-->
                        <!--<span class="shipmondo-pickup-point-zipcode"><?php echo $pickup_point->zipcode; ?></span>, <span-->
                        <!--class="shipmondo-pickup-point-city"><?php echo $pickup_point->city; ?></span>-->
                        <!--</div>-->
                        <!--<div class="shipmondo-pickup-point-id"><?php echo 'ID: ' . strtoupper($pickup_point->agent) . '-' . trim($pickup_point->number); ?></div>-->
                        *}

                        <input type="hidden" class="input_shop_id" id="shop_id_{$service_point->number nofilter}" name="shop_id_{$service_point->number nofilter}"
                               value="ID: {$service_point->agent nofilter}-{$service_point->number nofilter}">
                        <input type="hidden" class="input_shop_agent" id="shop_agent_{$service_point->agent nofilter}" name="shop_agent_{$service_point->agent nofilter}"
                               value="{$service_point->agent nofilter}">
                        <input type="hidden" class="input_shop_name" id="shop_name_{$service_point->number nofilter}"
                               name="shop_name_{$service_point->number nofilter}" value="{$service_point->company_name nofilter}">
                        <input type="hidden" class="input_shop_address" id="shop_address_{$service_point->number nofilter}"
                               name="shop_address_{$service_point->number nofilter}" value="{$service_point->address nofilter}">
                        <input type="hidden" class="input_shop_zip" id="shop_zip_{$service_point->number nofilter}"
                               name="shop_zip_{$service_point->number nofilter}" value="{$service_point->zipcode nofilter}">
                        <input type="hidden" class="input_shop_city" id="shop_city_{$service_point->number nofilter}"
                               name="shop_city_{$service_point->number nofilter}" value="{$service_point->city nofilter}">

                        <div class="shipmondo-radio-button"></div>

                        <div class="shipmondo-pickup-point-name">{$service_point->company_name nofilter}</div>
                        <div class="shipmondo-pickup-point-address">{$service_point->address nofilter}</div>
                        <div class="shipmondo-pickup-point-zipcode-city">
                            <span class="shipmondo-pickup-point-zipcode">{$service_point->zipcode nofilter}</span>, <span class="shipmondo-pickup-point-city">{$service_point->city nofilter}</span>
                        </div>
                        <div class="shipmondo-pickup-point-id">ID: {$shipping_agent nofilter}-{$service_point->number nofilter}</div>
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
    </div>
    <div class="shipmondo-modal-footer">
        {l s='Powered by Shipmondo' mod='shipmondo'}
    </div>
</div>
<div class="shipmondo-modal-checkmark">
    <svg class="shipmondo-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle class="shipmondo-checkmark_circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="shipmondo-checkmark_check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>
</div>