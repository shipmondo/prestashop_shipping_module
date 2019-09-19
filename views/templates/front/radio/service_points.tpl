<div class="shipmondo-shoplist">
    {foreach $service_points as $service_point}
    <div data-shopid="{$service_point->number}" class="shipmondo-shop-list">
        <div class="shipmondo-single-shop">
            <div class="shipmondo-radio-button"></div>
            <div class="selected_content">
                <div class="shipmondo-company-name">{$service_point->company_name}</div>
                <div class="shipmondo-Address">{$service_point->address}</div>
                <div class="shipmondo-ZipAndCity">
                    <span class="shipmondo-zipcode">{$service_point->zipcode}</span>
                    <span class="shipmondo-city">{$service_point->city}</span>
                </div>
                <div class="shipmondo-Packetshop" style="display:none;">
                    ID: {$shipping_agent}-{$service_point->number}
                </div>
            </div>
        </div>
    </div>
    {/foreach}
</div>
<script>
    jQuery('.shipmondo-shop-list').each(function() {
        jQuery(this).on('click', function() {
            jQuery('#hidden_choosen_shop').attr('shopid', jQuery(this).attr('data-shopid'));
            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selectedServicePointHeader);
            jQuery('#selected_shop_context').html(jQuery(this).children().children('.selected_content').html());
            //remove all the class selected, from previous li's
            if(typeof checkdroppointselected !=='undefined')
                checkdroppointselected(this); 
            jQuery('.shipmondo-shop-list').removeClass('selected');
            //adds the selected class to the newly selected li
            jQuery(this).addClass('selected');
            setTimeout(function()
            {
                if(typeof saveCartdetails !== 'undefined')
                    saveCartdetails();
            }, 1000) 
        })

        if( $(this).data('shopid') == {$selected_service_point_id})
            $(this).trigger('click')
    })

    if($('.shipmondo-shop-list.selected').length == 0)
        $('.shipmondo-shop-list').first().trigger('click');
</script>