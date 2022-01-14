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
		String[] tags = {"927", "928"};

		// check the 927 thru 928 # c to find proper location(building) for each book/record
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

					if(str.equals("JCL-PerPhy") || str.equals("JCL-ResupC") || str.equals("JCL-Sci") || str.equals("JCL-SciDDC") || str.equals("JCL-SciLg") || 
							str.equals("JCL-SciMic") || str.equals("JCL-SciRef") || str.equals("JCL-SciRes") || str.equals("JCL-SciTecP") || str.equals("JCL-PerBio") ||
							str.equals("JCL-SDDCLg") || str.equals("JCL-SFilm") || str.equals("JCL-SMedia") || str.equals("JCL-SMicDDC") ||  str.equals("JCL-Games") || 
							str.equals("JCL-SRefPer") || str.equals("JCL-MssCr") || str.equals("JCL-RareCr") || str.equals("JCL-RaCrInc") || str.equals("JCL-SciASR"))
					{               
						result.add("Crerar Library");
					}
					if(str.equals("JCL-SciRef") || str.equals("JCL-SRefPer"))
					{
						result.add("Crerar Reference");
					}
					if(str.equals("JCL-SciRes"))
					{
						result.add("Crerar Reserves");        
					}
					if(str.equals("DLL-LawDisp") || str.equals("DLL-Law") || str.equals("DLL-LawA") || str.equals("DLL-LawAcq") || 
							str.equals("DLL-LawAid") || str.equals("DLL-LawAnxN") || str.equals("DLL-LawAnxS") || str.equals("DLL-LawC") || 
							str.equals("DLL-LawCat") || str.equals("DLL-LawCity") || str.equals("DLL-LawCS") || str.equals("DLL-LawFul") || 
							str.equals("DLL-LawMic") || str.equals("DLL-LawMicG") || str.equals("DLL-LawPer") || str.equals("DLL-LawRar") ||
							str.equals("DLL-LawRef") || str.equals("DLL-LawRes") || str.equals("DLL-LawResC") || str.equals("DLL-LawResP") || 
							str.equals("DLL-LawRR") || str.equals("DLL-LawStor") || str.equals("DLL-ResupD") || str.equals("DLL-LawASR") || str.equals("DLL-LawSupr"))
					{
						result.add("D'Angelo Law Library");
					}
					if(str.equals("DLL-LawRes") || str.equals("DLL-LawResC") || str.equals("DLL-LawResP"))
					{
						result.add("Law Reserves");
					}
					if(str.equals("Eck-EckX") || str.equals("Eck-EckRef") || str.equals("Eck-EckRes") || str.equals("Eck-EMedia") || str.equals("Eck-ERes") || str.equals("Eck-ResupE"))
					{
						result.add("Eckhart Library");
					}
					if(str.equals("Eck-EckRef"))
					{
						result.add("Eckhart Reference");
					}
					if(str.equals("Eck-EckRes"))
					{
						result.add("Eckhart Reserves");
					}
					if(str.equals("ASR-JRLASR") || str.equals("ASR-LawASR") || 
							str.equals("ASR-LawSupr") || str.equals("ASR-SciASR"))
					{
						result.add("Mansueto");
					}       
					if(str.equals("JRL-Harp") || str.equals("JRL-JzAr") || str.equals("JRL-MapCl") || str.equals("JRL-MapRef") || str.equals("JRL-Mic") || 
							str.equals("JRL-MidEMic") || str.equals("JRL-MSRGen") || str.equals("JRL-Rec") || str.equals("JRL-RecHP") || str.equals("JRL-JRLRES") || 
							str.equals("JRL-Resup") || str.equals("JRL-RR") || str.equals("JRL-RR2Per") || str.equals("JRL-RR4") || str.equals("JRL-RR4Cla") || 
							str.equals("JRL-RR4J") || str.equals("JRL-RR5") || str.equals("JRL-RR5EA") || str.equals("JRL-RR5EPer") || str.equals("JRL-RR5Per") || 
							str.equals("JRL-RRExp") || str.equals("JRL-SAsia") || str.equals("JRL-Ser") || str.equals("JRL-SerArr") || str.equals("JRL-SerCat") || 
							str.equals("JRL-Slav") || str.equals("JRL-SOA") || str.equals("JRL-W") || str.equals("JRL-WCJK") || str.equals("JRL-BorDirc") || 
							str.equals("JRL-Res") || str.equals("JRL-Stor") || str.equals("JRL-Acq") || str.equals("JRL-Art420") || str.equals("JRL-ArtResA") || 
							str.equals("JRL-Cat") || str.equals("JRL-CircPer") || str.equals("JRL-CJK") || str.equals("JRL-CJKRar") || str.equals("JRL-CJKRef") || 
							str.equals("JRL-CJKRfHY") || str.equals("JRL-CJKSem") || str.equals("JRL-CJKSPer") || str.equals("JRL-CJKSpHY") || str.equals("JRL-CJKSpl") || 
							str.equals("JRL-CMC") || str.equals("JRL-Film") || str.equals("JRL-Gen") || str.equals("JRL-GenHY") || str.equals("JRL-JRLASR") || str.equals("JRL-RecASR"))
					{
						result.add("Regenstein Library");
					}       
					if(str.equals("JRL-Rec"))
					{
						result.add("Regenstein Recordings");
					}     
					if(str.equals("JRL-ArtResA") || str.equals("JRL-CJKRef") || str.equals("JRL-CJKRfHY") || str.equals("JRL-CJKSPer") || str.equals("JRL-MapRef") || 
							str.equals("JRL-RR") || str.equals("JRL-RR2Per") || str.equals("JRL-RR4") || str.equals("JRL-RR4Cla") || str.equals("JRL-RR4J") || 
							str.equals("JRL-RR5") || str.equals("JRL-RR5EA") || str.equals("JRL-RR5EPer") || str.equals("JRL-RR5Per") || str.equals("JRL-RRExp") || 
							str.equals("JRL-Art420") || str.equals("JRL-SOA") || str.equals("JRL-Slav"))
					{
						result.add("Regenstein Reference");
					}               
					if(str.equals("JRL-JRLRES"))
					{
						result.add("Regenstein Reserves");
					} 
					if(str.equals("ITS-TECHBAR"))
					{
						result.add("Regenstein TECHB@R");
					}
					if(str.equals("SPCL-AmDrama") || str.equals("SPCL-AmNewsp") || str.equals("SPCL-Arch") || str.equals("SPCL-ArcMon") || 
							str.equals("SPCL-ArcRef1") || str.equals("SPCL-ArcSer") || str.equals("SPCL-Aust") || str.equals("SPCL-Drama") || 
							str.equals("SPCL-EB") || str.equals("SPCL-French") || str.equals("SPCL-Incun") || str.equals("JSPCL-zMon") || str.equals("SPCL-JzRec") || 
							str.equals("SPCL-Linc") || str.equals("SPCL-MoPoRa") || str.equals("SPCL-Mss") || str.equals("SPCL-MssBG") || str.equals("SPCL-MssCdx") || 
							str.equals("SPCL-MssCr") || str.equals("SPCL-MssJay") || str.equals("SPCL-MssLinc") || str.equals("SPCL-MssMisc") || str.equals("SPCL-MssSpen") || 
							str.equals("SPCL-RaCrInc") || str.equals("SPCL-Rare") || str.equals("SPCL-RareCr") || str.equals("SPCL-RBMRef") || str.equals("SPCL-RefA") || 
							str.equals("SPCL-Rege") || str.equals("SPCL-Rosen") || str.equals("SPCL-SpClAr") || str.equals("SPCL-HCB") || str.equals("SPCL-ACASA") || 
							str.equals("SPCL-ARCHASR") || str.equals("SPCL-Lincke") || str.equals("SPCL-MSSASR") || str.equals("SPCL-RareASR") || str.equals("SPCL-UCPress"))
					{
						result.add("Special Collections Research Center");
					} 
					if(str.equals("SWL-SWL") || str.equals("SWL-SWLBdP") || str.equals("SWL-SWLDep") || str.equals("SWL-SWLDpY") || str.equals("SWL-SWLMed") || 
							str.equals("SWL-SWLMic") || str.equals("SWL-SWLPam") || str.equals("SWL-SWLPer") || str.equals("SWL-SWLRef") || str.equals("SWL-SWLRes"))
					{
						result.add("Social Work Library");
					} 
					if(str.equals("SWL-SWLRes"))
					{
						result.add("Social Work Reserves");
					}                       
				}               
			}
		} 
		return result;                   
	}
}
