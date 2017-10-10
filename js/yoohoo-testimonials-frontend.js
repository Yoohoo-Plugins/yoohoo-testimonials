jQuery(document).ready(function() {
	

	//ajax to update the WP Query
	jQuery('body').on('click', '.yoohoo_testimonial_paginate', function(){
		
		var page_number = jQuery(this).attr('pgnum');

		jQuery.ajax({
			url: yoohoo_testimonials_ajaxurl,
			type: 'POST',
			data: {
				action: 'change_yoohoo_testimonial',
				page_num: page_number,
			},
			success: function(response){
				console.log(response);
				jQuery('.yoohoo-testimonials-wrapper').html(response);
			}
		});
	});
});