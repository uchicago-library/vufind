/* 
 * Google Analytics Event tracking for the catalog interface
 * 
 * rev. 2015/03/11 jej
 * 
 * Note: Google Analytics has a limit of 10 simultaneous events. 
 * To keep the count of events down, especially in the advanced 
 * search which can fire a lot of events, I'm joining the limit
 * values into a single string with a pipe ('|') delimiter.
 * 
 * A certain percent of events (approximately 10%?) doesn't get
 * recorded in Google Analytics when they're attached to link 
 * clicks or forms submits because the browser moves to a new
 * page before they get the chance to execute. I added 100ms 
 * delays in these cases to give the events a chance to record.
 *
 * The default submit buttons in VuFind are often named "submit".
 * This creates a situation where the forms can't be submitted
 * by JavaScript- see:
 *
 * http://bugs.jquery.com/ticket/4652
 * 
 * phoenix/templates/search/searchbox.phtml
 * phoenix/templates/search/searchbox_mini.phtml
 * phoenix/templates/search/advanced/layout.phtml
 * http://stackoverflow.com/questions/26698141/google-universal-analytics-form-submit
 */

$(document).ready(function() {
	/* This is universal analytics. */
	
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	
	ga('create', 'UA-45852123-1', 'uchicago.edu');
	
	String.prototype.endsWith = function(suffix) {
		return this.indexOf(suffix, this.length - suffix.length) !== -1;
	};
	
	/* Uncomment the lines below to test event logging. */
	function catalogevent(a, b, c, d, hitcallback) {
	    //var s = '/vufind/themes/phoenix/js/empty.js?analyticstest=on&event=' + c + '&label=' + d;
	    //var s = 'https://www.lib.uchicago.edu/e/jej/empty.js?analyticstest=on&event=' + c + '&label=' + d;
	    //$('head').append("<script src='" + s + "' type='text/javascript'></script>");
        /* Be sure Google Analytics is present before continuing. */
        if (window.ga && ga.create) {
            if (hitcallback) {
	            ga(a, b, c, d, {
                    "hitCallback": hitcallback
                })
            } else {
	            ga(a, b, c, d);
            }
        }
	}

    function cataloglinkclick(a, b, c, d, link, e) {
        /* Be sure Google Analytics is present before continuing. */
        if (window.ga && ga.create) {
            var target = $(link).attr('target');
            if (target == '_blank') { /* Opening a new window? Just send the event. */
    	        catalogevent(a, b, c, d);
            } else { /* Otherwise register a hit callback. */
                var href = $(link).attr('href');
                if (href) {
                    e.preventDefault();
                    catalogevent(a, b, c, d, function() {
                        window.location = href;
                    });
                }
            }
        }
    }

    var q = 'https://www.lib.uchicago.edu/cgi-bin/subnetclass?jsoncallback=?';
    $.getJSON(q, function(data) {

	    /* Set the Subnetclass custom dimension and track the pageview. */
	    ga('set', 'dimension1', data);
	    ga('send', 'pageview');
	
		/**************************************** 
		 ******** BASIC KEYWORD SEARCHES ******** 
		 ****************************************/

        /* Basic search, from homepage. */	
	    $('#advSearchForm').on('submit.googleAnalytics', function(e) {
            / * Make sure it's a basic search. */
            if (!$(this).hasClass('basicSearch')) {
                return;
            }
	        $('#advSearchForm').unbind('submit.googleAnalytics');
	
	        e.preventDefault();
	
	        catalogevent('send', 'event', 'searchType', 'Basic Keyword Search');
			catalogevent('send', 'event', 'submitSearchFrom', 'Catalog Homepage');
	        catalogevent('send', 'event', 'searchField', $(this).find('.search select').val());

	        / * Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
        });

        /* Basic search, from a search result page or the full record view. */
        $('.mini-search').on('submit.googleAnalytics', function(e) {
            if (!$(this).find('input[name="lookfor"]')) {
                return;
            }
            if (!$(this).find('select[name="type"]').val()) {
                return;
            }
                
            $(this).unbind('submit.googleAnalytics');
	
	        e.preventDefault();

		    /* Keyword search result page */
		    if (window.location.href.indexOf('/vufind/Search/Results') !== -1) {
			    catalogevent('send', 'event', 'submitSearchFrom', 'Result Page');
		    }
		    /* Full record view */
		    else if (window.location.href.indexOf('/vufind/Record/') !== -1) {
			    catalogevent('send', 'event', 'submitSearchFrom', 'Full Record');
		    }
	        catalogevent('send', 'event', 'searchField', $(this).find('select[name="type"]').val());
	
	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
	    });
	    
		/**************************************** 
		 ******* ADVANCED KEYWORD SEARCHES ****** 
		 ****************************************/
	
		$('#advSearchForm').on('submit.googleAnalytics', function(e) {
            /* Make sure it's an advanced search. */
            if (!$(this).hasClass('advancedSearch')) {
                return;
            }

	        $('#advSearchForm').unbind('submit.googleAnalytics');
	
	        e.preventDefault();
	
			catalogevent('send', 'event', 'searchType', 'Advanced Keyword Search');
			catalogevent('send', 'event', 'submitSearchFrom', 'Catalog Homepage');
	
			/* Get number of fields used. */	
			var fields = 0;
			$('input[name^="lookfor"]').each(function() {
				if ($(this).val().trim() != "") {
					fields++;
				}
			});
			catalogevent('send', 'event', 'advancedSearchFieldCount', fields);
	
			/* Get number of groups used. */	
			var groups = [];	
			$('input[name^="lookfor"]').each(function() {
				if ($(this).val().trim() != "") {
					var g = $(this).attr('name').replace('lookfor', '').replace('[]', '');
					if (groups.indexOf(g) == -1) {
						groups.push(g);
					}
				}
			});
			catalogevent('send', 'event', 'advancedSearchGroupCount', groups.length.toString());
	
	        /* Record the fields submitted. First check to be sure there is
	         * something in the text box next to the field. */
	        $('select[id^="search_type"]').each(function() {
	            if ($(this).prev('input[id^="search_lookfor"]').val() != '') {
			        catalogevent('send', 'event', 'searchField', $(this).val());
	            }
	        });
	
			/* Advanced search language limits. Catch errors to deal with
	         * the condition where a select inputs value comes back as null. */
	        var language_limits = [];
	        try {
	            if ($('select#limit_language').val()[0] !== '') {
				    /* Send in each language separately- e.g. "English", "German", etc. */
	                $.each($('select#limit_language').val(), function(i, v) {
	                    language_limits.push(v);
	                });
				    catalogevent('send', 'event', 'advancedSearchLanguageLimit', language_limits.join('|'));
	            }
	        } catch (e) {}
	
			/* Advanced search format limits. */
	        var format_limits = [];
	        try {
			    if ($('select#limit_format').val()[0] !== '') {
				    /* Send in each format separately- e.g. CD, LP, etc. */
	                $.each($('select#limit_format').val(), function(i, v) {
	                    format_limits.push(v);
	                });
					catalogevent('send', 'event', 'advancedSearchFormatLimit', format_limits.join('|'));
				}
			} catch (e) {}
	
			/* Advanced search building limit. */
	        var building_limits = [];
	        try {
			    if ($('select#limit_building').val()[0] !== '') {
				    /* Send in each building separately- e.g. Crerar, etc. */
	                $.each($('select#limit_building').val(), function(i, v) {
	                    building_limits.push(v);
				    });
					catalogevent('send', 'event', 'advancedSearchLocationLimit', building_limits.join('|'));
	            }
	        } catch (e) {}
	
			/* Advanced search collection limit. */
	        var collection_limits = [];
	        try {
			    if ($('select#limit_collection').val()[0] !== '') {
				    /* Send in each collection separately. */
	                $.each($('select#limit_collection').val(), function(i, v) {
	                    collection_limits.push(v);
				    });
					catalogevent('send', 'event', 'advancedSearchCollectionLimit', collection_limits.join('|'));
	            }
	        } catch (e) {}
	
	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
		});
	
	    /* User changes the field pulldown. Because the advanced search
	     * pulldowns get added via JavaScript, check to see if there are any
	     * new ones on the page every so often. If there is a new one, add a
	     * class to it to "tag" it so we don't bound multiple events to it,
	     * and bind one change event. */
	    setInterval(function() {
	        $('select[id^="search_type"]:not(.changeEventBound)').each(function() {
	            $(this).addClass('changeEventBound');
	            $(this).change(function() {
                    if ($(this).parents('form:first').hasClass('basicSearch')) {
		                var search = 'basicKeywordSearch';
                    } else {
                        var search = 'advancedKeywordSearch';
                    }
		            catalogevent('send', 'event', 'changeSearchField', search);
	            });
	        });
	    }, 250);
	        
		/**************************************** 
		 ******** BEGINS WITH SEARCHES **********
		 ****************************************/

    	/* Catalog homepage begins with search */
		$('#alphaBrowseForm').on('submit.googleAnalytics', function(e) {
            if ($(this).parents('div.template-name-advanced').length == 0) {
                return;
            }
	        $('#alphaBrowseForm').unbind('submit.googleAnalytics');
	        e.preventDefault();
	
			catalogevent('send', 'event', 'searchType', 'beginsWithSearch');
	    	catalogevent('send', 'event', 'submitSearchFrom', 'Catalog Homepage');
		    catalogevent('send', 'event', 'searchField', $(this).find('#alphaBrowseForm_source option:selected').text());

	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
		});

	    /* Begins with form on the search result page. Because these
         * forms get added via JavaScript, check to see if there are any
	     * new ones on the page every so often. If there is a new one, add a
	     * class to it to "tag" it so we don't bound multiple events to it,
	     * and bind one change event. */
	    setInterval(function() {
	        $('header form:not(.submitEventBound)').each(function() {
	            $(this).addClass('submitEventBound');

		        $(this).on('submit.googleAnalytics', function(e) {
                    if ($(this).hasClass('submitEventSubmitted')) {
                        return;
                    }
                    $(this).addClass('submitEventSubmitted');
					if (window.location.href.toUpperCase().indexOf('/VUFIND/ALPHABROWSE/') == -1) {
		                return;
		            }
		            if ($(this).attr('id') != 'alphaBrowseForm') {
		                return;
		            }
					catalogevent('send', 'event', 'searchType', 'beginsWithSearch');
					catalogevent('send', 'event', 'submitSearchFrom', 'Result Page');
				    catalogevent('send', 'event', 'searchField', $(this).find('#alphaBrowseForm_source option:selected').text());
		        });
	        });
	    }, 250);

		/* User changes field pulldown. */
		$('#alphaBrowseForm_source').change(function() {
		    catalogevent('send', 'event', 'changeSearchField', 'beginsWithSearch');
		});
	
		/**************************************** 
		 ********* SEARCH RESULT PAGE ***********
		 ****************************************/
	
	    /* FACETS */
	
	    /* Get a count of the number of active facets, if at least
         * one facet is active. Note that since the most common way to
         * get facets is to add them from a result page, there will be a
         * '0' for every '1', and so on. (The other way is to use
         * filters on an advanced search- those will show up as facets
         * too.)
	     */
	    if ($('ul.filters').length > 0) {
	        var facetCount = $('ul.filters a.list-group-item').length;
	        catalogevent('send', 'event', 'facetCount', facetCount);
	    }
	
		/* User clicked a facet. */
        $('li.facet a, #search-sidebar .facet-group .collapse > a').on('click', function(e) {
            /* If the user clicked the "x" to exclude a facet, the
             * target would have been an <i> element. In that case, go up in the
             * element hierarchy to get to the actual anchor. */
            var link = $(this);
            if ($(link).is('i')) {
                link = $(link).parents('a').eq(0);
            }
	
	        var f = $(link).text();

            /* Check to see if this was a "NOT" facet. If so, add the NOT. */
            if ($(link).attr('title') && $(link).attr('title').indexOf('exclude') > -1) { 
                f = 'NOT ' + f;
            }

	        if ($(link).text().indexOf('more') == 0) {
	    	    catalogevent('send', 'event', 'moreFacets', f);
	        } else {
	    	    cataloglinkclick('send', 'event', 'selectFacet', f, $(link), e);
	        }
	    });

		/* User submits the year of publication form. */
        $('form#publishDateFilter').on('submit.googleAnalytics', function(e) {
	        e.preventDefault();
	        $(this).unbind('submit.googleAnalytics');
	        var f = $(this).parents('.facet:first').find('#publishDatefrom').val().trim();
	        var t = $(this).parents('.facet:first').find('#publishDateto').val().trim();
            var txt = f + '-' + t;
            catalogevent('send', 'event', 'moreFacets', txt);
	
	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
        });
	
	    /* User removes a facet. */
	    $('ul.filters a.list-group-item').on('click', function(e) {
	        var facetType = $(this).text().split(':')[0].trim();
	        cataloglinkclick('send', 'event', 'deleteFacet', facetType, $(this), e);
	    });
	
		/* Will send 'Relevance', 'Date Descending', 'Date Ascending', etc. */
		$('select#sort_options_1').parents('form').on('submit.googleAnalytics', function(e) {
	        e.preventDefault();
	        $(this).unbind('submit.googleAnalytics');
	
			catalogevent('send', 'event', 'changeResultSorting', $('select#sort_options_1 option:selected').text());
	
	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
		});

        /* User changes the number of results per page. */
        $('select#limit').parents('form').on('submit.googleAnalytics', function(e) {
            e.preventDefault();
            $(this).unbind('submit.googleAnalytics');
    
            /* will send 20, 40, 60, 80 or 100 */
            catalogevent('send', 'event', 'changeResultsPerPage', $(this).find('select#limit').val());
    
            /* Short delay for analytics. */
            var form = this;
            setTimeout(function() {
                $(form).submit();
            }, 100);
        });
	
	    /* On page load, record if this is a brief or detailed search result page. */
	    if ($('span.bv').length) {
			catalogevent('send', 'event', 'resultsView', 'briefView');
	    }
	    if ($('span.dv').length) {
			catalogevent('send', 'event', 'resultsView', 'detailedView');
	    }
	
		/* User switches to brief view. */
		$('a.bv').on('click', function(e) {
			cataloglinkclick('send', 'event', 'changeResultsView', 'toBriefView', $(this), e);
		});
	
		/* User switches to detailed view. */
		$('a.dv').on('click', function(e) {
			cataloglinkclick('send', 'event', 'changeResultsView', 'toDetailedView', $(this), e);
		});
		
		/* User clicks a link to go to a different result page. */
		$('ul.pagination a[href]').on('click', function(e) {
			// extract the page number from the href. 
            var page = 1;
			var m = /page=([0-9]+)/.exec($(this).attr('href'));
			if (m !== null && m.length > 0) {
                page = m[1];
            }
	    	cataloglinkclick('send', 'event', 'goToResultPage', page, $(this), e);
		});
	
		/* User clicked a result on the search result page- either the
         * thumbnail or the title itself. Which result did they click? */
		$('div.template-dir-search.template-name-results .ajaxItem a:has("img.recordcover"), div.template-dir-search.template-name-results .ajaxItem a.title').on('click', function(e) {
            var result = $(this).parents('div.result').eq(0);

            /* In a saved list, the id will be undefined. Skip those. */
            if (result.attr('id') == undefined) {
                return;
            } else {
			    var n = parseInt(result.attr('id').replace('result', '')) + 1;
			    cataloglinkclick('send', 'event', 'resultNumber', n, $(this), e);
            }
		});

		/**************************************** 
		 ********* BROWSE RESULT PAGE ***********
		 ****************************************/
   
        /* See also links. */ 
        $('ul.see-also a[href]').on('click', function(e) {
            var text = $(this).text();
            cataloglinkclick('send', 'event', 'seeAlso', text, $(this), e);
        });
        /* Use instead links. */
        $('ul.use-instead a[href]').on('click', function(e) {
            var text = $(this).text();
            cataloglinkclick('send', 'event', 'useInstead', text, $(this), e);
        });

		/**************************************** 
		 ************ MISCELLANEOUS *************
		 ****************************************/

        /* WorldCat Links */
        $('a.mini-worldcat-link').on('click', function(e) {
            var query_string = $(this).attr('href').replace(/^.*\?/, '');
            cataloglinkclick('send', 'event', 'worldCatSearch', query_string, $(this), e);
        });
	
		/**************************************** 
		 ************* RECORD VIEW **************
		 ****************************************/
	
		/* User clicks next or previous links in full record view. */
		$('ul.pager-phoenix a[href]').on('click', function(e) {
            /* There are three links in the pager- prev, next, and
             * number of results. 
             */
            var text = '';
            if ($(this).attr('href').indexOf('/vufind/Search/Results') > -1) {
                text = 'backToResults';
            } else {
                if ($(this).text().indexOf('Prev') > -1) {
                    text = 'Prev';
                } else if ($(this).text().indexOf('Next') > -1) {
                    text = 'Next';
                }
            }
            if (text == '') {
                return;
            }

            cataloglinkclick('send', 'event', 'nextOrPrevious', text, $(this), e);
		});
	
		/* User clicks toolbar link. */
		$('div.main ul.nav:not(.recordTabs) a[href]').on('click', function(e) {
            /* Skip homepage tabs. */
            if ($(this).parents('.template-dir-search.template-name-advanced').length > 0) {
                return;
            }
            
			/* Skip "Text this", "Email this", and "Export Records" because
	         * the real event happens when a form is submitted or submenu is
	         * clicked. */
            if ($(this).attr('id') == 'sms-record') {
				return;
            } else if ($(this).attr('id') == 'mail-record') {
				return;
			} else if ($(this).hasClass('export-toggle')) {
				return;
			} else {
			    catalogevent('send', 'event', 'toolbarItem', $(this).text().trim());
	            /* This opens a modal window- there is no need to set a
	             * delay for Google Analytics. */
	        }
		});
	
		/* User actually texts a record. Since the form is generated via
	     * JavaScript, keep checking to see if it's on the page. */
	    setInterval(function() {
	        $('form[name="smsRecord"]:not(.submitEventBound)').each(function() {
	            $(this).addClass('submitEventBound');
	            $(this).submit(function() {
			        catalogevent('send', 'event', 'toolbarItem', 'Text this');
	            });
	        });
	    }, 250);
	
		/* User actually emails a record. */
	    setInterval(function() {
	        $('form[name="emailRecord"]:not(.submitEventBound)').each(function() {
	            $(this).addClass('submitEventBound');
	            $(this).submit(function() {
			        catalogevent('send', 'event', 'toolbarItem', 'Email this');
	            });
	        });
	    }, 250);
	
		/* User clicks linked fields in full record view. */
		$('.record table a[href]').on('click', function(e) {
            /* Skip the "Add Tag" link. */
            if ($(this).attr('id') == 'tagRecord') {
                return;
            }

			/* check to be sure this link is going to a browse. */
			if ($(this).attr('href').indexOf('source=') !== -1 && $(this).attr('href').indexOf('from=') !== -1) {
				/* will send something like 'author' or 'topic'. */
				var m = /source=([a-z]+)/.exec($(this).attr('href'));
				if (m.length > 0) {
					cataloglinkclick('send', 'event', 'linkedField', m[1], $(this), e);
				}
			}
		});
	
		/* User switches tabs. */
		$('div.record-tabs ul.nav-tabs a[href]').on('click', function(e) {
			cataloglinkclick('send', 'event', 'changeFullRecordTab', $(this).text(), $(this), e);
		});
	
	    /* User clicks a borrowing service, like borrow direct, uborrow, or
	     * recall. */
	    $('a.service').on('click', function(e) {
	        var t = $(this).text();
			cataloglinkclick('send', 'event', 'borrowingService', t, $(this), e);
	    });
	
	    /* User clicks to browse the shelves. */
	    $('a.ablink').on('click', function(e) {
			cataloglinkclick('send', 'event', 'browseShelves', 'browseShelves', $(this), e);
	    });
	
		/* User clicks a "similar item" */
		$('ul.similar a[href]').on('click', function(e) {
			cataloglinkclick('send', 'event', 'similarItems', 'click', $(this), e);
		});

	    /* User clicks "more subjects" link. */
	    $('a.subjectsToggle').on('click', function() {
	        /* link opens via javascript, no need for a delay. */
			catalogevent('send', 'event', 'moreSubjects', 'moreSubjects');
	    });

	    /* User clicks "more details" link. */
	    $('a.bibToggle').on('click', function() {
	        /* link opens via javascript, no need for a delay. */
			catalogevent('send', 'event', 'moreDetails', 'moreDetails');
	    });
	
	    /* User clicks "view availability" for serial holdings. */
	    $('a.summaryToggle').on('click', function() {
	        /* link opens via javascript, no need for a delay. */
			catalogevent('send', 'event', 'viewSerialHoldings', 'viewSerialHoldings');
	    });

	    /* User clicks "view more items" for serial holdings. */
	    $('a.itemsToggle').on('click', function() {
	        /* link opens via javascript, no need for a delay. */
			catalogevent('send', 'event', 'viewMoreItems', 'viewMoreItems');
	    });
	
		/**************************************** 
		 ************ HEADER LINKS **************
		 ****************************************/
	
	    $('ul.top-tabs a[href]').on('click', function(e) {
	        var t = $(e.target).text();
	        cataloglinkclick('send', 'event', 'headerLink', t, $(this), e);
	    });
	
        /*Only affects logout link currently, but this would affect general links inside #header-collapse*/
	    $('#header-collapse a[href]').on('click', function(e) {
	        /* deal with the help and leave feedback (knowledge tracker) links separately. */
	        if ($(e.target).attr('title') == 'Help' || $(e.target).attr('data-lightbox') !== undefined) {
	            return;
	        }
            	
	        var t = $(e.target).text();
	        cataloglinkclick('send', 'event', 'headerLink', t, $(this), e);
	    });

		$('#header-collapse a[data-lightbox]').on('click', function(e) {
	        var t = $(e.target).text().trim();
	        catalogevent('send', 'event', 'headerLink', t);
        });
		
		/**************************************** 
		 *********** CONTEXTUAL HELP ************
		 ****************************************/
		
		/* User clicks contextual help. */
		$('a[title="Help"]').on('click', function(e) {
			var c = '';
            if ($('ul#homepageNavTabs li:first').hasClass('active')) {
                if ($('#advSearchForm').hasClass('basicSearch')) {
	                c = 'Basic Keyword Search';
                } else if ($('#advSearchForm').hasClass('advancedSearch')) {
				    c = 'Advanced Keyword Search';
                }
            } else if ($('ul#homepageNavTabs li:nth-child(2)').hasClass('active')) {
				c = 'Begins With Search';
			} else if (window.location.href.toUpperCase().indexOf('/VUFIND/SEARCH/RESULTS') > -1) {
				c = 'Keyword Results';
			} else if (window.location.href.toUpperCase().indexOf('/VUFIND/ALPHABROWSE/') > -1) {
				c = 'Begins With Results';
			} else if (window.location.href.toUpperCase().indexOf('/VUFIND/RECORD/') > -1) {
				c = 'Full Record';
			} else if (window.location.href.toUpperCase().indexOf('/VUFIND/MYRESEARCH/') > -1) {
				c = 'My Account';
			}
			cataloglinkclick('send', 'event', 'contextualHelp', c, $(this), e);
		});

		/* User clicks the Hathi Trust preview link. */
		$('a.eLink.hathi').on('click', function(e) {
		    cataloglinkclick('send', 'event', 'preview', 'Hathi Trust', $(this), e);
		});
	
		/* User clicks the Google Book preview link. */
		$('a.previewGBS').on('click', function(e) {
            cataloglinkclick('send', 'event', 'preview', 'Google', $(this), e);
		});
	
		/**************************************** 
		 *************** SFX LINK ***************
		 ****************************************/
	
	    /* User changes the field pulldown. Because the advanced search
	     * pulldowns get added via JavaScript, check to see if there are any
	     * new ones on the page every so often. If there is a new one, add a
	     * class to it to "tag" it so we don't bound multiple events to it,
	     * and bind one change event. */

	    setInterval(function() {
	        $('div.deduped-eholdings a[href]:not(.clickEventBound), #e-links > a[href]:not(.clickEventBound), .e-list > a[href]:not(.clickEventBound)').each(function() {
	            $(this).addClass('clickEventBound');
	            $(this).on('click', function(e) {
                    /* Get the text of the link. "Find It!" button links
                     * have no text. If the user clicked one of those
                     * buttons set the "text" of the link here.
                     */
	                var t = $(e.target).text();
                    if (t == '' && $(this).find('img.findit')) {	
                        t = 'Find It!';
                    }
                    cataloglinkclick('send', 'event', 'preview', t, $(this), e);
	            });
	        });
	    }, 250);
    });
});

