package org.vufind.index;

import java.util.ArrayList;
import java.util.Collection;
import java.util.HashSet;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.ListIterator;
import java.util.Set;
import java.util.regex.Pattern;  // FOR   int ind2 = Pattern.matches("\\d", s) ? Integer.parseInt(s) : 0;
//import java.util.cleanData;
import java.lang.String;
import java.lang.Object;

import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.DataFieldImpl;
import org.solrmarc.index.SolrIndexer;

//import org.solrmarc.tools.Utils;
//import org.solrmarc.index.VuFindIndexer;
import org.solrmarc.tools.Utils;
//import org.solrmarc.*;

//import org.solrmarc.index.SolrIndexer;

// Give ourselves the ability to import other BeanShell scripts
//addClassPath("../import");
//importCommands("index_scripts");

// define the base level indexer so that its methods can be called from the script.
// note that the SolrIndexer code will set this value before the script methods are called.


/**
 * Most of this code is lifted from the solrmarc Java source.
 *
 * Eventually, we may want to re-code this in java, as a SolrMarcMixin class.
 * Then all the pesky helper functions to not have to be copied.
 */

/**
 * Get Set of Strings as indicated by tagStr. For each field spec in the
 * tagStr that is NOT about bytes (i.e. not a 008[7-12] type fieldspec), the
 * result string is the concatenation of all the specific subfields.
 * 
 * @param record -
 *            the marc record object
 * @param tagStr
 *            string containing which field(s)/subfield(s) to use. This is a
 *            series of: marc "tag" string (3 chars identifying a marc
 *            field, e.g. 245) optionally followed by characters identifying
 *            which subfields to use. Separator of colon indicates a
 *            separate value, rather than concatenation. 008[5-7] denotes
 *            bytes 5-7 of the 008 field (0 based counting) 100[a-cf-z]
 *            denotes the bracket pattern is a regular expression indicating
 *            which subfields to include. Note: if the characters in the
 *            brackets are digits, it will be interpreted as particular
 *            bytes, NOT a pattern. 100abcd denotes subfields a, b, c, d are
 *            desired.
 * @param result <code>Collection</code> object to store results in.
 *
 * @return the <code>result</code> object, containing the contents of the 
 *         indicated marc field(s)/subfield(s), as a collection
 *         of Strings of sortable data.
 */


public class ucTitleBrowseFunctions {


	static org.solrmarc.index.SolrIndexer indexer = org.solrmarc.index.SolrIndexer.instance();


	public static Collection getSortableFields(Record record, String tagStr, Collection result)
	{
		//System.out.println(" Record record  "+ record);
		String[] tags = tagStr.split(":"); 
		//Set result = new LinkedHashSet();
		for (int i = 0; i < tags.length; i++)
		{
			// Check to ensure tag length is at least 3 characters
			if (tags[i].length() < 3)

			{
				System.err.println("Invalid tag specified: " + tags[i]);
				continue;
			}

			// Get Field Tag

			String tag = tags[i].substring(0, 3); 
			int subIndex = 3;

			//  boolean linkedField = false;
			boolean linkedField = false;
			if (tag.equals("LNK"))
			{
				tag = tags[i].substring(3, 6);
				linkedField = true;
				subIndex = 6;
			}
			// Process Subfields
			String subfield = tags[i].substring(subIndex);

			boolean havePattern = false;
			int subend = 0;
			// brackets indicate parsing for individual characters or as pattern
			int bracket = tags[i].indexOf('[');
			if (bracket != -1)
			{
				String [] sub = tags[i].substring(bracket + 1).split("[\\]\\[\\-, ]+");
				try
				{
					// if bracket expression is digits, expression is treated as character positions
					int substart = Integer.parseInt(sub[0]);
					subend = (sub.length > 1) ? Integer.parseInt(sub[1]) + 1 : substart + 1;
					String subfieldWObracket = subfield.substring(0, bracket-3);
					//   result.addAll(getSubfieldDataAsSet(record, tag, subfieldWObracket, substart, subend));
					result.addAll(indexer.getSubfieldDataAsSet(record, tag, subfieldWObracket, substart, subend));
					System.out.println(" 1  result:   "+ result);
				}
				catch (NumberFormatException e)
				{
					// assume brackets expression is a pattern such as [a-z]
					havePattern = true;
				}
			}
			if (subend == 0) // don't want specific characters.
			{
				String separator = null;
				if (subfield.indexOf('\'') != -1)
				{
					separator = subfield.substring(subfield.indexOf('\'') + 1, subfield.length() - 1);
					subfield = subfield.substring(0, subfield.indexOf('\''));
				}

				if (havePattern)
					if (linkedField){
						result.addAll(getSubfieldDataAsSet(record, tag, subfield, separator));
						System.out.println(" 2  result:   "+ result);}
					else{
						result.addAll(indexer.getAllSubfields(record, tag + subfield, separator));
						System.out.println(" 3  result:   "+ result);}
				else if (linkedField){
					result.addAll(indexer.getSubfieldDataAsSet(record, tag, subfield, separator));

				}
				else
				{

					// SolrIndexer.getSubfieldDataAsSet was changed for getSubfieldDataAsSet to get gata from subfield. it is needed for checking non-filing charachters to do alphabetical title browse     
					//result.addAll(getSubfieldDataAsSet(record, tag, subfield, separator));

					// Dec 19, 2012 Because we exclude subfield "volume" from Series, we had trailing punctuations when we did sorting on Series. 
					// Therefore next code had to be added to trim punctuations.

					LinkedHashSet subfieldData = (LinkedHashSet) getSubfieldDataAsSet(record, tag, subfield, separator);   

					Set<DataField> subfieldData1 = new LinkedHashSet<DataField>();
					for (DataField str : subfieldData1) 
						//subfieldData1.add(Utils.cleanData(str));
						subfieldData1.add((str));

					for(DataField str : subfieldData1)    
					{         
						//	result.add(Utils.cleanData(str));  // Utils.cleanData is a static method from org.solrmarc.tools.Utils; that trims the end of a string( something like --->   replaceAll("[ .,:;/]*$", "")    )    
						result.add(str);
						System.out.println(" 5  result:   "+ result);
					}

				}
			}
		}

		return result;
	}



	/**
	 * Get Set of Strings as indicated by tagStr. For each field spec in the
	 * tagStr that is NOT about bytes (i.e. not a 008[7-12] type fieldspec), the
	 * result string is the concatenation of all the specific subfields.
	 * 
	 * @param record -
	 *            the marc record object
	 * @param tagStr
	 *            string containing which field(s)/subfield(s) to use. This is a
	 *            series of: marc "tag" string (3 chars identifying a marc
	 *            field, e.g. 245) optionally followed by characters identifying
	 *            which subfields to use. Separator of colon indicates a
	 *            separate value, rather than concatenation. 008[5-7] denotes
	 *            bytes 5-7 of the 008 field (0 based counting) 100[a-cf-z]
	 *            denotes the bracket pattern is a regular expression indicating
	 *            which subfields to include. Note: if the characters in the
	 *            brackets are digits, it will be interpreted as particular
	 *            bytes, NOT a pattern. 100abcd denotes subfields a, b, c, d are
	 *            desired.
	 * @param result <code>Collection</code> object to store results in.
	 *
	 * @return the contents of the indicated marc field(s)/subfield(s), as a set
	 *         of Strings of sortable data.
	 */
	public static Set getSortableFieldsAsSet(Record record, String tagStr)
	{
		Set result = new LinkedHashSet();
		return (Set) getSortableFields(record, tagStr, result);
	}

	/**
	 * Get List of Strings as indicated by tagStr. For each field spec in the
	 * tagStr that is NOT about bytes (i.e. not a 008[7-12] type fieldspec), the
	 * result string is the concatenation of all the specific subfields.
	 * 
	 * @param record -
	 *            the marc record object
	 * @param tagStr
	 *            string containing which field(s)/subfield(s) to use. This is a
	 *            series of: marc "tag" string (3 chars identifying a marc
	 *            field, e.g. 245) optionally followed by characters identifying
	 *            which subfields to use. Separator of colon indicates a
	 *            separate value, rather than concatenation. 008[5-7] denotes
	 *            bytes 5-7 of the 008 field (0 based counting) 100[a-cf-z]
	 *            denotes the bracket pattern is a regular expression indicating
	 *            which subfields to include. Note: if the characters in the
	 *            brackets are digits, it will be interpreted as particular
	 *            bytes, NOT a pattern. 100abcd denotes subfields a, b, c, d are
	 *            desired.
	 * @param result <code>Collection</code> object to store results in.
	 *
	 * @return the contents of the indicated marc field(s)/subfield(s), as a list
	 *         of Strings of sortable data.
	 */

	public static List getSortableFieldsAsList(Record record, String tagStr)
	{	
		List result = new ArrayList();

		//System.out.println("!!!!!!!!!! (List) getSortableFields(record, tagStr, result):    " + (List) getSortableFields(record, tagStr, result));
		return (List) getSortableFields(record, tagStr, result);
	}

	/**
	 * Get the specified subfields from the specified MARC field, returned as a
	 * set of strings to become lucene document field values
	 *
	 * @param record - the marc record object
	 * @param fldTag - the field name, e.g. 245
	 * @param subfldsStr - the string containing the desired subfields
	 * @param separator - the separator string to insert between subfield items (if null, a " " will be used)
	 * @returns a Set of String, where each string is the concatenated contents
	 *          of all the desired subfield values from a single instance of the
	 *          fldTag
	 */

	protected static Set getSubfieldDataAsSet(Record record, String fldTag, String subfldsStr, String separator)
	{
		//  Set<Subfield> resultSet = new LinkedHashSet<Subfield>();
		Set resultSet = new LinkedHashSet<Subfield>();

		// Process Leader;
		if (fldTag.equals("000"))
		{
			resultSet.add(record.getLeader().toString());
			return resultSet;
		}

		// Loop through Data and Control Fields

		// List varFlds = record.getVariableFields(fldTag);
		List<DataField> varFlds = new ArrayList<DataField>();
		for (VariableField vf : varFlds)  varFlds.add((DataField)vf);

		for (DataField vf: varFlds)
		{
			if (!fldTag.substring(0,2).equals("00") && subfldsStr != null)
				//  if (!SolrIndexer.isControlField(fldTag) && subfldsStr != null)   <-- Original line. it was changed for the next line(did'not work with Maura's Series Title  Browse (bug#10722)
			{
				// DataField
				DataField dfield = (DataField) vf;



				// Get second non-filing indicator if there is some. If to look at marc record, some fields have second indicator and some don't(just empty space) but the empty spot returns as ASCII 48 ( ASCII 48 is zero '0')
				//  Creates integer ind2( means indexTwo ) and assigns it as second indicator from "String s" and at the same time check whether "String s" is an integer. If it's not(can be some character or other possible symbol if data is corrupted) assgns it as zero.
				String s1 = Character.toString(dfield.getIndicator1());
				Integer ind1 = Pattern.matches("\\d", s1) ? Integer.parseInt(s1) : null;
				if (subfldsStr.length() > 1 || separator != null)
				{
					// concatenate subfields using specified separator or space
					StringBuffer buffer = new StringBuffer("");


					//List subFlds = dfield.getSubfields();
					List<Subfield> subFlds = new ArrayList<Subfield>();
					for (Subfield sf : subFlds) subFlds.add((Subfield)sf);

					for(Subfield sf: subFlds)
					{

						if (subfldsStr.indexOf(sf.getCode()) != -1)
						{
							// if subfield is 'a' and there are non-filing characters, trim the subfield
							String trimmedData = sf.getData().trim();
							//	System.out.println("trimmedData:  "+ trimmedData);

							if((sf.getCode() == 'a') && (ind1 != null) && nonFiling1(fldTag))
								trimmedData = trimmedData.substring(ind1, trimmedData.length());
							//System.out.println("trimmedData:  "+ trimmedData);
							if (buffer.length() > 0)
								buffer.append(separator != null ? separator : " ");
							buffer.append(trimmedData);
						}
					}
					if (buffer.length() > 0)
						resultSet.add(buffer.toString());
				}
				else
				{
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfldsStr.charAt(0));
					//List<Subfield> subFlds = new ArrayList<Subfield>();

					for (Subfield sf : subFlds)
						//subFlds.add((Subfield)sf);
					{
						resultSet.add(sf.getData().trim());
					}
				}



				// Get second non-filing indicator if there is some. If to look at marc record, some fields have second indicator and some don't(just empty space) but the empty spot returns as ASCII 48 ( ASCII 48 is zero '0')
				//  Creates integer ind2( means indexTwo ) and assigns it as second indicator from "String s" and at the same time check whether "String s" is an integer. If it's not(can be some character or other possible symbol if data is corrupted) assgns it as zero.
				String s = Character.toString(dfield.getIndicator2());
				Integer ind2 = Pattern.matches("\\d", s) ? Integer.parseInt(s) : null;
				if (subfldsStr.length() > 1 || separator != null)
				{
					// concatenate subfields using specified separator or space
					StringBuffer buffer = new StringBuffer("");


					//List subFlds = dfield.getSubfields();
					List<Subfield> subFlds = new ArrayList<Subfield>();
					for (Subfield sf : subFlds) subFlds.add((Subfield)sf);

					for(Subfield sf: subFlds)
					{

						if (subfldsStr.indexOf(sf.getCode()) != -1)
						{
							// if subfield is 'a' and there are non-filing characters, trim the subfield
							String trimmedData = sf.getData().trim();
							//	System.out.println("trimmedData:  "+ trimmedData);

							if((sf.getCode() == 'a') && (ind2 != null) && nonFiling2(fldTag))
								trimmedData = trimmedData.substring(ind2, trimmedData.length());
							//System.out.println("trimmedData:  "+ trimmedData);
							if (buffer.length() > 0)
								buffer.append(separator != null ? separator : " ");
							buffer.append(trimmedData);
						}
					}
					if (buffer.length() > 0)
						resultSet.add(buffer.toString());
				}
				else
				{
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfldsStr.charAt(0));
					//List<Subfield> subFlds = new ArrayList<Subfield>();

					for (Subfield sf : subFlds)
						//subFlds.add((Subfield)sf);
					{
						resultSet.add(sf.getData().trim());
					}
				}
			}

			else
			{
				// Control Field
				resultSet.add(((ControlField) vf).getData().trim());
			}
		}
		//System.out.println("trimmedData:  "+ resultSet);
		return resultSet;

	}

	protected static boolean nonFiling1(String tag)
	{
		if (tag.equals("130") || tag.equals("730") || tag.equals("740"))
		{
			return true;
		}
		return false;    
	}

	protected static boolean nonFiling2(String tag)
	{

		if (tag.equals("240") || tag.equals("242") || tag.equals("243") 
				|| tag.equals("245") || tag.equals("440") || tag.equals("830"))
		{
			return true;
		}
		return false;    
	}


	// public static boolean isJournal(Record record)  is needed for Journal index sort
	// if record's Leader letter at possition 7 is 'b', 'i' or 's', it's a journal.

	public static boolean isJournal(Record record) 
	{       
		char leaderAt7 =  record.getLeader().toString().charAt(7);  

		if (leaderAt7 == 'b' || leaderAt7 == 'i' || leaderAt7 == 's')
		{   
			return true;
		}
		return false;
	}

	// methods journalBrowse and journalBrowseSort are called from marc_local.properties file

	public static List journalBrowse(Record record, String tagList)
	{ 
		if (isJournal(record)) 
		{ 
			return indexer.getFieldListAsList(titleDoubleEntryFilter(record, tagList), tagList); 
		}
		return null;
	}

	/*
	public static List journalBrowseSort(Record record, String tagList)
	{ 
		if (isJournal(record)) 
		{ 
			return getSortableFieldsAsList(titleDoubleEntryFilter(record, tagList), tagList); 
		}
		return null;
	}


	 */
/*
	public static List titleBrowse(Record record, String tagStr)
	{   

		List result = new ArrayList();

		Record filteredRec = titleDoubleEntryFilter(record, tagStr);
		List fields = indexer.getFieldListAsList(filteredRec, tagStr);
		Iterator iter = fields.iterator();

		String f;
		while (iter.hasNext())
		{
			f = (String) iter.next();
			String trimmed = org.solrmarc.tools.Utils.removeTrailingChar(f, "([,/;:.])*");  
			result.add(trimmed);
		}
		return result;
	}*/

	
	public static List titleBrowse(Record record, String tagStr)
	{ 

		//System.out.println(" Title:   "+titleDoubleEntryFilter(record, tagStr));
		return getSortableFieldsAsList(titleBrowseDoubleEntryFilter(record, tagStr), tagStr); 

	}
	
	public static List titleBrowseSort(Record record, String tagStr)
	{ 

		//System.out.println(" Title:   "+titleDoubleEntryFilter(record, tagStr));
		return getSortableFieldsAsList(titleDoubleEntryFilter(record, tagStr), tagStr); 

	}

	/**
	 * Filter record so title fields can be browseable with and without
	 * initial article.
	 *
	 * Build up a new Record object with only the needed titles. For any
	 * title field which has non-filing data, include a duplicate of the
	 * field with non-filing indicator of 0, i.e. one version of the field
	 * to trim, one version not to trim.
	 * 
	 * Example:
	 * Input: 
	 *     245 _4 |a The Hobbit
	 * Output: 
	 *     245 _4 |a The Hobbit
	 *     245 _0 |a The Hobbit
	 * 
	 * @param record -
	 *            the marc record object
	 * @param tagStr
	 *            string containing which field(s)/subfield(s) to use. This is a
	 *            series of: marc "tag" string (3 chars identifying a marc
	 *            field, e.g. 245) optionally followed by characters identifying
	 *            which subfields to use. Separator of colon indicates a
	 *            separate value, rather than concatenation. 008[5-7] denotes
	 *            bytes 5-7 of the 008 field (0 based counting) 100[a-cf-z]
	 *            denotes the bracket pattern is a regular expression indicating
	 *            which subfields to include. Note: if the characters in the
	 *            brackets are digits, it will be interpreted as particular
	 *            bytes, NOT a pattern. 100abcd denotes subfields a, b, c, d are
	 *            desired.
	 * @return new Record object with needed fields
	 */

	public static Record titleDoubleEntryFilter(Record record, String tagList)
	{
		List result = new ArrayList();
		// factory is the recommended way to get record objects
		MarcFactory factory = MarcFactory.newInstance(); 

		Record newRecord = factory.newRecord();
		Set titleFields = new HashSet();
		Set titleLinkingFields = new HashSet();

		boolean linkedField = false;

		boolean flag = false;
		String lTag;

		// Put tags of interest into useful form
		String[] tags = tagList.split(":");
		for (int i = 0; i < tags.length; i++)
		{
			// Check to ensure tag length is at least 3 characters
			if (tags[i].length() < 3)
			{
				System.err.println("Invalid tag specified: " + tags[i]);
				continue;
			}
			// Get Field Tag (we don't care about subfields in this function)
			String tag = tags[i].substring(0, 3); 
			int subIndex = 3;

			boolean linkingField = false; // get 880?
			if (tag.equals("LNK"))
			{
				tag = tags[i].substring(3, 6);
				linkedField = true;
				subIndex = 6;
			}
			if (linkingField) 
			{
				titleLinkingFields.add(tag);
			}
			else
			{
				titleFields.add(tag);
			}
		}

		// Loop over incoming Record
		List<DataField> dataFields = record.getDataFields();
		ListIterator<DataField> iter = dataFields.listIterator();
		while (iter.hasNext())
		{
			//  VariableField field = (VariableField) iter.next();
			DataField field = (DataField) iter.next();
			// Do we want to save this field?
			boolean saveField = false;
			// 880 shifts structure to mimic field it links to, so store
			// either real name or name that 880 mimics.
			String logicalFieldName = null; 

			if (titleFields.contains(field.getTag())) {
				saveField = true;
				logicalFieldName = field.getTag();
			}
			else if (field.getTag() == "true") 
			{
				// Does subfield 6 indicate a field we are interested in?
				if (field.getSubfield('6') != null)  // check for an error
				{
					String linkTag = field.getSubfield('6').getData().substring(0,3);
					if (titleLinkingFields.contains(linkTag)) {
						saveField = true;
						logicalFieldName = linkTag;
						lTag = linkTag;

					}
				}
			}

			boolean subFlag1 = false;
			boolean subFlag2 = false;
			
			if (saveField) {
				newRecord.addVariableField(field);
				List<Subfield> fields = field.getSubfields();

				// if field has non-filing characters, make a copy of the
				// field with non-filing indicator set to 0
				char ind2 = field.getIndicator2();	

				if (nonFiling2(logicalFieldName) && Character.isDigit(ind2) && Character.getNumericValue(ind2) > 0)
				{
					DataField fieldCopy = new DataFieldImpl(field.getTag(), field.getIndicator1(), '0');
					for(Subfield sf : fields)
					{
						//System.out.println("     ~~  SORT: "+ sf);
						if (field.getIndicator2() != 0 && subFlag2 == false )
						{

							int nonSort=Character.getNumericValue(ind2);

							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData().substring(nonSort)));
							//System.out.println("FLAG fieldCopy: "+fieldCopy);
							subFlag2 = true;
						}
						else{
							//	System.out.println("~~fieldCopy: "+fieldCopy);
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData()));
						}

					}
					newRecord.addVariableField(fieldCopy);

				}

				char ind1 = field.getIndicator1();
				if (nonFiling1(logicalFieldName) && Character.isDigit(ind1) && Character.getNumericValue(ind1) > 0) 
				{
					DataField fieldCopy = new DataFieldImpl(field.getTag(), '0', field.getIndicator2());


					for(Subfield sf : fields)
					{
					//	System.out.println("     ~~  SORT: "+ sf);
						if (field.getIndicator1() != 0 && subFlag1 == false )
						{
							int nonSort=Character.getNumericValue(ind1);
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData().substring(nonSort)));
							//System.out.println("FLAG fieldCopy: "+fieldCopy);
							subFlag1 = true;
						}
						else{
							//	System.out.println("~~fieldCopy: "+fieldCopy);
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData()));
						}
					}
					newRecord.addVariableField(fieldCopy);
				}
			}
		}	
	//	System.out.println("SORT newRecord:  "  + newRecord );
		return newRecord;
	}


	public static Record titleBrowseDoubleEntryFilter(Record record, String tagList)
	{
		List result = new ArrayList();
		// factory is the recommended way to get record objects
		MarcFactory factory = MarcFactory.newInstance(); 

		Record newRecord = factory.newRecord();
		Set titleFields = new HashSet();
		Set titleLinkingFields = new HashSet();

		boolean linkedField = false;

		boolean flag = false;
		String lTag;

		// Put tags of interest into useful form
		String[] tags = tagList.split(":");
		for (int i = 0; i < tags.length; i++)
		{
			// Check to ensure tag length is at least 3 characters
			if (tags[i].length() < 3)
			{
				System.err.println("Invalid tag specified: " + tags[i]);
				continue;
			}
			// Get Field Tag (we don't care about subfields in this function)
			String tag = tags[i].substring(0, 3); 
			int subIndex = 3;

			boolean linkingField = false; // get 880?
			if (tag.equals("LNK"))
			{
				tag = tags[i].substring(3, 6);
				linkedField = true;
				subIndex = 6;
			}
			if (linkingField) 
			{
				titleLinkingFields.add(tag);
			}
			else
			{
				titleFields.add(tag);
			}
		}

		// Loop over incoming Record
		List<DataField> dataFields = record.getDataFields();
		ListIterator<DataField> iter = dataFields.listIterator();
		while (iter.hasNext())
		{
			//  VariableField field = (VariableField) iter.next();
			DataField field = (DataField) iter.next();
			// Do we want to save this field?
			boolean saveField = false;
			// 880 shifts structure to mimic field it links to, so store
			// either real name or name that 880 mimics.
			String logicalFieldName = null; 

			if (titleFields.contains(field.getTag())) {
				saveField = true;
				logicalFieldName = field.getTag();
			}
			else if (field.getTag() == "true") 
			{
				// Does subfield 6 indicate a field we are interested in?
				if (field.getSubfield('6') != null)  // check for an error
				{
					String linkTag = field.getSubfield('6').getData().substring(0,3);
					if (titleLinkingFields.contains(linkTag)) {
						saveField = true;
						logicalFieldName = linkTag;
						lTag = linkTag;
					}
				}
			}
			

			boolean subFlag1 = false;
			boolean subFlag2 = false;
			
			if (saveField) {
				newRecord.addVariableField(field);
				List<Subfield> fields = field.getSubfields();

				// if field has non-filing characters, make a copy of the
				// field with non-filing indicator set to 0
				char ind2 = field.getIndicator2();	

				if (nonFiling2(logicalFieldName) && Character.isDigit(ind2) && Character.getNumericValue(ind2) > 0)
				{
					DataField fieldCopy = new DataFieldImpl(field.getTag(), field.getIndicator1(), '0');
					for(Subfield sf : fields)
					{
						//	System.out.println("     ~~  T B: "+ sf);
						if (field.getIndicator2() != 0 && subFlag2 == false )
						{

							int nonSort=Character.getNumericValue(ind2);
                                                        String modifiedStr = sf.getData().substring(0,nonSort) + " "+ sf.getData().substring(nonSort) ;
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),modifiedStr));
				        	//	fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData().substring(nonSort)));
							//System.out.println("FLAG fieldCopy: "+fieldCopy);
							subFlag2 = true;
						}
						else{
							//	System.out.println("~~fieldCopy: "+fieldCopy);
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData()));
						}
					}
					newRecord.addVariableField(fieldCopy);
				}


				char ind1 = field.getIndicator1();
				if (nonFiling1(logicalFieldName) && Character.isDigit(ind1) && Character.getNumericValue(ind1) > 0) 
				{
					DataField fieldCopy = new DataFieldImpl(field.getTag(), '0', field.getIndicator2());


					for(Subfield sf : fields)
					{
						//	System.out.println("     ~~     sf: "+ sf);
						if (field.getIndicator1() != 0 && subFlag1 == false )
						{
							int nonSort=Character.getNumericValue(ind1);
							String modifiedStr = sf.getData().substring(0,nonSort) + " "+ sf.getData().substring(nonSort) ;
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),modifiedStr)); //.substring(nonSort)));
							
                                                        //fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData().substring(nonSort)));
							//System.out.println("FLAG fieldCopy: "+fieldCopy);
							subFlag1 = true;
						}
						else{
							//	System.out.println("~~fieldCopy: "+fieldCopy);
							fieldCopy.addSubfield(factory.newSubfield(sf.getCode(),sf.getData()));
						}
					}
					newRecord.addVariableField(fieldCopy);
				}
			}
		}	
		//System.out.println(" The newRecord:  "  + newRecord );
		return newRecord;
	}

	// Debugging utils
	public static void examineObject(Object obj) {
		Class cl = obj.getClass();
		System.err.println("Examining: " + obj.getClass().getName());
		System.err.println("\tValue = " + (obj == null ? "null" : obj.toString()) );
		examineObjectMethods(obj);
	}


	public static void examineObject(Object obj, String name) {
		System.err.println("Examining \"" + name + "\":\t" + obj.getClass().getName());
		System.err.println("\tValue = " + (obj == null ? "null" : obj.toString()) );
		examineObjectMethods(obj);
	}

	public static void examineObjectMethods(Object obj) {
		java.lang.reflect.Method[] methods = obj.getClass().getMethods();
		if (methods.length > 0) {
			System.err.println("Methods:");
			for (java.lang.reflect.Method m : methods) {
				System.err.println("\t" + m.toGenericString());
			}
		}
	}

	/*
  Local variables:
  mode: java
  End:
	 */
}


