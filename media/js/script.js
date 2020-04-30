jQuery(function() {
	jQuery('#shipping_countries_id').on('change', function(e) {
		// Joomla.loadingLayer('show');
		var element = jQuery(this),
			selectedValues = [];
		element.find(':selected').each(function() {
			selectedValues.push(jQuery(this).val());
		});
		var formData = new FormData();
		formData.append('countries', JSON.stringify(selectedValues));
		//formData.append('method', element.parents('form').find('[name="sh_pr_method_id"]').val());
		jQuery.ajax('/index.php?option=com_ajax&plugin=region_filter_sm&group=jshopping&format=raw', {
			method: "POST",
			data: {
				'countries': JSON.stringify(selectedValues)
			}
		})
			.then(
			function success(userInfo) {
				console.log(userInfo);
			}
		);



		//
		// jQuery.ajax();
		// jQuery('input[name=task]').val('reload');
		// this.form.submit();
		// Joomla.getOptions('csrf.token', '')
	});
});