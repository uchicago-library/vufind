/*
 * Update placeholder text for keyword searches. 
 * Can be used onload and onchange.
 */
function updateSearchPlaceholderText(select) {
  var placeholder = '';
  switch(select.val()) {
    case 'VuFind:Solr|AllFields':
        placeholder = 'evolutionary biology';
        break;
    case 'VuFind:Solr|Title':
        placeholder = 'chicago style manual';
        break;
    case 'VuFind:Solr|Author':
        placeholder = 'saul bellow';
        break;
    case 'VuFind:Solr|Subject':
        placeholder = 'united states history';
        break;
    case 'VuFind:Solr|JournalTitle':
        placeholder = 'american journal of sociology';
        break;
    case 'VuFind:Solr|ISN':
        placeholder = '0375412328';
        break;
    case 'VuFind:Solr|Series':
        placeholder = 'lecture notes in computer science';
        break;
    case 'VuFind:Solr|Publisher':
        placeholder = 'university of chicago press';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=title&from=':
        placeholder = 'a manual for writers of';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=author&from=':
        placeholder = 'dickens charles';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=topic&from=':
        placeholder = 'world\'s columbian expo';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=lcc&from=':
        placeholder = 'ps2700 f16';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=series&from=':
        placeholder = 'lecture notes in math';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=dewy&from=':
        placeholder = '900';
        break;
    case 'External:/vufind/Alphabrowse/Home?source=journal&from=':
        placeholder = 'chronicle of higher education';
        break;

    break;
  }
  select.prevAll('input').eq(0).attr('placeholder', placeholder);
}

/*
 * Get all e-holdings and e-links from the holdings
 * mega service. Responsible for generating the "Intranet"
 * box with e-holdings links on full record page and the
 * same links on results pages.
 */
const eholdingsMegaService = (isbns, oclc, target, onlineHeader = false) => {
  let url ='https://www.lib.uchicago.edu/cgi-bin/megaholdings?function=megaholdings&callback=x&nums=';
  url += isbns.map(x => `isbn:${x}`).join(',');
  url += oclc.map(x => `oclc:${x}`).join(',');
  $.get(url, function(data, status, xhr) {
    const response = JSON.parse(data);
    const links = response.oks;
    if (links !== undefined && links.length != 0) {
      let html = '';
      if (onlineHeader) {
        html = '<h3>Online</h3>';
      }
      html += links.map(l => {
        return `<div><a href="${l.link}">${l.linktext}</a></div>`;
      }).join('');
      target.append(html);
    }
  }, 'text'); // Not JSON?
}

/*
 * Get deduped eholdings, SFX.
 */
function getDedupedEholdings(issns, sfx, target, onlineHeader = false) {
  $.get( VuFind.path + '/AJAX/JSON?method=dedupedEholdings', 'issns=' + issns + '&sfx=' + sfx + '&header=' + onlineHeader, function(data, status, xhr) {
    var response = JSON.parse(data);

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

/*
 * Use AJAX to generate a friendlier version of the map link with location text.
 */
function getMapLink(loc, callnum, prefix, target) {
  var urlparams = '&location=' + loc + '&callnum=' + callnum + '&callnumPrefix=' + prefix;
  var maplookupurl = 'https://forms2.lib.uchicago.edu/lib/maplookup/maplookup.php?json=true' + encodeURI(urlparams);
  $.get(maplookupurl, function(data, status, xhr) {
    var response = JSON.parse(data);
    if(response == null) {
        $(target).addClass('hide');
    } else {
        target.html('<i class="fa fa-map-marker" aria-hidden="true"></i> ' + response.location);
        target.attr("href", response.url);
    }
  }, 'html');
}

$(document).ready(function() {
  // Better RSS icon
  $('.fa-bell').addClass('fa-rss').removeClass('fa-bell');

  /*** Maplookup service link ***/
  $('.maplookup').each(function() {
    var loc = $(this).data('location');
    var callnum = $(this).data('callnum');
    var prefix = $(this).data('prefix');
    getMapLink(loc, callnum, prefix, $(this));
  });

  // Update searchbox placeholder text on page load and
  // when the select pulldown changes.
  updateSearchPlaceholderText($('#searchForm_type'));
  $('#searchForm_type').change(function() {
    updateSearchPlaceholderText($(this));
  });

  // Add "About the Library Catalog" and "Power searching instructions" links
  var about = '<a href="https://www.lib.uchicago.edu/research/help/catalog-help/about/" class="about-catalog external">About the Library Catalog</a>';
  var powerSearching = '<a href="https://www.lib.uchicago.edu/research/help/catalog-help/power-searching/" class="power-searching external">Power searching instructions (Boolean, etc.)</a>';
  $('.search-hero, #advSearchForm').after(about);
  $('.searchHomeContent #searchForm').after(powerSearching);

  // Add "back to basic search" link to advanced search page
  var basicSearchLink = '<a href="/vufind/">Basic Search</a>';
  $('.template-name-advanced ul.breadcrumb').append(`<li>${basicSearchLink}</li>`);

  // Add Bootstrap 'from-control' to homepage search button 
  var homeSearchButton = $('.template-name-home #searchForm .btn-primary');
  homeSearchButton.addClass('form-control');

  // E-holdings Mega Service
  $('.e-links[data-isbns], .e-links[data-oclc-nums]').each(function() {
    const isbns = $(this).data('isbns');
    const oclc = $(this).data('oclc-nums');
    const showOnlineHeader = $(this).data('online-header');
    eholdingsMegaService(isbns, oclc, $(this), showOnlineHeader);
  });

  /*** Deduped eholdings instead of sfx ***/
  $('.deduped[data-issns]').each(function() {
    const issns = $(this).data('issns');
    const sfxNum = $(this).data('sfx');
    const showOnlineHeader = $(this).data('online-header');
    getDedupedEholdings(issns, sfxNum, $(this), showOnlineHeader);
  });
});
