<?php
/**
 *  @author    Shipmondo
 *  @copyright 2019 Shipmondo
 *  @license   All rights reserved
 */

class PakkelabelsShopListController extends Module
{
    /** Roohi*/
    public function getshoplist($zipcode, $agent, $frontend_key, $address, $country = 'DK')
    {
        $method = 'GET';
        $url = 'https://service-points.pakkelabels.dk/pickup-points.json';
        $data = ['frontend_key' => $frontend_key,
            'agent' => $agent,
            'zipcode' => $zipcode,
            'country' => $country,
            'address' => $address,
            /* 'number' => $number */
        ];

        $sError_msg_wrong_zipcode = $this->l('Enter zip code and Address to see Pickup Points');
        $sError_msg_no_pickuppoint = $this->l('Could not find any pickup locations for the selected zip code');

        $response = [];
        if ((!empty($zipcode) || !empty($address)) && !empty($frontend_key)) {
            //Calls the Curl method, from the main class
            $tempShopList = json_decode($this->callPakkelabelsAPI($method, $url, $data));

            if (!empty($tempShopList->message)) {
                if ($tempShopList->message == 'Invalid frontend_key') {
                    return [
                        'status' => false,
                        'error' => $this->l('Please add a valid delivery module key in admin!'),
                    ];
                } else {
                    return [
                        'status' => false,
                        'error' => $tempShopList->message,
                    ];
                }
            }

            if (!empty($tempShopList)) {
                $response['shoplist_json'] = $tempShopList;
                $response['frontendoption'] = Configuration::get('PAKKELABELS_FRONT_OPTION');
                $response['status'] = true;

                //makes the map
                ob_start(); ?><div id="map-canvas"></div><?php
                $response['map'] = ob_get_clean();

                //makes the shop list
                if (Configuration::get('PAKKELABELS_FRONT_OPTION') == 'Popup') {
                    ob_start(); ?>
                    <div class="pakkelabels-shoplist">
                        <ul class="pakkelabels-shoplist-ul">
                        <?php
                        foreach ($tempShopList as $shop) { ?>
                            <li data-shopid="<?php echo $shop->number; ?>" class="pakkelabels-shop-list">
                                <div class="pakkelabels-single-shop">
                                <div class="pakkelabels-radio-button"></div>
                                <div class="pakkelabels-company-name"><?php echo trim($shop->company_name); ?></div>
                                <div class="pakkelabels-Address"><?php echo trim($shop->address); ?></div>
                                <div class="pakkelabels-ZipAndCity">
                                    <?php echo '<span class="pakkelabels-zipcode">' . trim($shop->zipcode) . '</span>,'; ?>
                                    <?php echo '<span class="pakkelabels-city">' . ucwords(mb_strtolower(trim($shop->city), 'UTF-8')) . '</span>'; ?>
                                </div>
                                <div class="pakkelabels-Packetshop">
                                        <?php echo 'ID: ' . Tools::strtoupper($agent) . '-' . trim($shop->number); ?>
                                </div>
                                </div>
                            </li>
                        <?php
                        } ?>
                        </ul>
                    </div>
                <script>
                    jQuery('.pakkelabels-shop-list').each(function() {

                        jQuery(this).on('click', function()
                        {
                            jQuery('#shop_radio_'+jQuery(this).attr('data-shopid')).trigger('click')
                            li_addlistener_open_marker(jQuery(this));
                            //remove all the class selected, from previous li's
                            jQuery('.pakkelabels-shop-list').removeClass('selected');
                            //adds the selected class to the newly selected li
                            jQuery(this).addClass('selected');
                            //sets the checked = true on the newly selected LI
                            jQuery(this).children().children(':radio').prop('checked', true);
                        })
                    })

                    if($('.pakkelabels-shop-list.selected').length == 0)
                        $('.pakkelabels-shop-list').first().trigger('click');
                </script>
            <?php
                } elseif (Configuration::get('PAKKELABELS_FRONT_OPTION') == 'radio') {
                    ob_start(); ?>
                <div class="pakkelabels-shoplist">
                    <?php
                    foreach ($tempShopList as $shop) { ?>
                        <div data-shopid="<?php echo $shop->number; ?>" class="pakkelabels-shop-list">
                        <div class="pakkelabels-single-shop">
                            <div class="pakkelabels-radio-button"></div>
                            <div class="selected_content">
                            <div class="pakkelabels-company-name"><?php echo trim($shop->company_name); ?></div>
                            <div class="pakkelabels-Address">
                            <?php echo trim($shop->address) . ','; ?>
                            <?php echo  '<span class="pakkelabels-zipcode">' . trim($shop->zipcode) . '</span>'; ?>
                                <?php echo '<span class="pakkelabels-city">' . ucwords(mb_strtolower(trim($shop->city), 'UTF-8')) . '</span>'; ?>
                            </div>
                            <div class="pakkelabels-Packetshop" style="display:none;">
                                <?php echo 'ID: ' . Tools::strtoupper($agent) . '-' . trim($shop->number); ?>                     </div>
                            </div>
                            </div>
                        </div>
                        </div>
                        <?php
                    } ?>
                </div>
                <script>
                    jQuery('.pakkelabels-shop-list').each(function() {
                        jQuery(this).on('click', function() {
                            jQuery('#hidden_choosen_shop').attr('shopid', jQuery(this).attr('data-shopid'));
                            //adds the shop information to the #selected_shop div
                            jQuery('#selected_shop_header').html(selected_shop_header);
                            jQuery('#selected_shop_context').html(jQuery(this).children().children('.selected_content').html());
                            //remove all the class selected, from previous li's
                            if(typeof checkdroppointselected !=='undefined')
                                checkdroppointselected(this); 
                            jQuery('.pakkelabels-shop-list').removeClass('selected');
                            //adds the selected class to the newly selected li
                            jQuery(this).addClass('selected');
                            setTimeout(function()
                            {
                                if(typeof saveCartdetails !=='undefined')
                                saveCartdetails();
                            }, 1000) 
                        })
                    })

                    if($('.pakkelabels-shop-list.selected').length == 0)
                        $('.pakkelabels-shop-list').first().trigger('click');
                </script>
            <?php
                } else {
                    ob_start(); ?>
                    <ul class="pakkelabels-shoplist-dropdownul dropdown-menu">
                        <?php foreach ($tempShopList as $shop) { ?>
                            <li data-shopid="<?php echo $shop->number; ?>" class="pakkelabels-shop-list">
                                <div class="pakkelabels-single-shop">
                                    <div class="row">
                                        <div class="col-xs-2">
                                        <div class="pakkelabels-dropimage">
                                        <img src="<?php echo _MODULE_DIR_; ?>pakkelabels_shipping/views/img/<?php echo $agent; ?>.png" style="width:100%">
                                        </div>
                                                                                    </div>
                                            <div class="col-xs-10">
                                                <div class="pakkelabels-company-name"><?php echo trim($shop->company_name); ?></div>
                                                <div class="pakkelabels-Address"><?php echo trim($shop->address); ?></div>
                                                <div class="pakkelabels-ZipAndCity">
                                                <?php echo  '<span class="pakkelabels-zipcode">' . trim($shop->zipcode) . '</span>,'; ?>
                                                <?php echo '<span class="pakkelabels-city">' . ucwords(mb_strtolower(trim($shop->city), 'UTF-8')) . '</span>'; ?>
                                                </div>
                                                <div class="pakkelabels-Packetshop">
                                                <?php echo 'ID: ' . Tools::strtoupper($agent) . '-' . trim($shop->number); ?>                     </div>
                                            </div>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                        <li class="pakkelabels_lilogo"> <span>Powered by Shipmondo</span></li>
                    </ul>
                <script>
                    jQuery('.pakkelabels-shop-list').each(function()
                    {
                        jQuery(this).on('click', function()
                        {
                            jQuery('#hidden_choosen_shop').attr('shopid', jQuery(this).attr('data-shopid'));
                            //adds the shop information to the #selected_shop div
                            jQuery('#selected_shop_header').html(selected_shop_header);
                            jQuery('#selected_shop_context').html(jQuery(this).children().children().children('.col-xs-10').html());
                            //remove all the class selected, from previous li's
                            if(typeof checkdroppointselected !=='undefined')
                                checkdroppointselected(this); 
                            jQuery('.pakkelabels-shop-list').removeClass('selected');
                            //adds the selected class to the newly selected li
                            jQuery(this).addClass('selected');
                            setTimeout(function()
                            {
                                if(typeof saveCartdetails !=='undefined')
                                    saveCartdetails();
                            }, 1000) 
                        })
                    })
                </script>
            <?php
                }
                $response['shoplist'] = ob_get_clean();
            } else {
                $response['status'] = false;
                $response['error'] = $sError_msg_no_pickuppoint;
            }
        } else {
            $response['status'] = false;
            $response['error'] = $sError_msg_wrong_zipcode;
        }

        return $response;
    }

    public function callPakkelabelsAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                if ($data) {
                    $url = sprintf('%s?%s', $url, http_build_query($data));
                }
        }

        // Optional Authentication:
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, 'username:password');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
