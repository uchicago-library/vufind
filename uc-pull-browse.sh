#!/bin/bash
# Tod Olson <tod@uchicago.edu>
# Much is stolen shamelessly from scripts by Keith Waclena
#
# Copy browse index from a remote host to local host via scp.  Sets up
# the .db-ready file semaphore and calls browse handler to move the
# index into place.
#

ME=`basename $0 .sh`

USAGE="Usage: $ME [-t] [-V remote_home] [user@]host index; -H for help"
HELP="Copies browse index from remote host and triggers the browse-handler to 
swap in the new index.
Note: Requires passwordless ssh between hosts.

OPTIONS:
 -H: print this help message
 -B: use scp batch and quiet modes
 -V: VUFIND_HOME on remote host (if different from localhost)
 -t: testing only - NYI

ARGUMENTS:
 host:  name of the remote host to copy from, can accept
        SSH-style user@host syntax
 index: name of the index (less the _browse suffix)

ENVIRONMENT
 The following variables must be set:

 VUFIND_HOME"
TAB="$(printf '\t')"

TESTING=false

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

prereqs scp curl

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
# Check environment
#
if [ -z "$VUFIND_HOME" ]
then
    fatal "VUFIND_HOME not set"
fi

REMOTE_VUFIND_HOME="$VUFIND_HOME"
SCP_BATCH=""
SSH_QUIET=""

while true
do
   case "$1" in
   -H)     shift; usage; echo "$HELP"; exit 0 ;;
   -t)     shift; TESTING=true ;;
   -B)     shift; SCP_BATCH="-Bq"; SSH_QUIET="-q";;
   -V)     REMOTE_VUFIND_HOME=$2; shift; shift;;
   --)     shift; break ;;
   -)      break ;;
   -*)     usage 1 ;;
   *)      break ;;
   esac
done
[ $# -eq 2 ] || usage 1
REMOTE_HOST=$1
INDEX=$2

#
# Main
#

INDEX_BASE=${INDEX}_browse
REMOTE_BASE=$REMOTE_VUFIND_HOME/solr/alphabetical_browse/${INDEX_BASE}
LOCAL_BASE=$VUFIND_HOME/solr/alphabetical_browse/${INDEX_BASE}

# .db-ready is the signal that .db-updated is complete, but
# .db-updated could move into place and .db-ready could be deleted at
# any time
if ssh $SSH_QUIET $REMOTE_HOST test -f ${REMOTE_BASE}.db-ready
then
    scp ${SCP_BATCH} -p ${REMOTE_HOST}:${REMOTE_BASE}.db-updated ${LOCAL_BASE}.db-updated ||
    scp ${SCP_BATCH} -p ${REMOTE_HOST}:${REMOTE_BASE}.db ${LOCAL_BASE}.db-updated
else
    scp ${SCP_BATCH} -p ${REMOTE_HOST}:${REMOTE_BASE}.db ${LOCAL_BASE}.db-updated
fi

if [ $? -ne 0 ]
then
    fatal "failed to copy $INDEX_BASE from $REMOTE_HOST"
fi

touch ${LOCAL_BASE}.db-ready || fatal "failed to create ${LOCAL_BASE}.db-ready"

# trigger browse-handler to swap in new indexes
curl "http://localhost:8080/solr/biblio/browse?source=${INDEX}" > /dev/null

# Local Variables:
# End:
