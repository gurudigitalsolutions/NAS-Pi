#!/bin/bash

#-----------------------------------------------------------------------
#
# These are the functions responsible for creating new entries into the 
# existing fstab as well as individual fstab files in the FSTAB_DIR.  By 
# default the path is /etc/fstab.d  The mount specific options are kept in
# the CONFIGURATION files in /etc ~/.naspid.conf ~/.naspi/naspid.conf
#
#-----------------------------------------------------------------------

local Source=$1
FSType=$(get_data $Source FSType)

#
# Makes decisions based on share types
#
function device_commit() {		
	#set -x
	local UUID=$(get_data $1 UUID)
	local Source_Code=$(get_data $1 SourceCode)

	echo "UUID=$UUID \
	$MOUNT_PATH/$Source_Code \
	$DEVICE_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Create fstab for smb specific shares
#
function smb_commit() {
	#set -x
	local Remote_Host=$(get_data RemoteHost)
	local Remote_Path=$(get_data RemotePath)
	local Source_Code=$(get_data SourceCode)
	local Username=$(get_data Username)
	local Password=$(get_data Password)
	
	echo "username=$Username\npassword=$Password" \
	> $HOME/$CREDENTIALS/$1.smb
	
	echo "//$Remote_Host${Remote_Path// /\\040} \
	$MOUNT_PATH/$Source_Code \
	$SMB_DEFAULTS$HOME/$CREDENTIALS/$1.smb \
	0 0" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Create shell script for sshfs shares
#
function sshfs_commit() {
	#set -x
	local Remote_Host=$(get_data RemoteHost)
	local REMOTE_PORT=$(get_data Port)
	local Remote_Path=$(get_data RemotePath)
	local Source_Code=$(get_data SourceCode)
	local Username=$(get_data Username)
	local Password=$(get_data Password)
	
	echo "$Password" \
	> $HOME/$CREDENTIALS/$1.sshfs
	
	echo "sshfs $Username@$Remote_Host:$Remote_Path \
	-p $REMOTE_PORT \
	-o password_stdin \
	-o allow_other \
	-o StrictHostKeyChecking=no \
	$MOUNT_PATH/$Source_Code" \
	> $FSTAB_DIR/$1-sshfs.sh
	set +x
}

#
# Create fstab for ftp shares
#
function ftp_commit() {
	#set -x
	local Remote_Host=$(get_data RemoteHost)
	local REMOTE_PORT=$(get_data Port)
	local Source_Code=$(get_data SourceCode)
	local Username=$(get_data Username)
	local Password=$(get_data Password)
	
	echo -e  "machine $Remote_Host\nlogin $Username\npassword $Password" \
	> $HOME/.netrc
	
	echo "curlftpfs#$Username:$Password@$Remote_Host \
	$MOUNT_PATH/$Source_Code \
	$FTP_DEFAULTS"\
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Create fstab for bind mounts
#
function bind_commit() {
	#set -x
	local TARGET=$(get_data Target)
	local Source_Code=$(get_data SourceCode)
	
	echo "$TARGET \
	$Source_Code \
	$BIND_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Write new fstab file for resume at boot
#
function write() {
	#set -x
	if [[ ! -f $FSTAB_DIR/fstab.orignial ]]; then
		cat /etc/fstab > $FSTAB_DIR/fstab.orignial
	fi
	
	if [[ -f $FSTAB_DIR/*.fstab ]]; then
		cat $FSTAB_DIR/fstab.orignial $FSTAB_DIR/*.fstab > $FSTAB_DIR/fullstab
	else
		cp $FSTAB_DIR/fstab.orignial $FSTAB_DIR/fullstab
	fi
	
	cat $FSTAB_DIR/fullstab > /etc/fstab
	set +x
}

#-----------------------------------------------------------------------
#set -x


FSType=$(get_data $Source FSType)

# Generic message for mount logging
Write_Log="Wrote FSType: $FSType to $FSTAB_DIR/$Source"

if [[ $FSType = device ]]; then
	device_commit $Source
	log "$Write_Log.fstab"
elif [[ $FSType = smb ]]; then
	smb_commit $Source
	log "$Write_Log.fstab"
elif [[ $FSType = sshfs ]]; then
	sshfs_commit $Source
	log "$Write_Log-sshfs.sh"
elif [[ $FSType = ftp ]]; then
	ftp_commit $Source
	log "$Write_Log.fstab"
elif [[ $FSType = bind ]]; then
	bind_commit $Source
	log "$Write_Log.fstab"
fi
write

set +x

#-----------------------------------------------------------------------
#
#
#
#
#
#-----------------------------------------------------------------------

#
#
#
function is_mounted() {
	#set -x
	IS_MOUNTED=$(mount -l | grep "on $MOUNT_PATH/$Source type ")
	set +x
}

#
# mount unmounted sources if enabled
#
function not_mounted() {
	#set -x
	if [[ -n $Is_Enabled ]]; then
		create_missing_directory "$MOUNT_PATH/$Source"
		Attempt=0
		until [[ -n $IS_MOUNTED ]] || [[ $Attempt = $RETRIES ]];do
			mount_by_type
			is_mounted
			((Attempt++))
			sleep $RETRY_INTERVAL
		done
		success_fail "Mounting"
	fi
	set +x
}

#
# mounts source using methods based on filesystem type
#
function mount_by_type() {
	#set -x
	if [[ $FSType = sshfs ]]; then
		SSHFS_SCRIPT=$(cat ${FSTAB_DIR}/$Source-sshfs.sh)
		$SSHFS_SCRIPT < $HOME/$CREDENTIALS/$Source.sshfs
	else
		mount "$MOUNT_PATH/$Source"
	fi
	set +x
}

#
# unmount sources if disabled
#
function already_mounted() {
	#set -x
	if [[ -z $Is_Enabled ]]; then
		Attempt=0
		until [[ -z $IS_MOUNTED ]] || [[ $Attempt = $RETRIES ]];do
			unmount_by_type
			is_mounted
			((Attempt++))
			sleep $RETRY_INTERVAL
		done
		success_fail "Unmounted"
	fi
	set +x
}

#
# unmounts source using methods based on filesystem type
#
function unmount_by_type() {
	#set -x
	if [[ $FSType = sshfs ]]; then
		fusermount -u $MOUNT_PATH/$Source
	else
		umount "$MOUNT_PATH/$Source"
	fi
	set +x
}

#
# sends final status update to file
#
function success_fail() {
	#set -x
	if [[ $Attempt = $RETRIES ]];then
		log "$E_MOUNT" "$M_MOUNT" "$1"
	else
		log "$1 $Source successfully"
	fi
	
	set +x
}


set -x


is_mounted
Is_Enabled=$(get_data $Source Enabled)

[[ $IS_MOUNTED ]] && not_mounted || already_mounted

set +x

