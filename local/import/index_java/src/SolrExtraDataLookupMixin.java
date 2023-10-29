package org.solrmarc.index;

import java.util.ArrayList;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.solrmarc.solr.SolrCoreLoader;
import org.solrmarc.solr.SolrProxy;
import org.solrmarc.solr.SolrRuntimeException;
import org.solrmarc.tools.SolrMarcIndexerException;

/**
 * This class facilitates connecting to a Solr core to do a search based on ISBN.
 * It first extracts the ISBN (or ISBNs) from a MARC record that is passed in, then
 * builds a query to search the Solr index.  It then takes the results of that query 
 * and returns the data to be included in the Solr add document that is being built 
 * for the current record.
 *  
 * @author rh9ec
 *
 */
public class SolrExtraDataLookupMixin extends SolrIndexerMixin
{
    public final static int MAX_DOCS_TO_RETRIEVE = 100;
    /**
     *  serverMap - caches connections to one or more Solr indexes, using a separate 
     *  CommonsHttpSolrServer for each separate Solr index that is used.
     */
    Map<String, SolrProxy> serverMap = null;
    /**
     *   resultMap - caches the results returned from a Solr index for a query by ISBN.
     *   Because multiple SolrMarc index specifications could exist for a given record
     *   this cache of the results returned by the first index specification is used by
     *   all subsequent index specifications for that record. 
     */
    Map<String, SolrDocumentList> resultMap = null;
    /**
     *   queryMap - caches the queries built for a given MARC record, that will be used
     *   for searching the Solr index.  At present only one query is built and used, 
     *   based on the ISBNs in a given record.  So this map could be replaced by a single 
     *   String variable.
     */
    Map<String, String> queryMap = null;
    
    public SolrExtraDataLookupMixin()
    {
        serverMap = new LinkedHashMap<String, SolrProxy>();
    }
    
    /**
     * perRecordInit - Called once per record before any index specifications are processed.
     * This method calls buildISBNQuery to create the query string to be used by any 
     * index specifications called for this record.  As noted above since this Mixin 
     * currently only supports lookups by ISBN, and will only have one query per record,
     * the queryMap could be replaced by a single String variable.
     * 
     * @param record - The MARC record being indexed.
     * 
     * @see org.solrmarc.index.SolrIndexerMixin#perRecordInit(org.marc4j.marc.Record)
     */
    public void perRecordInit(Record record)
    {
        queryMap = new LinkedHashMap<String, String>();

        queryMap.put("isbn", buildISBNQueryString(record, "020az", "isbn_text"));
        
        resultMap = new LinkedHashMap<String, SolrDocumentList>();
    }

    /**
     * buildISBNQueryString - Create a query string to be sent to a solr index to look up 
     * items based on ISBN by extracting all of the ISBNs from the provided MARC record.
     * Note: it uses entries from both the 020 $a field and the 020 $z field, since the $z
     * subfield (invalid ISBN) frequently contains the ISBN of a different form of the same
     * item.  ie. the 020 $z contains the ISBN of the hardback edition while the record itself 
     * refers to the paperback edition or an electronic version.
     * 
     * @param record - The MARC record being indexed.
     * @param fieldspec - the MARC field/subfield spec for where ISBNs can be found in record.
     * @param prefixStr - The solr field in the external solr index that contains the ISBNs
     * @return  a query string containing all of the ISBNs found in the provided record
     *          e.g.      isbn_text:(0780650808 OR 078065062X)
     *          or null if no ISBNs are found
     */
    private String buildISBNQueryString(Record record, String fieldspec, String prefixStr)
    {
        List<VariableField> fieldsISBN = record.getVariableFields(fieldspec.substring(0,3));
        StringBuilder sb = new StringBuilder();
        boolean first = true;
        for (VariableField vf : fieldsISBN)
        {
            
            DataField df;
            List<Subfield> sfl = ((DataField)vf).getSubfields(fieldspec.substring(3));
            for (Subfield sf : sfl)
            {
                String isbnval = sf.getData().replaceFirst("([- 0-9]*[0-9Xx]).*", "$1").replaceAll("[- ]", "");
                if (isbnval.matches("([0-9]{9}[0-9Xx])|([0-9]{13})"))
                {
                    if (first)  sb.append("(");
                    else        sb.append(" OR ");
                    first = false;
                    sb.append(isbnval);
                }
            }            
        }
        if (!first) sb.append(")");
        
        //if the record contained no ISBNs return null as the query string
        if (sb.length() == 0) return(null);
        
        String parameterizedQuery = prefixStr + ":" + sb.toString();
        return(parameterizedQuery);
    }
    
    
    /**
     * getServerForURL - returns the SolrServer object to use to communicate
     * to a Solr server at the provided URL.  Caches the SolrServer object in a map 
     * using the url as the key, so that only one SolrServer objects is created for a 
     * given URL.
     * 
     * @param solrUrl - The URL of a particular Solr server, including the core to access
     *                  e.g.  http://localhost:8080/solr/summ
     * @return SolrServer object
     */
    private SolrProxy getServerForURL(String solrUrl)
    {
        SolrProxy server = null;
        if (serverMap.containsKey(solrUrl))
        {
            server = serverMap.get(solrUrl);
        }
        else
        {
//            try
//            {
                server = SolrCoreLoader.loadRemoteSolrServer(solrUrl, null, true);
                serverMap.put(solrUrl, server);
//            }
//            catch (MalformedURLException e)
//            {
//                // TODO Auto-generated catch block
//                e.printStackTrace();
//            }
        }      
        return(server);
    }
    

    /**
     * getSolrDocumentsForQuery - Retrieve the set of documents from the separate solr index 
     * that match the provided query.  Then cache the results so that subsequent index specifications
     * for the same MARC record, that query the same server can merely use the cached list of documents.
     * 
     * Note: the static value MAX_DOCS_TO_RETRIEVE controls how many matching documents are returned 
     * from the solr server.
     * 
     * @param server - The SolrServer to send query to.
     * @param responseKey - The key to retrieve the search result, or the key to use to cache it.
     * @param queryStr - The query string to send to Solr.
     * @return  A SolrDocumentList matching the provided query.
     */
    private SolrDocumentList getSolrDocumentsForQuery(SolrProxy server, String responseKey, String queryStr)
    {
        SolrDocumentList sdl = null;
        // if query already performed, return those results
        if (resultMap.containsKey(responseKey))
        {
            sdl = resultMap.get(responseKey);
        }
        else
        {
            SolrQuery params = new SolrQuery();
            params.setQuery(queryStr);
            params.setRows(MAX_DOCS_TO_RETRIEVE);
            QueryResponse resp = null;
            try
            {
                resp = server.query(params);
                sdl = resp.getResults();
                resultMap.put(responseKey, sdl);
            }
            catch (SolrRuntimeException e)
            {
                throw new SolrMarcIndexerException(SolrMarcIndexerException.EXIT, e.getMessage());
            }
        }
        return(sdl);

    }
    
    /**
     * fillResultSetFromSolrDocs - Traverse over the SolrDocumentList returned for the current query
     * and select fields that match the parameter  fieldToFetch.
     * 
     * @param sdl - The list of matching SolrDocuments
     * @param fieldToFetch - the field to look for within the list of matching SolrDocuments
     * @param ensureUnique - boolean specify whether duplicate values should be omitted 
     * @return
     */
    private List<String> fillResultSetFromSolrDocs(SolrDocumentList sdl, String fieldToFetch, boolean ensureUnique)
    {
        List<String> result = new ArrayList<String>();
        if (sdl != null)
        {
            long numFound = sdl.getNumFound();
            for (int rec = 0; rec < numFound && rec < MAX_DOCS_TO_RETRIEVE; rec++)
            {
                SolrDocument doc = sdl.get(rec);
                Collection<Object> vals = doc.getFieldValues(fieldToFetch);
                if (vals != null)
                {
                    for (Object val : vals)
                    {
                        String sVal = val.toString();
                        if (!ensureUnique || ! result.contains(sVal))
                        {
                            result.add(sVal);
                        }
                    }
                }
            }
        }
        return(result);
    }

    
    /**
     * getExtraSolrDataByISBN - The actual custom indexing function called by SolrMarc.
     * The index specification provides the URL of the Solr server (including the core name)
     * that contains the desired data, and the name of the field within that data to return 
     * for the current index specification.
     * 
     * For example the index specification:  
     *     getExtraSolrDataByISBN(http://localhost:8080/solr/summ, summary_display)
     * will query the solr server at "http://localhost:8080/solr/summ" and return 
     * all occurrences of the summary_display field.
     * 
     * @param record - The MARC record being indexed.
     * @param solrUrl - The URL of the Solr server containing the data to be looked up.
     * @param fieldToFetch
     * @return
     */
    public List<String> getExtraSolrDataByISBN(final Record record, String solrUrl, String fieldToFetch)
    {
        // Get the possibly cached SolrServer object to use
        SolrProxy server = getServerForURL(solrUrl);
        
        String responseKey = solrUrl + "isbn";
        String queryStr = queryMap.get("isbn");
        if (queryStr == null) return (new ArrayList<String>());
        
        SolrDocumentList sdl = getSolrDocumentsForQuery(server, responseKey, queryStr);
        
        List<String> result = fillResultSetFromSolrDocs(sdl, fieldToFetch, true);

        return(result);
    }    
}

