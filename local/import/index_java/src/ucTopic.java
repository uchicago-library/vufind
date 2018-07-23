package org.vufind.index;

import java.util.*;
import org.marc4j.marc.Record;
//import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
//import org.marc4j.marc.VariableField;
import org.marc4j.marc.Subfield;
//import org.marc4j.marc.impl.DataFieldImpl;

/**
 * get topics for Topic Browse and insert display dashes
 * @param record
 * @param tagStr colon-separated list of topic fields
 * @return       Collection of topic strings
 */

public class ucTopic {

	public Collection getTopicsWithDashes(Record record, String tagStr) {
		Collection result = new ArrayList();

		// Put desired tags into a handy set...
		Set tags = new HashSet();
		String[] tagArray = tagStr.split(":");
		for (int i = 0; i < tagArray.length; i++) {
			tags.add(tagArray[i]);
		}

		// ...the loop over the data fields 
		List dataFields = record.getDataFields();
		Iterator it = dataFields.iterator();
		while (it.hasNext()) {
			DataField field = (DataField) it.next();
			if (tags.contains(field.getTag())) {
				StringBuilder buf = new StringBuilder();
				List subfields = field.getSubfields();
				Iterator sfIt = subfields.iterator();
				while (sfIt.hasNext()) {
					Subfield subfield = (Subfield) sfIt.next();
					char code = subfield.getCode();
					if (Character.isLetter(code)) {
						// If not the first subfield, add space or dashes
						if (buf.length() > 0) {
							if (code == 'v' || code == 'x'
									|| code == 'y' || code == 'z') {
								buf.append(" -- ");
							} else {
								buf.append(" ");
							}
						}
						buf.append(subfield.getData());
					}
				}
				// Now trim trailing spaces, periods
				/*
	            if (buf.length() > 0) {
	                int last = buf.length()-1;
	                if (buf.charAt(last) == '.') {
	                    buf.deleteCharAt(last);
	                }
	            }
				 */
				int last = buf.length()-1;
				while (last > 0 && (buf.charAt(last) == '.' || buf.charAt(last) == ' ')) {
					//System.err.println("Subject in:  '" + buf.toString() + "'");
					buf.deleteCharAt(last);
					//System.err.println("Subject out: '" + buf.toString() + "'\n");
					last--;
				}
				result.add(buf.toString());
			}
		}

		return result;
	}
}
