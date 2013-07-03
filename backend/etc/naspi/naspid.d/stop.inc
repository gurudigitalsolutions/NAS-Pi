#!/bin/bash
# set -x

set -f;IFS=$'\n'
ps=($(ps -e | grep -e ' naspid$' -e ' cat$'))
ps=(${ps[@]%%[?|p]*})
unset IFS;set +f

kill $(echo ${ps[@]% *})
export RUN=FALSE

set +x
