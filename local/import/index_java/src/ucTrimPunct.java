package org.vufind.index;


/*
 * Functions for removing trailing punctuation from strings
 *
 */

// import java.lang.String;
// import java.util.Set;

import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.solrmarc.tools.Utils;
import java.util.*;





// Give ourselves the ability to import other BeanShell scripts
//addClassPath("../import");
//importCommands("index_scripts");

/**
 * Removes trailing punctuation from returned fields.
 */


public class ucTrimPunct {

	//static org.solrmarc.index.SolrIndexer indexer = null;
	 org.solrmarc.index.SolrIndexer indexer = org.solrmarc.index.SolrIndexer.instance();
	
	
	public Set ucGetFieldsRemoveTrailingPunctuation(Record record, String tagStr)
	{
		Set result = new LinkedHashSet();

		Set fields = indexer.getFieldList(record, tagStr);
		Iterator iter = fields.iterator();

		String f;
		while (iter.hasNext())
		{
			f = (String) iter.next();
			String trimmed = org.solrmarc.tools.Utils.removeTrailingChar(f, "([,/;:.])*");
			result.add(trimmed);
		}
		return result;
	}


	/**
	 * Removes trailing period from returned fields.
	 */
	public Set ucGetFieldsRemoveTrailingPeriod(Record record, String tagStr)
	{
		Set result = new LinkedHashSet();

		Set fields = indexer.getFieldList(record, tagStr);
		Iterator iter = fields.iterator();

		String f;
		while (iter.hasNext())
		{
			f = (String) iter.next();
			String trimmed = org.solrmarc.tools.Utils.removeTrailingChar(f, "[.]");
			result.add(trimmed);
		}
		return result;
	}
}

