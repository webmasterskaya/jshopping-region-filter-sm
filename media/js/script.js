jQuery(function() {
	jQuery('#shipping_countries_id').on('change', function(e) {
		Joomla.loadingLayer('show');
		var element = jQuery(this),
			selectedValues = [];
		element.find(':selected').each(function() {
			selectedValues.push(jQuery(this).val());
		});
		jQuery.ajax(
			Joomla.getOptions('system.paths').base + '/index.php?option=com_ajax&plugin=region_filter_sm&group=jshopping&format=raw',
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

function changeOptions(btn) {
	btn = jQuery(btn);
	var element = '', chzn = false;
	switch (btn.data('select')) {
		case 'country':
			element = jQuery('#shipping_countries_id');
			if (jQuery('#shipping_countries_id_chzn').length > 0) {
				chzn = true;
			}
			break;
		case 'states':
			element = jQuery('#shipping_states_id');
			if (jQuery('#shipping_states_id_chzn').length > 0) {
				chzn = true;
			}
			break;
	}

	element.find('option').prop('selected', !!(btn.data('mode')));
	element.trigger('change');
	if (chzn) {
		element.trigger('liszt:updated');
	}
}