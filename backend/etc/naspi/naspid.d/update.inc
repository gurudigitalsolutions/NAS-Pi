#!/bin/bash

# These are the functions responsible for the mount/unmounting of any user
# defined sources.  It uses an included PHP module to query the NASPI web
# interface for each source's relevant data


# search mounted items for target source

function is_mounted() {
	#set -x
	IS_MOUNTED=$(mount -l | grep "on $MOUNT_PATH/$SOURCE type ")
	set +x
}

# mount unmounted sources if enabled

function not_mounted() {
	#set -x
	if [[ -n $IS_ENABLED ]]; then
		create_missing_directory "$MOUNT_PATH/$SOURCE"
		ATTEMPT=0
		until [[ -n $IS_MOUNTED ]] || [[ $ATTEMPT = $RETRIES ]];do
			mount_by_type
			is_mounted
			((ATTEMPT++))
			sleep $RETRY_INTERVAL
		done
		success_fail "Mounting"
	fi
	set +x
}

# mounts source using methods based on filesystem type

function mount_by_type() {
	#set -x
	if [[ $FSTYPE = sshfs ]]; then
		SSHFS_SCRIPT=$(cat ${FSTAB_DIR}/$SOURCE-sshfs.sh)
		$SSHFS_SCRIPT < $HOME/$CREDENTIALS/$SOURCE.sshfs
	else
		mount "$MOUNT_PATH/$SOURCE"
	fi
	set +x
}

# unmount sources if disabled

function already_mounted() {
	#set -x
	if [[ -z $IS_ENABLED ]]; then
		ATTEMPT=0
		until [[ -z $IS_MOUNTED ]] || [[ $ATTEMPT = $RETRIES ]];do
			unmount_by_type
			is_mounted
			((ATTEMPT++))
			sleep $RETRY_INTERVAL
		done
		success_fail "Unmounted"
	fi
	set +x
}

# unmounts source using methods based on filesystem type

function unmount_by_type() {
	#set -x
	if [[ $FSTYPE = sshfs ]]; then
		fusermount -u $MOUNT_PATH/$SOURCE
	else
		umount "$MOUNT_PATH/$SOURCE"
	fi
	set +x
}

# sends final status update to file

function success_fail() {
	#set -x
	create_missing_directory $STATUS_PATH
	if [[ $ATTEMPT = $RETRIES ]];then
		echo "$1 attempt failed"
		echo "FAILED" > $STATUS_PATH/$SOURCE.status
	else
		echo "$1 $SOURCE successfully"
		echo "SUCCESS" > $STATUS_PATH/$SOURCE.status
	fi
	set +x
}

set -x

local SOURCE=$1
FSTYPE=$(get_data $SOURCE FSType)
is_mounted
IS_ENABLED=$(get_data $SOURCE Enabled)

[[ $IS_MOUNTED ]] && not_mounted || already_mounted

set +x
