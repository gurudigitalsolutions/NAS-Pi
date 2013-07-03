#!/bin/bash

# These functions are responsible for interpreting the controls sent to the
# Daemon's listener and then invoking the corresponding function to handle
# the control.

# directs daemon controls based on contorl input

function input() {
	# set -x
	case $1 in
		commit )
			. $SRC_PATH/$1.inc $2
			;;
		update )
			. $SRC_PATH/$1.inc $2
			;;
		remove )
			remove_source
			;;
		*)
			echo "${INPUT[@]}"
			#echo $E_CONTROL
			#exit
			;;
	esac
	set +x
}

# Updates the fstabs and mount/unmounts the source

function update() {
	#set -x

	mount_filesystem
	set +x
}

# set -x

RUN=TRUE

echo "Starting Listener" >> $LOG
echo $PPID >> $NASPID_LOCK_FILE

mkfifo -m 0600 $CONTROL_FILE
chown "$USER:$USER" $CONTROL_FILE

while [[ $RUN = TRUE ]]; do
	INPUT=($(cat $CONTROL_FILE))
	echo "sending ${INPUT[@]}" >> $LOG
	input ${INPUT[@]}
done

set +x
