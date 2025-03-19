jQuery(function ($) {

    var rules = {}, toListen = [];
    $('input[data-depends],textarea[data-depends],select[data-depends]').each(function (i, el) {
        rules[$(el).attr('id')] = {};
        var i, idName;
        for (i in $(el).data('depends')) {
            idName = '#woocommerce_monri_' + i;
            rules[$(el).attr('id')][idName] = $(el).data('depends')[i];
            if (!toListen.includes(idName)) {
                toListen.push(idName);
            }
        }
    });

    var updateOptions = function () {
        var show, el;
        for (el in rules) {
            show = true;
            for (depends in rules[el]) {
                if (!$(depends).parents('tr').is(':visible')) {
                    show = false;
                    break;
                }

                if (Array.isArray(rules[el][depends])) {
                    if (!rules[el][depends].includes($(depends).val())) {
                        show = false;
                        break;
                    }
                } else {
                    if ($(depends).val() != rules[el][depends]) {
                        show = false;
                        break;
                    }
                }

                if ($(depends).attr('type') === 'checkbox' && !$(depends).is(':checked')) {
                    show = false;
                    break;
                }
            }
            show ? $('#' + el).parents('tr').show() : $('#' + el).parents('tr').hide();
        }
    }

    $(toListen.join(',')).on('change', updateOptions);
    updateOptions();

});

