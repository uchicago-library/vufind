package org.solrmarc.index;

import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;

import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.solrmarc.tools.SolrMarcIndexerException;

/**
 * This portion of the Mixin is used to process the Syndetics TOC data to create the solr data store 
 * that subsequently will be used by the other part of the Mixin to add this data to the solr records 
 * created for bibliographic MARC records. 
 * 
 * @author rh9ec
 *
 */
public class SynTocEncode extends SolrIndexerMixin
{
    
    /**
     * indicatorMatch - Determine whether the indicators of the provided DataField match the regular expressions 
     * passed  in as the second and third parameters 
     * 
     * @param df
     * @param indicator1ToMatch
     * @param indicator2ToMatch
     * @return
     */
    boolean indicatorMatch(DataField df, String indicator1ToMatch, String indicator2ToMatch)
    {
        return((""+df.getIndicator1()).matches(indicator1ToMatch) && (""+df.getIndicator2()).matches(indicator2ToMatch));
    }
    
    /**
     * getTocDataFromOrig - Traverse over the 970 fields in the MARC record (which contain the toc info)
     * if the indicators of the field matches the supplied regular expressions then extract the subfields 
     * listed in the subfieldsToFetch parameter
     * 
     * @param record - The MARC record being indexed
     * @param indicator1ToMatch - A regular expression specifying which indicator values should be included
     * @param indicator2ToMatch - A regular expression specifying which indicator values should be included
     * @param subfieldsToFetch - The list of subfields to collect and return
     * @return
     */
    public List<String> getTocDataFromOrig(final Record record, String indicator1ToMatch, String indicator2ToMatch, String subfieldsToFetch)
    {
        List<VariableField> tocs = record.getVariableFields("970");
        List<String> result = new ArrayList<String>();
        for (VariableField vf : tocs)
        {
            DataField df = (DataField)vf;
            if (indicatorMatch(df, indicator1ToMatch, indicator2ToMatch))
            {
                for (Subfield sf : df.getSubfields(subfieldsToFetch))
                {
                    result.add(sf.getData());
                }
            }
        }
        return(result);
    }
    
    /**
     * getTocDataFromOrigUniq - Traverse over the 970 fields in the MARC record (which contain the toc info)
     * if the indicators of the field matches the supplied regular expressions then extract the subfields 
     * listed in the subfieldsToFetch parameter.   Note this is Identical to the method getTocDataFromOrig
     * above, but since it uses and returns a Set rather than a List duplicate entries are only included once. 
     * 
     * @param record - The MARC record being indexed
     * @param indicator1ToMatch - A regular expression specifying which indicator values should be included
     * @param indicator2ToMatch - A regular expression specifying which indicator values should be included
     * @param subfieldsToFetch - The list of subfields to collect and return
     * @return
     */
    public Set<String> getTocDataFromOrigUniq(final Record record, String indicator1ToMatch, String indicator2ToMatch, String subfieldsToFetch)
    {
        List<VariableField> tocs = record.getVariableFields("970");
        Set<String> result = new LinkedHashSet<String>();
        for (VariableField vf : tocs)
        {
            DataField df = (DataField)vf;
            if (indicatorMatch(df, indicator1ToMatch, indicator2ToMatch))
            {
                for (Subfield sf : df.getSubfields(subfieldsToFetch))
                {
                    result.add(sf.getData());
                }
            }
        }
        return(result);
    }
    
    /**
     * getTocDataFromOrigFormat - Similar to the above methods, this method traverses over the 970 fields 
     * in the MARC record if the indicators of the field matches the supplied regular expressions then 
     * extract the subfields listed in the subfieldsToFetch parameter.   This routine then formats the data 
     * in a JSON-like manner that might be useful for presenting the data in a formatted manner.   
     * 
     * @param record - The MARC record being indexed
     * @param indicator1ToMatch - A regular expression specifying which indicator values should be included
     * @param indicator2ToMatch - A regular expression specifying which indicator values should be included
     * @param subfieldsToFetch - The list of subfields to collect and return
     * @return
     */
    public List<String> getTocDataFromOrigFormat(final Record record, String indicator1ToMatch, String indicator2ToMatch, String subfieldsToFetch)
    {
        List<VariableField> tocs = record.getVariableFields("970");
        List<String> result = new ArrayList<String>();
        for (VariableField vf : tocs)
        {
            DataField df = (DataField)vf;
            StringBuilder fval = new StringBuilder();
            if (!indicatorMatch(df, indicator1ToMatch, indicator2ToMatch))
                continue;
            fval.append(df.getIndicator1());
            fval.append(df.getIndicator2());
            for (Subfield sf : df.getSubfields(subfieldsToFetch))
            {
               fval.append('{').append(sf.getCode()).append(':').append(sf.getData()).append('}'); 
            }
            result.add(fval.toString());
        }
        return(result);
    }
    

    /**
     * getISBNUniq - Traverse over all 020 fields extracting the first $a subfield that 
     * contains a valid ISBN number and return that number for use as the unique id for 
     * the solr record.  If no valid ISBN is found create a placeholder string to return
     * for use as the id.  This is necessary since the DeleteRecordIfFieldEmpty directive
     * isn't used when the field being generated is the unique id.
     * 
     * 
     * @param record - The MARC record being indexed
     * @return   String - the first valid ISBN in the MARC record, expanded to ISBN-13
     */
    public String getISBNUniq(final Record record)
    {
        List<VariableField> tocs = record.getVariableFields("020");
        String result = null;
        for (VariableField vf : tocs)
        {
            DataField df = (DataField)vf;
            for (Subfield sf : df.getSubfields("a"))
            {
                try {
                    result = ISBNNormalizer.normalize(sf.getData());
                    return (result);
                }
                catch (IllegalArgumentException e) 
                {
                    // no valid ISBN found in that subfield, keep looking
                }
            }
        }
        return("NoValidISBN-"+record.getControlNumber());
    }

    /**
     * getISBNAll - Traverse over all 020 fields extracting all $a subfields that 
     * contains a valid ISBN number, convert them all to ISBN-13 and return them in a Set
     * (to eliminate duplicates) 
     * If no valid ISBN are found return and empty set.  When called with the DeleteRecordIfFieldEmpty
     * directive will prevent the record from being added to the index.
     * 
     * 
     * @param record - The MARC record being indexed
     * @return   String - the first valid ISBN in the MARC record, expanded to ISBN-13
     */
    public Set<String> getISBNAll(final Record record)
    {
        List<VariableField> tocs = record.getVariableFields("020");
        Set<String> result = new LinkedHashSet<String>();
        for (VariableField vf : tocs)
        {
            DataField df = (DataField)vf;
            for (Subfield sf : df.getSubfields("a"))
            {
                try {
                    result.add(ISBNNormalizer.normalize(sf.getData()));
                }
                catch (IllegalArgumentException e) 
                {
                    // invalid ISBN found in that subfield, keep looking
                }
            }
        }
        return(result);
    }

}
