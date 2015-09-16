#!/bin/bash

ME=`basename $0 .sh`
AUTHOR="Tod Olson"
# Much is stolen shamelessly from scripts by Keith Waclena
#WWW=http://www.lib.uchicago.edu/tod/
# NYI
#VERSION=VERSION		# replaced with mercurial version number by make

# START CONFIGURE
TMP_DIR=${TMP_DIR:-/tmp}
# END CONFIGURE

# Sample variables for .bashrc:
#VUFIND_HOME=/usr/local/vufind
#VUFIND_LOCAL_DIR=${VUFIND_HOME}/local
#VUFIND_HARVEST_DIR=${VUFIND_LOCAL_DIR}/harvest
#VUFIND_BIB_DIR=${VUFIND_HARVEST_DIR}/bib
#VUFIND_AUTH_DIR=${VUFIND_HARVEST_DIR}/auth
#VUFIND_SFX_DIR=${VUFIND_HARVEST_DIR}/sfx

# list required variables here
REQVARS="VUFIND_HOME VUFIND_LOCAL_DIR VUFIND_HARVEST_DIR VUFIND_BIB_DIR VUFIND_SFX_DIR"

USAGE="Usage: $ME [-t] [-s slave1[,slave2[,...]]] command [command ...]; -H for help"
HELP="where command may be:
bib:\timport bib records found in VUFIND_BIB_DIR
browse:\tbuild alphabetical browse indexes, copy to any named slaves
sfx:\timport SFX records harvested into VUFIND_SFX_DIR

 -T test mode (developer only)
 -s comma-separated list of replication slave hosts, used for copying browse indexes
    Note: requires passwordless ssh between master and slaves
 -v verbose (NYI)"
TAB="$(printf '\t')"

TESTING=false
VERBOSE=false
NEW_BIBS=true
SLAVES=()      # create SLAVES as an array


# check environment/configuration

checkenv () {
    local notset=""
    for var in $REQVARS
    do
	if [ -v $var ]
	then
	    printf "$var\t"
	    eval echo \$$var
	else
	    if [ -z "$notset" ]
	    then
		notset=$var
	    else
		notset="$notset $var"
	    fi
	    error=1
	fi
    done
    if [ ! -z "$notset" ]
    then 
	fatal "Needed variables are not set: $notset"
    fi
}

# prerequisites

prereqs ()
{
   satisfied=
   missing=
   for p in $@
   do
	if type "$p" > /dev/null 2>&1
	then satisfied="$satisfied|  $p${TAB}$(which $p)"
	else
	    missing="$missing  $p"
	fi
   done
   if [ "$missing" != "" ]
   then
       (
	    echo "$ME: missing prerequisites:"
	    echo "$missing"
	    echo -n "$ME: satisfied prerequisites:"
	    echo "$satisfied" | tr \| '\012'
	) >&2
       exit 69
   else : ok
   fi
}

noreq ()
{
   fatal "DRYROT: missing prerequisite!"
}

choosereq ()
{
   for p in $@ noreq
   do
       if type "$p" > /dev/null 2>&1
	then echo "$p"; return 0
	else :
	fi
   done
}

# Removed curl: in /usr/local/bin on FreeBSD, but may do SFX download & import via PHP
#prereqs mktemp curl
prereqs mktemp java

# handy functions

usage () {
   echo "$USAGE" 1>&2
   case "$1" in
   '') ;;
   0)  exit 0 ;;
   *)  exit "$1" ;;
   esac
}

nyi () {
   echo "$1: not yet implemented" 1>&2
   exit 2
}

warning () {
   echo "$*" 1>&2
}

fatal () {
   if expr "$1" : '[0-9][0-9]*$' > /dev/null
   then X=$1; shift
   else X=1
   fi
   echo "$*" 1>&2
   exit $X
}

prefix () {
   if $TESTING
   then echo "TEST$1"
   else echo "$1"
   fi
}

#
# Functions for doing the work
#

delete_bib () {
    if [ -z "$VUFIND_BIB_DIR" ]
    then 
	fatal 2 "VUFIND_BIB_DIR is not set"
    fi

    for f in $VUFIND_BIB_DIR/*[Dd]eleted*.txt
    do
	php $VUFIND_HOME/util/deletes.php "$f" flat &&
	    mv "$f" $VUFIND_BIB_DIR/processed/$(basename "$f")
    done
}

import_bib () {
    if [ -z "$VUFIND_BIB_DIR" ]
    then 
	fatal 2 "VUFIND_BIB_DIR is not set"
    fi
    
    local BIBFILES=$(find $VUFIND_BIB_DIR -maxdepth 1 -name \*.xml -o -name \*.mrc)
    if [ -z "$BIBFILES" ]
    then
	# index_browse need not build indexes if no new bib files
	NEW_BIBS=false
    fi

    $VUFIND_HOME/harvest/batch-import-marc.sh -d -p local/import/import_ole_syn.properties $VUFIND_BIB_DIR
}

index_browse () {
    # If import_bib ran and found no files, skip reindex
    if [ $NEW_BIBS ]
    then
	$VUFIND_HOME/uc-index-alphabetic-browse.sh

	# TODO: copy browse indexes to slaves
	INDEXES=($(for f in $VUFIND_HOME/solr/alphabetical_browse/*_browse*; do basename $f; done | sed -e 's/_browse.*//' | sort -u))

	for host in ${SLAVES[@]};
	do
	    for index in ${INDEXES[@]};
            do
	        echo pull $index to $host
	        ssh $host . .env \&\& '$VUFIND_HOME'/uc-pull-browse.sh  $(hostname) $index
            done
	done
    fi
}

import_sfx () {
    if [ -z "$VUFIND_SFX_DIR" ]
    then 
	fatal 2 "VUFIND_SFX_DIR is not set"
    fi
    if [ \! -d "$VUFIND_SFX_DIR" ]
    then
	mkdir -p $VUFIND_SFX_DIR
	mkdir -p $VUFIND_SFX_DIR/log
    fi
     
    curl --output $VUFIND_SFX_DIR/sfx.xml --fail --silent --show-error 'http://sfx.lib.uchicago.edu:3002/lens/lensexport.xml'
    $VUFIND_HOME/harvest/batch-import-marc.sh -d -p local/import/import_sfx.properties $VUFIND_SFX_DIR
}

#
# Main
#

while true
do
   case "$1" in
   -H)     shift; usage; printf "$HELP\n"; exit 0 ;;
   -t)     shift; TESTING=true ;;
   -s)     shift; IFS=, read -a SLAVES <<< $1; shift ;;
   -v)     shift; VERBOSE=true ;;
   --)     shift; break ;;
   -)      break ;;
   -*)     usage 1 ;;
   *)      break ;;
   esac
done

for CMD in "$@"
do
    case $CMD in
	bib)
	    # Process deletes first, use the import for the commit
	    # as of VF 2.3, delete.php does not send a commit
	    delete_bib
	    import_bib;;
	browse)
	    # TODO: optimize - don't build browse if import_bib did no work
	    index_browse;;
	check)
	    checkenv;;
	sfx)
	    import_sfx;;
	*) usage 1;;
    esac
done

# Local Variables:
# End:
