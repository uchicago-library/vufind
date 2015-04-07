#!/bin/bash

# list required variables here
REQVARS="VUFIND_HOME VUFIND_LOCAL_DIR VUFIND_HARVEST_DIR VUFIND_BIB_DIR VUFIND_SFX_DIR VUFIND_SFX_DELETES_DIR"
mkdir -p ${VUFIND_SFX_DELETES_DIR}/generateDeletes
mkdir -r ${VUFIND_SFX_DIR}
VUFIND_SFX_GENERATE_DELETES_DIR="${VUFIND_SFX_DELETES_DIR}/generateDeletes"

while getopts 'h:' OPT
do
    case "$OPT" in
	h)  VF_HOST=$OPTARG;;
	-)  break;;
	\?) echo "Invalid option: -$OPTARG" >&2; usage 1;;
    esac
done
shift -- $((OPTIND - 1))

# Download all sxf records from VuFind host(one million sxp records) and put them into sfxRecordsFromSolr.log file.
# sed '/"id":"sfx_/!d' Gets the lines with sfx IDs
# sed 's/[^0-9]*//g' Gets just numbers(which are sfx bib IDs)
curl "http://${VF_HOST}:8080/solr/biblio/select?q=id%3Asfx_*&rows=1000000&wt=json&indent=true&id"  > ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.log 
sed '/"id":"sfx_/!d'  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.log >   ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.text 
sed 's/[^0-9]*//g'   ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.text >   ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.txt 

#Download sfx records from a server
# awk 'f{print;f=0} /<datafield tag="090" ind1="" ind2="">/{f=1}' Gets next line after datafield with tag 090 (which is the line with sfx bib ID)
# sed 's/[^0-9]*//g'   Gets just number line from that line 
curl 'http://sfx.lib.uchicago.edu/lens/lensexport.xml' -o ${VUFIND_SFX_DIR}/sfx.xml
awk 'f{print;f=0} /<datafield tag="090" ind1="" ind2="">/{f=1}' ${VUFIND_SFX_DIR}/sfx.xml >  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfx.text
sed 's/[^0-9]*//g'  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfx.text >  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfx_bib_IDs.txt

#Sort both files with sfx bib IDs
sort -h  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxRecordsFromSolr.txt >  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxSorted_bib_IDs_fromSolr.txt
sort -h  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfx_bib_IDs.txt >  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxSorted_bib_IDs.txt

# Compares two files and finds which sfx bib IDs are present in VuFind but not in new sfx.xml file
grep -Fxv -f  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxSorted_bib_IDs.txt   ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxSorted_bib_IDs_fromSolr.txt >  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxDeletes.txt
# Adds "sfx_" prefix to each line with bib ID in delete file
sed -i -e 's/^/sfx_/'  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxDeletes.txt

today=`date '+%Y_%m_%d__%H_%M_%S'`;
mv  ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfxDeletes.txt ${VUFIND_SFX_DELETES_DIR}/sfxDeletes-$today.txt
# Check sxfDelete file size. If the file is empty, delete this file, import new sxf file and exit the script; otherwise, continue script to the end.
file=${VUFIND_SFX_DELETES_DIR}/sfxDeletes*.txt
if [ ! -s $file ]; then	
        rm ${VUFIND_SFX_GENERATE_DELETES_DIR}/sfx*
	rm ${VUFIND_SFX_DELETES_DIR}/sfxDelete*.txt
        cd ${VUFIND_HOME}
        ./import-marc.sh -p local/import/import_sfx.properties ${VUFIND_SFX_DIR}/sfx.xml
        exit 0	
else
	#Processed sfx delete file and move that file into processed directory
	mkdir -p ${VUFIND_SFX_DELETES_DIR}/processed
	cd ${VUFIND_HOME}/util/
	find ${VUFIND_SFX_DELETES_DIR}/ -name \*sfxDeletes*.txt | sed 's/.*/php deletes.php & flat/' | sh
	mv ${VUFIND_SFX_DELETES_DIR}/*sfxDeletes*.txt ${VUFIND_SFX_DELETES_DIR}/processed

	cd ${VUFIND_HOME}
	./import-marc.sh -p local/import/import_sfx.properties ${VUFIND_SFX_DIR}/sfx.xml
fi
