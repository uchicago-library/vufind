/*global extractClassParams, path*/

function loadResolverLinks($target, openUrl) {
    $target.addClass('ajax_availability');
    var url = path + '/AJAX/JSON?' + $.param({method:'getResolverLinks',openurl:openUrl});
    $.ajax({
        dataType: 'json',
        url: url,
        success: function(response) {
            if (response.status == 'OK') {
                $target.removeClass('ajax_availability')
                    .empty().append(response.data);
            } else {
                $target.removeClass('ajax_availability').addClass('error')
                    .empty().append(response.data);
            }
        }
    });
}

$(document).ready(function() {
    // assign action to the openUrlWindow link class
    $('a.openUrlWindow').click(function(){
        var params = extractClassParams(this);
        var settings = params.window_settings;
        window.open($(this).attr('href'), 'openurl', settings);
        return false;
    });

    if ($.cookie('view_toggle') != 'brief'){
        // assign action to the openUrlEmbed link class
        $('.template-name-view a.openUrlEmbed, .template-name-results a.openUrlEmbed').each(function(){
            var params = extractClassParams(this);
            var openUrl = $(this).children('span.openUrl:first').attr('title');
            $(this).hide();
            loadResolverLinks($('#openUrlEmbed'+params.openurl_id).removeClass('hidden'), openUrl);
            return true; //Default is fals, changing to true fixes it, I don't know why
        });
    }
    else {
        $('.template-name-view a.openUrlEmbed').click(function(){
            var params = extractClassParams(this);
            var openUrl = $(this).children('span.openUrl:first').attr('title');
            $(this).hide();
            loadResolverLinks($('#openUrlEmbed'+params.openurl_id).removeClass('hidden'), openUrl);
            return true; //Default is fals, changing to true fixes it, I don't know why
        });
    }
});
