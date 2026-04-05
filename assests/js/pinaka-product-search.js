jQuery(function($) {
	$('#product_ids').select2({
		ajax: {
			url: pinakaProductSearch.ajax_url,
			dataType: 'json',
			delay: 250,
			data: function(params) {
				return {
					action: 'pinaka_product_search',
					security: pinakaProductSearch.nonce,
					term: params.term
				};
			},
			processResults: function(data) {
				return { results: data };
			}
		},
		minimumInputLength: 2,
		placeholder: 'Search products...',
	});
});