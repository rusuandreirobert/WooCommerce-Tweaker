jQuery(document).ready(function($) {

	// Product category widget enhancement
	if(typeof product_category_widget_enhancement != 'undefined' && product_category_widget_enhancement == true)
	{
		var lists = $('.product-categories .children');
			lists.hide();
			lists.prev().bind('click', function(evt){
				evt.preventDefault();
				$(this).next('.children').slideToggle();
			});
	}
    
    var all_descriptions = $('#tab-variation_description .variation').hide();

    $('.variations select').live('change', function(){
	
		var variation = $('input[name=variation_id]').attr('value');
	
		if(variation != '')
		{
			all_descriptions.hide();
			$('.item'+variation).show();
		}
	
	});
});
