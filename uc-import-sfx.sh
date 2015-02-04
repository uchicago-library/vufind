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

# list required variables here
REQVARS="VUFIND_HOME VUFIND_LOCAL_DIR VUFIND_HARVEST_DIR VUFIND_SFX_DIR"

USAGE="Usage: $ME [-t] command [command ...]; -H for help"
HELP="where command may be:
bib:\timport bib records found in VUFIND_BIB_DIR

 -T test mode (developer only)"
TAB="$(printf '\t')"

TESTING=false

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

prereqs mktemp 

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

import_bib () {
    if [ -z "$VUFIND_BIB_DIR" ]
    then 
	fatal 2 "VUFIND_BIB_DIR is not set"
    fi
    $VUFIND_HOME/harvest/batch-import-marc.sh -d -p local/import/import_horizon.properties $VUFIND_BIB_DIR
}

#
# Main
#

while true
do
   case "$1" in
   -H)     shift; usage; printf "$HELP\n"; exit 0 ;;
   -t)     shift; TESTING=true ;;
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
	    import_bib;;
	check)
	    checkenv;;
	*) usage 1;;
    esac
done

# Local Variables:
# End:
