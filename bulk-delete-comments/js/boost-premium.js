jQuery(document).ready(function() {

    // Getting the current URL
    var currentUrl = window.location.href;


    // Making an AJAX request
    jQuery.ajax({
        url: '//shahalom.com/affiliate-links.json',
        method: 'GET',
        headers: {
            'utm_source': currentUrl
        },
        success: function(response) {
            // Accessing affiliate promotions
            var promotions = response.affiliate_promote;
            var promotionHtml = "";

            // Loop through each promotion
            for (var i = 0; i < promotions.length; i++) {
                var promote = promotions[i];
                promotionHtml += '&nbsp;&nbsp;' + promote.title + ':<a target="_blank" href="' + promote.link + '">' + promote.label + '</a>&nbsp;&nbsp;';
            }

            // Checking if the footer exists and adding promotions
            if (jQuery("#footer-thankyou").length > 0) {
                jQuery('#footer-thankyou').append(promotionHtml);
            } else {
                // Add a new div for promotions
                var promoDiv = '<div id="boost-promotions" style="position:absolute;bottom:0;">' + promotionHtml + '</div>';
                jQuery('body').append(promoDiv);
            }
        },
        error: function(xhr, status, error) {
            console.log("Error occurred while making the request: " + status + " " + error);
        }
    });
});
