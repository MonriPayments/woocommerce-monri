jQuery( document ).ready(function()
{
    $('#place_order').click(function()
	{
        validateForm();
    });

    function validateForm()
	{
        var first_name = jQuery("#billing_first_name").val();
        var last_name = jQuery("#billing_last_name").val();
        var postcode = jQuery("#billing_postcode").val();
        var phone = jQuery("#billing_phone").val();
        var email = jQuery("#billing_email").val();
        var address = jQuery("#billing_address_1").val();
        var city = jQuery("#billing_city").val();

        if(first_name.val().length>1)
        {
            jQuery(first_name).after('<span class="error"> First name must be between 3-100 characters</span>');
        }

        var inputVal = new Array(names, company, email, telephone, message);
        var inputMessage = new Array("name", "company", "email address", "telephone number", "message");

        $('.error').hide();

        if(inputVal[0] == "")
        {
            $('#nameLabel').after('<span class="error"> Please enter your ' + inputMessage[0] + '</span>');
        }
        else if(!nameReg.test(names))
        {
            $('#nameLabel').after('<span class="error"> Letters only</span>');
        }

        if(inputVal[1] == "")
        {
            $('#companyLabel').after('<span class="error"> Please enter your ' + inputMessage[1] + '</span>');
        }

        if(inputVal[2] == "")
        {
            $('#emailLabel').after('<span class="error"> Please enter your ' + inputMessage[2] + '</span>');
        }
        else if(!emailReg.test(email))
        {
            $('#emailLabel').after('<span class="error"> Please enter a valid email address</span>');
        }

        if(inputVal[3] == "")
        {
            $('#telephoneLabel').after('<span class="error"> Please enter your ' + inputMessage[3] + '</span>');
        }

        else if(!numberReg.test(telephone))
        {
            $('#telephoneLabel').after('<span class="error"> Numbers only</span>');
        }

        if(inputVal[4] == "")
        {
            $('#messageLabel').after('<span class="error"> Please enter your ' + inputMessage[4] + '</span>');
        }
    }
});

