/* Add the startswWith method to IE11 */
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position){
      position = position || 0;
      return this.substr(position, searchString.length) === searchString;
  };
}
function getAlert(){
    var api = 'https://www.lib.uchicago.edu/api/v2/pages/?type=alerts.AlertPage&fields=title,banner_message,more_info,alert_level,url&format=json';
    json = $.getJSON(api, function(data) {
        var pages = data.items;
        $.each(pages, function(key){
            var page = pages[key];
            var level = page.alert_level;
            var msg = page.banner_message;
            var url = page.url;
            var html = '';
            var link = '';
            if (page.more_info.replace( /<.*?>/g, '' )) {
                link = '<a href="' + url + '">More info...</a>';
            }
            if (level == 'alert-high') {
                html += '<div id="alert" class="container">' + msg + link + ' </div>';
                $('.container.navbar').before(html);
                return false;
            }
        }); 
    });
}

function getDedupedEholdings(issns, target) {
  var hasPHPLinks = target.attr('data-server-side-links');
  $.get( VuFind.path + '/AJAX/JSON?method=dedupedEholdings', 'issns=' + issns, function(data, status, xhr) {
    var response = JSON.parse(data);

    // Delete the Online links box if there aren't any server 
    // side links and nothing is returned from the deduping 
    // service. Should only affect full record page
    if (!hasPHPLinks && !response.data) {
        $('.tab-pane .online').remove();
    }

    target.append(response.data);

    if (response.data != '') {
        target.parent().find('.local-eholding').hide();
    }

    $(target).children('.toggle').click(function() {
        $(this).toggleClass('open');
        $(this).parent().children('.e-list').toggleClass('hide');
    });

  }, 'html');
}

function getMapLink(loc, callnum, prefix, target) {
  $.get( VuFind.path + '/AJAX/JSON?method=mapLink', 'location=' + loc + '&callnum=' + callnum + '&prefix=' + prefix, function(data, status, xhr) {
    var response = JSON.parse(data);
    if(response.data == null) {
        $(target).addClass('hide');
    } else {
        target.html('<i class="fa fa-map-marker" aria-hidden="true"></i> ' + response.data.location);
        target.attr("href", response.data.url);
    }
  }, 'html');
}

$(document).ready(function() {

    /*** Deduped eholdings instead of sfx ***/
    $('[data-issns]').each(function() {
        var issns = $(this).data('issns');
        getDedupedEholdings(issns, $(this));
    });
    
    /*** Maplookup service link ***/
    $('.maplookup').each(function() {
        var loc = $(this).data('location');
        var callnum = $(this).data('callnum');
        var prefix = $(this).data('prefix');
        getMapLink(loc, callnum, prefix, $(this));
    });

    getAlert();

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
        $('.ajaxItem .iconlabel, .edition, .description, .imprint, .col-sm-2.col-xs-0').addClass('hide');

        //make the title column wider
        $('.ajaxItem .col-sm-8').removeClass('col-sm-8').addClass('col-sm-9');

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
     * Collapse and expand bibliographic data 
     */

    // Re-stripe tables on the full record page so that successive tables display as if
    // they are one, continuous table
    $('#record').each(function(){
        $(this).find('tr:odd').css('background-color','#f9f9f9');
        $(this).find('tr:even').css('background-color','#ffffff');
    });

    $('table tr').each(function(){
        var trcolor = $(this).css('backgroundColor');
    });

    // Text for various states
    var viewMoreBibText = 'More details <i class="fa fa-arrow-circle-right"></i>';
    var viewLessBibText = 'Fewer details <i class="fa fa-arrow-circle-down"></i>';

    // Links to display for various states
    var viewMoreBib = '<a href="#" class="bibToggle">' + viewMoreBibText + '</a>';
    var viewLessBib = '<a href="#" class="bibToggle">' + viewLessBibText + '</a>';

    // Number of rows in the additional bibliographic data table
    var bibRowCount = 0;

    // Number of rows to show by default
    var configNum = 1;

    // Table containing additonal bibliographic data
    var addtionalBibData = '#top-hidden';

    $(addtionalBibData).each(function(){
        // Number of things to display by default
        var bibDisplayNum = 2;

        // Boolean, is there a toggle link appended
        var hasBibToggleLink = false;

        // Hidden additional biblographic data count
        $(this).find('tr').each(function(){ 
            bibRowCount++;
        });

        if (bibRowCount > 0 && bibRowCount > configNum) {
            $(addtionalBibData).before(viewMoreBib);
            $(addtionalBibData).hide();
        }
    });

    // Toggle additional bibliographic data
    $('.bibToggle').click(function(){
        // Toggle the inner html of the link
        var text = $(this).html();
        if (text == viewMoreBibText) {
            text = viewLessBibText;
        }
        else {
            text = viewMoreBibText;
        }
        $(this).html(text);

        // Show/hide items
        $(this).parent().find(addtionalBibData).toggle();
    });

    /**
     * Collapse and Expand Subject headings in 
     * the top part of the MARC record 
     */

    // Text for various states
    var viewSubjectsText = 'More subjects <i class="fa fa-arrow-circle-right"></i>';
    var hideSubjectsText = 'Hide subjects <i class="fa fa-arrow-circle-down"></i>';

    // Links to display for various states
    var viewSubjects = '<a href="#" class="subjectsToggle">' + viewSubjectsText + '</a>';
    var hideSubjects = '<a href="#" class="subjectsToggle">' + hideSubjectsText + '</a>';

    $('.topic').parent().each(function(){
        // Number of things to display by default
        var displayNum = 3;

        // Boolean, is there a toggle link appended
        var hasLink = false;

        // Loop over items
        var i = 1;
        $(this).find('.topic').each(function(){
            if (i == displayNum) {
                $(this).addClass('subjectToggleTarget');
            }
            if (i > displayNum) {
                $(this).hide();
                $(this).addClass('subjectToToggle');
                if(!hasLink > 0 ){
                    $(this).before(viewSubjects);
                    hasLink = true;
                }
            }
            i++;
        });
    });
   


    /**
     * Collapse and Expand Items and Summary Holdings 
     */

    // Text for various states
    var viewItemsText = 'View more items <i class="fa fa-arrow-circle-right"></i>';
    var hideItemsText = 'Hide items <i class="fa fa-arrow-circle-down"></i>';
    var viewSummaryText = 'View more holdings <i class="fa fa-arrow-circle-right"></i>';
    var hideSummaryText = 'Hide holdings <i class="fa fa-arrow-circle-down"></i>'; 

    // Links to display for various states
    var viewItems = '<tr><td colspan="3"><a href="#" class="itemsToggle text-success">' + viewItemsText + '</a></td></tr>';
    var hideItems = '<a href="#" class="itemsToggle text-success">' + hideItemsText + '</a>';
    var viewSummary = '<li><a href="#" class="summaryToggle text-success">' + viewSummaryText + '</a></li>';
    var hideSummary = '<li><a href="#" class="summaryToggle text-success">' + hideSummaryText + '</a></li>';


    // Loop over holdings_text_fields (summary holdings)
    $('.summary-holdings').each(function(){
        // Number of things to display by default
        var displayNum = $(this).data('display');

        hasLink = false;
        var i = 1;
        $(this).find('li').each(function(){
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

                // If there isn't already a link
                if(!hasLink){
                    $(this).parent().find('.summaryToggleTarget').after(viewSummary);
                    hasLink = true;
                }
            }
            i++;
        });
    });



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
                //$(this).parent().parent().parent().find('.itemsToggle').removeClass('hide');

                // If there isn't already a link
                if(!hasLink > 0 ){
                    $(this).parent().find('.itemsToggleTarget').after(viewItems);
                    hasLink = true;
                }
            }
            i++;
        });
    });

    //prevent links from making the page jump
    $('a[href^="#"]').bind('click focus', function(e) {
        e.preventDefault();    var viewItems = '<a href="#" class="itemsToggle text-success hide">' + viewItemsText + '</a>';
        var hideItems = '<a href="#" class="itemsToggle text-success">' + hideItemsText + '</a>';
    });

    // Toggle hidden subject headings in the top portion of the MARC record
    $('.subjectsToggle').click(function(){
        // Toggle the inner html of the link
        var text = $(this).html();
        if (text == viewSubjectsText) {
            text = hideSubjectsText;
        }
        else {
            text = viewSubjectsText;
        }
        $(this).html(text);

        // Show/hide items
        $(this).parent().find('.subjectToToggle').toggle();
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
        $(this).parent().parent().find('.toToggle').toggle();
    });

    // Toggle the mini-search box and keep search box terms between windows
    window.searchTerms = $('.mini-search-on .search-query').attr('value');
    $('.mini-box-toggle').click(function() {
        $('.search-query').each(function() {
            $(this).val(window.searchTerms);
        });
        // Toggle the mini-search box in the gray bar
        $('.mini-search-on, .mini-search-off').toggle();
    });
    $(".search-query").change(function(){ 
        window.searchTerms = $(this).val();
    });

    // Clear inputs on the mini-search box in the gray bar
    $('.mini-search #alphaBrowseForm_from, .mini-search #searchForm_lookfor').after('<i class="input-clear fa fa-times-circle-o"></i>');
    $('.mini-search .input-clear').click(function(){
        $(this).parent().find('input').val('');
        $(this).parent().find('input').attr('placeholder',' ');
        $(this).parent().find('input').focus();
        window.searchTerms = '';
    });

    $('#searchtabinfolink').popover({
        'container': '#searchtabinfolink',
        'content': '<div style="padding: 0 10px;"><p>Keyword searches produce lists of records sorted by relevance:</p><p style="padding-left: 10px;"><strong>Basic Keyword Search</strong><br/>Use for exploring a general topic, or if the exact title or author of a book is unknown.</p><p style="padding-left: 10px;"><strong>Advanced Keyword Search</strong><br/>Use for very specific or complex topics.</p><p><strong>Begins With</strong><br/>Begins With allows you to browse through an alphabetical list of titles, authors, subjects, etc.  Use to locate a book when the exact title or author\'s entire name is known, or when searching for items on a specific subject.</p><p><a href="https://www.lib.uchicago.edu/research/help/catalog-help/selecting/" target="_blank">More info</a><br/><a href="https://goo.gl/TqhhYd" target="_blank">90 second video on search types <i style="text-decoration: none;" class="icon-facetime-video "></i></a></p></div>',
        'delay': 500,
        'html': true,
        'placement': 'right',
        'trigger': 'hover focus'
    });
    $('#searchtabinfolink').click(function(e) {
        // fix for Chrome- make sure the link gets focus when it is clicked.
        $(this).focus();
        if ($(e.target).hasClass('external')) {
            window.location.href = $(e.target).attr('href');
        }
    });

    // Homepage cookies. 
    var cookie_settings = { expires: 365, path: '/' };

    function setup_homepage_cookies() {
        // If we're not on the homepage, do nothing.
        if ($('#advSearchForm').length == 0) {
            clearInterval(setup_homepage_cookies_interval_id);
            return;
        }

        // If all four links haven't loaded yet, try again next time.
        if ($('#homepageNavTabs li:nth-child(1) a, #homepageNavTabs li:nth-child(2) a, #basicSearchSwitch a, #advancedSearchSwitch a').length < 4) {
            return;
        }

        // If we made it this far, clear the timer and proceed. 
        clearInterval(setup_homepage_cookies_interval_id);

        // Allow advanced searches from a URL. 
        if (window.location.href.indexOf('/vufind/Search/Advanced') > -1) {
            $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
            $.cookie('basic_or_advanced', 'advanced', cookie_settings);
        }
    
        // If the homepage cookie is set...
        if ($.cookie('keyword_or_begins_with') == 'keyword') {
            if ($.cookie('basic_or_advanced') == 'basic') {
                switchToBasicSearch();
            } else if ($.cookie('basic_or_advanced') == 'advanced') {
                switchToAdvancedSearch();
            }
        } else if ($.cookie('keyword_or_begins_with') == 'begins with') {
            $('#homepageNavTabs li:nth-child(2) a').click();
        }
        // When a user clicks Keyword...
        $('#homepageNavTabs li:nth-child(1) a').click(function(e) {
            if ($.cookie('basic_or_advanced') == 'basic') {
                switchToBasicSearch();
            } else if ($.cookie('basic_or_advanced') == 'advanced') {
                switchToAdvancedSearch();
            }
        });
        // When a user clicks Keyword or Begins With...
        $('#homepageNavTabs li:nth-child(1) a, #homepageNavTabs li:nth-child(2) a').click(function(e) {
            if ($(this).text() == 'Keyword') {
                $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
    
                // Update the title tag
                $('head title').text(getTitleTag());
            }
            if ($(this).text() == 'Begins With') {
                $.cookie('keyword_or_begins_with', 'begins with', cookie_settings);
                    
                // Update the title tag
                $('head title').text(getTitleTag());
            }
        });
        // When a user clicks Basic or Advanced Search...
        $('#basicSearchSwitch a, #advancedSearchSwitch a').click(function(e) {
            if ($(this).text() == 'Basic') {
                $.cookie('basic_or_advanced', 'basic', cookie_settings);
            }
            if ($(this).text() == 'Advanced Search') {
                $.cookie('basic_or_advanced', 'advanced', cookie_settings);
            }
        });
    }

    // When a user clicks "Advanced" in a gray bar mini-searchbox
    $('.mini-adv-link').click(function(e) {
        $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
        $.cookie('basic_or_advanced', 'advanced', cookie_settings);
    });
    // When a user clicks "Browse" in a gray bar mini-searchbox...
    $('.mini-box-toggle.browse').click(function(e) {
        $.cookie('keyword_or_begins_with', 'begins with', cookie_settings);
    });
    // When a user clicks "Keyword" in a gray bar mini-searchbox...
    $('.mini-box-toggle.keyword').click(function(e) {
        $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
    });
    // When a user clicks "Start a new Basic Search" in a gray bar mini-searchbox...
    $('.mini-basic-link').click(function(e) {
        $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
        $.cookie('basic_or_advanced', 'basic', cookie_settings);
    });


    // Set up cookies after a short delay, because some of the things we
    // need are loaded in via javascript. 
    var setup_homepage_cookies_interval_id = setInterval(setup_homepage_cookies, 100);
   
    // Set default cookie values on page load. 
    if ($.cookie('keyword_or_begins_with') == null) {
        $.cookie('keyword_or_begins_with', 'keyword', cookie_settings);
    }
    if ($.cookie('basic_or_advanced') == null) {
        $.cookie('basic_or_advanced', 'basic', cookie_settings);
    }

    // It can take about a minute to recall an item or place a Mansueto
    // request. When a user clicks the submit button, disable it to
    // discourage them from submitting it twice. 
    $('form[name="placeHold"], form[name="mansuetoRequests"]').submit(function(e) {
        var $form = $(this);

        if ($form.data('submitted') === true) {
            // Previously submitted - don't submit again
            e.preventDefault();
        } else {
            // Mark it so that the next submit can be ignored
            $form.data('submitted', true);
        }
    });

    /*
     * Add a referrer parameter to the help link.
     * Use that to give context-specific help.
     */
    $("a[title='Help']").attr('href',
        $("a[title='Help']").attr('href') +
            '?r=' +
            encodeURIComponent(window.location)
        );
});

/**
 * Convert a string to title case.
 */
function getTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

/**
 * Constructs the proper text for a title tag based on the cookie settings.
 */
function getTitleTag() {
    var title_tag = '';
    ($.cookie('keyword_or_begins_with') == 'keyword') ? title_tag = $.cookie('basic_or_advanced') + ' ' + $.cookie('keyword_or_begins_with') + ' Search' : title_tag = $.cookie('keyword_or_begins_with') + ' Search';
    return getTitleCase(title_tag); 
}

(function() {
    /*
     * Make every external link on every page open in a new tab.
     * This should even work for links that are generated dynamically,
     * after the page has already loaded.
     */
    function add_target_blank() {
        $('a[href]').each(function() {
            var h = $(this).attr('href');
            if (h.substring(0, 4) == 'http' || h.substring(0, 2) == '//') {
                $(this).attr('target', '_blank');
                // Needed for Safari support
                $(this).addClass('external');
            }
        });
        // This makes sure services like mansueto requesting open in new
        // tabs.
        $('a.service').attr('target', '_blank');
        // Find more Safari specific code related to opening links in a new tab
        // in the document ready function above.
    };
    setInterval(add_target_blank, 1000);
})();

$(document).ready(function() {
  // Expand and collapse of No-CNet-ID login, when the page is 
  $('#login-toggle').click(function() {
    $(this).parents('.login-toggle-wrapper').next('.login-toggle-content').toggle();
  });

  // Turn bulk action buttons off on page load. 
  $('.bulkActionButtons input[type=submit]').prop('disabled', true);
  // Turn bulk action buttons on when something is checked. 
  $('.template-dir-search.template-name-results .checkbox-select-item, .checkbox-select-all').change(function() {
    var disabled = true;
    if ($('.checkbox-select-item:checked').length > 0) {
        disabled = false;
    }
    if ($('.checkbox-select-item:checked').length > 0) {
        disabled = false;
    }
    $('.bulkActionButtons input[type=submit]').prop('disabled', disabled);
  });

  // Highlight previous links, grey out following
  $('.backlink')
    .mouseover(function() {
      // Underline back
      var t = $(this);
      do {
        t.css({'text-decoration':'underline'});
        t = t.prev();
      } while(t.length > 0);
      // Mute ahead
      t = $(this).next();
      do {
        t.css({'color':'#999'});
        t = t.next();
      } while(t.length > 0);
    })
    .mouseout(function() {
      // Underline back
      var t = $(this);
      do {
        t.css({'text-decoration':'none'});
        t = t.prev();
      } while(t.length > 0);
      // Mute ahead
      t = $(this).next();
      do {
        t.css({'color':''});
        t = t.next();
      } while(t.length > 0);
    });
    // Advanced facets
    function updateOrFacets(url, op) {
      window.location.assign(url);
      var list = $(op).parents('ul');
      var header = $(list).find('li.nav-header');
      list.html(header[0].outerHTML+'<div class="alert alert-info">'+vufindString.loading+'...</div>');
    }
    function setupOrFacets() {
      $('.facetOR').find('.icon-check').replaceWith('<input type="checkbox" checked onChange="updateOrFacets($(this).parent().parent().attr(\'href\'), this)"/>');
      $('.facetOR').find('.icon-check-empty').replaceWith('<input type="checkbox" onChange="updateOrFacets($(this).parent().attr(\'href\'), this)"/> ');
    }

    // Advanced facets
    setupOrFacets();
  
    $('[name=bulkActionForm]').find("input[type=submit]").click(function() {
      // Abort requests triggered by the lightbox
      $('#modal .fa-spinner').remove();
      // Remove other clicks
      $(this).closest('form').find('input[type="submit"][clicked=true]').attr('clicked', false);
      // Add useful information
      $(this).attr("clicked", "true");
    });
});

$(document).ready(function() {
    $('#modal').on('shown.bs.modal', function() {
        // Expand and collapse of No-CNet-ID login
        $(this).find('#login-toggle').click(function() {
            $(this).parents('.login-toggle-wrapper').next('.login-toggle-content').toggle();
        });
    }); 
});

$(document).ready(function() {
    // homepage searches: basic, advanced, begins with.
    if (window.location.pathname.toLowerCase() == '/vufind/') {
        setInterval(function () {
            var url = '';
            if ($('a#advancedSearchSwitch').hasClass('disabled')) {
                url = 'https://www.lib.uchicago.edu/research/help/catalog-help/advanced/';
            } else {
                url = 'https://www.lib.uchicago.edu/research/help/catalog-help/selecting/';
            }
            if (url != '') {
                $('a[title="Help"]').attr('href', url);
            }
        }, 250);
    // all other pages. 
    } else {
        var url = '';
        if (window.location.pathname.toLowerCase().startsWith('/vufind/search/results')) {
            // Keyword search results.
            url = 'https://www.lib.uchicago.edu/research/help/catalog-help/keyword/';
        } else if (window.location.pathname.toLowerCase().startsWith('/vufind/alphabrowse/home')) {
            // Begins with search results. 
            url = 'https://www.lib.uchicago.edu/research/help/catalog-help/begins/';
        } else if (window.location.pathname.toLowerCase().startsWith('/vufind/record')) {
            // Any full record.
            url = 'https://www.lib.uchicago.edu/research/help/catalog-help/full-record/';
        } else if (window.location.pathname.toLowerCase().startsWith('/vufind/myresearch')) {
            // My account.
            url = 'https://www.lib.uchicago.edu/research/help/catalog-help/my-account/';
        }
        if (url != '') {
            $('a[title="Help"]').attr('href', url);
        }
    }
});

$(document).ready(function() {
    if ($('.template-dir-myresearch.template-name-checkedout').length) {
        // CHECKED OUT ITEMS EXPORT PULLDOWN
        // add the sort pulldown.
        var sort = $('#checked_out_items_sort_field').val();
        var sort_labels = new Object;
        sort_labels['callNumber'] = 'Call Number';
        sort_labels['duedate'] = 'Due Date';
        sort_labels['loanedDate'] = 'Checkout Date';
        sort_labels['title'] = 'Title';
        sort_labels['author'] = 'Author';
        sort_labels['loanType'] = 'Loan Type';

        var html = ' ';
        html = html + '<span class="dropdown">';
        html = html + '<button type="button" class="btn btn-default dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Sort By: <strong id="checked_out_items_sort_label">' + sort_labels[sort] + '</strong> <span class="caret"></span></button>';
        html = html + '<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">';
        html = html + '<li><a class="checked_out_items_sort" data-sort="title">Title</a></li>';
        html = html + '<li><a class="checked_out_items_sort" data-sort="author">Author</a></li>';
        html = html + '<li><a class="checked_out_items_sort" data-sort="callNumber">Call Number</a></li>';
        html = html + '<li><a class="checked_out_items_sort" data-sort="loanedDate">Checkout Date</a></li>';
        html = html + '<li><a class="checked_out_items_sort" data-sort="duedate">Due Date</a></li>';
        html = html + '<li><a class="checked_out_items_sort" data-sort="loanType">Loan Type</a></li>';
        html = html + '</ul>';
        html = html + '</span>';
        $('#renewSelected').after(html);

        // submit the form when the sort pulldown has changed. 
        $('.checked_out_items_sort').click(function() {
            // update the label and the hidden sort field and submit the form. 
            $('#checked_out_items_sort_label').html(sort_labels[$(this).attr('data-sort')]);
            $('#checked_out_items_sort_field').attr('value', $(this).attr('data-sort'));
            $('#renewals').submit();
        });

        // add the export pulldown.
        var html = ' ';
        html = html + '<span class="dropdown">';
        html = html + '<button type="button" class="btn btn-default dropdown-toggle checked_out_items_export_dropdown disabled" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Export <span class="caret"></span></button>';
        html = html + '<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">';
        html = html + '<li><a class="checked_out_items_export" data-export-format="EndNoteWeb">EndNoteWeb</a></li>';
        html = html + '<li><a class="checked_out_items_export" data-export-format="EndNote">EndNote/Zotero</a></li>';
        html = html + '<li><a class="checked_out_items_export" data-export-format="BiBTeX">BibTex</a></li>';
        html = html + '</ul>';
        html = html + '</span>';
        $('#renewSelected').after(html);
    }

    // when an export link is clicked, dynamically create and submit a
    // form to get the export. 
    $('.checked_out_items_export').click(function() {
        var form = $('<form>').attr('action', '/vufind/Cart/Export').attr('method', 'post').appendTo('body');
        $('<input>').attr('type', 'hidden').attr('name', 'format').val($(this).attr('data-export-format')).appendTo(form);
        $('.checkbox-select-item:checked').each(function() { 
            $('<input>').attr('type', 'hidden').attr('name', 'ids[]').val($(this).attr('data-record-id')).appendTo(form);
        });
        $('<input>').attr('type', 'submit').attr('name', 'submit').val('Export').appendTo(form);
        form.find('input[type="submit"]').click();
    });

    // toggle the export pulldown's status when individual checkboxes are selected.
    $('.checkbox-select-item').click(function() {
        if ($('.checkbox-select-item:checked').length) {
            $('.checked_out_items_export_dropdown').removeClass('disabled');
        } else {
            $('.checked_out_items_export_dropdown').addClass('disabled');
        }
    });
    // toggle the export pulldown's status when all checkboxes are selected.
    $('.checkbox-select-all').click(function() {
        if ($('.checkbox-select-all:checked').length) {
            $('.checked_out_items_export_dropdown').removeClass('disabled');
        } else {
            $('.checked_out_items_export_dropdown').addClass('disabled');
        }
    });

    // HOLDS
    if ($('.template-dir-myresearch.template-name-holds').length) {
        // EXPORT BUTTON
        var html = ' ';
        html = html + '<span class="dropdown">';
        html = html + '<button class="btn btn-default dropdown-toggle disabled holds_export_dropdown" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
        html = html + ' Export ';
        html = html + '<span class="caret"></span>';
        html = html + '</button>';
        html = html + '<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">';
        html = html + '<li><a class="holds_export" data-export-format="EndNoteWeb">EndNoteWeb</a></li>';
        html = html + '<li><a class="holds_export" data-export-format="EndNote">EndNote/Zotero</a></li>';
        html = html + '<li><a class="holds_export" data-export-format="BiBTeX">BibTex</a></li>';
        html = html + '</ul>';
        html = html + '</span>';
        $('div.toolbar').prepend(html);

        // toggle the export pulldown's status when checkboxes are selected.
        $('.checkbox-select-item').click(function() {
            if ($('.checkbox-select-item:checked').length) {
                $('.holds_export_dropdown').removeClass('disabled');
            } else {
                $('.holds_export_dropdown').addClass('disabled');
            }
        });

        // when an export link is clicked, dynamically create and submit a
        // form to get the export. 
        $('.holds_export').click(function() {
            var form = $('<form>').attr('action', '/vufind/Cart/Export').attr('method', 'post').appendTo('body');
            $('<input>').attr('type', 'hidden').attr('name', 'format').val($(this).attr('data-export-format')).appendTo(form);
            $('.checkbox-select-item:checked').each(function() { 
                $('<input>').attr('type', 'hidden').attr('name', 'ids[]').val($(this).attr('data-record-id')).appendTo(form);
            });
            $('<input>').attr('type', 'submit').attr('name', 'submit').val('Export').appendTo(form);
            form.find('input[type="submit"]').click();
        });
    }
});


