jQuery(document).ready(function($) {

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

    var all_descriptions = $('#tab-variation_description .variation').hide();

	var variation = $('input[name=variation_id]').attr('value');
	
	if(variation != '')
	{
		all_descriptions.hide();
		$('.item'+variation).show();
	}
    
    // $('.variations select').on('change', function(){
    $('input[name=variation_id]').on('change', function(){
	
		var variation = $(this).attr('value'); //$('input[name=variation_id]').attr('value');
	
		if(variation != '')
		{
			all_descriptions.hide();
			$('.item'+variation).show();
		}
	
	});
});
