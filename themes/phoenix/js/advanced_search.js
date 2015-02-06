/*global addSearchString, deleteSearchGroupString, searchFields, searchJoins, searchLabel, searchMatch */

/*
 * Update placeholder text for keyword searches. 
 * Do it onload and do it when the dropdown changes.
 */
function update_keyword_search(select) {
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

function addSearch(link, term, field)
{
  // Get the group number. 
  var group = parseInt($(link).parents('.group').attr('id').replace('group', ''));

  // Add the 'match' pulldown 
  if ($('#group' + group).find('.search').length > 0) {
    $('select#search_bool'+group).removeClass('hidden').show();
    $('label[for="search_bool'+group+'"]').removeClass('hidden').show();
  }

  // Does anyone use this???
  if (term  == undefined) {term  = '';}
  if (field == undefined) {field = '';}

  // Build the new search
  var inputIndex = $('#group'+group+' input').length;
  var inputID = group+'_'+inputIndex;
  var newSearch = '<div class="search form-group form-inline" id="search'+inputID+'">' + '<input id="search_lookfor'+inputID+'" class="form-control input-large" type="text" name="lookfor'+group+'[]" value="'+term+'" placeholder="evolutionary biology"/> '
    + '<select id="search_type'+inputID+'" name="type'+group+'[]" class="form-control">';
  for (var key in searchFields) {
    newSearch += '<option value="' + key + '"';
    if (key == field) {
      newSearch += ' selected="selected"';
    }
    newSearch += ">" + searchFields[key] + "</option>";
  }
  newSearch += '</select> <a class="delete';
  if(inputIndex == 0) {
    newSearch += ' hidden';
  }
  newSearch += '" href="#" onclick="deleteSearch(this)" class="delete">&times;</a></div>';

  // Insert it
  $("#group" + group + "Holder").before(newSearch);
  // Show x if we have more than one search inputs
  if(inputIndex > 0) {
    $('#group'+group+' .search .delete').removeClass('hidden');
  }
  // When the field pulldown changes, update the text box placeholder text. 
  $('#group'+group+' .search select').change(function() {
    update_keyword_search($(this));
  });
}

function deleteSearch(link)
{
  // Get the group number and search number from the containing .search elements ID.
  var pieces = $(link).parents('.search').attr('id').replace('search', '').split('_');
  var group = parseInt(pieces[0]);
  var eq = parseInt(pieces[1]);

  var searches = $('#group'+group+' .search');
  for(var i=eq;i<searches.length-1;i++) {
    $(searches[i]).find('input').val($(searches[i+1]).find('input').val());
    var select0 = $(searches[i]).find('select')[0];
    var select1 = $(searches[i+1]).find('select')[0];
    select0.selectedIndex = select1.selectedIndex;
  }
  if($('#group'+group+' .search').length > 1) {
    $('#group'+group+' .search:last').remove();
  }
  // Hide x
  if($('#group'+group+' .search').length == 1) {
    $('#group'+group+' .search .delete').addClass('hidden');
  }
  // Hide Match pulldown. 
  if($('#group'+group+' .search').length == 1) {
    $('select#search_bool'+group).hide();
    $('label[for="search_bool'+group+'"]').hide();
  }
}

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

function addGroup(firstTerm, firstField, join)
{
  updateGroups();

  if (firstTerm  == undefined) {firstTerm  = '';}
  if (firstField == undefined) {firstField = '';}
  if (join       == undefined) {join       = '';}

  var nextGroup = $('.group').length;

  var newGroup = ''
    + '<div id="group'+nextGroup+'" class="group well">'
    + '<div class="form-group form-inline">'
    + '<label class="hidden" for="search_bool'+nextGroup+'">'+searchMatch+':&nbsp;</label>'
    + '<a href="#" class="close hidden" onclick="deleteGroup(this)" title="'+deleteSearchGroupString+'">&times;</a>'
    + '<select id="search_bool'+nextGroup+'" name="bool'+nextGroup+'[]" class="form-control hidden">'
    + '<option value="AND"';
  if(join == 'AND') {
    newGroup += ' selected';
  }
  newGroup += '>' +searchJoins['AND'] + '</option>'
    + '<option value="OR"';
  if(join == 'OR') {
    newGroup += ' selected';
  }
  newGroup += '>' +searchJoins['OR'] + '</option>'
    + '<option value="NOT"';
  if(join == 'NOT') {
    newGroup += ' selected';
  }
  newGroup += '>' +searchJoins['NOT'] + '</option>'
    + '</select></div>'
    + '<i id="group'+nextGroup+'Holder" class="fa fa-plus-circle"></i> <a href="#" id="add_search_link_'+nextGroup+'" onClick="addSearch(this)">'+addSearchString+'</a>'
    + ' <a style="display: inline;" href="http://www.lib.uchicago.edu/e/using/catalog/help.html#searchfield" id="what_is_a_field" class="external"><i style="text-decoration: none;" class="icon-info-sign icon-large"></i>What is a Field?</a>'
    + '</div>';

  $('#groupPlaceHolder').before(newGroup);

  addSearch($('#add_search_link_' + nextGroup), firstTerm, firstField);

  // Show join menu
  if($('.group').length > 1) {
    $('#groupJoin').removeClass('hidden');
    // Show x
    $('.group .close').removeClass('hidden');
  }

  return nextGroup++;
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

    //toggle basic/advanced search links. 
    $('#advancedSearchSwitch a').addClass('disabled');
    $('#basicSearchSwitch a').removeClass('disabled');

    //change text input's name to 'lookfor0[]' (advanced search)
    $('#search_lookfor0_0').attr('name', 'lookfor0[]');

    //change field pulldown's name to 'type0[]' (advanced search)
    $('#search_type0_0').attr('name', 'type0[]');

    addSearch($('#add_search_link_0'), '', '');
    addSearch($('#add_search_link_0'), '', '');

    //show 'Add Search Field'
    $('#group0Holder').show();
    $('#add_search_link_0').show(); 

    //show "what is a field?" link. 
    $('#what_is_a_field').show();

    //show 'Add Search Group'
    $('#groupPlaceHolder').show();

    //show limits. 
    $('fieldset').has('legend:contains("Limit To")').show();

    //show results per page.
    $('fieldset').has('legend:contains("Results per page")').show();

    //show year of publication.
    $('fieldset').has('legend:contains("Year of Publication")').show();

    //hide basic search buttons.
    $('.basicSearchBtn').hide();

    //show advanced search buttons.
    $('.advancedSearchBtn').show();

    return false;
}

function switchToBasicSearch()
{
    //if the basic search is already active, do nothing. 
    if ($('#basicSearchSwitch a.disabled').length > 0) {
        return false;
    }

    //toggle basic/advanced search links. 
    $('#advancedSearchSwitch a').removeClass('disabled');
    $('#basicSearchSwitch a').addClass('disabled');

    //remove 'match' pulldown. 
    $('#groupJoin').remove();

    //change text input's name to 'lookfor' (basic search)
    $('#search_lookfor0_0').attr('name', 'lookfor');

    //change field pulldown's name to 'type' (basic search)
    $('#search_type0_0').attr('name', 'type');

    //delete all but the first search group.
    $('.group:not(:first)').each(function() {
        deleteGroup($(this).find('a.close'));
    });

    //delete all but the first search box. 
    $('.group:first').find('.search:not(:first)').each(function() {
        deleteSearch($(this).find('a.delete'));
    });
    
    //hide 'Add Search Field'
    $('#group0Holder').hide();
    $('#add_search_link_0').hide(); 

    //hide "what is a field?" link. 
    $('#what_is_a_field').hide();

    //hide 'Add Search Group'
    $('#groupPlaceHolder').hide();

    //hide limits. 
    $('fieldset').has('legend:contains("Limit To")').hide();

    //hide results per page.
    $('fieldset').has('legend:contains("Results per page")').hide();

    //hide year of publication.
    $('fieldset').has('legend:contains("Year of Publication")').hide();

    //show basic search buttons.
    $('.basicSearchBtn').show();

    //hide advanced search buttons.
    $('.advancedSearchBtn').hide();

    return false;
}

$(document).ready(function() {
    // Set up basic and advanced search links. 
    $('#basicSearchSwitch a').click(switchToBasicSearch);
    $('#advancedSearchSwitch a').click(switchToAdvancedSearch);

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
                   selected.val().indexOf('Special Collections') == -1) {
            options.filter('.ns').appendTo('#limit_collection');

        //Crerar
        } else if (selected.val().indexOf('Crerar Library') > -1) {
            options.filter('[data-main-location*="crerar"]').appendTo('#limit_collection');

        //SCRC
        } else if (selected.val().indexOf('Special Collections') > -1) {
            options.filter('[data-main-location*="scrc"]').appendTo('#limit_collection');
        }
    }); 
    //link up the location and collection pulldowns onload, for "edit this advanced search." 
    $('#limit_building').change();

    //get rid of unwanted options on submit.
    $("#advSearchForm").submit(function(){
        $('.all, .all_languages, .all_formats, .all_locations, .all_collections').prop("selected", false);
    }); 
});

