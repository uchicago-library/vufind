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


    /**
     * Collapse and Expand Items and Summary Holdings 
     */

    // Text for various states
    var viewItemsText = 'View more items <i class="fa fa-arrow-circle-right"></i>';
    var hideItemsText = 'Hide items <i class="fa fa-arrow-circle-down"></i>';
    var viewSummaryText = 'View more volumes <i class="fa fa-arrow-circle-right"></i>';
    var hideSummaryText = 'Hide volumes <i class="fa fa-arrow-circle-down"></i>'; 

    // Links to display for various states
    var viewItems = '<a href="#" class="itemsToggle text-success hide">' + viewItemsText + '</a>';
    var hideItems = '<a href="#" class="itemsToggle text-success">' + hideItemsText + '</a>';
    var viewSummary = '<a href="#" class="summaryToggle text-success hide">' + viewSummaryText + '</a>';
    var hideSummary = '<a href="#" class="summaryToggle text-success">' + hideSummaryText + '</a>';

    $('.holdings-unit table').each(function(){
        // Number of things to display by default
        var displayNum = $(this).data('display');

        // Boolean, is there a toggle link appended
        var hasLink = false;

        // Build toggle link
        //$(this).parent().append(viewItems);

        // Loop over items
        var i = 1;
        $(this).find('[typeof="Offer"]').each(function(){
            // Provide a target for the items toggle link.
            // We'll append a link here if we need one.
            if (i == displayNum) {
                $(this).addClass('itemsToggleTarget');
            }
            // Hide items and append a class when there are more than the allowed quantity
            if (i > displayNum) {
                $(this).hide();
                $(this).addClass('toToggle');
                // Show the view link if possible
                $(this).parent().parent().parent().find('.itemsToggle').removeClass('hide');

                // 
                if(!hasLink){
                    $('.itemsToggleTarget td').append(viewItems);
                    hasLink = true;
                }
            }
            i++;
        });

        // Loop over holdings_text_fields (summary holdings)
        $(this).find('td').each(function(){
            hasLink = false;
            var i = 1;
            $(this).find('[typeof="Enumeration"]').each(function(){
                // Provide a target for the items toggle link.
                // We'll append a link here if we need one.
                if (i == displayNum) {
                    $(this).addClass('summaryToggleTarget');
                }
                // Hide items and append a class when there are more than the allowed quantity
                if (i > displayNum) {
                    $(this).hide();
                    $(this).addClass('toToggle');
                    // Show the view link if possible
                    $(this).parent().find('.summaryToggle').removeClass('hide');

                    // 
                    if(!hasLink){
                        $('.summaryToggleTarget').append(viewSummary);
                        hasLink = true;
                    }
                }
                i++;
            });
        });


    });

    //prevent links from making the page jump
    $('a[href^="#"]').bind('click focus', function(e) {
        e.preventDefault();    var viewItems = '<a href="#" class="itemsToggle text-success hide">' + viewItemsText + '</a>';
    var hideItems = '<a href="#" class="itemsToggle text-success">' + hideItemsText + '</a>';
    });


    // Toggle hidden items
    $('.itemsToggle').click(function(){
        // Toggle the inner html of the link
        var text = $(this).html();
        if (text == viewItemsText) {
            text = hideItemsText;
        }
        else {
            text = viewItemsText;
        }
        $(this).html(text);

        // Show/hide items 
        $(this).parent().parent().parent().find('tr.toToggle').toggle();
    });

    // Toggle hidden holdings_text_fields (summary holdings)
    $('.summaryToggle').click(function(){
        // Toggle the inner html of the link
        var text = $(this).html();
        if (text == viewSummaryText) {
            text = hideSummaryText;
        }
        else {
            text = viewSummaryText;
        }
        $(this).html(text);

        // Show/hide summary holdings text fields
        $(this).parent().parent().find('div.toToggle').toggle();
    });

});

