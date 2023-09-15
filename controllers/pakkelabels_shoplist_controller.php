<?php
/**
*  @author    Pakkelabels
*  @copyright 2017 Pakkelabel
*  @license   All rights reserved
*/

class Pakkelabels_Shoplist_Controller extends Module
{

    public function get_shop_list_callback($zipcode, $agent, $number, $frontend_key, $country = 'DK')
    {
        
        $method = 'GET';
        $url = 'https://service-points.pakkelabels.dk/pickup-points.json';
        $data = array(  'frontend_key' => $frontend_key,
            'agent' => $agent,
            'zipcode' => $zipcode,
            'country' => $country,
            //'address' => '',
            'number' => $number
        );

        $sError_msg_wrong_zipcode = $this->l('The zipcode is empty - please try again');
        $sError_msg_no_pickuppoint = 'Kunne ikke finde nogen afhentningsteder for det valgte postnummer';
        /* oversættes senere */
        $response=array();
        if (!empty($zipcode) && !empty($frontend_key)) {
            //Calls the Curl method, from the main class
            $tempShopList = json_decode($this->callPakkelabelsAPI($method, $url, $data));

            if (!empty($tempShopList->message)) {
                if ($tempShopList->message == 'Invalid frontend_key') {
                    return array('status' => false, 'error' => 'Tilføj venligst en gyldig fragtmodul nøgle');//$this->l('Please add a valid deliverymodule key in Admin!'));
                } else {
                    return array('status' => false, 'error' => $tempShopList->message);
                }
            }

            if (!empty($tempShopList)) {
                $response['shoplist_json'] = $tempShopList;
                $response['frontendoption'] = Configuration::get('PAKKELABELS_FRONT_OPTION');
                $response['status'] = true;

                //makes the map
                ob_start();
                ?><div id="map-canvas"></div><?php
                $response['map'] = ob_get_clean();

                //makes the shop list
if (Configuration::get('PAKKELABELS_FRONT_OPTION')=='Popup') {
                ob_start();
                ?><div class="pakkelabels-shoplist">
                    <ul class="pakkelabels-shoplist-ul">
                        <?php
                        foreach ($tempShopList as $shop) {
                            ?>
                            <li data-shopid="<?php echo $shop->number; ?>" class="pakkelabels-shop-list">
                                    <div class="pakkelabels-single-shop">
                                        <div class="pakkelabels-radio-button"></div>
                                        <div class="pakkelabels-company-name"><?php echo trim($shop->company_name); ?></div>
                                        <div class="pakkelabels-Address"><?php echo trim($shop->address); ?></div>
                                        <div class="pakkelabels-ZipAndCity">
                                            <?php echo  '<span class="pakkelabels-zipcode">' . trim($shop->zipcode);
                                            echo '</span>, <span class="pakkelabels-city">' ;
                                            echo ucwords(mb_strtolower(trim($shop->city), 'UTF-8')) .'</span>'; ?>
                                        </div>
                                        <div class="pakkelabels-Packetshop"><?php echo 'ID: ' . Tools::strtoupper($agent) .'-' . trim($shop->number); ?></div>
                                    </div>
                                <?php
                                ?>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
                <script>
                    jQuery('.pakkelabels-shop-list').each(function()
                    {
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
                </script>
            <?php } else {
                ob_start();
                ?>
                    <ul class="pakkelabels-shoplist-dropdownul dropdown-menu">
                        <?php
                        $count=1;
                        foreach ($tempShopList as $shop) {
                            if ($count<=10) {
                            ?>
                                <li data-shopid="<?php echo $shop->number; ?>" class="pakkelabels-shop-list">
                                    <div class="pakkelabels-single-shop">
                                        <div class="row">
                                            <div class="col-xs-2">
                                                <div class="pakkelabels-dropimage">
                                                    <img src="<?php echo _MODULE_DIR_;?>pakkelabels_shipping/views/img/<?php echo $agent;?>.png" style="width:100%">
                                                </div>
                                            </div>
                                            <div class="col-xs-10">
                                                <div class="pakkelabels-company-name"><?php echo trim($shop->company_name); ?></div>
                                                <div class="pakkelabels-Address"><?php echo trim($shop->address); ?></div>
                                                <div class="pakkelabels-ZipAndCity">
                                                    <?php echo  '<span class="pakkelabels-zipcode">' . trim($shop->zipcode);
                                                    echo '</span>, <span class="pakkelabels-city">' ;
                                                    echo ucwords(mb_strtolower(trim($shop->city), 'UTF-8')) .'</span>'; ?>
                                                </div>
                                                <div class="pakkelabels-Packetshop"><?php echo 'ID: ' . Tools::strtoupper($agent) .'-' . trim($shop->number); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                            $count++;
                        }
                        ?>
                        <li class="pakkelabels_lilogo"> <span>Powered by Pakkelabels.dk</span></li>
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
            <?php }
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
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }
        // Optional Authentication:
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "username:password");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
