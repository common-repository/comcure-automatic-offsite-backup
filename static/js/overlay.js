jQuery(function($) {
    $blockUI = function(message) {
        body = (typeof message == 'undefined') ? '<img class="wpspin" src="' + comcure_plugin_url + 'static/images/loading-trans.gif">' : '<div class="comcure-loader">' + message + '</div>';
        $('<div></div>').attr('id', 'comcure-overlay').css({
            'position': 'absolute',
            'top': 0,
            'left': 0,
            'z-index': 99999,
            'opacity': 0.6,
            'width':'100%',
            'height':'100%',
            'color':'white',
            'background-color':'black'
        }).html(body).appendTo('body');
    };
    $unblockUI = function() {
        $('#comcure-overlay').remove();
    };
});