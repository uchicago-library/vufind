function getAlert(){
    var api = 'https://www.lib.uchicago.edu/api/v1/pages/?type=alerts.AlertPage&fields=title,banner_message,more_info,alert_level,url&format=json';
    json = $.getJSON(api, function(data) {
        var pages = data.pages;
        $.each(pages, function(key){
            var page = pages[key];
            var level = page.alert_level;
            var msg = page.banner_message;
            var url = page.url;
            var html = '';
            if (level == 'alert-high') {
                html += '<div id="alert" class="container">' + msg +' | <a href="' + url + '">More info...</a></div>';
                $('.container.navbar').before(html);
                return false;
            }
        }); 
    });
}
$(document).ready(function() {
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
    var viewItems = '<a href="#" class="itemsToggle text-success">' + viewItemsText + '</a>';
    var hideItems = '<a href="#" class="itemsToggle text-success">' + hideItemsText + '</a>';
    var viewSummary = '<a href="#" class="summaryToggle text-success">' + viewSummaryText + '</a>';
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
                //$(this).parent().parent().parent().find('.itemsToggle').removeClass('hide');

                // If there isn't already a link 
                if(!hasLink > 0 ){
                    $(this).parent().find('.itemsToggleTarget td').append(viewItems);
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

                    // If there isn't already a link
                    if(!hasLink){
                        $(this).parent().find('.summaryToggleTarget').append(viewSummary);
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
        $(this).parent().parent().find('div.toToggle').toggle();
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
        'content': '<div style="padding: 0 10px;"><p>Keyword searches produce lists of records sorted by relevance:</p><p style="padding-left: 10px;"><strong>Basic Keyword Search</strong><br/>Use for exploring a general topic, or if the exact title or author of a book is unknown.</p><p style="padding-left: 10px;"><strong>Advanced Keyword Search</strong><br/>Use for very specific or complex topics.</p><p><strong>Begins With</strong><br/>Begins With allows you to browse through an alphabetical list of titles, authors, subjects, etc. Use to locate a book when the exact title or author\'s entire name is known, or when searching for items on a specific subject.</p><p><a href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchtypes" target="_blank">More info</a><br/><a href="http://youtu.be/I4kOECCepew" target="_blank">90 second video on search types <i style="text-decoration: none;" class="icon-facetime-video "></i></a></p></div>',
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
  // Lightbox
  /*
   * This function adds jQuery events to elements in the lightbox
   *
   * This is a default open action, so it runs every time changeContent
   * is called and the 'shown' lightbox event is triggered
   */
  function bulkActionSubmit($form) {
    var submit = $form.find('input[type="submit"][clicked=true]').attr('name');
    var checks = $form.find('input.checkbox-select-item:checked');
    if(checks.length == 0 && submit != 'empty') {
      return Lightbox.displayError(vufindString['bulk_noitems_advice']);
    }
    if (submit == 'print') {
      //redirect page
      var url = path+'/Records/Home?print=true';
      for(var i=0;i<checks.length;i++) {
        url += '&id[]='+checks[i].value;
      }
      document.location.href = url;
    } else {
      Lightbox.submit($form, Lightbox.changeContent);
    }
    return false;
  }
  function registerLightboxEvents() {
    var modal = $("#modal");
    // New list
    $('#make-list').click(function() {
      var parts = this.href.split('?');
      var get = deparam(parts[1]);
      get['id'] = 'NEW';
      return Lightbox.get('MyResearch', 'EditList', get);
    });
    // New account link handler
    $('.createAccountLink').click(function() {
      var parts = this.href.split('?');
      var get = deparam(parts[1]);
      return Lightbox.get('MyResearch', 'Account', get);
    });
    // Select all checkboxes
    $(modal).find('.checkbox-select-all').change(function() {
      $(this).closest('.modal-body').find('.checkbox-select-item').prop('checked', this.checked);
    });
    $(modal).find('.checkbox-select-item').change(function() {
      $(this).closest('.modal-body').find('.checkbox-select-all').prop('checked', false);
    });
    // Highlight which submit button clicked
    $(modal).find("form input[type=submit]").click(function() {
      // Abort requests triggered by the lightbox
      $('#modal .fa-spinner').remove();
      // Remove other clicks
      $(modal).find('input[type="submit"][clicked=true]').attr('clicked', false);
      // Add useful information
      $(this).attr("clicked", "true");
      // Add prettiness
      $(this).after(' <i class="fa fa-spinner fa-spin"></i> ');
    });
    /**
     * Hide the header in the lightbox content
     * if it matches the title bar of the lightbox
     */
    var header = $('#modal .modal-title').html();
    var contentHeader = $('#modal .modal-body .lead');
    if(contentHeader.length == 0) {
      contentHeader = $('#modal .modal-body h2');
    }
    contentHeader.each(function(i,op) {
      if (op.innerHTML == header) {
        $(op).hide();
      }
    });
   // Expand and collapse of No-CNet-ID login
   $(modal).find('#login-toggle').click(function() {
      $(this).parents('.login-toggle-wrapper').next('.login-toggle-content').toggle();
   });
  }
  function updatePageForLogin() {
    // Hide "log in" options and show "log out" options:
    $('#loginOptions').addClass('hidden');
    $('.logoutOptions').removeClass('hidden');
  
    var recordId = $('#record_id').val();
  
    // Update user save statuses if the current context calls for it:
    if (typeof(checkSaveStatuses) == 'function') {
      checkSaveStatuses();
    }
  
    // refresh the comment list so the "Delete" links will show
    $('.commentList').each(function(){
      var recordSource = extractSource($('#record'));
      refreshCommentList(recordId, recordSource);
    });
  
    var summon = false;
    $('.hiddenSource').each(function(i, e) {
      if(e.value == 'Summon') {
        summon = true;
        // If summon, queue reload for when we close
        Lightbox.addCloseAction(function(){document.location.reload(true);});
      }
    });
  
    // Refresh tab content
    var recordTabs = $('.recordTabs');
    if(!summon && recordTabs.length > 0) { // If summon, skip: about to reload anyway
      var tab = recordTabs.find('.active a').attr('id');
      ajaxLoadTab(tab);
    }
  }
  function newAccountHandler(html) {
    updatePageForLogin();
    var params = deparam(Lightbox.openingURL);
    if (params['subaction'] != 'UserLogin') {
      Lightbox.getByUrl(Lightbox.openingURL);
      Lightbox.openingURL = false;
    } else {
      Lightbox.close();
    }
  }
  
  // This is a full handler for the login form
  function ajaxLogin(form) {
    Lightbox.ajax({
      url: path + '/AJAX/JSON?method=getSalt',
      dataType: 'json',
      success: function(response) {
        if (response.status == 'OK') {
          var salt = response.data;
  
          // get the user entered password
          var password = form.password.value;
  
          // base-64 encode the password (to allow support for Unicode)
          // and then encrypt the password with the salt
          password = rc4Encrypt(salt, btoa(unescape(encodeURIComponent(password))));
  
          // hex encode the encrypted password
          password = hexEncode(password);
  
          var params = {password:password};
  
          // get any other form values
          for (var i = 0; i < form.length; i++) {
            if (form.elements[i].name == 'password') {
              continue;
            }
            params[form.elements[i].name] = form.elements[i].value;
          }
  
          // login via ajax
          Lightbox.ajax({
            type: 'POST',
            url: path + '/AJAX/JSON?method=login',
            dataType: 'json',
            data: params,
            success: function(response) {
              if (response.status == 'OK') {
                updatePageForLogin();
                // and we update the modal
                var params = deparam(Lightbox.lastURL);
                if (params['subaction'] == 'UserLogin') {
                  Lightbox.close();
                } else {
                  Lightbox.getByUrl(
                    Lightbox.lastURL,
                    Lightbox.lastPOST,
                    Lightbox.changeContent
                  );
                }
              } else {
                Lightbox.displayError(response.data);
              }
            }
          });
        } else {
          Lightbox.displayError(response.data);
        }
      }
    });
  }

  /******************************
   * LIGHTBOX DEFAULT BEHAVIOUR *
   ******************************/
  /*
  Lightbox.addOpenAction(registerLightboxEvents);
  Lightbox.addFormCallback('newList', Lightbox.changeContent);
  Lightbox.addFormHandler('loginForm', function(evt) {
    ajaxLogin(evt.target);
    return false;
  });
  Lightbox.addFormCallback('accountForm', newAccountHandler);
  Lightbox.addFormCallback('emailSearch', function(html) {
    Lightbox.confirm(vufindString['bulk_email_success']);
  });
  Lightbox.addFormCallback('saveRecord', function(html) {
    Lightbox.close();
    checkSaveStatuses();
  });
  Lightbox.addFormCallback('bulkRecord', function(html) {
    Lightbox.close();
    checkSaveStatuses();
  });
  Lightbox.addFormHandler('feedback', function(evt) {
    var $form = $(evt.target);
    // Grabs hidden inputs
    var formSuccess     = $form.find("input#formSuccess").val();
    var feedbackFailure = $form.find("input#feedbackFailure").val();
    var feedbackSuccess = $form.find("input#feedbackSuccess").val();
    // validate and process form here
    var name  = $form.find("input#name").val();
    var email = $form.find("input#email").val();
    var comments = $form.find("textarea#comments").val();
    if (name.length == 0 || comments.length == 0) {
      Lightbox.displayError(feedbackFailure);
    } else {
      Lightbox.get('Feedback', 'Email', {}, {'name':name,'email':email,'comments':comments}, function() {
        Lightbox.changeContent('<div class="alert alert-info">'+formSuccess+'</div>');
      });
    }
    return false;
  });
  Lightbox.addFormHandler('KnowledgeTracker', function(evt) {
    var $form = $(evt.target);
    // Grabs hidden inputs
    var formSuccess     = $form.find("input#formSuccess").val();
    var feedbackFailure = $form.find("input#feedbackFailure").val();
    var feedbackSuccess = $form.find("input#feedbackSuccess").val();
    // validate and process form here
    var name  = $form.find("input#name").val();
    var email = $form.find("input#email").val();
    var question = $form.find("textarea#question").val();
    var tasks = $form.find("textarea#tasks").val();
    var library = $form.find("input#library").val();
    var affiliation = $form.find("select#affiliation").val();
    var pageUrl = $form.find("input#pageUrl").val();
    var refUrl = $form.find("input#refUrl").val();
    if (name.length == 0 || question.length == 0) {
      Lightbox.displayError(feedbackFailure);
    } else {
      Lightbox.get('Feedback', 'KnowledgeTrackerForm', {}, {'library':library, 'name':name, 'email':email, 'affiliation':affiliation, 'question':question, 'tasks':tasks, 'page_url': pageUrl, 'referring_page': refUrl}, function() {
        Lightbox.changeContent('<div class="alert alert-info">'+formSuccess+'</div>');
      });
    }
    return false;
  });
  */
});
$(document).ready(function() {
  // Feedback
  $('#feedbackLink').click(function() {
    return Lightbox.get('Feedback', 'Home');
  });
  // Knowledge Tracker
  /* Only commented this out because php libcurl is failing 
  in the production envirionment. I would like to bring this back - brad 5/18/2015
  $('#knowledgeTrackerLink').click(function() {
    return Lightbox.get('Feedback', 'KnowledgeTracker');
  });*/
  // Help links
  $('.help-link').click(function() {
    var split = this.href.split('=');
    return Lightbox.get('Help','Home',{topic:split[1]});
  });
  // Hierarchy links
  $('.hierarchyTreeLink a').click(function() {
    var id = $(this).parent().parent().parent().find(".hiddenId")[0].value;
    var hierarchyID = $(this).parent().find(".hiddenHierarchyId")[0].value;
    return Lightbox.get('Record','AjaxTab',{id:id},{hierarchy:hierarchyID,tab:'HierarchyTree'});
  });
  // Login link
  // JEJ: I think we can eliminate this:
  // see https://vufind.org/wiki/development:architecture:lightbox
  /*
  $('#loginOptions a.modal-link').click(function() {
    //return Lightbox.get('MyResearch','UserLogin');
    var url = VuFind.path + '/AJAX/JSON?' + $.param({
        method: 'getLightbox',
        submodule: 'MyResearch',
        subaction: 'UserLogin'
    });
    $.ajax({
        dataType: 'json',
        cache: false,
        url: url
    })
    .done(function (response) {
        if (response.data.status) {
            VuFind.lightbox.alert('login test');
            // response.data.msg
        }
    });
  });
  */
  // Email search link
  $('.mailSearch').click(function() {
    return Lightbox.get('Search','Email',{url:document.URL});
  });
  // Save record links
  $('.save-record').click(function() {
    var parts = this.href.split('/');
    return Lightbox.get(parts[parts.length-3],'Save',{id:$(this).attr('id')});
  });
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
  
    $('[name=bulkActionForm]').submit(function() {
      return bulkActionSubmit($(this));
    });
    $('[name=bulkActionForm]').find("input[type=submit]").click(function() {
      // Abort requests triggered by the lightbox
      $('#modal .fa-spinner').remove();
      // Remove other clicks
      $(this).closest('form').find('input[type="submit"][clicked=true]').attr('clicked', false);
      // Add useful information
      $(this).attr("clicked", "true");
    });
});


