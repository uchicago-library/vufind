package org.vufind.index;

import java.util.*;
import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import java.lang.String;

public class ucVideoGameIndex

{

        public Set getVideoGameIndex(Record record)
        {
                Set result = new LinkedHashSet();
                List df753List  = record.getVariableFields("753");
                Iterator iter = df753List.iterator();


                DataField fld753;
                while (iter.hasNext()) {
                        fld753 = (DataField) iter.next();
                        String vGameStr = fld753.getSubfield('a').getData();
                        
                        String str = vGameStr.toLowerCase();
                        if(str != null) 
			{
				if(str.contains("apple mac os 9") || str.contains("atari 2600") || str.contains("atari jaguar") || str.contains("atari lynx") || str.contains("colecovision") || str.contains("intellivision") || str.contains("magnavox odyssey 2") || str.contains("microsoft xbox") || str.contains("microsoft xbox 360") || str.contains("microsoft xbox one") || str.contains("new nintendo 3ds") || str.contains("nintendo 3ds") || str.contains("nintendo 64") || str.contains("nintendo ds") || str.contains("nintendo dsi") || str.contains("nintendo nintertainment system") || str.contains("nintendo game boy") || str.contains("nintendo game boy advance") || str.contains("nintendo game boy color") || str.contains("nintendo gamecube") || str.contains("nintendo switch") || str.contains("nintendo switch 2") || str.contains("nintendo virtual boy") || str.contains("nintendo wii") || str.contains("nintendo wii u") || str.contains("sega cd") || str.contains("sega dreamcast") || str.contains("sega game gear") || str.contains("sega genesis") || str.contains("sega mega drive") || str.contains("sega mega drive 32x") || str.contains("sony playstation") || str.contains("sony playstation 2") || str.contains("sony playstation 3") || str.contains("sony playstation 4") || str.contains("sony playstation portable") || str.contains("sony playstation vita") || str.contains("super nintendo entertainment system") || str.contains("turbografx-16") || str.contains("xbox") || str.contains("wii")  )

                                {
					result.add(str);
					result.add(vGameStr);
                                } 
                        }
                }
                return result;
        }
	

	public Set getOnlineVideoGameIndex(Record record)	
	{
	        Set result = new LinkedHashSet();
                List df928List  = record.getVariableFields("928");
                Iterator iter = df928List.iterator();


                DataField fld928;
                while (iter.hasNext())
		 {
                        fld928 = (DataField) iter.next();
                        String onlineGame = fld928.getSubfield('g').getData().toLowerCase();


                        if(onlineGame != null)
  		        {
				if( onlineGame.contains("vgameonline") )
				{
					result.add(onlineGame);
				} 
			}
		}
		return result;	
	}

}

