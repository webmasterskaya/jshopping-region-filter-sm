jQuery(function() {
	jQuery('#shipping_countries_id').on('change', function(e) {
		Joomla.loadingLayer('show');
		var element = jQuery(this),
			selectedValues = [];
		element.find(':selected').each(function() {
			selectedValues.push(jQuery(this).val());
		});
		jQuery.ajax(
			'/index.php?option=com_ajax&plugin=region_filter_sm&group=jshopping&format=raw',
			{
				method: 'GET',
				data: {
					'countries': JSON.stringify(selectedValues),
					'method': element.parents('form').
						find('[name="sh_pr_method_id"]').val(),
				},
			}).then(
			function success(output) {
				jQuery('#shipping_states_id_chzn').remove();
				jQuery('#shipping_states_id').replaceWith(output);
				if (jQuery('#shipping_countries_id_chzn').length > 0 &&
					jQuery('#shipping_states_id').prop('tagName') ===
					'SELECT') {
					jQuery('#shipping_states_id').chosen();
				}
				Joomla.loadingLayer('hide');
			},
		);
	});
});