jQuery(function($) {
    $('#comcure-login').submit(function() {
        $blockUI();        
        $.post('plugins.php?page=comcure-overview&action=login', $(this).serialize(), function(json) {
            if(!json.result) {
                $unblockUI();
                $('#login_error').html(json.reason).show().fadeOut(5000);
            } else {
                document.location = 'plugins.php?page=comcure-overview';
            }
        }, "json");
        return false;
    }); 
});