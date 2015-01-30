$(document).ready(function() {

    /*** Brief View / Detailed View Toggle***/
    var upd = '&upd=123456';
    //set a cookie for brief/detailed view toggle, uses jquery.cookie.js
    $('a.bv').click(function(){
        $.cookie('view_toggle', 'brief', { expires: 2 });
        //location.reload(true);
        //window.location = location.href + '?upd=' + 123456;
        $('.view-toggle a').attr('href', location.href.replace(upd, '') + upd);
    });

    //brief view is selected
    if ($.cookie('view_toggle') == 'brief') {
    
        //give the body an easy targeting class
        $('body').addClass('brief-view');
    
        //hide some stuff
        //$('[class^="googlePreviewDiv"], .format-list, .detailed').addClass('hide');
        $('.ajaxItem .iconlabel, .edition, .description, .imprint, .col-sm-2.col-xs-3').addClass('hide');

        //make the title column wider
        $('.ajaxItem .col-sm-7').removeClass('col-sm-7').addClass('col-sm-9');
        $('.ajaxItem .col-xs-6').removeClass('col-xs-6').addClass('col-xs-9');

        //regenerate the view toggle links
        $('.bv').replaceWith('<span class="bv">Brief View</span>');
        $('.dv').replaceWith('<a class="dv" href="">Detailed View</a>');

        //set trim length to use below
        var l = 88;
    }
    else {
        //set trim length to use below
        var l = 154;
    }

    /*$('.result .result').each(function(){
        //move openurl (sfx) coverage information to a new place
        var sfxtext = $(this).find('[id^="openUrlEmbed"]');
        $(this).find('.status').after(sfxtext);
    });*/

    //trim long titles
    $('.ajaxItem .title').each(function(){
        if ($(this).text().length > +l ) {
            $(this).text($(this).text().substr(0,+l));
            $(this).append('...');
        }
    });

    //destroy cookie for brief/detailed view toggle, uses jquery.cookie.js, brings detailed view back
    $('a.dv').click(function(){
        //remove the cookie
        $.cookie('view_toggle', null);
        //location.reload(true);
        //window.location = location.href + '?upd=' + 123456;
        $('.view-toggle a').attr('href', location.href.replace(upd, '') + upd);
    });
});

