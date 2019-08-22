/*!
 * Bootstrap v3.3.5 (http://getbootstrap.com)
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */
var markerIcon = ''; // Data marker icon
var defaultZoom = 5; // Zoom level of the map
var defaultMaxZoom = 18; // Max zoom level of the map
var map; // Variable for map
var infowindow; // Variable for marker info window
var ms_marker_list = {};
var bounds = ''; // Set bounds
var sCompany_name;
var sPacketshop_id;
var sAdress;
var sCity;
var iZipcode;       
var sFirstname;
var sLastname;
var labelData;

var usedZipCode = '';
var usedAgent = '';
var gotError = '';
var selectedCarrier = '';
var hiddenvalue = '';
var defaultZipcode = '';
/** Roohi***/
var defaultAddress = '';

function getShopList(shipping_agent, zipcode, address)
{
    var myZipcode = zipcode;//.trim();

    /* if(usedZipCode == zipcode && usedAgent == shipping_agent){
        if(gotError !== ''){
           // alert(gotError);
			$(".custom_msg").html(gotError);
            return false;
        }
        jQuery('#Pakkelabels_zipcode_field').removeAttr("disabled");
        jQuery('#Pakkelabels_address_field').removeAttr("disabled");
        jQuery('#pakkelabels_find_shop_btn').removeAttr("disabled");
		if(iPakkelabels_ID_WINDOW=='Popup') {
			jQuery('#pakkelabel-modal').modal({
				show: true,
				backdrop: true,
			});
		}
        return true;
    }else{
		jQuery('.pakkelabels-shoplist-dropdownul').remove();
        usedZipCode = zipcode;
        usedAgent = shipping_agent;
        gotError = '';
    } 
 */
    markerIcon =  shipping_agent + '.png';
	laoding = '<img src="'+baseDir+'modules/pakkelabels_shipping/views/img/loadiing.gif" class="loading_drop">';
	jQuery('#pakkelabels_find_shop_btn span').removeClass('caret');
	jQuery('#pakkelabels_find_shop_btn span').html(laoding);
    jQuery.ajax({
        url: baseDir + 'modules/pakkelabels_shipping/ajax.php',
        type: 'POST',
        data: { 'method': 'ajaxGetShopList', 'sShippinAgent': shipping_agent, 'iZipcode': zipcode, 'iAddress': address },
       // dataType: 'json',
        success: function(response)
        {
			jQuery('#pakkelabels_find_shop_btn span').addClass('caret');
			jQuery('#pakkelabels_find_shop_btn span').html('');
            jQuery('#Pakkelabels_zipcode_field').prop("disabled",false);
            jQuery('#pakkelabels_find_shop_btn').prop("disabled",false);
            if(response){
                var returned = JSON.parse(response);
                if(returned.status == false)
                {
                    gotError = returned.error;
                   //alert(returned.error);
					jQuery(".custom_msg").html(gotError);
					jQuery(".loading_radio").hide();
					if(jQuery("#onepagecheckoutps_step_two .cm").length<=0)
						jQuery("#onepagecheckoutps_step_two").prepend("<div class='cm'>"+gotError+"</div>");
					else
						jQuery("#onepagecheckoutps_step_two .cm").html(gotError);
					
					
					return false;
                } else if(returned.status = true)
                {
					jQuery("#onepagecheckoutps_step_two .cm").remove();
					if (returned.frontendoption == 'dropdown') {
                        setTimeout(function()
                        {
                           jQuery('.pakkelabels-shoplist').append(returned.shoplist);
                           jQuery('.pakkelabels-shoplist').addClass('open');
                        }, 1000) 
                    }else if (returned.frontendoption == 'radio') {
                        setTimeout(function()
                        {
							jQuery(".loading_radio").hide();
                           jQuery('.pakkelabels-shoplist').append(returned.shoplist);
                           jQuery('.pakkelabels-shoplist').addClass('open');
                        }, 1000) 
                    } else {
						jQuery('#pakkelabel-modal').modal({
							show: true,
							backdrop: true,
						});
						jQuery('#pakkelabel-map-wrapper').html(returned.map);
						jQuery('#pakkelabel-list-wrapper').html(returned.shoplist);
						//jQuery('#pakkelabels-hidden-shop').html(returned.hidden_pakkelabels);
						markerFile = returned.shoplist_json;
						undefined_cords_markerFile = new Array();

						for (var key in markerFile) {
							if ( !markerFile[key].hasOwnProperty('latitude') || !markerFile[key].hasOwnProperty('longitude'))
							{
								undefined_cords_markerFile[key] = markerFile[key];
								delete markerFile[key];
							}
						}

						//loads the map and other map related stuff
						loadMap();

						//loads any markers that already have cords
						if(Object.keys(markerFile).length >= 1)
						{
							jQuery(markerFile).each(function()
							{
								loadMarker(this);
							})
						}

						//checks if their is any markers, that have no lng or lat that needs to be loaded
						if(Object.keys(undefined_cords_markerFile).length > 0)
						{
							load_markers_without_cords_from_streetname(undefined_cords_markerFile)
						}

						setTimeout(function()
						{
							google.maps.event.trigger(map, 'resize');
							map.fitBounds(bounds);
						}, 1000);
					}
                }
            } else {
                jQuery('#Pakkelabels_zipcode_field').prop("disabled",false);
                jQuery('#pakkelabels_find_shop_btn').prop("disabled",false);
                //alert(returned.error);
				$(".custom_msg").text(returned.error);
				if(jQuery("#onepagecheckoutps_step_two .cm").length<=0)
						jQuery("#onepagecheckoutps_step_two").prepend("<div class='cm'>"+returned.error+"</div>");
					else
						jQuery("#onepagecheckoutps_step_two .cm").html(returned.error);
				
					
				return false;
            }
        }
    });
}
/** Roohi code end s***/

function saveCartdetails()
{
	if(jQuery('#selected_shop_context').children().size() != 0)
	{
		jQuery('#hidden_selected_shop_context').html(jQuery('#selected_shop_context').html());
		sCompany_name = jQuery('#selected_shop_context > .pakkelabels-company-name').text();
		sPacketshop_id = jQuery('#selected_shop_context  > .pakkelabels-Packetshop').text();
		sAdress = jQuery('#selected_shop_context > .pakkelabels-Address').text();
		sCity = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-city').text();
		iZipcode = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-zipcode').text();
		//the last chosen was a pakkeshop, it want save the primary address again



		if (jQuery('#address_delivery').is(':visible')) {

			var new_billing = false;
			if (jQuery('#addressesAreEquals').prop('checked') == true) {
				new_billing = true;
			}	
			
		   jQuery.ajax({
				url: baseDir + 'modules/pakkelabels_shipping/ajax.php',
				type: 'POST',
				data: { 'method': "ajaxTempCartAddressOPC", 'sCompany_name': sCompany_name, 'sPacketshop_id': sPacketshop_id, 'sAdress': sAdress, 'sCity': sCity, 'iZipcode': iZipcode, 'addBilling': new_billing },
				//success: success, sFirstname sLastname
				dataType: 'json',
				error: function (response)
				{
				  // Error
				},
				success: function (response) {
				   
					if (response.status == "success")
					{
						if(jQuery('.hidden_primary_address').children().length == 0)
						{
							jQuery('.hidden_primary_address').html('' +
								'<div class="hidden_primary_firstname">'+ jQuery('#address_delivery > li.address_firstname').text() +'</div>' +
								'<div class="hidden_primary_company">'+ jQuery('#address_delivery > li.address_company').text() +'</div>' +
								'<div class="hidden_primary_address1">'+ jQuery('#address_delivery > li.address_address1').text() +'</div>' +
								'<div class="hidden_primary_address2">'+ jQuery('#address_delivery > li.address_address2').text() +'</div>' +
								'<div class="hidden_primary_city">'+ jQuery('#address_delivery > li.address_city').text() +'</div>' +
								'<div class="hidden_primary_country">'+ jQuery('#address_delivery > li.address_country_name').text() +'</div>' +
								'<div class="hidden_primary_phone">'+ jQuery('#address_delivery > li.address_phone').text() +'</div>' +
								'<div class="hidden_primary_phone_mobile">'+ jQuery('#address_delivery > li.address_phone_mobile').text() +'</div>' +
								'<div class="hidden_primary_id">'+ jQuery('#id_address_delivery > option:selected').val() +'</div>'
							)
						}
						
					} else if (response.status == "error") {
						//alert(error_no_shop_selected);
						/** Roohi***/
						$(".custom_msg").text(error_no_shop_selected);
						if(jQuery("#onepagecheckoutps_step_two .cm").length<=0)
							jQuery("#onepagecheckoutps_step_two").prepend("<div class='cm'>"+error_no_shop_selected+"</div>");
						else
							jQuery("#onepagecheckoutps_step_two .cm").html(error_no_shop_selected);
						
					
						return false;
						/** Roohi end **/
					}

				}
			});
		   
		}
		else
		{
			var sFirstname = jQuery('#customer_firstname').val();
			var sLastname = jQuery('#customer_lastname').val();

			jQuery.ajax({
				url: baseDir + 'modules/pakkelabels_shipping/ajax.php',
				type: 'POST',
				data: { 'method': "ajaxTempCartAddressOPCGuest", 'sCompany_name': sCompany_name, 'sPacketshop_id': sPacketshop_id, 'sAdress': sAdress, 'sCity': sCity, 'iZipcode': iZipcode, 'sFirstname': sFirstname, 'sLastname': sLastname },
				dataType: 'json',
				success: function (response) {
					if (response) {

					}
					else if (response.status == "error") {
						/** Roohi***/
						$(".custom_msg").text(error_no_shop_selected);
						if(jQuery("#onepagecheckoutps_step_two .cm").length<=0)
						jQuery("#onepagecheckoutps_step_two").prepend("<div class='cm'>"+error_no_shop_selected+"</div>");
					else
						jQuery("#onepagecheckoutps_step_two .cm").html(error_no_shop_selected);
						
					
						return false;
						/** end ***/
					}
				}
			});
		}
	}
}
//Calls googles gmap api, and gets the cords for the streetnames from the shopilst generated by pakkelabels
function load_markers_without_cords_from_streetname(aMarkerFile)
{
    var geocoder = new google.maps.Geocoder();
    jQuery(aMarkerFile).each(function(key)
    {
        var address = this.address + ", " + this.city + ", " + this.zipcode
        var iShopid = this.number
       geocoder.geocode({'address': address}, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK)
            {
                aMarkerFile[key].latitude = results[0].geometry.location.lat() + "";
                aMarkerFile[key].longitude = results[0].geometry.location.lng() + "";
                loadMarker(aMarkerFile[key]);
                
            }
           else
            {
                jQuery('[data-shopid="' + iShopid +'"] > div').append('<div class="no_cords_found">'+ error_no_cords_found +'</div>');
            }
        })
    })
    return aMarkerFile;
}


//loads the map and other map related stuff
function loadMap()
{
	var defaultLatlng = new google.maps.LatLng(55.9150835, 10.4713954); // Set default map properties
	var myOptions = {
		zoom: defaultZoom,
		center: defaultLatlng,
		maxZoom: defaultMaxZoom,
		mapTypeId: google.maps.MapTypeId.Road
	}; // Option for google map object

    // Create new map and place it in the target DIV
    map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
    // Create new info window for marker detail pop-up
    infowindow = new google.maps.InfoWindow();
    bounds = new google.maps.LatLngBounds();
}


//loades a single marker (used by loaderMarkers())
function loadMarker(markerData)
{
    // Create new marker location
    var myLatlng = new google.maps.LatLng(markerData['latitude'], markerData['longitude']);

    // Create new marker
    var marker = new google.maps.Marker({
        map: map,
        position: myLatlng,
        icon: dataRoot + '/views/img/' + markerIcon
    });

    // Add information to the marker
    google.maps.event.addListener(marker, 'click', (function (marker) {
        return function () {
            infowindow.setContent( "<strong>" + markerData['company_name'] + "</strong><br/>" + markerData['address'] + "<br/> " + markerData['city'] + " <br/> " + markerData['zipcode']);
            infowindow.open(map, marker);
            var shop_list = jQuery('.pakkelabels-shoplist > ul >li');
            shop_list.removeClass('selected').filter('[data-shopid=' + markerData['number'] + ']').trigger('click').addClass('selected');
            jQuery('#shop_radio_'+markerData['number']).trigger('click');

            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selected_shop_header);
			 jQuery('#selected_shop_wrapper').addClass("add_border");
        }
    })(marker));
    bounds.extend(marker.position);
    //adds a marker to the list of markers
    ms_marker_list[markerData['number']] = marker;

    /*if(markerData['number'] == jQuery('#hidden_choosen_shop').attr('shopid'))
    {
        jQuery('li[data-shopid="'+markerData['number']+'"').trigger('click');
    };*/ 
}


//When a LI with a shop is pressed, the assosiated marker will have its informationwindow opened
function checkdroppointselected(eventElement){
	hiddenvalue = jQuery(eventElement).attr('data-shopid');
	 // Show buy button
	jQuery('#btn_place_order_disabled').remove();
	jQuery("#btn_place_order").show();
}
function li_addlistener_open_marker(eventElement)
{
    var event = eventElement;

    jQuery.each(ms_marker_list, function(key, value)
    {

        if (key == event['context'].getAttribute('data-shopid'))
        {
           // jQuery('#selected_shop_context').html("");
          //  jQuery(event).children().children().each(function()
          //  {
         //       jQuery('#selected_shop_context').append( jQuery(this).get(0).outerHTML);
           // })

			// Set hidden values
            jQuery('#hidden_choosen_shop').attr('shopid', event['context'].getAttribute('data-shopid'));
            hiddenvalue = event['context'].getAttribute('data-shopid');
            
            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selected_shop_header);
			 jQuery('#selected_shop_wrapper').addClass("add_border");
            jQuery('#selected_shop_context').html(eventElement['context']['childNodes'][1].innerHTML);
            
            // Show buy button
            jQuery('#btn_place_order_disabled').remove();
			jQuery("#btn_place_order").show();
            
            //adds the shop information to the marker corresponding with the shop
            infowindow.setContent(eventElement['context']['childNodes'][1].innerHTML);
            infowindow.open(map,value);
        }
    });
}



jQuery(document).ready(function () {
    //html to be injected into the prestashop
    var sModalHTML = '<div class="pakkelabel-modal fade-pakkelabel" id="pakkelabel-modal" tabindex="-1" role="dialog" aria-labelledby="packetshop window"> <div class="pakkelabel-modal-dialog" role="document"> <div class="pakkelabel-modal-content"> <div class="pakkelabel-modal-header"> <h4 class="pakkelabel-modal-title" id="pakkelabel-modal-header-h4">'+ sPakkelabel_modal_header_h4 +'</h4> <button id="pakkelabel-modal-header-button"type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <div class="pakkelabel-open-close-button-wrap"> <div class="pakkelabel-open-close-button pakkelabel-open-map">'+ sPakkelabel_open_map +'</div> <div class="pakkelabel-open-close-button pakkelabel-hide-map">'+ sPakkelabel_hide_map +'</div> </div></div> <div class="pakkelabel-modal-body"> <div id="pakkelabel-map-wrapper"></div> <div id="pakkelabel-list-wrapper"></div> </div> <div class="pakkelabel-modal-footer"> <button id="choose-stop-btn" type="button" class="button button-small btn btn-default" data-dismiss="modal">'+ sChoose_stop_btn +'</button> <div class="powered-by-pakkelabels">Powered by</div> </div> </div> </div> </div>';
	/** Roohi***/
	if(iPakkelabels_ID_WINDOW=='Popup') {
		var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="custom_msg"></div><div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"><input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"> </div> <div> <button class="button button-small btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' + sPakkelabels_find_shop_btn_text + '</button>  </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
	}else if(iPakkelabels_ID_WINDOW=='radio') {
		var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="custom_msg"></div><div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <span><img src="'+baseDir+'modules/pakkelabels_shipping/views/img/loadiing.gif" class="loading_radio" style="display:none;"></span><input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"><input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"> </div> <div class="pakkelabels-shoplist">  </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper" style="display:none;"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
	} else {
		var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <div class="custom_msg"></div><input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"> <input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"></div> <div class="pakkelabels-shoplist dropdown"><button class="button button-small btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' + sPakkelabels_find_shop_btn_text + '<span class="caret"></span></button> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
	}
	if(iPakkelabels_ID_WINDOW=='Popup') {
		var sZipcodeHTMLtr = '<tr><td><div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="custom_msg"></div><div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"><input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"> </div> <div> <button class="button button-small btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' + sPakkelabels_find_shop_btn_text + '</button>  </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div></td></tr>';
	}else if(iPakkelabels_ID_WINDOW=='radio') {
		var sZipcodeHTMLtr = '<tr><td><div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="custom_msg"></div><div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div><span><img src="'+baseDir+'modules/pakkelabels_shipping/views/img/loadiing.gif" class="loading_radio" style="display:none;"></span> <input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"> <input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"></div> <div class="pakkelabels-shoplist">  </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div></td></tr>';
	} else { 
		var sZipcodeHTMLtr = '<tr><td><div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="custom_msg"></div><div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <input type="hidden" id="Pakkelabels_zipcode_field" name="pakkelabels_zipcode" class="input" placeholder="'+sPakkelabels_zipcode_field+'"><input type="hidden" id="Pakkelabels_address_field" name="pakkelabels_address" class="input" placeholder="'+sPakkelabels_address_field+'"> </div> <div class="pakkelabels-shoplist dropdown"><button class="button button-small btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' + sPakkelabels_find_shop_btn_text + '<span class="caret"></span></button> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div class="pakkelabels-clearfix" id="selected_shop_header">'+ sSelected_shop_header +'</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div></td></tr>';
	}
    var sHiddenSelectedHTML = '<div style="display:none !important;"><div class="hidden_primary_address"></div><div id="hidden_selected_shop_context"></div><div id="hidden_last_choosen_carrier" carrier_id=""></div><div id="hidden_last_choosen_carrier_radio" carrier_id=""></div></div>'
    //appends the modal to the body of the prestashop checkout page

    jQuery('body').append(sModalHTML);
    jQuery('footer').append(sHiddenSelectedHTML);

    //if a pakkelabels.dk shipping method choosen on pageload, add the zipcode div
    var checked_shipping = jQuery('.delivery_option input[type="radio"]:checked').val();
    console.log(checked_shipping)
	// remove comma from shipping value
	if (checked_shipping) {
		checked_shipping = checked_shipping.replace(',','');
	console.log(checked_shipping)
		if(checked_shipping == iPakkelabels_ID_GLS || checked_shipping == iPakkelabels_ID_POSTNORD || checked_shipping == iPakkelabels_ID_DAO || checked_shipping == iPakkelabels_ID_BRING) {   
			// Hide buy button and show other button
			jQuery('#pakkelabels-zipcode-wrapper').remove();
			jQuery('#btn_place_order_disabled').remove();
			var final_button = jQuery("#btn_place_order");
			var final_button_parent = jQuery(final_button).parent();
			jQuery("#btn_place_order").hide();
			jQuery(final_button_parent).append('<button type="button" id="btn_place_order_disabled" class="btn btn-primary btn-lg pull-right"><i class="fa-pts fa-pts-shopping-cart fa-pts-1x"></i> '+sPakkelabel_modal_header_h4+'</button>');

     
		    tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('.delivery_option');  
		    if (!tempVar) {
		    	tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('tr');
		    	jQuery(sZipcodeHTMLtr).insertAfter(tempVar);
		    } else {    
		    	jQuery(sZipcodeHTML).insertAfter(tempVar);
		    }
		    
		    // Add zipcode from customer address
			if (defaultZipcode) {
				jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
			}
				/** Roohi **/
			if (defaultAddress) {
				jQuery('#Pakkelabels_address_field').val(defaultAddress);
			}
			if(defaultZipcode=='' && defaultAddress==''){
				// Check if we got an input field
				zipcode = jQuery('#delivery_postcode').val();
				jQuery('#Pakkelabels_zipcode_field').val(zipcode);
				address = jQuery('#delivery_address1').val();
				jQuery('#Pakkelabels_address_field').val(address);
			}
			console.log(checked_shipping);
			if(iPakkelabels_ID_WINDOW=='radio') {
				jQuery(".loading_radio").show();
				if(checked_shipping == iPakkelabels_ID_GLS ){
				getShopList('gls', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
				}
				if(checked_shipping == iPakkelabels_ID_DAO){
				getShopList('dao', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
				}
				if(checked_shipping == iPakkelabels_ID_POSTNORD){
				getShopList('pdk', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
				}
				if(checked_shipping == iPakkelabels_ID_BRING){
				getShopList('bring', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
				}
			}
			/**Roohi ends***/
		} else {
			jQuery('#btn_place_order_disabled').remove();
			jQuery("#btn_place_order").show();
		}
		
		console.log(checked_shipping)
/** Roohi end ***/
    }

    //New shipping method is selected, remove "saved" data about the old
    jQuery(document).on('click', '.delivery_option input[type="radio"]', function() {
		
		jQuery('#hidden_last_choosen_carrier_radio').attr('carrier_id', jQuery('.delivery_option input[type="radio"]:checked').val());
        
        // Reset certain values
        jQuery('#hidden_last_choosen_carrier').attr('carrier_id', '');
        jQuery('#hidden_selected_shop_context').html("");
		/***Roohi ***/
		var checked_shipping = jQuery('.delivery_option input[type="radio"]:checked').val();
    	// remove comma from shipping value
    	if (checked_shipping) {
	    	checked_shipping = checked_shipping.replace(',','');
		if(checked_shipping == iPakkelabels_ID_GLS || checked_shipping == iPakkelabels_ID_POSTNORD || checked_shipping == iPakkelabels_ID_DAO || checked_shipping == iPakkelabels_ID_BRING) {   
		// Remove zipcode wrapper
        jQuery('#pakkelabels-zipcode-wrapper').remove();
		 tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('.delivery_option');  
		if (!tempVar) {
			tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('tr');
			jQuery(sZipcodeHTMLtr).insertAfter(tempVar);
		} else {    
			jQuery(sZipcodeHTML).insertAfter(tempVar);
		}
        
		/** Roohi***/
		console.log(jQuery("input[name='delivery_postcode']").val());
		jQuery(".cm").html('');
		if(jQuery("input[name='delivery_postcode']").val()!=''){
			jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
		}
		if(jQuery("input[name='delivery_address1']").val()!=''){
			jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
		}
		
	
	    
		if(iPakkelabels_ID_WINDOW=='radio') {
			jQuery(".loading_radio").show();
			console.log(jQuery('#Pakkelabels_zipcode_field').val());
			if(checked_shipping == iPakkelabels_ID_GLS ){
			getShopList('gls', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
			}
			if(checked_shipping == iPakkelabels_ID_DAO){
			getShopList('dao', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
			}
			if(checked_shipping == iPakkelabels_ID_POSTNORD){
			getShopList('pdk', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
			}
			if(checked_shipping == iPakkelabels_ID_BRING){
			getShopList('bring', jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());
			}
		}
		}
		}
		/** Roohi ends ***/
    });
 
    //Called when the prestashop reloads all the shipping LIs and stuff, and adds the zipcode div
    jQuery( document ).ajaxComplete(function( event, xhr, settings ) {	        	
    	var checked_shipping = jQuery('.delivery_option input[type="radio"]:checked').val();
    	// remove comma from shipping value
    	if (checked_shipping) {
	    	checked_shipping = checked_shipping.replace(',','');
	    
	        if( checked_shipping == iPakkelabels_ID_GLS || checked_shipping == iPakkelabels_ID_DAO || checked_shipping == iPakkelabels_ID_POSTNORD || checked_shipping == iPakkelabels_ID_BRING) {
				
				if (checked_shipping == selectedCarrier && hiddenvalue && jQuery('#pakkelabels-zipcode-wrapper').length) {
					// Do nothing
				} else {
		        	jQuery('#pakkelabels-zipcode-wrapper').remove();

				    // Reset used zipcode from latest search
				    usedZipCode = '';
				    hiddenvalue = '';
				    
				    // Hide buy button and show other button
				    jQuery('#btn_place_order_disabled').remove();
					var final_button = jQuery("#btn_place_order");
					var final_button_parent = jQuery(final_button).parent();
					jQuery("#btn_place_order").hide();
					jQuery(final_button_parent).append('<button type="button" id="btn_place_order_disabled" class="btn btn-primary btn-lg pull-right"><i class="fa-pts fa-pts-shopping-cart fa-pts-1x"></i> '+sPakkelabel_modal_header_h4+'</button>');

				    tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('.delivery_option');  
				    if (!tempVar) {
				    	tempVar = jQuery('.delivery_option input[type="radio"]:checked').closest('tr');
				    	jQuery(sZipcodeHTMLtr).insertAfter(tempVar);
				    } else {    
				    	jQuery(sZipcodeHTML).insertAfter(tempVar);
				    }
				    
				    if(jQuery('#cgv').is(':checked'))
				    {
				        if(jQuery('#hidden_selected_shop_context').not(':empty') && jQuery('#hidden_last_choosen_carrier').attr('carrier_id')+"," == jQuery('.delivery_option_radio:checked').val())
				        {
				            jQuery('#selected_shop_header').text(selected_shop_header);
				            jQuery('#selected_shop_context').html(jQuery('#hidden_selected_shop_context').html());
				        }
				    }
				    
				    jQuery('#pakkelabels-zipcode-wrapper').html(labelData);
				}
				
				// Add zipcode from customer address
				if (defaultZipcode) {
					jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
				}
				/** Roohi***/
				if (defaultAddress) {
					jQuery('#Pakkelabels_address_field').val(defaultAddress);
				}
				if(defaultZipcode=='' && defaultAddress=='') {
					// Check if we got an input field
					zipcode = jQuery('#delivery_postcode').val();
					jQuery('#Pakkelabels_zipcode_field').val(zipcode);
					address = jQuery('#delivery_address1').val();
					jQuery('#Pakkelabels_address_field').val(address);
				}
				/** Roohi ends ***/
					
	        } else {
	        	jQuery('#btn_place_order_disabled').remove();
				jQuery("#btn_place_order").show();
	        }
	        
	        selectedCarrier = checked_shipping;
        }
    });
    
    jQuery(document).on('click', '#HOOK_PAYMENT a, .confirm_button', function(e) {
		e.preventDefault();
		
		var checked_shipping = jQuery('.delivery_option input[type="radio"]:checked').val();
    	// remove comma from shipping value
    	checked_shipping = checked_shipping.replace(',','');
		
		if(checked_shipping == iPakkelabels_ID_GLS || checked_shipping == iPakkelabels_ID_DAO || checked_shipping == iPakkelabels_ID_POSTNORD || checked_shipping == iPakkelabels_ID_BRING) {
				// Check billing address for DAO, GLS or PDK
				var packetinfo = jQuery('.pakkelabels-Packetshop').text();											
				if (packetinfo.indexOf('ID: GLS') == -1 && packetinfo.indexOf('ID: PDK') == -1 && packetinfo.indexOf('ID: DAO') == -1 && packetinfo.indexOf('ID: BRING') == -1) {
					//alert('Du har valgt en ugyldig Pakkeshop'); // Maybe scroll to invoice address fields
					$(".custom_msg").text('Du har valgt en ugyldig Pakkeshop');
					if(jQuery("#onepagecheckoutps_step_two .cm").length<=0)
						jQuery("#onepagecheckoutps_step_two").prepend("<div class='cm'>Du har valgt en ugyldig Pakkeshop</div>");
					else
						jQuery("#onepagecheckoutps_step_two .cm").html('Du har valgt en ugyldig Pakkeshop');
					return;
				}	            
	        } 
	       
	       	if (jQuery(this).attr('href')) {
	        	window.location = jQuery(this).attr('href');
	        }
	});

    //Event fired when the find nearest shop is pressed
    jQuery(document).on('click', '#pakkelabels_find_shop_btn', function() {
		
		/** Roohi***/
		jQuery(".cm").html('');
		if(jQuery("input[name='delivery_postcode']").val()!=''){
			jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
		}
		if(jQuery("input[name='delivery_address1']").val()!=''){
			jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
		}
		/** Roohi ed ***/
        var sFirstname = jQuery('#customer_firstname').val();
        var sLastname = jQuery('#customer_lastname').val();

        iPakkelabels_choosen_delivery_option = jQuery('.delivery_option input[type="radio"]:checked').val();
        iPakkelabels_choosen_delivery_option = iPakkelabels_choosen_delivery_option.replace(/\D/g,'');
        if(iPakkelabels_choosen_delivery_option == iPakkelabels_ID_GLS)
        {
            sChoosenShippingAgent = 'gls';
        }
        else if(iPakkelabels_choosen_delivery_option == iPakkelabels_ID_DAO)
        {
            sChoosenShippingAgent = 'dao';
        }
        else if(iPakkelabels_choosen_delivery_option == iPakkelabels_ID_POSTNORD)
        {
            sChoosenShippingAgent = 'pdk';
        }
        else if(iPakkelabels_choosen_delivery_option == iPakkelabels_ID_BRING)
        {
            sChoosenShippingAgent = 'bring';
        }
        
        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        
        jQuery('#hidden_last_choosen_carrier').attr('carrier_id', iPakkelabels_choosen_delivery_option)
        getShopList(sChoosenShippingAgent, jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());/** Roohi***/

    });

    jQuery(document).on('keypress', '#Pakkelabels_zipcode_field', function(event) {
        if (event.keyCode == 13 ) {
            event.preventDefault();
            if(!jQuery('#pakkelabels_find_shop_btn').is(":disabled")) {

                iPakkelabels_choosen_delivery_option = jQuery('.delivery_option input[type="radio"]:checked').val();
                iPakkelabels_choosen_delivery_option = iPakkelabels_choosen_delivery_option.replace(/\D/g, '');
                if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_GLS) {
                    sChoosenShippingAgent = 'gls';
                }
                else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_DAO) {
                    sChoosenShippingAgent = 'dao';
                }
                else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_POSTNORD) {
                    sChoosenShippingAgent = 'pdk';
                }
                else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_BRING) {
                    sChoosenShippingAgent = 'bring';
                }
                
                defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();

                jQuery('#hidden_last_choosen_carrier').attr('carrier_id', iPakkelabels_choosen_delivery_option)
                getShopList(sChoosenShippingAgent, jQuery('#Pakkelabels_zipcode_field').val(),jQuery('#Pakkelabels_address_field').val());/** Roohi***/
                jQuery('#Pakkelabels_zipcode_field').blur();
                jQuery('#Pakkelabels_zipcode_field').prop("disabled", true);
                jQuery('#pakkelabels_find_shop_btn').prop("disabled", true);
            }
        }
    })

    //If modal window is open, enter will choose the shop for the user
    jQuery(document).on('keypress', function(event)
    {
        if(jQuery('.pakkelabels-shop-list').hasClass('selected')  && event.keyCode == 13 && jQuery('#pakkelabel-modal:visible').length != 0)
        {
            jQuery('#choose-stop-btn').trigger( "click" );
            jQuery('#choose-stop-btn').blur();
        }
    });

   // shows map
    jQuery('.pakkelabel-open-map').on('click', function()
    {
        jQuery('.pakkelabel-hide-map').show();
        jQuery('.pakkelabel-open-map').hide();
        jQuery('#pakkelabel-map-wrapper').show();
        google.maps.event.trigger(map, 'resize');
        map.fitBounds(bounds);
    })

    //hide map
    jQuery('.pakkelabel-hide-map').on('click', function()
    {
        jQuery('.pakkelabel-hide-map').hide();
        jQuery('.pakkelabel-open-map').show();
        jQuery('#pakkelabel-map-wrapper').hide();
    })

    jQuery('#pakkelabel-modal').on('show.bs.modal', function (e)
    {
        jQuery('body').toggleClass('pakkelabels-modal-shown');
        jQuery('.pakkelabel-modal-body').scrollTop(0);
    });


    //Sets the choosen shipping address when modal closes
    jQuery('#pakkelabel-modal').on('hidden.bs.modal', function (e)
    {
		saveCartdetails();
    });


    jQuery('.payment_module').on('click', function(event)
    {
        if(jQuery('#cgv').is(':checked') && jQuery('.payment_module:visible').length != 0 && jQuery('#selected_shop_context > .pakkelabels-Packetshop:visible').length)
        {

        }
        else
        {
            event.preventDefault();
        }
    })


    jQuery(document).on('click', 'input.delivery_option_radio', function()
    {
        if(jQuery('.delivery_option_radio:checked').val() != iPakkelabels_ID_GLS+',' && jQuery('.delivery_option_radio:checked').val() != iPakkelabels_ID_DAO+',' && jQuery('.delivery_option_radio:checked').val() != iPakkelabels_ID_POSTNORD+',' && jQuery('.delivery_option_radio:checked').val() != iPakkelabels_ID_BRING+',')
        {
			 jQuery('#pakkelabels-zipcode-wrapper').remove();
			 		jQuery('#selected_shop_wrapper').removeClass("add_border");

            if(jQuery('.hidden_primary_address').children().length != 0)
            {
                sFullName = jQuery('.hidden_primary_address > .hidden_primary_firstname').text();
                sCompany = jQuery('.hidden_primary_address > .hidden_primary_company').text();
                sAddress1 = jQuery('.hidden_primary_address > .hidden_primary_address1').text();
                sAddress2 = jQuery('.hidden_primary_address > .hidden_primary_address2').text();
                sCityZipcode = jQuery('.hidden_primary_address > .hidden_primary_city').text();
                sCountry = jQuery('.hidden_primary_address > .hidden_primary_country').text();
                sPhone = jQuery('.hidden_primary_address > .hidden_primary_phone').text();
                sPhoneMobile = jQuery('.hidden_primary_address > .hidden_primary_phone_mobile').text();
                sID = jQuery('.hidden_primary_address > .hidden_primary_id').text();

                var aCityZipcode = [];                      //new storage
                sCityZipcode = sCityZipcode.split(' ');     //split by spaces
                aCityZipcode.push(sCityZipcode.shift());    //add the number
                aCityZipcode.push(sCityZipcode.join(' '));  //and the rest of the string

                var aFullName = [];                   //new storage
                sFullName = sFullName.split(' ');     //split by spaces
                aFullName.push(sFullName.shift());    //add the number
                aFullName.push(sFullName.join(' '));  //and the rest of the string

                jQuery.ajax({
                    url: baseDir + 'modules/pakkelabels_shipping/ajax.php',
                    type: 'POST',
                    data: { 'method': 'ajaxUpdatePrimaryAddress', 'sFirstName': aFullName[0], 'sLastName': aFullName[1], 'sCompany': sCompany, 'sAddress1': sAddress1, 'sAddress2': sAddress2, 'iZipcode': aCityZipcode[0], 'sCity': aCityZipcode[1], 'sCountry': sCountry, 'sPhone': sPhone, 'sPhoneMobile': sPhoneMobile, 'sID': sID },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status = 'success')
                        {
                            jQuery('#address_delivery > .address_firstname').text(aFullName[0] + " " + aFullName[1]);
                            jQuery('#address_delivery > .address_company').text(sCompany);
                            jQuery('#address_delivery > .address_address1').text(sAddress1);
                            jQuery('#address_delivery > .address_address2').text(sAddress2);
                            jQuery('#address_delivery > .address_postcode').text(aCityZipcode[0] + " " + aCityZipcode[1]);
                            jQuery('#address_delivery > .address_country_name').text(sCountry);
                            jQuery('#address_delivery > .address_phone').text(sPhone);
                            jQuery('#address_delivery > .address_phone_mobile').text(sPhoneMobile);
                            jQuery('.hidden_primary_address').html("");

                            formatedAddressFieldsValuesList[sID]['formated_fields_values']['postcode']  = aCityZipcode[0];
                            formatedAddressFieldsValuesList[sID]['formated_fields_values']['city']      = aCityZipcode[1];
                            formatedAddressFieldsValuesList[sID]['formated_fields_values']['company']   = sCompany;
                            formatedAddressFieldsValuesList[sID]['formated_fields_values']['address1']  = sAddress1;
                            formatedAddressFieldsValuesList[sID]['formated_fields_values']['address2']  = sAddress2;
                        }
                        else if (response.status == "error")
                        {
                            // Error
                        }
                    },
                    error: function (response)
                    {
                        // Error
                    }
                });
            }
        }
    })


    //adds 3 events to the zipcode text field, that will disable the "find shop button", until a zipcode thats 4 in lentgh % numeric is choosen!
    jQuery(document).on('keyup focusout input change', '#Pakkelabels_zipcode_field',function () {
        if (jQuery('#Pakkelabels_zipcode_field').val().length > 0) {
            jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
        }
        else {
            jQuery('#pakkelabels_find_shop_btn').prop("disabled", true);
        }
    });
});







