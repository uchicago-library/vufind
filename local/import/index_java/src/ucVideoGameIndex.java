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
                while (iter.hasNext()) 
		{
                        fld753 = (DataField) iter.next();

			String videoGame = fld753.getSubfield('a').getData().replaceAll("\\.", "");
                        String str = videoGame.toLowerCase();

                        if(str != null) 
			{
				if(str.contains("apple mac os 9"))
				{ 
					result.add(str);
					result.add(videoGame);
					result.add("mac");
					result.add("apple mac");	
				}
				if(str.contains("atari 2600"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Atari");
				}
				if(str.contains("atari jaguar"))
				{
					result.add(str);
					result.add(videoGame);
				}
				if(str.contains("colecovision"))
				{
				       	result.add(str);
					result.add(videoGame);
					result.add("Coleco Vision");
					result.add("coleco vision");
				}
				if(str.contains("intellivision"))
				{
					result.add(str);
					result.add(videoGame);
				}
				if(str.contains("magnavox odyssey 2"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Magnavox");
					result.add("magnavox");
					result.add("Magnavox Odyssey");
				}
				if(str.contains("microsoft xbox"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Xbox");
					result.add("xbox");
				}
				if(str.contains("microsoft xbox 360") || str.contains("xbox 360") )
				{
					result.add(str);
					result.add(videoGame);
					result.add("Xbox 360");
					result.add("xbox 360");
				}
				if(str.contains("microsoft xbox one"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Xbox One");
					result.add("Xbox one");
					result.add("xbox one");
					result.add("xbox 1");
				}
				if(str.contains("new nintendo 3ds"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("nintendo 3ds");
					result.add("Nintendo 3ds");
					result.add("Nintendo 3DS");
					result.add("nintendo 3DS");
				}
				if(str.contains("nintendo 3ds"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("nintendo 3ds");
					result.add("Nintendo 3ds");
					result.add("Nintendo 3DS");
					result.add("nintendo 3DS");
				}
				if(str.contains("nintendo 64"))
				{
					result.add(str);
					result.add(videoGame);
				}
				if(str.contains("nintendo ds"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("nintendo DS");
				}
				if(str.contains("nintendo dsi"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("nintendo DSI");
				}
				if(str.contains("nintendo entertainment system"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("nintendo");
					result.add("Nintendo");
					result.add("Nintendo system");
					result.add("NES");
					result.add("nes");
				}
				if(str.contains("nintendo game boy"))
				{	
					result.add(str);
					result.add(videoGame);
					result.add("game boy");
					result.add("Game Boy");
					result.add("Nintendo");
					result.add("gameboy");
				}
				if(str.contains("nintendo game boy advance"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("game boy advance");
					result.add("nintendo advance");
					result.add("game boy");
					result.add("Game Boy");
					result.add("Nintendo");
					result.add("GBA");
					result.add("gba");
				}
				if(str.contains("nintendo game boy color"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("game boy color");
					result.add("Game Boy Color");
					result.add("game boy");
					result.add("Game Boy");
					result.add("Nintendo");
				}
				if(str.contains("nintendo gamecube"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("gamecube");
					result.add("GameCube");
					result.add("Nintendo");
				}
				if(str.contains("nintendo switch"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Nintendo");
				}
				if(str.contains("nintendo switch 2"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("switch 2");
					result.add("Switch 2");
					result.add("Nintendo");
				}
				if(str.contains("nintendo virtual boy"))
				{	
					result.add(str);
					result.add(videoGame);	
					result.add("virtual boy");
					result.add("Virtual Boy");
					result.add("Nintendo");
				}
				if(str.contains("nintendo wii"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("wii");
					result.add("Wii");
					result.add("WII");	
					result.add("Nintendo");
				}
				if(str.contains("nintendo wii u"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("wii u");
					result.add("Wii U");
					result.add("Nintendo");				
				}
				if(str.contains("sega cd"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
				}
				if(str.contains("sega dreamcast"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
				}
				if(str.contains("sega game gear"))
				{	
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
				}
				if(str.contains("sega genesis"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
				}
				if(str.contains("sega mega drive"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
					result.add("Mega Drive");			
				}
				if(str.contains("sega mega drive 32x"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
					result.add("Mega Drive 32X");
					result.add("mega drive 32x");
				}
				if(str.contains("sega mega-cd"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
					result.add("sega mega cd");
					result.add("Sega Mega CD");
				}
				if(str.contains("sega saturn"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("Sega");
				}
				if(str.contains("sega super 32x"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("sega super");
					result.add("sega super 32");
					result.add("Sega");
				}
				if(str.contains("sony playstation") || str.contains("playstation") || str.contains("playstation 1") || str.contains("playstation one"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("playstation");
					result.add("PlayStation");
					result.add("PS1"); result.add("ps one");
				}
				if(str.contains("sony playstation 2") || str.contains("playstation 2"))					
				{
					result.add(str);
					result.add(videoGame);
					result.add("playstation");
					result.add("PlayStation");
					result.add("Playstation 2");
					result.add("PS2");  result.add("ps two");
				}
		                if(str.contains("sony playstation 3") || str.contains("playstation 3"))
                                {
                                        result.add(str);
                                        result.add(videoGame);
                                        result.add("playstation");
                                        result.add("PlayStation");
                                        result.add("Playstation 3");
					result.add("PS3"); result.add("ps three");
                                }
		    	 	if(str.contains("sony playstation 4") || str.contains("playstation 4"))
                                {
                                        result.add(str);
                                        result.add(videoGame);
                                        result.add("playstation");
                                        result.add("PlayStation");
                                        result.add("Playstation 4");
					result.add("PS4"); result.add("ps four");
                                }
				if(str.contains("sony playstation 5") || str.contains("playstation 5"))
                                {
                                        result.add(str);
                                        result.add(videoGame);
                                        result.add("playstation");
                                        result.add("PlayStation");
                                        result.add("Playstation 5");
                                        result.add("PS5"); result.add("ps five");
                                } 		
	
                                if(str.contains("sony playstation portable"))
                                {
                                        result.add(str);
                                        result.add(videoGame);
                                        result.add("playstation");
                                        result.add("PlayStation");
                                }
				if(str.contains("sony playstation vita"))
                                {
                                        result.add(str);
                                        result.add(videoGame);
                                        result.add("playstation");
                                        result.add("PlayStation");
                                        result.add("Playstation Vita");
					result.add("playstation vita");
                                }
				if(str.contains("super nintendo entertainment system"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("super nintendo");
					result.add("nintendo");
					result.add("Nintendo");
					result.add("SNES");
				}
				if(str.contains("turbografx-16"))
				{
					result.add(str);
					result.add(videoGame);
					result.add("turbografx 16");
					result.add("TurboGrafx 16");
					result.add("Turbo Grafx");
					result.add("Turbo Grafx 16");
					result.add("Grafx 16");
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
