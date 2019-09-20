{*
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
*}

<div class="pakkelabels-shoplist">
    {foreach $service_points as $service_point}
    <div data-shopid="{$service_point->number nofilter}" class="pakkelabels-shop-list">
        <div class="pakkelabels-single-shop">
            <div class="pakkelabels-radio-button"></div>
            <div class="selected_content">
                <div class="pakkelabels-company-name">{$service_point->company_name nofilter}</div>
                <div class="pakkelabels-Address">{$service_point->address nofilter}</div>
                <div class="pakkelabels-ZipAndCity">
                    <span class="pakkelabels-zipcode">{$service_point->zipcode nofilter}</span>
                    <span class="pakkelabels-city">{$service_point->city nofilter}</span>
                </div>
                <div class="pakkelabels-Packetshop" style="display:none;">
                    ID: {$shipping_agent nofilter}-{$service_point->number nofilter}
                </div>
            </div>
        </div>
    </div>
    {/foreach}
</div>
<script>
    jQuery('.pakkelabels-shop-list').each(function() {
        jQuery(this).on('click', function() {
            jQuery('#hidden_choosen_shop').attr('shopid', jQuery(this).attr('data-shopid'));
            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selectedServicePointHeader);
            jQuery('#selected_shop_context').html(jQuery(this).children().children('.selected_content').html());
            //remove all the class selected, from previous li's
            if(typeof checkdroppointselected !=='undefined')
                checkdroppointselected(this); 
            jQuery('.pakkelabels-shop-list').removeClass('selected');
            //adds the selected class to the newly selected li
            jQuery(this).addClass('selected');
            setTimeout(function()
            {
                if(typeof saveCartdetails !== 'undefined')
                    saveCartdetails();
            }, 1000) 
        })

        if( $(this).data('shopid') == {$selected_service_point_id nofilter})
            $(this).trigger('click')
    })

    if($('.pakkelabels-shop-list.selected').length == 0)
        $('.pakkelabels-shop-list').first().trigger('click');
</script>