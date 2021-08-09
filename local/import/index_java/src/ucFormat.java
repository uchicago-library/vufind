package org.vufind.index;

import java.util.*;

import org.marc4j.marc.Record;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;

//import java.lang.Object

public class ucFormat
{
	private char formatCode3;
	private char formatCode4;
	private char formatCode6;
	private char formatCode12;
	private char formatCode16;
	private char formatCode29;
	private char formatCode23;
	private char formatCode33;
	private char formatCode17;
	private char formatCode28;

	private char CharAt6;
	private char leaderBit6;
	private char leaderBit7;




	/*
	 * Determine Record Format(s)
	 *
	 * @param  Record          record
	 * @return Set     format of record
	 */
	public Set getFormat(Record record)
	{
		Set result = new LinkedHashSet();
		String leader = record.getLeader().toString();
		char leaderBit;
		ControlField fixedField = (ControlField) record.getVariableField("008");
		List<VariableField> items  = record.getVariableFields("929");
		List df024List  = record.getVariableFields("024");
                List df300List  = record.getVariableFields("300");
		List df336List  = record.getVariableFields("336");
		List df337List  = record.getVariableFields("337"); 
		List df338List  = record.getVariableFields("338"); 
		List df344List  = record.getVariableFields("344"); 
		List df347List  = record.getVariableFields("347"); 
		List df502List  = record.getVariableFields("502"); 
		List df538List  = record.getVariableFields("538"); 
		List df856List  = record.getVariableFields("856");
		List df903List  = record.getVariableFields("903");
	        List df928List  = record.getVariableFields("928");
	        List df929List  = record.getVariableFields("929");

		String formatString;
		char formatCode = ' ';
		char formatCode1 = ' ';
		char beginPrefix = '\u0018';
		char endPrefix = '\u0019';

		// marc4j documentation: 
		//   http://marc4j.tigris.org/doc/apidoc/help-doc.html
		//   http://marc4j.tigris.org/doc/apidoc/index.html


		// 929 $b is the prefix, which is separate in OLE. It has been moved from 929 field #0018 #0019 control field to 929$b just in OLE records 
		Iterator iter929 = df929List.iterator();
		{
			DataField fld929;
			while (iter929.hasNext())
			{
				fld929 = (DataField) iter929.next();

				if(fld929.getSubfield('b') != null) 
				{
					String str = fld929.getSubfield('b').getData();

					if (str.equals("DVD"))
					{
						result.add("DVD");
						result.add("Video");
					}
					if (str.equals("VidCass"))
					{
						result.add("tapeVideo");
						result.add("Video");
					}
					if (str.equals("LasDisc"))
					{
						result.add("LaserDisc");
						result.add("Video");
					}
					if (str.equals("AudCD"))
					{
						result.add("CD");  
						result.add("Audio");
					}
					if (str.equals("AudDisc"))
					{
						result.add("Phonograph");
						result.add("Audio");
					}
					if (str.equals("AudCass"))
					{
						result.add("AudioCass");
						result.add("Audio");
					}
					if (str.contains("micro"))
					{
						result.add("Microform");
					}     

				}
			}
		}


                Iterator iter024 = df024List.iterator();
                {
                        DataField fld024;
                        while (iter024.hasNext())
                        {
                                fld024 = (DataField) iter024.next();

                                if(fld024.getSubfield('2') != null)
                                {
                                        if ( fld024.getSubfield('2').getData().contains("doi"))
                                        {
                                        	String str = (fld024.getSubfield('a').getData());
                          			if(str != null)
                                                 result.add(str);
                                        }
                                }
                        }
                }



		Iterator iter300 = df300List.iterator();
		{
			DataField fld300;
			while (iter300.hasNext())
			{
				fld300 = (DataField) iter300.next();

				if(fld300.getSubfield('b') != null) 
				{
					if ( fld300.getSubfield('b').getData().contains("33 1/3"))
					{
						result.add("Phonograph");      // $
						result.add("Audio");
					}
				}
			}
		}


		Iterator iter336 = df336List.iterator();
		{
			DataField fld336;
			while (iter336.hasNext())
			{
				fld336 = (DataField) iter336.next();

				if(fld336.getSubfield('a') != null) 
				{
					String str = fld336.getSubfield('a').getData();

					if ( str.contains("cartographic"))
					{
						result.add("Map");      // ch
					}
					if ( str.contains("notated music"))
					{
						result.add("SheetMusic");  // $   // Music Score
					}
					if ( str.contains("spoken word"))
					{
						result.add("Spoken"); //Spoken word Recording  // $
						result.add("Audio");
					}
					if ( str.contains("two-dimensional moving image"))
					{
						result.add("Video");   // $
					}
				}
			}
		}


		Iterator iter337 = df337List.iterator();
		{
			DataField fld337;
			while (iter337.hasNext())
			{
				fld337 = (DataField) iter337.next();

				if(fld337.getSubfield('a') != null) 
				{
					if ( fld337.getSubfield('a').getData().contains("microform"))
					{
						result.add("Microform");      // $
					}
				}
			}
		}




		Iterator iter338 = df338List.iterator();
		{
			DataField fld338;
			while (iter338.hasNext())
			{
				fld338 = (DataField) iter338.next();

				if(fld338.getSubfield('a') != null)         
				{

					String str = fld338.getSubfield('a').getData();
					if ( str.contains("audiocassette"))
					{
						result.add("AudioCass");    // $
						result.add("Audio");
					}
					if ( str.contains("videocassette")) 
					{
						result.add("tapeVideo");   //$
						result.add("Video");
					}
				}
			}
		}





		Iterator iter344 = df344List.iterator();
		{
			DataField fld344;
			while (iter344.hasNext())
			{
				fld344 = (DataField) iter344.next();
				{
					if(fld344.getSubfield('c') != null) 
					{
						String str = fld344.getSubfield('c').getData();
						if ( str.contains("33 1/3 rpm"))
						{
							result.add("Phonograph");  // $
							result.add("Audio");
						}
						if ( str.contains("1.4 m/s"))
						{
							result.add("CD");   // audioCD   // $ 
							result.add("Audio");
						}
					}
				}
			}
		} 




		Iterator iter347 = df347List.iterator();
		{
			DataField fld347;
			while (iter347.hasNext())
			{
				fld347 = (DataField) iter347.next();
				{
					if(fld347.getSubfield('b') != null) 
					{
						String str = fld347.getSubfield('b').getData();
						if ( str.contains("Blu-ray"))
						{
							result.add("Bluray");            // $
							result.add("Video");
						}
						if ( str.contains("DVD video"))
						{
							result.add("DVD");            // $
							result.add("Video");
						}
						if ( str.contains("CD audio"))
						{
							result.add("CD");   // audioCD   // *             
							result.add("Audio");
						}

					}	 	 
				}
			}
		}



		Iterator iter538 = df538List.iterator();
		{
			DataField fld538;
			while (iter538.hasNext())
			{
				fld538 = (DataField) iter538.next();
				{
					if(fld538.getSubfield('a') != null) 
					{
						String str = fld538.getSubfield('a').getData();

						if ( str.contains("Blu-ray"))
						{
							result.add("Bluray");        //$
							result.add("Video");
						}
						if ( str.contains("DVD"))
						{
							result.add("DVD");            // $
							result.add("Video");
						}
						if ( str.contains("Laserdisc"))
						{
							result.add("LaserDisc");            // $
							result.add("Video");
						}

					}
				}
			}
		}



		Iterator iter502 = df502List.iterator();
		{
			DataField fld502;
			if (iter502.hasNext())
			{
				result.add("Dissertations");
			}
		}


               Iterator iter856 = df856List.iterator();
                {
                        DataField fld856;
                        while (iter856.hasNext())
                        {
				fld856 = (DataField) iter856.next();
				{						
		 			if(fld856.getSubfield('u') != null)
                                       	{
                                               	String str = fld856.getSubfield('u').getData();
			
                       	                        if ( str.contains("/10."))
                               	                {
                                       	                result.add(str);        //$
                                               	}
					}
				}
                        }
                }



		Iterator iter928 = df928List.iterator();
		{
			DataField fld928;
			if (iter928.hasNext())
			{
				result.add("Eresource");
			}
                        while(iter928.hasNext())
                        {
                                fld928 = (DataField) iter928.next(); 
                                if(fld928.getSubfield('g') != null)
		        	{
			        	if ( fld928.getSubfield('g').getData().contains("vidstream"))
                                        {
 						 result.add("StreamingVideo"); 
                                        }  
                                        if ( fld928.getSubfield('g').getData().contains("audstream"))
                                        { 
                                                 result.add("StreamingAudio");
                                        }   
                                }
                        }
		}

		Iterator iter903 = df903List.iterator();
		{
			DataField fld903;
			while (iter903.hasNext())
			{
				fld903 = (DataField) iter903.next();

				if(fld903.getSubfield('a') != null)
				{
				 	if ( fld903.getSubfield('a').getData().contains("Hathi"))
			                {
                                               if(fld903.getSubfield('r') != null)
                                               {
                                                       if ( fld903.getSubfield('r').getData().toUpperCase().contains("ETAS"))
                                                       {
                                                               break;
                                                       }
                                               }
						result.add("Eresource");
					}
				}
			}
		}

		List<DataField> l = new ArrayList<DataField>();
		for (VariableField item : items) l.add((DataField)item); 
		for (DataField item : l) 
		{
			DataField df = (DataField)item;
			if (item != null) 
			{
				// 1. item.getSubfield returns a Subfield object,
				Subfield a = ((DataField) item).getSubfield('a');

				if( a != null)
				{


					// 2. Subfield.getData() returns a String
					String callNo = a.getData();		 

					// 3. Check the String for 0x18. 
					//    Use String methods to search for 0x18. Try "indexOf" method.
					int prefixStartIdx = callNo.indexOf(beginPrefix);
					int prefixEndIdx = callNo.indexOf(endPrefix);

					String startStr = "<U+0018>";
					String endStr = "<U+0019>";

					if (prefixStartIdx == -1)
					{
						if (callNo.startsWith(startStr))
						{
							prefixStartIdx = startStr.length() -1;
							prefixEndIdx = callNo.indexOf(endStr);
						}
					}

					// 4. If there's a 0x18, then get the substring between 0x18 and 0x19
					//    Check String methods, subsequence? substring?
					if (prefixStartIdx >= 0  || callNo.startsWith(startStr))

					{
						String prefixes = callNo.substring(prefixStartIdx+1, prefixEndIdx);

						if (prefixes.equals("DVD"))
						{
							result.add("DVD");
							result.add("Video");
						}
						if (prefixes.equals("VidCass"))
						{
							result.add("tapeVideo");
							result.add("Video");
						}
						if (prefixes.equals("LasDisc"))
						{
							result.add("LaserDisc");
							result.add("Video");
						}
						if (prefixes.equals("AudCD"))
						{
							result.add("CD");  
							result.add("Audio");
						}
						if (prefixes.equals("AudDisc"))
						{
							result.add("Phonograph");
							result.add("Audio");
						}
						if (prefixes.equals("AudCass"))
						{
							result.add("AudioCass");
							result.add("Audio");
						}
						if (prefixes.contains("micro"))
						{
							result.add("Microform");
						}              	    		

					}	
				}
				// Collection codes for mircoforms
				Subfield c = ((DataField) item).getSubfield('c');
				if(c != null)
				{
					String subC = c.getData();

					if(subC.equals("LawMic") || subC.equals("Mic") || subC.equals("MidEMic") || subC.equals("SciMic")
							|| subC.equals("SMicDDC") || subC.equals("SMicDoc") || subC.equals("WSciMic"))
					{
						result.add("Microform");   	         	 
					}   
				}     

			}
		}



		// check the 006 - this is a repeating field

		List fields006 = record.getVariableFields("006");
		Iterator fieldsIter006 = fields006.iterator();
		if (fields006 != null) 
		{
			ControlField formatField;
			while(fieldsIter006.hasNext()) 
			{
				formatField = (ControlField) fieldsIter006.next();
				formatString = formatField.getData().toUpperCase();
				formatCode  = formatString.length() > 0 ? formatString.charAt(0) : ' ';    //@ 0
				formatCode3 = formatString.length() > 3 ? formatString.charAt(3) : ' ';    //@ 3
				formatCode4 = formatString.length() > 4 ? formatString.charAt(4) : ' ';    //@ 4
				formatCode6 = formatString.length() > 6 ? formatString.charAt(6) : ' ';    //@ 6
				formatCode12 = formatString.length() > 12 ? formatString.charAt(12) : ' ';    //@ 12
				formatCode16 = formatString.length() > 16 ? formatString.charAt(16) : ' ';    //@ 16

				switch (formatCode) 
				{ 
				case 'A':
				case 'C':
				case 'D':	
				case 'P':
				case 'T':
					switch(formatCode6)
					{
					case 'A':
					case 'B':
					case 'C':
						result.add("Microform");  // ch
						break;
					}
					break;
				}

				switch(formatCode)
				{
				case 'C':
				case 'D':
					result.add("SheetMusic");  // $
					break;

				case 'J':
					result.add("Music");    //Music recording  // ch                                 
					result.add("Audio");   
					break;

				case 'I':	    
					result.add("Spoken"); //Spoken word Recording  // $
					result.add("Audio");
					break;
				}

				switch(formatCode){
				case 'E':
				case 'F':
					result.add("Map");  // ch
					break;
				}

				switch(formatCode){
				case 'E':
				case 'F':
				case 'G':
				case 'K':
				{
					switch(formatCode12){
					case 'A':
					case 'B':
					case 'C':
						result.add("Microform");  // $
					}
					break;
				}
				}

				switch(formatCode){
				case 'G':
					switch(formatCode16)
					{
					case 'F':
					case 'S':
					case 'T':
						result.add("Image");  // $
						break;
					}
					break;
				case 'O':
					result.add("Kit");   //ch
					break;

				}
			}
		}


		// check the 007 - this is a repeating field
		List fields007 = record.getVariableFields("007");
		Iterator fieldsIter007 = fields007.iterator();
		if (fields007 != null) 
		{
			ControlField formatField;
			while(fieldsIter007.hasNext()) 
			{
				formatField = (ControlField) fieldsIter007.next();
				formatString = formatField.getData().toUpperCase();
				formatCode  = formatString.length() > 0 ? formatString.charAt(0) : ' ';    //@ 0
				formatCode1 = formatString.length() > 1 ? formatString.charAt(1) : ' ';    //@ 1
				formatCode3 = formatString.length() > 3 ? formatString.charAt(3) : ' ';    //@ 3
				formatCode4 = formatString.length() > 4 ? formatString.charAt(4) : ' ';    //@ 4

				switch (formatCode) 
				{
				case 'A':
					switch(formatCode1) 
					{
					case 'D':
						result.add("Book");  // ch
						break;
					}
					break;

				case 'H':
					result.add("Microform"); //$
					break;
				}
				switch (formatCode) 
				{
				case 'A':
				case 'D':
					result.add("Map");  // ch
					break;

				case 'G':
				case 'K': 
					result.add("Image");  // ch

				case 'S':      
					switch(formatCode1) 
					{
					case 'S': 	
						result.add("AudioCass");  // ch
						break;
					}
					switch(formatCode3) 
					{
					case 'B':
						result.add("Phonograph");  // LP   //$
						break;

					case 'F':    
						result.add("CD");   // audioCD   // $
						break;
					}
					break;  
				case 'V':
					switch(formatCode1) {         
					case 'F':
						result.add("tapeVideo"); //videoCassette  // $
						result.add("Video");
						break;
					}
					//007/01=f, g, h, v
					switch(formatCode1) { 
					case 'F':
					case 'G':
					case 'H':
					case 'V':
						CharAt6 = Character.toUpperCase(leader.charAt(6));						
						if(CharAt6 == 'K')
							result.add("Photo"); //Photo  // ch
						break;
					}

					switch(formatCode4) {
					case 'G':
						result.add("LaserDisc");  // ch
						result.add("Video");
						break;

					case 'V':
						result.add("DVD");   // DVD  // ch
						result.add("Video");
						break;

					case 'S':
						result.add("Bluray");  //ch
						result.add("Video");
						break;
					}

				case 'M':
					result.add("Video");  // $ 
					break;

				case 'O':
					result.add("Kit");   //ch
					break;
				}
			}
		}



		// check the 008 - this is a repeating field
		List fields008 = record.getVariableFields("008");
		Iterator fieldsIter008 = fields008.iterator();
		if (fields008 != null) {

			ControlField formatField;
			while(fieldsIter008.hasNext()) {
				formatField = (ControlField) fieldsIter008.next();
				formatString = formatField.getData().toUpperCase();
				// formatCode  = formatString.length() > 0 ? formatString.charAt(0) : ' ';     //@ 0
				// formatCode1 = formatString.length() > 1 ? formatString.charAt(1) : ' ';    //@ 1
				formatCode17 = formatString.length() > 17 ? formatString.charAt(17) : ' ';    //@ 17
				formatCode23 = formatString.length() > 23 ? formatString.charAt(23) : ' ';    //@ 23
				formatCode28 = formatString.length() > 28 ? formatString.charAt(28) : ' ';    //@ 28
				formatCode29 = formatString.length() > 29 ? formatString.charAt(29) : ' ';    //@ 29
				formatCode33 = formatString.length() > 33 ? formatString.charAt(33) : ' ';    //@ 33

				if(formatCode17 == 'U' && formatCode28 == 'F'){    
					result.add("USfedGovDoc");  
				}

				switch(formatCode29){
				case 'D': 
				case 'R':
				case ' ':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'E' || CharAt6 == 'F' || CharAt6 == 'K')
						result.add("Print");  // ch
					break;

				case 'A':
				case 'B':
				case 'C':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'E' || CharAt6 == 'F' || CharAt6 == 'G' || CharAt6 == 'K')
						result.add("Microform");  // ch
					break; 

				}

				switch(formatCode23){
				case ' ':
				case 'D': 
				case 'F':
				case 'R':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'A' || CharAt6 == 'C' || CharAt6 == 'D' || CharAt6 == 'P' || CharAt6 == 'T')
						result.add("Print");  // ch
					break;

				case 'A':
				case 'B':
				case 'C':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'A' || CharAt6 == 'C' || CharAt6 == 'D' ||
							CharAt6 == 'P' || CharAt6 == 'T')
						result.add("Microform");  // ch
					break; 
				}

				switch(formatCode33){
				case 'F':
				case 'S':
				case 'T':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'G')
						result.add("Image"); //ch
					break;

				case 'M':
				case 'V':
					CharAt6 = Character.toUpperCase(leader.charAt(6));
					if(CharAt6 == 'G')
						result.add("Video"); //$
					break;
				}			
				break;  
			}
		}

		// check the Leader at position 6
		leaderBit6 = leader.charAt(6);
		switch (Character.toUpperCase(leaderBit6)) {
		case 'A':
			formatCode = Character.toUpperCase(leader.charAt(7));
			if (formatCode == 'A' || formatCode == 'C' || formatCode == 'D' || formatCode == 'M')
				result.add("Book");             //ch                 
			if (formatCode == 'B'  || formatCode == 'I' || formatCode == 'S')
				result.add("Journals");    // ch  //Serial
			break;
		case 'C':
		case 'D':
			result.add("SheetMusic");  // ch   // Music Score
			break;
		case 'E':
		case 'F':
			result.add("Map");  // ch
			break;
		case 'I':
			result.add("Spoken"); //Spoken word Recording  // ch
			result.add("Audio");
			break;
		case 'J':
			result.add("Music");    //Music recording  // ch
			result.add("Audio");
			break;
		case 'O':
			result.add("Kit");  // ch
			break;
		case 'P':
			result.add("ArchivalCollections");   //Manuscripts   // $ 
			break;


		}

		// Nothing worked!
		if (result.isEmpty()) {
			result.add("Unknown");
		}

		return result;
	}


	// For hathiTrust Files
	public Set getHathiFormat (Record record)
	{

		Set result = getFormat(record);
		// Many HathiTrust records do not have the data we use for format

		result.remove("Print");
		result.remove("Microform");
		result.remove("Unknown");	
		result.add("Eresource");
		return result;
	}


	// For SFX Files
	public Set getSFXformat (Record record)
	{
		Set result = getFormat(record);
		result.add("Eresource");
		result.add("Journals");
		return result;
	}

}
