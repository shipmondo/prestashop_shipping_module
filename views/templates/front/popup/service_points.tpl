{*
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
*}

<div class="pakkelabels-shoplist">
    <ul class="pakkelabels-shoplist-ul">
        {foreach $service_points as $service_point}
        <li data-shopid="{$service_point->number nofilter}" class="pakkelabels-shop-list">
            <div class="pakkelabels-single-shop">
                <div class="pakkelabels-radio-button"></div>
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
        </li>
        {/foreach}
    </ul>
</div>

<script>
$('.pakkelabels-shop-list').each(function() {
    console.log('each')
    $(this).on('click', function() {
        console.log('click')
        $('#shop_radio_' + $(this).attr('data-shopid')).trigger('click')
        li_addlistener_open_marker($(this));
        // Remove all the class selected, from previous li's
        $('.pakkelabels-shop-list').removeClass('selected');
        // $ the selected class to the newly selected li
        $(this).addClass('selected');
        // Sets the checked = true on the newly selected LI
        $(this).children().children(':radio').prop('checked', true);
    })
})
</script>
