#!/bin/bash

set -e
set -x

cd "`dirname $0`/import"
CLASSPATH="browse-indexing.jar:../solr/lib/*"

TMPDIR=${TMPDIR:-`pwd`}
export TMPDIR

echo "TMPDIR=$TMPDIR"

# Hack for FreeBSD: load the Zentus SQLlite JDBC, as Xerial version
# does not support FreeBSD and Zentus is the ports.
OS="$(uname -s)"
ZENTUS_SQLITE_JDBC="/usr/local/share/java/classes/sqlitejdbc-native.jar"
if [ $OS = FreeBSD -a -f $ZENTUS_SQLITE_JDBC ]
then
    # browse-indexing.jar must be first
    CLASSPATH="browse-indexing.jar:$ZENTUS_SQLITE_JDBC:../solr/lib/*"
fi


# make index work with replicated index
# current index is stored in the last line of index.properties
function locate_index
{
    local targetVar=$1
    local indexDir=$2
    # default value
    local subDir="index"
    if [ -e $indexDir/index.properties ]
    then
        # read it into an array
        readarray farr < $indexDir/index.properties
        # get the last line
        indexline="${farr[${#farr[@]}-1]}"
        # parse the lastline to just get the filename
        subDir=`echo $indexline | sed s/index=//`
    fi

    eval $targetVar="$indexDir/$subDir"
}

locate_index "bib_index" "../solr/biblio"
locate_index "auth_index" "../solr/authority"
index_dir="../solr/alphabetical_browse"

mkdir -p "$index_dir"

function build_browse
{
    browse=$1
    field=$2
    skip_authority=$3

    extra_jvm_opts=$4

    if [ "$skip_authority" = "1" ]; then
        java ${extra_jvm_opts} -Dfile.encoding="UTF-8" -Dfield.preferred=heading -Dfield.insteadof=use_for -cp $CLASSPATH PrintBrowseHeadings "$bib_index" "$field" "$TMPDIR/${browse}.tmp"
    else
        java ${extra_jvm_opts} -Dfile.encoding="UTF-8" -Dfield.preferred=heading -Dfield.insteadof=use_for -cp $CLASSPATH PrintBrowseHeadings "$bib_index" "$field" "$auth_index" "$TMPDIR/${browse}.tmp"
    fi

    sort -u -t$'\1' -k1 "${browse}.tmp" -o "sorted-${browse}.tmp"
    java -Dfile.encoding="UTF-8" -cp $CLASSPATH CreateBrowseSQLite "sorted-${browse}.tmp" "${browse}_browse.db"

    rm -f *.tmp

    mv "${browse}_browse.db" "$index_dir/${browse}_browse.db-updated"
    touch "$index_dir/${browse}_browse.db-ready"
}

#
# Java properties for browse-indexer:
#
# browse.normalizer: class to use for normalizing the index. Default is
#     org.vufind.util.ICUCollatorNormalizer, good for text-based browses
#     (title, author, etc.).
#     Must also set as "normalizer" in solr/biblio/config/solrconfig.xml.
#

build_browse "title" "title_browse" 1 "-Dbibleech=StoredFieldLeech -Dsortfield=title_browse_sort -Dvaluefield=title_browse"
build_browse "journal" "journal_browse" 1 "-Dbibleech=StoredFieldLeech -Dsortfield=journal_browse_sort -Dvaluefield=journal_browse"
build_browse "topic" "topic_browse"
build_browse "author" "author_browse"
build_browse "series" "series_browse" 1
build_browse "lcc" "callnumber-raw" 1 "-Dbrowse.normalizer=org.vufind.util.LCCallNormalizer"
build_browse "dewey" "dewey-raw" 1 "-Dbrowse.normalizer=org.vufind.util.DeweyCallNormalizer"
