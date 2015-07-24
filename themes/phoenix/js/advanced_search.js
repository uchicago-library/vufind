/*global addSearchString, deleteSearchGroupString, searchFields, searchJoins, searchLabel, searchMatch */

/*
 * Update placeholder text for keyword searches. 
 * Do it onload and do it when the dropdown changes.
 */
function update_keyword_search(select) {
    if ($(select).parents('form').eq(0).hasClass('basicSearch')) {
	    var placeholder = '';
	    switch(select.val()) {
	        case 'AllFields':
	            placeholder = 'evolutionary biology';
	            break;
	        case 'Title':
	            placeholder = 'chicago style manual';
	            break;
	        case 'Author':
	            placeholder = 'saul bellow';
	            break;
	        case 'Subject':
	            placeholder = 'united states history';
	            break;
	        case 'JournalTitle':
	            placeholder = 'american journal of sociology';
	            break;
	        case 'StandardNumbers':
	            placeholder = '0375412328';
	            break;
	        case 'Series':
	            placeholder = 'lecture notes in computer science';
	            break;
	        case 'Publisher':
	            placeholder = 'university of chicago press';
	            break;
	    }
	    select.prevAll('input').eq(0).attr('placeholder', placeholder);
    }
}

/*
 * Update the placeholder text for begins with searches. 
 * Do it on load, and do it when the dropdown changes. 
 */
function update_begins_with_search() {
    var placeholder = '';
    switch($('select#alphaBrowseForm_source').val()) {
        case 'title':
            placeholder = 'a manual for writers of';
            break;
        case 'author':
            placeholder = 'dickens charles';
            break;
        case 'topic':
            placeholder = 'world\'s columbian expo';
            break;
        case 'lcc':
            placeholder = 'ps2700 f16';
            break;
        case 'series':
            placeholder = 'lecture notes in math';
            break;
        case 'dewey':
            placeholder = '900';
            break;
        case 'journal':
            placeholder = 'chronicle of higher education';
            break;
    }
    $('input#alphaBrowseForm_from').attr('placeholder', placeholder);
}

var term = '';
var field = '';
$.fn.addSearch = function(term, field) 
{ 
  // Be sure this is being executed on a .group
  if (!$(this).hasClass('group')) {
    return;
  }

  // Does anyone use this???
  if (term  == undefined) {term  = '';}
  if (field == undefined) {field = '';}

  // Get the group number. 
  var group = $(this).index('.group');

  // Add the 'match' pulldown 
  if ($(this).find('.search').length > 0) {
    $(this).find('.search_bool').show();
  }

  // Build the new search
  var inputIndex = $(this).find('input').length;
  var inputID = group+'_'+inputIndex;

  var newSearch = $('<div class="search form-group form-inline" id="search'+inputID+'"></div>');

  // Insert it
  $(this).find('.searchPlaceHolder').before(newSearch);

  // Build new search term. 
  var newTerm = $('<input id="search_lookfor'+inputID+'" class="form-control input-large" type="text" name="lookfor'+group+'[]" value="'+term+'"/>');

  // Append the new search term. 
  newSearch.append(newTerm);

  // Build the advanced field pulldown. 
  var newFieldStr = '<select id="search_type'+inputID+'" name="type'+group+'[]" class="form-control">';
  $.each([
    { value: 'AllFields', text: 'All Fields' },
    { value: 'Title', text: 'Title' },
    { value: 'JournalTitle', text: 'Journal Title' },
    { value: 'Author', text: 'Author' },
    { value: 'Performer', text: 'Performer' },
    { value: 'Subject', text: 'Subject' },
    { value: 'CallNumber', text: 'Call Number' },
    { value: 'StandardNumbers', text: 'ISBN, ISSN, etc.' },
    { value: 'Publisher', text: 'Publisher'},
    { value: 'publication_place', text: 'Publication Place' },
    { value: 'Series', text: 'Series' },
    { value: 'year', text: 'Year of Publication' },
    { value: 'toc', text: 'Table of Contents' },
    { value: 'Donor', text: 'Donor' },
    { value: 'Title Uniform', text: 'Uniform Title' },
    { value: 'Notes', text: 'Notes' },
  ], function(i, v) {
    //if this field was selected, keep it selected. 
    var selected = "";
    if (v['value'] == field) {
      selected = " selected='selected'";
    }
    newFieldStr += '<option value="' + v['value'] + '"';
    if (v['value'] == field) {
      newFieldStr += ' selected="selected"';
    }
    newFieldStr += ">" + v['text'] + "</option>";
  });
  newFieldStr += '</select>';

  var newField = $(newFieldStr);

  // Append the field pulldown. 
  newSearch.append(newField);

  // When the field pulldown changes, update the text box placeholder text. 
  newField.change(function() {
    update_keyword_search($(this));
  });

  // Create delete link
  var deleteLink = $(" <a class='delete' href='#' class='delete'>&times;</a>");

  // Add onclick event.
  deleteLink.click(function() {
    newSearch.deleteSearch();
  });

  // Append the delete link. 
  $("#search" + inputID).append(deleteLink);

  // Show x if we have more than one search inputs
  if(inputIndex == 0) {
    $(this).find('.search .delete').hide();
  } else {
    $(this).find('.search .delete').show();
  }
}

$.fn.deleteSearch = function deleteSearch()
{
  // Be sure this function is being run with a .search div. 
  if (!$(this).hasClass('search')) {
    return;
  }

  // Hide 'x' and boolean pulldown if there is only going to be one
  // search box. 
  if($(this).parent('.group').find('.search .delete').length <= 2) {
    $(this).parent('.group').find('.search .delete').hide();
    $(this).parent('.group').find('.search_bool').hide();
  }

  // Remove this search query and field pulldown. 
  $(this).remove();

  // Update everything...
  updateGroups();
};

function updateGroups()
{
    //for each search group...
    $('.group').each(function(g, group) {
        //set the id of the group to group0, group1, etc.
        $(group).attr('id', 'group' + g);

        //get match pulldown. 
        var match = $(group).find('select[id^="search_bool"]');

        //update the match pulldown's ID.
        $(match).attr('id', 'search_bool' + g);

        //update the match pulldown's name.
        $(match).attr('name', 'bool' + g + '[]');

        //update the label's 'for' attribute. 
        $(match).prevAll('label').attr('for', 'search_bool' + g).eq(0);

        //for each search box/field combo...
        $(group).find('.search').each(function(s, search) {
            //set the ID of this element to something like search0_0.
            $(search).attr('id', 'search' + g + '_' + s);

            //find the text input.
            var input_text = $(search).find('input[type="text"]');

            //set the text input's ID to something like search_lookfor0_0.
            $(input_text).attr('id', 'search_lookfor' + g + '_' + s);

            //set the text input's name to something like lookfor0[].
            $(input_text).attr('name', 'lookfor' + g + '[]');

            //get the field pulldown.
            var select = $(search).find('select');

            //set the field pulldown's ID to something like search_type0_0.
            $(select).attr('id', 'search_type' + g + '_' + s);

            //set the field pulldown's name to something like type0[].
            $(select).attr('name', 'type' + g + '[]');

            //set the plus sign in front of the 'add search field' link. 
            $(group).find('i[id$="Holder"]').attr('id', 'group' + g + 'Holder');

            //get 'add seach field link'.
            var link = $(group).find('a[id^="add_search_link"]');
  
            //set the add search field link's id to something like add_search_link_0.  
            $(link).attr('id', 'add_search_link_' + g); 
        });
    });
}

function addGroup()
{
  updateGroups();

  var nextGroup = $('.group').length;

  // Create and append a new search group. 
  var newGroup = $('<div id="group'+nextGroup+'" class="group well"></div>');
  $('#groupPlaceHolder').before(newGroup);

  // Build boolean pulldown. 
  var s = '';
  s += '<div class="form-group form-inline">';
  s += '<div class="search_bool">';
  s += '<label for="search_bool'+nextGroup+'">'+searchMatch+':&nbsp;</label>';
  s += '<select id="search_bool'+nextGroup+'" name="bool'+nextGroup+'[]" class="form-control">';
  s += '<option value="AND" selected="selected"';
  s += '>' +searchJoins['AND'] + '</option>';
  s += '<option value="OR"';
  s += '>' +searchJoins['OR'] + '</option>';
  s += '<option value="NOT"';
  s += '>' +searchJoins['NOT'] + '</option>';
  s += '</select>';
  s += '</div>';

  s += '<a href="#" class="close hidden" onclick="deleteGroup(this)" title="'+deleteSearchGroupString+'">&times;</a>';
  s += '</div>';

  var searchBool = $(s);
  newGroup.append(searchBool);
  newGroup.find('.search_bool').hide();

  // Create and append 'add a new field' and 'what is a field?' links. 
  var s = '';
  s += '<span class="searchPlaceHolder">';
  s += '<i id="group'+nextGroup+'Holder" class="fa fa-plus-circle"></i>';
  s += ' <a href="#" id="add_search_link_'+nextGroup+'">'+addSearchString+'</a>';
  s += ' <a href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchfield" class="external what_is_a_field"><i style="text-decoration: none;" class="icon-info-sign icon-large"></i>What is a Field?</a>';
  s += '</span>';

  var fieldLinks = $(s);
  newGroup.append(fieldLinks);

  fieldLinks.find('a:first').click(function() {
    newGroup.addSearch();
  });

  // Add a new search box to this group.
  newGroup.addSearch();

  // Show join menu
  if ($('.group').length > 1) {
    $('#groupJoin').removeClass('hidden');
    // Show x
    $('.group .close').removeClass('hidden');
  }
}

function deleteGroup(link)
{
  // Get the group that contains this link.  
  var group = $(link).parents('.group').eq(0);

  // Find the group and remove it
  $(group).remove();
  // If the last group was removed, add an empty group
  if($('.group').length == 0) {
    addGroup();
  } else if($('.group').length == 1) { // Hide join menu
    $('#groupJoin').addClass('hidden');
    // Hide x
    $('.group .close').addClass('hidden');
  }

  updateGroups();
}

// Fired by onclick event
function deleteGroupJS(group)
{
  var groupNum = group.id.replace("delete_link_", "");
  deleteGroup(groupNum);
  return false;
}

// Fired by onclick event
function addSearchJS(group)
{
  var groupNum = group.id.replace("add_search_link_", "");
  addSearch(groupNum);
  return false;
}

function switchToAdvancedSearch()
{
    //if the advanced search is already active, do nothing. 
    if ($('#advancedSearchSwitch a.disabled').length > 0) {
        return false;
    }

    //switch form class from basic to advanced. 
    $('#advSearchForm').removeClass('basicSearch');
    $('#advSearchForm').addClass('advancedSearch');

    //toggle basic/advanced search links. 
    $('#advancedSearchSwitch a').addClass('disabled');
    $('#basicSearchSwitch a').removeClass('disabled');

    //save the search term and field from the basic search.
    var term = $('.group:first .search:first').find('input:first').val();
    var field = $('.group:first .search:first').find('select').val();

    //remove the existing search. 
    $('.search').deleteSearch();
   
    //add three search boxes- fill the first one out if it's
    //appropriate.  
    $('.group:first').addSearch(term, field);
    $('.group:first').addSearch();
    $('.group:first').addSearch();

    // Update the title tag
    setTimeout(function() {
        $('head title').text(getTitleTag);
    }, 100);
    
    return false;
}

function switchToBasicSearch()
{
    //if the basic search is already active, do nothing. 
    if ($('#basicSearchSwitch a.disabled').length > 0) {
        return false;
    }

    //switch form class from advanced to basic.
    $('#advSearchForm').addClass('basicSearch');
    $('#advSearchForm').removeClass('advancedSearch');

    //toggle basic/advanced search links. 
    $('#advancedSearchSwitch a').removeClass('disabled');
    $('#basicSearchSwitch a').addClass('disabled');

    //if the user was editing and advanced search and the sidebar is
    //there, expland the main content column and delete the sidebar. 
    if ($('.sidebar').length > 0) {
        var oldClass = $('.sidebar').prevAll('[class^="col-"]').eq(0).attr('class');
        var newClass = oldClass.replace(/[0-9]+$/, '12');

        var mainColumn = $('.sidebar').prevAll('[class^="col-"]').eq(0);
        mainColumn.removeClass(oldClass);
        mainColumn.addClass(newClass);

        $('.sidebar').remove();
    }

    //delete all but the first search group.
    $('.group:not(:first)').each(function() {
        deleteGroup($(this).find('a.close'));
    });

    //delete all but the first search box. 
    $('.group:first').find('.search:not(:first)').each(function() {
        $(this).deleteSearch();
    });

    //change text input's name to 'lookfor' (basic search)
    $('.group:first .search:first input:first').attr('name', 'lookfor');

    //change field pulldown's name to 'type' (basic search)
    $('.group:first .search:first select').attr('name', 'type');

    //update the placeholder text.
    update_keyword_search($('.group:first .search:first select'));

    //save the advanced search field value. 
    var selectedField = $('.group:first .search:first select').val();

    //remove existing search options. 
    $('.group:first .search:first select option').remove();

    //add basic search fields.
    $.each([
		{ value: 'AllFields', text: 'All Fields' },
		{ value: 'Title', text: 'Title' },
		{ value: 'Author', text: 'Author' },
		{ value: 'Subject', text: 'Subject' },
		{ value: 'JournalTitle', text: 'Journal Title' },
		{ value: 'StandardNumbers', text: 'ISBN, ISSN, etc.' },
		{ value: 'Series', text: 'Series' },
		{ value: 'Publisher', text: 'Publisher' },
    ], function(i,v) {
        //if this field was selected, keep it selected. 
        var selected = "";
        if (v['value'] == selectedField) {
            selected = " selected='selected'";
        }
        $('.group:first .search:first select').append($("<option" + selected + " value='" + v['value'] + "'>" + v['text'] + "</option>"));
    });

    //remove text elements between things. (Even though I'm not sure where they get added... -jej)
    $('.group:first .search:first').contents().filter(function() {
        return this.nodeType == 3; // Node.TEXT_NODE
    }).remove();

    //add 'power searching instructions', if necessary.
    if ($('#advSearchP').length == 0) {
        $('.group:first').append('<p id="advSearchP"><a class="external" href="http://www.lib.uchicago.edu/e/using/catalog/help.html#powersearch">Power searching instructions (Boolean, etc.)</a></p>');
    }
    
    //make a basic search button, if necessary. 
    if ($('.basicSearchBtn').length == 0) {
        $('.group:first .search:first').append('<input type="submit" value="Search" class="btn btn-primary basicSearchBtn">');
    }

    //Update the title tag
    setTimeout(function() {
        $('head title').text(getTitleTag());
    }, 100);

    return false;
}

$(document).ready(function() {

    // Add basic/advanced search switch links.
    $('#advSearch').prepend('<p id="basicAdvancedSearchSwitches"><span id="basicSearchSwitch"><a class="disabled" href="#">Basic</a></span> | <span id="advancedSearchSwitch"><a href="#" id="advancedSearchSwitch">Advanced Search</a></span></p>');

    // Set up basic and advanced search links. 
    $('#basicSearchSwitch a').click(switchToBasicSearch);
    $('#advancedSearchSwitch a').click(switchToAdvancedSearch);

    // Create "Add search group" and "what is a group?" links.
    var groupPlaceHolder = $('<div id="groupPlaceHolder"><i class="fa fa-plus-circle"></i> <a href="#">Add Search Group</a> <a href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchgroup" id="what_is_a_group" target="_blank" class="external"><i style="text-decoration: none;" class="icon-info-sign icon-large"></i>What is a Group?</a></div>');
    $('.group:first').after(groupPlaceHolder);

    // Add click event to 'add search group'
    $('#groupPlaceHolder a:first').click(addGroup);

    // Create "Add search field" and "what is a field?" links.
    var searchPlaceHolder = $('<span class="searchPlaceHolder"><i class="fa fa-plus-circle"></i> <a href="#">Add Search Field</a> <a href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchfield" class="external what_is_a_field"><i style="text-decoration: none;" class="icon-info-sign icon-large"></i>What is a Field?</a></span>');

    // Append those at the end of the first search group. 
    $('.group:first').append(searchPlaceHolder);

    // Attach the 'add a search field' click event. 
    searchPlaceHolder.find('a:first').click(function(){
        $(this).parents('.group').eq(0).addSearch();
    });

    // assume we're working with a basic search (not an 'edit this advanced search') if the first text box is empty. 
    if ($('.group .search input:first').val() == '') {
        $('#basicSearchSwitch a').removeClass('disabled');
        switchToBasicSearch();
    }

    // if we're loading an 'edit this advanced search' page...
    if ($('#advSearchForm.advancedSearch').length > 0) {
        // switch the basic/advanced search switch.
        $('#basicSearchSwitch a').removeClass('disabled');
        $('#advancedSearchSwitch a').addClass('disabled');
    }

    // Update keyword search placeholder text when the select pulldown changes. 
    $('.search select').change(function() {
        update_keyword_search($(this));
    });
    // Update begins with placeholder text when the select pulldown changes. 
    $('select#alphaBrowseForm_source').change(function() {
        update_begins_with_search();
    });
    //indent child options on specific select boxes
    $('#limit_format option, #limit_building option').not('.cat').each(function(){
        $(this).html('&nbsp;&nbsp;&nbsp;'+$(this).text());
    });

    /*
     * dynamic chained select boxes 
     */

    //detach the select options.
    var save_options = $('#limit_collection option').detach();

    $('#limit_building').change(function() {
        var selected = $('#limit_building').find('option:selected');

        if (selected.length == 0) {
            return;
        }

        //Get a copy of all of the options.
        var options = save_options.clone();

        //Clear the Collection box. 
        $('#limit_collection option').remove();

        //All Locations
        if (selected.val() == '') {
            options.filter(':not(".ns")').appendTo('#limit_collection');

        //Anything without sub-collections just gets a message. 
        } else if (selected.val().indexOf('Crerar Library') == -1 &&
                   selected.val().indexOf('Regenstein Library') == -1 &&
                   selected.val().indexOf('Special Collections') == -1) {
            options.filter('.ns').appendTo('#limit_collection');

        //Crerar
        } else if (selected.val().indexOf('Crerar Library') > -1) {
            options.filter('[data-main-location*="crerar"]').appendTo('#limit_collection');

        //Regenstein
        } else if (selected.val().indexOf('Regenstein Library') > -1) {
            options.filter('[data-main-location*="reg"]').appendTo('#limit_collection');

        //SCRC
        } else if (selected.val().indexOf('Special Collections') > -1) {
            options.filter('[data-main-location*="scrc"]').appendTo('#limit_collection');
        }
    }); 
    //link up the location and collection pulldowns onload, for "edit this advanced search." 
    $('#limit_building').change();

    //get rid of unwanted options on submit.
    $("#advSearchForm").submit(function(e){
        $('.all, .all_languages, .all_formats, .all_locations, .all_collections').prop("selected", false);

        //if we're submitting the form as a basic search, get rid of a few extra inputs.
        if ($('#advSearchForm').find('input[name="lookfor"]').length > 0) {
            //remove form fields for arrays of things. 
            $('[name$="[]"]').remove();
            //some other elements...
            $('[name="sort"]').remove();
            $('[name="join"]').remove();
            $('[name="publishDatefrom"]').remove();
            $('[name="publishDateto"]').remove();
        }

        // Remove the location facet for certain collection queries
        selected_col = $('#limit_collection option:selected').text();
        if (selected_col == 'Crerar Rare Books' || selected_col == 'Crerar Manuscripts') {
            $('#limit_building option').remove();
        } else if (selected_col == 'No sub-collection') {
            $('#limit_collection option').remove();
        }
    });

    // Preserve search box terms between begins with and keyword tabs 
    $(' #alphaBrowseForm_from,  #search_lookfor0_0').change(function() {
        window.searchTerms = $(this).val();
    });
    $('.template-name-advanced [data-toggle="tab"]').click(function() {
        window.searchTerms = $(this).parent().parent().parent().find('#homepageNavContent .active .input-large:first').val();
        $('#search_lookfor0_0').each(function() {
            $(this).val(window.searchTerms);
        });
        $('#alphaBrowseForm_from').each(function() {
            $(this).val(window.searchTerms);
        });
    });

    // Set the initial title tag when a user comes to the page
    $('head title').text(getTitleTag());

    //popover for 'which search should I use?' link. 
    $('#searchtabinfolink').popover({
        'container': '#searchtabinfolink',
        'content': '<div style="padding: 0 10px;"><p>Keyword searches produce lists of records sorted by relevance:</p><p style="padding-left: 10px;"><strong>Basic Keyword Search</strong><br/>Use for exploring a general topic, or if the exact title or author of a book is unknown.</p><p style="padding-left: 10px;"><strong>Advanced Keyword Search</strong><br/>Use for very specific or complex topics.</p><p><strong>Begins With</strong><br/>Begins With allows you to browse through an alphabetical list of titles, authors, subjects, etc. Use to locate a book when the exact title or author\'s entire name is known, or when searching for items on a specific subject.</p><p><a href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchtypes" target="_blank">More info</a></p></div>',
        'delay': 500,
        'html': true,
        'placement': 'right',
        'trigger': 'hover focus'
    });

});

