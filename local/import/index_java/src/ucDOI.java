package org.vufind.index;

import java.util.*;
import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import java.lang.String;

public class ucDOI
{

        public Set getDOI(Record record)
        {
                Set result = new LinkedHashSet();
                List df856List  = record.getVariableFields("856");
                Iterator iter = df856List.iterator();


                DataField fld856;
                while (iter.hasNext()) {
                        fld856 = (DataField) iter.next();
                        String str = fld856.getSubfield('u').getData();

                        if(str != null) {
                                if(str.contains("/10."))
				{
		                result.add((str.substring(str.indexOf("/10."))).substring(1));
    				} 
                        }
                }
                return result;
        }
}

