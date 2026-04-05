jQuery(function ($) {

    function initProductSearch(context) {

        $(context).find('.wc-product-search').each(function () {

            const $el = $(this);

            if ($el.data('select2')) {
                return;
            }

            $el.select2({
                width: '100%',
                minimumInputLength: 2,
                allowClear: true,
                placeholder: 'Search product…',
                ajax: {
                    url: multipackDiscount.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term || '',
                            action: 'multipack_search_products',
                            security: multipackDiscount.nonce
                        };
                    },
                    // processResults: function (data) {

                    //     if (!data || typeof data !== 'object') {
                    //         return { results: [] };
                    //     }

                    //     const results = [];

                    //     $.each(data, function (id, text) {
                    //         results.push({
                    //             id: id,
                    //             text: text
                    //         });
                    //     });

                    //     return { results: results };
                    // },
                    // cache: true
                    processResults: function (data) {

                        if (!data || !Array.isArray(data.results)) {
                            return { results: [] };
                        }

                        return { results: data.results };
                    },
                    cache: true
                }
            });
        });
    }

    // function hasDuplicateProducts() {

    //     const used = [];
    //     let duplicate = false;

    //     $('.wc-product-search').each(function () {

    //         let val = $(this).val();
    //         if (!val) return;

    //         val = Array.isArray(val) ? val[0] : val;

    //         const $selection = $(this)
    //             .next('.select2')
    //             .find('.select2-selection');

    //         if (used.includes(val)) {
    //             duplicate = true;
    //             $selection.css('border', '2px solid #dc3232');
    //         } else {
    //             used.push(val);
    //             $selection.css('border', '');
    //         }
    //     });

    //     return duplicate;
    // }

    initProductSearch('#multipack-rows');

    $('#add-multipack-row').on('click', function () {

        const $row = $('#multipack-row-template')
            .clone()
            .removeAttr('id')
            .show();
        $row.find('.select2').remove();
        $row.find('input').val('');
        $row.find('select').empty();

        $('#multipack-rows').append($row);

        initProductSearch($row);
    });

    $('#multipack-rows').on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
    });

    // $('form').on('submit', function (e) {
    //     if (hasDuplicateProducts()) {
    //         e.preventDefault();
    //         alert('Duplicate product detected. Each product can have only one rule.');
    //         return false;
    //     }
    // });
});
