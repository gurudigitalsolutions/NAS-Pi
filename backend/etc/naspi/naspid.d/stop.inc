#!/bin/bash
set -x
PID_2_KILL=$(cat $NASPID_LOCK_FILE)
rm $NASPID_LOCK_FILE
kill $PID_2_KILL
kill $((PID_2_KILL+1))
export RUN=FALSE
set +x
