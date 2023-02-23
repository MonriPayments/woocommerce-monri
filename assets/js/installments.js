jQuery(document).ready(function () {
    jQuery(document).on("keyup", "#monri-card-number", function () {
        var input_value = jQuery("#monri-card-number").val();
        input_value = input_value.replace(/\s+/g, '');
        if (input_value.match("^4058400000000005") ||
            input_value.match("^441280") ||
            input_value.match("^433310") ||
            input_value.match("^414637") ||
            input_value.match("^414636") ||
            input_value.match("^557105") ||
            input_value.match("^404867") ||
            input_value.match("^460043"))
        {
            jQuery("#monri-card-installments-p").show();
        } else {
            jQuery("#monri-card-installments-p").hide();
            jQuery("#monri-card-installments").val('1').change();
        }
    });
    jQuery(document).on("change", "#monri-card-installments", function () {
        var value = jQuery("#monri-card-installments").val();
        jQuery(".price-increase-message").hide();
        if (value > 1) {
            jQuery("#price-increase-" + value).show();
        }
    });
});
