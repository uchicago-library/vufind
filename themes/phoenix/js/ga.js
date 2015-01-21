/* 
 * Google Analytics Event tracking for the catalog interface
 * 
 * rev. 2014/04/17 jej
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
 */

/* This is universal analytics. */

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-45852123-1', 'uchicago.edu');

String.prototype.endsWith = function(suffix) {
	return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

/* Uncomment the lines below to test event logging. */
function catalogevent(a, b, c, d) {
    //var s = '/vufind/themes/phoenix/js/empty.js?analyticstest=on&event=' + c + '&label=' + d;
    //$('head').append("<script src='" + s + "' type='text/javascript'></script>");
    ga(a, b, c, d);
}

$(document).ready(function() {
    var q = 'https://www.lib.uchicago.edu/cgi-bin/subnetclass?jsoncallback=?';
    $.getJSON(q, function(data) {

	    /* Set the Subnetclass custom dimension and track the pageview. */
	    ga('set', 'dimension1', data);
	    ga('send', 'pageview');
	
		/**************************************** 
		 ******** BASIC KEYWORD SEARCHES ******** 
		 ****************************************/
	
	    $('#searchForm').on('submit.googleAnalytics', function(e) {
	        $('#searchForm').unbind('submit.googleAnalytics');
	
	        e.preventDefault();
	
	        catalogevent('send', 'event', 'searchType', 'Basic Keyword Search');
		    /* Catalog homepage basic keyword search */
		    if (window.location.href.endsWith('/vufind/')) {
			    catalogevent('send', 'event', 'submitSearchFrom', 'Catalog Homepage');
		    }
		    /* Keyword search result page */
		    else if (window.location.href.indexOf('/vufind/Search/Results') !== -1) {
			    catalogevent('send', 'event', 'submitSearchFrom', 'Result Page');
		    }
		    /* Full record view */
		    else if (window.location.href.indexOf('/vufind/Record/') !== -1) {
			    catalogevent('send', 'event', 'submitSearchFrom', 'Full Record');
		    }
	        catalogevent('send', 'event', 'searchField', $('#searchForm_type').val());
	
	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
	    });
	    
	    /* User changes the field pulldown. */
		$('#searchForm_type').change(function() {
			catalogevent('send', 'event', 'changeSearchField', 'basicKeywordSearch');
		}); 
	
		/**************************************** 
		 ******* ADVANCED KEYWORD SEARCHES ****** 
		 ****************************************/
	
		$('#advSearchForm').on('submit.googleAnalytics', function(e) {
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
	            if ($(this).parent('div').prev('div').find('input[id^="search_lookfor"]').val() != '') {
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
					catalogevent('send', 'event', 'advancedSearchCollectionLimit', collection_limits('|'));
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
		            catalogevent('send', 'event', 'changeSearchField', 'advancedKeywordSearch');
	            });
	        });
	    }, 250);
	        
		/**************************************** 
		 ******** BEGINS WITH SEARCHES **********
		 ****************************************/
	
		$('#alphaBrowseForm').on('submit.googleAnalytics', function(e) {
	        $('#alphaBrowseForm').unbind('submit.googleAnalytics');

	        e.preventDefault();
	
			catalogevent('send', 'event', 'searchType', 'beginsWithSearch');

			/* Catalog homepage begins with search */
			if (window.location.href.endsWith('/vufind/alphabrowse')) {
				catalogevent('send', 'event', 'submitSearchFrom', 'Catalog Homepage');
			}
		
			/* Begins with search results page */
			else if (window.location.href.indexOf('/vufind/alphabrowse/results') !== -1) {
				catalogevent('send', 'event', 'submitSearchFrom', 'Result Page');
			}
		    catalogevent('send', 'event', 'searchField', $(this).find('#alphaBrowseForm_source option:selected').text());

	        /* Short delay for analytics. */
	        var form = this;
	        setTimeout(function() {
	            $(form).submit();
	        }, 100);
		});
	
		/* User changes field pulldown. */
		$('#alphaBrowseForm_source').change(function() {
		    catalogevent('send', 'event', 'changeSearchField', 'beginsWithSearch');
		});
	
		/**************************************** 
		 ********* SEARCH RESULT PAGE ***********
		 ****************************************/
	
	    /* FACETS */
	
	    /* Get a count of the number of active facets. 
	     * Note that since you click facets one at a time, there will
	     * be a '0' for every '1', and so on.
	     */
	    if ($('ul.filters').length > 0) {
	        var facetCount = $('ul.filters li').length;
	        catalogevent('send', 'event', 'facetCount', facetCount);
	    }
	
		/* User clicked a facet. */
	    $('#narrowsearch dl a[href]').on('click touchstart', function(e) {
	        e.preventDefault();
	
	        var f = $(this).parents('dl').eq(0).find('dt').text();
	        if ($(this).text().indexOf('more') == 0) {
	    	    catalogevent('send', 'event', 'moreFacets', f);
	        } else {
	    	    catalogevent('send', 'event', 'selectFacet', f);
	        }
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
	    });
	
	    /* User removes a facet. */
	    $('#narrowsearch ul.filters a').on('click touchstart', function(e) {
	        e.preventDefault();
	
	        var element = $(e.target).parents('li').find('a').eq(1);
	        var facetType = element.text().split(':')[0];
	        catalogevent('send', 'event', 'deleteFacet', facetType);
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
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
		$('a.bv').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'changeResultsView', 'toBriefView');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
		/* User switches to detailed view. */
		$('a.dv').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'changeResultsView', 'toDetailedView');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
		
		/* User clicks a link to go to a different result page. */
		$('div.pagination a[href]').on('click touchstart', function(e) {
	        e.preventDefault();

			// extract the page number from the href. 
			var m = /page=([0-9]+)/.exec($(this).attr('href'));
			if (m !== null && m.length > 0) {
				catalogevent('send', 'event', 'goToResultPage', m[1]); 
            } else {
				catalogevent('send', 'event', 'goToResultPage', 1);
            }

	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
		/* User clicked a result on the search result page. Which one? */
		$('a.title').on('click touchstart', function(e) {
            var li = $(this).parents('li.result').eq(0);

            /* In a saved list, the id will be undefined. Skip those. */
            if (li.attr('id') == undefined) {
                return;
            } else {
	            e.preventDefault();
	
			    var n = parseInt(li.attr('id').replace('result', '')) + 1;
			    catalogevent('send', 'event', 'resultNumber', n);
	
	            /* Short delay for analytics. */
	            var link = this;
	            setTimeout(function() {
                    var target = $(link).attr('target');
                    var href = $(link).attr('href');
                    if ($.trim(target).length > 0) {
                        window.open(href, target);
                    } else {
                        window.location = href;
                    }
	            }, 100);
            }
		});
	
		/**************************************** 
		 ************* RECORD VIEW **************
		 ****************************************/
	
		/* User clicks next or previous links in full record view. */
		$('div.resultscroller a[href]').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'nextOrPrevious', $(this).text().trim());
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
		/* User clicks toolbar link. */
		$('div.toolbar a[href]').on('click touchstart', function(e) {
			/* Skip "Text this", "Email this", and "Export Records" because
	         * the real event happens when a form is submitted or submenu is
	         * clicked. */
			if ($(this).hasClass('smsRecord')) {
				return;
			} else if ($(this).hasClass('mailRecord')) {
				return;
			} else if ($(this).hasClass('export')) {
				return;
			} else {
			    catalogevent('send', 'event', 'toolbarItem', $(this).text().trim());
	            /* This opens a modal window- there is no need to set a
	             * delay for Google Analytics. */
	        }
		});
	
		/* User actually emails a record. Since the form is generated via
	     * JavaScript, keep checking to see if it's on the page. */
	    setInterval(function() {
	        $('form[name="smsRecord"]:not(.submitEventBound)').each(function() {
	            $(this).addClass('submitEventBound');
	            $(this).submit(function() {
			        catalogevent('send', 'event', 'toolbarItem', 'Text this');
	                // JEJ add delay for this??
	            });
	        });
	    }, 250);
	
		/* User actually emails a record. */
	    setInterval(function() {
	        $('form[name="emailRecord"]:not(.submitEventBound)').each(function() {
	            $(this).addClass('submitEventBound');
	            $(this).submit(function() {
			        catalogevent('send', 'event', 'toolbarItem', 'Email this');
	                // JEJ add delay for this?
	            });
	        });
	    }, 250);
	
		/* User clicks linked fields in full record view. */
		$('table.citation a[href]').on('click touchstart', function(e) {
			/* check to be sure this link is going to a browse. */
			if ($(this).attr('href').indexOf('source=') !== -1 && $(this).attr('href').indexOf('from=') !== -1) {
	            e.preventDefault();
	
				/* will send something like 'author' or 'topic'. */
				var m = /source=([a-z]+)/.exec($(this).attr('href'));
				if (m.length > 0) {
					catalogevent('send', 'event', 'linkedField', m[1]);
				}
	
	            /* Short delay for analytics. */
	            var link = this;
	            setTimeout(function() {
                    var target = $(link).attr('target');
                    var href = $(link).attr('href');
                    if ($.trim(target).length > 0) {
                        window.open(href, target);
                    } else {
                        window.location = href;
                    }
	            }, 100);
			}
		});
	
		/* User switches tabs. */
		$('div#tabnav a[href]').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'changeFullRecordTab', $(this).text());
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
	    /* User clicks a borrowing service, like borrow direct, uborrow, or
	     * recall. */
	    $('a.service').on('click touchstart', function(e) {
	        e.preventDefault();
	
	        var t = $(this).text();
			catalogevent('send', 'event', 'borrowingService', t);
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
	    });
	
	    /* User clicks to browse the shelves. */
	    $('a.ablink').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'browseShelves', 'browseShelves');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
	    });
	
		/* User clicks a "similar item" */
		$('ul.similar a[href]').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'similarItems', 'click');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
	    /* User clicks "view availability" for serial holdings. */
	    $('.va a[href]').on('click touchstart', function() {
			catalogevent('send', 'event', 'viewSerialHoldings', 'viewSerialHoldings');
	        /* link opens via javascript, no need for a delay. */
	    });
	
		/**************************************** 
		 ************ HEADER LINKS **************
		 ****************************************/
	
	    $('#headerRight a[href]').on('click touchstart', function(e) {
	        e.preventDefault();
	
	        var t = $(e.target).text();
	        catalogevent('send', 'event', 'headerLink', t);
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
	    });
	
	    $('#nav a[href]').on('click touchstart', function(e) {
	        /* deal with the help link separately. */
	        if ($(e.target).attr('title') == 'Help') {
	            return;
	        }
	
	        e.preventDefault();
	
	        var t = $(e.target).text();
	        catalogevent('send', 'event', 'headerLink', t);
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
	    });
		
		/**************************************** 
		 *********** CONTEXTUAL HELP ************
		 ****************************************/
		
		/* User clicks contextual help. */
		$('a[title="Help"]').on('click touchstart', function(e) {
	        e.preventDefault();
	
			var c = '';
	        if (/\/vufind\/$/.test(window.location.href)) {
	            c = 'Basic Keyword Search';
			} else if (/\/Search\/Advanced$/.test(window.location.href)) {
				c = 'Advanced Keyword Search';
			} else if (/\/alphabrowse$/.test(window.location.href)) {
				c = 'Begins With Search';
			} else if (/\/Search\/Results.*$/.test(window.location.href)) {
				c = 'Keyword Results';
			} else if (/\/alphabrowse\/results.*$/.test(window.location.href)) {
				c = 'Begins With Results';
			} else if (/\/Record\/.*$/.test(window.location.href)) {
				c = 'Full Record';
			} else if (/\/MyResearch.*$/.test(window.location.href)) {
				c = 'My Account';
			}
			catalogevent('send', 'event', 'contextualHelp', c);
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
		/* User clicks the Hathi Trust preview link. */
		$('a.previewHT').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'preview', 'Hathi Trust');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
		});
	
		/* User clicks the Google Book preview link. */
		$('a.previewGBS').on('click touchstart', function(e) {
	        e.preventDefault();
	
			catalogevent('send', 'event', 'preview', 'Google');
	
	        /* Short delay for analytics. */
	        var link = this;
	        setTimeout(function() {
                var target = $(link).attr('target');
                var href = $(link).attr('href');
                if ($.trim(target).length > 0) {
                    window.open(href, target);
                } else {
                    window.location = href;
                }
	        }, 100);
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
	        $('.openurls a[href]:not(.clickEventBound)').each(function() {
	            $(this).addClass('clickEventBound');
	            $(this).on('click touchstart', function(e) {
	                e.preventDefault();
	
	                var t = $(e.target).text();
		            catalogevent('send', 'event', 'SFXLink', t);
	
	                /* Short delay for analytics. */
	                var link = this;
	                setTimeout(function() {
                        var target = $(link).attr('target');
                        var href = $(link).attr('href');
                        if ($.trim(target).length > 0) {
                            window.open(href, target);
                        } else {
                            window.location = href;
                        }
	                }, 100);
	            });
	        });
	    }, 250);
    });
});

