/*global addSearchString, deleteSearchGroupString, searchFields, searchJoins, searchLabel, searchMatch */

function addSearch(link, term, field)
{
  // Get the group number. 
  var group = parseInt($(link).parents('.group').attr('id').replace('group', ''));

  // Add the 'match' pulldown 
  $('select#search_bool'+group).show();
  $('label[for="search_bool'+group+'"]').show();

  // Does anyone use this???
  if (term  == undefined) {term  = '';}
  if (field == undefined) {field = '';}

  // Build the new search
  var inputIndex = $('#group'+group+' input').length;
  var inputID = group+'_'+inputIndex;
  var newSearch = '<div class="search form-group form-inline" id="search'+inputID+'">'
    + '<input id="search_lookfor'+inputID+'" class="form-control input-large" type="text" name="lookfor'+group+'[]" value="'+term+'"/> '
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

        //update group's close button.
        //$(group).find('a.close').attr('onclick', 'deleteGroup(' + g + ')');

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

  var newGroup = '<div id="group'+nextGroup+'" class="group well">'
    + '<div class="row">'
    + '<div class="col-md-12">'
    + '<div class="form-group form-inline">'
    + '<label for="search_bool'+nextGroup+'">'+searchMatch+':&nbsp;</label>'
    + '<a href="#" class="close hidden" onclick="deleteGroup(this)" title="'+deleteSearchGroupString+'">&times;</a>'
    + '<select id="search_bool'+nextGroup+'" name="bool'+nextGroup+'[]" class="form-control">'
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
    + '</div></div>';

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
    $('#advancedsearchlink').parent().hide();

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
