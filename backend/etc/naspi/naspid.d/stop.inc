#!/bin/bash
# set -x

set -f;IFS=$'\n'
ps=($(ps -e | grep -e ' naspid$' -e ' nc$'))
ps=(${ps[@]%%[?|p]*})
unset IFS;set +f

kill $(echo ${ps[@]% *})
if [[ $? -eq 0 ]];then
	rm $NASPID_LOCK_FILE
fi

export RUN=FALSE

set +x
