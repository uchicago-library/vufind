

// ./import/index_java/src/org/vufind/index/ucSFX.java

package org.vufind.index;

import java.util.*;
import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;

public class ucSFX
{

	public Set getSFX(Record record)
	{
		Set result = new LinkedHashSet();
		List df090List  = record.getVariableFields("090");
		Iterator iter = df090List.iterator();


		DataField fld090;
		while (iter.hasNext()) {
			fld090 = (DataField) iter.next();
			String str = fld090.getSubfield('a').getData();

			if(str != null) {
				String sfx = "sfx_" + str;
				result.add(sfx);
			}
		}
		return result;
	}
}


