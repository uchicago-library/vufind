Directories here contain source code and build scripts for java code used with the Syndetics data

SolrExtraDataLookupMixin:

 contains the source code for the Syndetics data Mixin
it is used for creating the toc index core and also for performing lookups by ISBN to add toc data
and summary data when indexing MARC records into the biblio core.

to build the mixin jar containing this code, run the command:   ant install


isbnfilter :

contains a Solr anaylzer filter for handling ISBNs, it is a fork of the anaylzer created by Bill
Dueber, but since his version requires at least Solr 4.4 the code here is edited slightly to allow 
it to work with Solr 4.2 
see https://github.com/billdueber/umich_solr_library_filters


RemoteSolrSearcher:

A replacement for the class of the same name in SolrMarc to work with binary MARC21 records stored 
in the solr index.  Once this file is update in SolrMarc, this separate implementation won't be needed
