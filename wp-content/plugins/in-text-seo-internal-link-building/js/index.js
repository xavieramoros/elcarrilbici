notedlinks_JQ = jQuery.noConflict();
notedlinks_JQ(document).ready(function()
{
    //fields validation of the settings form
    notedlinks_JQ("#save-changes").click(
        function(event)
        {
            event.preventDefault();
            var valid = true;

            notedlinks_JQ("#msg-key-max").hide();
            notedlinks_JQ("#msg-page-max").hide();

            var keyMaxLinks = notedlinks_JQ('#keyword_max').val();
            var pageMaxLinks = notedlinks_JQ('#page_max').val();

            if (!isNumber(keyMaxLinks))
            {
                notedlinks_JQ("#msg-key-max").text("You haven't written a enter number. Please, try it again!").show();
                valid = false;
            }
            else if(keyMaxLinks<0 || keyMaxLinks>30)
            {
                notedlinks_JQ("#msg-key-max").text("You have to write a enter number between 0 and 30. Please, try it again!").show();
                valid = false;
            }

            if (!isNumber(pageMaxLinks))
            {
                notedlinks_JQ("#msg-page-max").text("You haven't written a enter number. Please, try it again!").show();
                valid = false;
            }
            else if(pageMaxLinks<0 || pageMaxLinks>30)
            {
                notedlinks_JQ("#msg-page-max").text("You have to write a enter number between 0 and 30. Please, try it again!").show();
                valid = false;
            }

            if(valid)
            {
                notedlinks_JQ('form#notedlinks').submit();
            }
        }
    );

    function isNumber(n) 
    {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

});