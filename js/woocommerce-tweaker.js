jQuery(document).ready(function($) {

	// Disable tabs on product page
	// if(typeof disable_tabs_on_product_page != 'undefined' && disable_tabs_on_product_page == true)
	// {
	// 	$( '.woocommerce-tabs .panel' ).show();

	// 	$( '.woocommerce-tabs ul.tabs' ).hide();
	// }

	// Product category widget enhancement
	if(typeof product_category_widget_enhancement != 'undefined' && product_category_widget_enhancement == true)
	{
		var lists = $('.product-categories .children');
			lists.hide();
			lists.prev().bind('click', function(evt){
				evt.preventDefault();
				$(this).toggleClass('active');
				$(this).next('.children').slideToggle().toggleClass('opened');
			});
	}

	// Default variation description

	if(typeof disable_tabs_on_product_page != 'undefined' && disable_tabs_on_product_page == true) var all_descriptions = $('#panel-variation_description .variation').hide();
	else var all_descriptions = $('#tab-variation_description .variation').hide();

	var variation = $('input[name=variation_id]').attr('value');
	
	if(variation != '')
	{
		all_descriptions.hide();
		$('.item'+variation).show();
	}
    
    // $('.variations select').on('change', function(){
    
    if($('input[name=variation_id]').length > 1 && $('input[name^=bundle_variation_id]').length > 0)
    {
    	// console.log($('input[name=variation_id]').length);

    	$('input[name^=bundle_variation_id]').on('change', function(){

    		all_descriptions.hide();

    		$('input[name^=bundle_variation_id]').each(function(index){

    			$('.item'+$(this).val()).show();
    			console.log($(this).val());
    		});
    	});
    }
    else
    {
    	$('input[name=variation_id]').on('change', function(){
	
		var variation = $(this).attr('value'); //$('input[name=variation_id]').attr('value');
	
		if(variation != '')
		{
			all_descriptions.hide();
			$('.item'+variation).show();
		}
	
		});
    }
    
});
