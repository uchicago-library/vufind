package org.vufind.index;

import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.DataFieldImpl;
import java.util.*;



public class ucCollection {

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
                                                                                            
					if(str.equals("JCL-Games") || str.equals("JCL-PerBio") || str.equals("JCL-PerPhy") || str.equals("JCL-ResupC") || str.equals("JCL-Sci") || str.equals("JCL-SciDDC") || str.equals("JCL-SciHY") || str.equals("JCL-SciLg") || str.equals("JCL-SciRef") || str.equals("JCL-SciRes") ||str.equals("JCL-SciRR") || str.equals("JCL-SciTecP") || str.equals("JCL-SDDCLg") || str.equals("JCL-SFilm") ||  str.equals("JCL-SMedia") || str.equals("JCL-SRefPer") || str.equals("ASR-SciASR") || str.equals("SPCL-MssCr") || str.equals("SPCLASR-RARECRASR") || str.equals("SPCL-RaCrinc"))
					{               
						result.add("Crerar Library");
					}
					if(str.equals("SPCL-MssCr"))
					{
						result.add("Crerar Manuscripts");
					}
					if(str.equals("SPCLASR-RARECRASR") || str.equals("SPCL-RareCr"))
					{
						result.add("Crerar Rare Books");
					}
					if(str.equals("JCL-SciRef") || str.equals("JCL-SRefPer"))
					{
						result.add("Crerar Reference");
					}
					if(str.equals("JCL-SciRes"))
					{
						result.add("Crerar Reserves");
					}
					if(str.equals("JCL-Games"))
					{
						result.add("Crerar Video Games");
					}
					if(str.equals("DLL-Law") || str.equals("DLL-LawA")  || str.equals("DLL-LawAcq") || str.equals("DLL-LawAid") || str.equals("DLL-LawAnxN") || str.equals("DLL-LawAnxS") || str.equals("DLL-LawC") || str.equals("DLL-LawCat") || str.equals("DLL-LawCity") || str.equals("DLL-LawCS") || str.equals("DLL-LawDisp") || str.equals("DLL-LawDVD") || str.equals("DLL-LawFul") || str.equals("DLL-LawMic") || str.equals("DLL-LawPer") || str.equals("DLL-LawRar") || str.equals("DLL-LawRef") || str.equals("DLL-LawRes") || str.equals("DLL-LawResC") || str.equals("DLL-LawResP") || str.equals("DLL-LawRR") || str.equals("DLL-LawStor") || str.equals("DLL-LawWell") || str.equals("DLL-ResupD") || str.equals("ASR-LawASR") || str.equals("ASR-LawSupr"))
					{
						result.add("D'Angelo Law Library");
					}
					if(str.equals("DLL-LawRes") || str.equals("DLL-LawResC") || str.equals("DLL-LawResP"))
					{
						result.add("D'Angelo Law Reserves");
					}
					if(str.equals("Eck-Eck") || str.equals("Eck-EckRef") || str.equals("Eck-EckRes") || str.equals("Eck-EMedia") || str.equals("Eck-ResupE"))
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
					if(str.equals("ASR-JRLASR") || str.equals("ASR-LawASR") || str.equals("ASR-LawSupr") || str.equals("ASR-RecASR") || str.equals("ASR-SciASR"))
					{
						result.add("Mansueto");
					}
if(str.equals("JRL-Acq") || str.equals("JRL-READ") || str.equals("JRL-Art420") || str.equals("JRL-ArtResA") || str.equals("JRL-Cat") || str.equals("JRL-CDEV") || str.equals("JRL-CircPer") || str.equals("JRL-CJK") || str.equals("JRL-CJKRar") || str.equals("JRL-CJKRef") || str.equals("JRL-CJKRfHY") || str.equals("JRL-CJKSem") || str.equals("JRL-CJKSPer") || str.equals("JRL-CJKSpHY") || str.equals("JRL-CJKSpl") || str.equals("JRL-CMC") || str.equals("JRL-ERICMic") || str.equals("JRL-Film") || str.equals("JRL-Gen") || str.equals("JRL-GenHY") || str.equals("JRL-Harp") || str.equals("JRL-ILL") || str.equals("JRL-JRLDisp") || str.equals("JRL-JRLRES") || str.equals("JRL-JzAr") || str.equals("JRL-LawMicG") || str.equals("JRL-MapCl") || str.equals("JRL-MapRef") || str.equals("JRL-Mic") || str.equals("JRL-MidEMic") || str.equals("JRL-MSRGen") || str.equals("JRL-Pam") || str.equals("JRL-Rec") || str.equals("JRL-RecHP") || str.equals("JRL-Res") || str.equals("JRL-Resup") || str.equals("JRL-RR") || str.equals("JRL-RR2Per") || str.equals("JRL-RR4") || str.equals("JRL-RR4Cla") || str.equals("JRL-RR4J") || str.equals("JRL-RR5") || str.equals("JRL-RR5EA") || str.equals("JRL-RR5EPer") || str.equals("JRL-RR5Per") || str.equals("JRL-RRExp ") || str.equals("JRL-SAsia") || str.equals("JRL-SciMic") || str.equals("JRL-Ser") || str.equals("JRL-SerArr") || str.equals("JRL-SerCat")  || str.equals("JRL-Slav") || str.equals("JRL-SMicDDC") || str.equals("JRL-SOA") || str.equals("JRL-Stor") || str.equals("JRL-W")  || str.equals("JRL-WCJK") || str.equals("JRL-XClosedCJK") || str.equals("JRL-XClosedGen") || str.equals("ASR-JRLASR")  || str.equals("ASR-RecASR"))
					{
						result.add("Regenstein Library");
					}
					if(str.equals("JRL-CJKRar") || str.equals("JRL-CJKSpHY") || str.equals("JRL-CJKSpl"))
					{
						result.add("East Asian Treasure Room");
					}
					if(str.equals("JRL-ArtResA"))
					{
						result.add("East Asian Art Reading Room");
					}
					if(str.equals("JRL-ArtResA") || str.equals("JRL-CJKRef") || str.equals("JRL-CJKRfHY") || str.equals("JRL-CJKSPer") || str.equals("JRL-MapRef") || str.equals("JRL-RR") || str.equals("JRL-RR2Per") || str.equals("JRL-RR4") || str.equals("JRL-RR4Cla") || str.equals("JRL-RR4J") || str.equals("JRL-RR5") || str.equals("JRL-RR5EA") || str.equals("JRL-RR5EPer") || str.equals("JRL-RR5Per") || str.equals("JRL-RRExp") || str.equals("JRL-Art420") || str.equals("JRL-SOA") || str.equals("JRL-Slav"))
					{
						result.add("Regenstein Reference");
					}
					if(str.equals("JRL-JRLRES")) 
					{
						result.add("Regenstein Reserves");
					}
					if(str.equals("ITS-POLSKY") || str.equals("ITS-TECHBAR"))
					{
						result.add("Regenstein TECHB@R");
					}
					if(str.equals("JRL-CMC"))
					{
						result.add("Curriculum Materials Collection");
					}
					if(str.equals("SWL-SWL") || str.equals("SWL-SWLBdP") || str.equals("SWL-SWLDep") || str.equals("SWL-SWLDiss") || str.equals("SWL-SWLDpY") || str.equals("SWL-SWLMEd") || str.equals("SWL-SWLMic") || str.equals("SWL-SWLPam") || str.equals("SWL-SWLPer") || str.equals("SWL-SWLRef") || str.equals("SWL-SWLRes"))
					{
						result.add("Social Work Library");
					}
					if(str.equals("SWL-SWLRes"))
					{
						result.add("Social Work Reserves");
					}
					if(str.equals("SPCL-AmDrama") || str.equals("SPCL-AmNewsp") || str.equals("SPCL-Arch") || str.equals("SPCL-ArcMon") || str.equals("SPCL-ArcRef1") || str.equals("SPCL-ArcSer") || str.equals("SPCL-Aust") || str.equals("SPCL-Drama") || str.equals("SPCL-EB") || str.equals("SPCL-French") || str.equals("SPCL-HCB") || str.equals("SPCL-Incun") || str.equals("SPCL-JzMon") || str.equals("SPCL-JzRec") || str.equals("SPCL-Linc") || str.equals("SPCL-MoPoRa") || str.equals("SPCL-Mss") || str.equals("SPCL-MssBG") || str.equals("SPCL-MssCdx") || str.equals("SPCL-MssCr") || str.equals("SPCL-MssJay") || str.equals("SPCL-MssLini") || str.equals("SPCL-MssMis") || str.equals("SPCL-MssSpen") || str.equals("SPCL-RaCrinci") || str.equals("SPCL-Rare") || str.equals("SPCL-RareCr") || str.equals("SPCL-RareMaps") || str.equals("SPCL-RBMRef") || str.equals("SPCL-RefA") || str.equals("SPCL-Rege") || str.equals("SPCL-Rosen") || str.equals("SPCL-SpClAr") || str.equals("SPCLASR-ACASA") || str.equals("SPCLASR-ARCHASR") || str.equals("SPCLASR-Lincke") || str.equals("PCLASR-MSSASR") || str.equals("SPCLASR-RareASR") || str.equals("SPCLASR-UCPress"))
					{
						result.add("Special Collections Research Center");
					}
					if(str.equals("SPCL-AmDrama"))
					{
						result.add("SCRC American Drama");
					}
					if(str.equals("SPCL-AmNewsp"))
					{
						result.add("SCRC American Newspapers");
					}
					if(str.equals("SPCL-Arch") || str.equals("SPCL-ArcMon") || str.equals("SPCL-ArcRef1") || str.equals("SPCL-ArcSer") || str.equals("SPCL-Mss"))
					{
						result.add("SCRC Archives/Manuscripts");
					}
					if(str.equals("SPCLASR-ACASA"))
					{
						result.add("SCRC ACASA");
					}
					if(str.equals("SPCLASR-Atk"))
					{
						result.add("SCRC Atkinson Collection of American Drama");
					}
					if(str.equals("SPCL-Aust"))
					{
						result.add("SCRC Austrian Collection of Drama");
					}
					if(str.equals("SPCL-MssCdx"))
					{
						result.add("SCRC Codex Manuscripts");
					}
					if(str.equals("SPCL-Drama"))
					{
						result.add("SCRC Drama Collection");
					}
					if(str.equals("SPCL-EB"))
					{
						result.add("SCRC EB Children's Literature");
					}
					if(str.equals("SPCL-Rege"))
					{
						result.add("SCRC Helen and Ruth Regenstein Collection of Rare Books");
					}
					if(str.equals("SPCL-HCB"))
					{
						result.add("SCRC Historical Children's Books");
					}
					if(str.equals("SPCL-Incun") || str.equals("SPCL-RaCrinc"))
					{
						result.add("SCRC Incunabula");
					}
					if(str.equals("SPCLASR-Lincke"))
					{
						result.add("SCRC Lincke Collection of Popular German Literature");
					}
					if(str.equals("SPCL-Linc"))
					{
						result.add("SCRC Lincoln Collection");
					}
					if(str.equals("SPCL-Rosen"))
					{
						result.add("SCRC Ludwig Rosenberger Library of Judaica");
					}
					if(str.equals("SPCL-MoPoRa"))
					{
						result.add("SCRC Modern Poetry Rare");
					}
					if(str.equals("SPCL-Rare"))
					{
						result.add("SCRC Rare Books");
					}
					if(str.equals("SPCLASR-UCPress"))
					{
						result.add("SCRC University of Chicago Press Imprint Collection");
					}
					if(str.equals("UC-FullText"))
					{
						result.add("Internet");
					}
                                        if(str.equals("JRL-READ"))
                                        {
                                                result.add("Reg Reads Collection");
                                        }
				}               
			}
		}
		return result;                   
	}
}
