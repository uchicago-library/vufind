#!/bin/bash

# This script pulls over OLE daily incremental files from ole01.uchicago.edu 
# saves those files under   /vufind/vufind/local/harvest/OLE* directories
# pulls sfx records 
# run all freshly pulled records
# moves all run files into processed directories

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

#
# Defaults
#
MTIME='-1'    # mtime arg for find, how many days old (note minus sign)



USAGE="Usage: $ME [-t] -h _host_ remote_dir ; -H for help"
HELP="Copy export files from remote host to VUFIND_BIB_DIR ($VUFIND_BIB_DIR)
 remote_dir: export directory on remote host
 -d  days old (default = 1)
 -h  name of remote host (required)
 -t  test mode (developer only)
 -u  user for remote host
 -v  verbose (NYI)"

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

# Check programs that we need
prereqs ssh

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



#
# Main
#

while getopts 'd:Hh:tu:v' OPT
do
    case "$OPT" in
	h)  HOST=$OPTARG;;
	H)  usage; printf "$HELP\n"; exit 0 ;;
	d)  MTIME="-$OPTARG";;
	t)  TESTING=true;;
	u)  SSH_USER=$OPTARG;;
	-)  break;;
	\?) echo "Invalid option: -$OPTARG" >&2; usage 1;;
    esac
done
shift -- $((OPTIND - 1))

# while true
# do
#     case "$1" in
# 	-h)  shift; HOST=$1; shift;;
# 	-H)  shift; usage; printf "$HELP\n"; exit 0 ;;
# 	-t)  shift; TESTING=true ;;
# 	-u)  shift; SSH_USER=$1
# 	-v)  shift; VERBOSE=true ;;
# 	--)  shift; break ;;
# 	-)   break ;;
# 	-*)  usage 1 ;;
# 	*)   break ;;
#     esac
# done

# Make sure we have the expected number of arguments
E_BADARGS=65
EXPECTED_ARGS=1

if [ $# -ne $EXPECTED_ARGS ]
then
    echo "Missing remote directory" >&2
    usage $E_BADARGS
fi

if [[ -z $HOST ]]
then
    fatal "-h is required, must specify host"
fi

if [[ -z $SSH_USER ]]
then
    USER_AT_HOST="$HOST"
else
    USER_AT_HOST="$SSH_USER@$HOST"
fi

#OLE_DIR=/opt/tomcat6/kuali/main/local/olefs-webapp/work/staging/export/VFIJan15/
OLE_DIR=$1
OLE_DELETES=/opt/tomcat6/kuali/main/local/olefs-webapp/work/staging/export/


ssh ${USER_AT_HOST} find ${OLE_DIR} -type f -iname "*.mrc" -mtime ${MTIME}  | xargs -I{} scp ${USER_AT_HOST}:{} ${VUFIND_BIB_DIR}

ssh ${USER_AT_HOST} find ${OLE_DELETES} -type f -iname "*Deleted_Bibs.txt" -mtime ${MTIME}  | xargs -I{} scp ${USER_AT_HOST}:{} ${VUFIND_BIB_DIR}

exit $?

# Local Variables:
# End:
