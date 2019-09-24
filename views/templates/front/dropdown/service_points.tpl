{*
*  @author    Shipmondo
*  @copyright 2019 Shipmondo
*  @license   All rights reserved
*}

<!--<div class="pakkelabels-dropdown-menu dropdown-menu">-->
    <!--<div class="pakkelabels-loader-wrapper">-->
        <!--<div class="pakkelabels-loader"></div>-->
    <!--</div>-->
    <ul class="pakkelabels-shoplist-dropdownul">
        {foreach $service_points as $service_point}
        <li data-shopid="{$service_point->number nofilter}" class="pakkelabels-shop-list">
            <div class="pakkelabels-single-shop">
                <img src="{$shipping_agent_logo nofilter}" class="agent_icon">
                <div class="shipmondo-shop-info">
                    <div class="pakkelabels-company-name">{$service_point->company_name nofilter}</div>
                    <div class="pakkelabels-Address">{$service_point->address nofilter}</div>
                    <div class="pakkelabels-ZipAndCity">
                        <span class="pakkelabels-zipcode">{$service_point->zipcode nofilter}</span>
                        <span class="pakkelabels-city">{$service_point->city nofilter}</span>
                    </div>
                    <div class="pakkelabels-Packetshop">
                        ID: {$shipping_agent nofilter}-{$service_point->number nofilter}
                    </div>
                </div>
            </div>
        </li>
        {/foreach}
    </ul>
    <!--<div class="pakkelabels-dropdown-footer">-->
        <!--Powered by Shipmondo-->
    <!--</div>-->
<!--</div>-->
<script>
    jQuery('.pakkelabels-shop-list').each(function () {
        jQuery(this).on('click', function () {
            jQuery('#hidden_choosen_shop').attr('shopid', jQuery(this).attr('data-shopid'));
            // Adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selectedServicePointHeader);
            jQuery('#selected_shop_context').html(jQuery(this).find('.shipmondo-shop-info').html());
            // Remove all the class selected, from previous li's
            if (typeof checkdroppointselected !== 'undefined')
                checkdroppointselected(this);
            jQuery('.pakkelabels-shop-list').removeClass('selected');
            // Adds the selected class to the newly selected li
            jQuery(this).addClass('selected');
            setTimeout(function () {
                if (typeof saveCartdetails !== 'undefined')
                    saveCartdetails();
            }, 1000)
        });
    })
</script>
