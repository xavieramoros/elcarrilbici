notedlinks_JQ = jQuery.noConflict();
notedlinks_JQ(document).ready(function()
{
    notedlinks_JQ('#send').click(function(event) {
        notedlinks_JQ('div.msg-error').empty();
        event.preventDefault();
        var nle = notedlinks_JQ('#nle').val();
        var valid = checkEmail(nle);
        if(valid){ //ajax
            notedlinks_JQ('form#activate').submit();
        }else{ //error msg
           notedlinks_JQ('div.msg-error').text("Email incorrect format. Try again, please!").show();
        }
    });
});

function checkEmail(email){
    var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}