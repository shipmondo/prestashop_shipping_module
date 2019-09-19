<div class="shipmondo-shoplist">
    <ul class="shipmondo-shoplist-ul">
        {foreach $service_points as $service_point}
        <li data-shopid="{$service_point->number}" class="shipmondo-shop-list">
            <div class="shipmondo-single-shop">
                <div class="shipmondo-radio-button"></div>
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
        </li>
        {/foreach}
    </ul>
</div>
<script>
    $('.shipmondo-shop-list').each(function() {
        console.log('each')
        $(this).on('click', function() {
            console.log('click')
            $('#shop_radio_'+$(this).attr('data-shopid')).trigger('click')
            li_addlistener_open_marker($(this));
            //remove all the class selected, from previous li's
            $('.shipmondo-shop-list').removeClass('selected');
            //$ the selected class to the newly selected li
            $(this).addClass('selected');
            //sets the checked = true on the newly selected LI
            $(this).children().children(':radio').prop('checked', true);
        })
    })
</script>