package org.vufind.index;

import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.DataFieldImpl;
import java.util.*;



public class ucOLEcollection {

	public Set getCollection(Record record)

	{
		Set result = new LinkedHashSet();
		String[] tags = {"927", "928", "929"};

		// check the 927 thru 929 # c to find proper location(building) for each book/record
		List fields = record.getVariableFields(tags);  
		Iterator i = fields.iterator();
		if (fields != null)
		{
			while( i.hasNext())
			{
				DataField formatField = (DataField) i.next();
				Subfield subfield = formatField.getSubfield('c');

				if(subfield != null)
				{      
					String str = formatField.getSubfield('c').getData();

					if(str.equals("PerPhy") || str.equals("ResupC") || str.equals("Sci") || str.equals("SciDDC") || str.equals("SciLg") || 
							str.equals("SciMic") || str.equals("SciRef") || str.equals("SciRes") || str.equals("SciTecP") || str.equals("PerBio") ||
							str.equals("SDDCLg") || str.equals("SFilm") || str.equals("SMedia") || str.equals("SMicDDC") ||  str.equals("Games") || 
							str.equals("SRefPer") || str.equals("MssCr") || str.equals("RareCr") || str.equals("RaCrInc") || str.equals("SciASR"))
					{               
						result.add("Crerar Library");
					}
					if(str.equals("SciRef") || str.equals("SRefPer"))
					{
						result.add("Crerar Reference");
					}
					if(str.equals("SciRes"))
					{
						result.add("Crerar Reserves");        
					}
					if(str.equals("LawDisp") || str.equals("Law") || str.equals("LawA") || str.equals("LawAcq") || 
							str.equals("LawAid") || str.equals("LawAnxN") || str.equals("LawAnxS") || str.equals("LawC") || 
							str.equals("LawCat") || str.equals("LawCity") || str.equals("LawCS") || str.equals("LawFul") || 
							str.equals("LawMic") || str.equals("LawMicG") || str.equals("LawPer") || str.equals("LawRar") ||
							str.equals("LawRef") || str.equals("LawRes") || str.equals("LawResC") || str.equals("LawResP") || 
							str.equals("LawRR") || str.equals("LawStor") || str.equals("ResupD") || str.equals("LawASR") || str.equals("LawSupr"))
					{
						result.add("D'Angelo Law Library");
					}
					if(str.equals("LawRes") || str.equals("LawResC") || str.equals("LawResP"))
					{
						result.add("Law Reserves");
					}
					if(str.equals("EckX") || str.equals("EckRef") || str.equals("EckRes") || str.equals("EMedia") || str.equals("ERes") || str.equals("ResupE"))
					{
						result.add("Eckhart Library");
					}
					if(str.equals("EckRef"))
					{
						result.add("Eckhart Reference");
					}
					if(str.equals("EckRes"))
					{
						result.add("Eckhart Reserves");
					}
					if(str.equals("GameASR") || str.equals("HarpASR") || str.equals("JRLASR") || str.equals("LawASR") || 
							str.equals("LawSupr") || str.equals("RRADiss") || str.equals("SciASR") || str.equals("RecASR"))
					{
						result.add("Mansueto");
					}       
					if(str.equals("Harp") || str.equals("JzAr") || str.equals("MapCl") || str.equals("MapRef") || str.equals("Mic") || 
							str.equals("MidEMic") || str.equals("MSRGen") || str.equals("Rec") || str.equals("RecHP") || str.equals("JRLRES") || 
							str.equals("Resup") || str.equals("RR") || str.equals("RR2Per") || str.equals("RR4") || str.equals("RR4Cla") || 
							str.equals("RR4J") || str.equals("RR5") || str.equals("RR5EA") || str.equals("RR5EPer") || str.equals("RR5Per") || 
							str.equals("RRExp") || str.equals("SAsia") || str.equals("Ser") || str.equals("SerArr") || str.equals("SerCat") || 
							str.equals("Slav") || str.equals("SOA") || str.equals("W") || str.equals("WCJK") || str.equals("BorDirc") || 
							str.equals("Res") || str.equals("Stor") || str.equals("Acq") || str.equals("Art420") || str.equals("ArtResA") || 
							str.equals("Cat") || str.equals("CircPer") || str.equals("CJK") || str.equals("CJKRar") || str.equals("CJKRef") || 
							str.equals("CJKRfHY") || str.equals("CJKSem") || str.equals("CJKSPer") || str.equals("CJKSpHY") || str.equals("CJKSpl") || 
							str.equals("CMC") || str.equals("Film") || str.equals("Gen") || str.equals("GenHY") || str.equals("JRLASR") || str.equals("RecASR"))
					{
						result.add("Regenstein Library");
					}       
					if(str.equals("Rec"))
					{
						result.add("Regenstein Recordings");
					}     
					if(str.equals("ArtResA") || str.equals("CJKRef") || str.equals("CJKRfHY") || str.equals("CJKSPer") || str.equals("MapRef") || 
							str.equals("RR") || str.equals("RR2Per") || str.equals("RR4") || str.equals("RR4Cla") || str.equals("RR4J") || 
							str.equals("RR5") || str.equals("RR5EA") || str.equals("RR5EPer") || str.equals("RR5Per") || str.equals("RRExp") || 
							str.equals("Art420") || str.equals("SOA") || str.equals("Slav"))
					{
						result.add("Regenstein Reference");
					}               
					if(str.equals("JRLRES"))
					{
						result.add("Regenstein Reserves");
					} 
					if(str.equals("ITSadap") || str.equals("ITScaco") || str.equals("ITSipad") || str.equals("ITSlap"))
					{
						result.add("Regenstein TECHB@R");
					}
					if(str.equals("AmDrama") || str.equals("AmNewsp") || str.equals("Arch") || str.equals("ArcMon") || 
							str.equals("ArcRef1") || str.equals("ArcSer") || str.equals("Aust") || str.equals("Drama") || 
							str.equals("EB") || str.equals("French") || str.equals("Incun") || str.equals("JzMon") || str.equals("JzRec") || 
							str.equals("Linc") || str.equals("MoPoRa") || str.equals("Mss") || str.equals("MssBG") || str.equals("MssCdx") || 
							str.equals("MssCr") || str.equals("MssJay") || str.equals("MssLinc") || str.equals("MssMisc") || str.equals("MssSpen") || 
							str.equals("RaCrInc") || str.equals("Rare") || str.equals("RareCr") || str.equals("RBMRef") || str.equals("RefA") || 
							str.equals("Rege") || str.equals("Rosen") || str.equals("SpClAr") || str.equals("HCB") || str.equals("ACASA") || 
							str.equals("ARCHASR") || str.equals("Lincke") || str.equals("MSSASR") || str.equals("RareASR") || str.equals("UCPress"))
					{
						result.add("Special Collections Research Center");
					} 
					if(str.equals("SSAdX") || str.equals("SSAdBdP") || str.equals("SSAdDep") || str.equals("SSAdDpY") || str.equals("SSAdMed") || 
							str.equals("SSAdMic") || str.equals("SSAdPam") || str.equals("SSAdPer") || str.equals("SSAdRef") || str.equals("SSAdRes"))
					{
						result.add("SSA");
					} 
					if(str.equals("SSAdRes"))
					{
						result.add("SSA Reserves");
					}                       
				}               
			}
		} 
		return result;                   
	}
}

