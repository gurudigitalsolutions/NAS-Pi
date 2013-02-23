#!/bin/bash

# These are the functions responsible for creating new entries into the 
# existing fstab as well as individual fstab files in the FSTAB_DIR.  By 
# default the path is /etc/fstab.d  The mount specific options are kept in
# the CONFIGURATION files in /etc ~/.naspid.conf ~/.naspi/naspid.conf

# Makes decisions based on share types

function device_commit() {		
	#set -x
	local UUID=$(get_data $1 UUID)
	local SOURCE_CODE=$(get_data $1 SourceCode)

	echo "UUID=$UUID \
	$MOUNT_PATH/$SOURCE_CODE \
	$DEVICE_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

# Create fstab for smb specific shares

function smb_commit() {
	#set -x
	local REMOTE_HOST=$(get_data RemoteHost)
	local REMOTE_PATH=$(get_data RemotePath)
	local SOURCE_CODE=$(get_data SourceCode)
	local USERNAME=$(get_data Username)
	local PASSWORD=$(get_data Password)
	
	echo "username=$USERNAME\npassword=$PASSWORD" \
	> $HOME/$CREDENTIALS/$1.smb
	
	echo "//$REMOTE_HOST${REMOTE_PATH// /\\040} \
	$MOUNT_PATH/$SOURCE_CODE \
	$SMB_DEFAULTS$HOME/$CREDENTIALS/$1.smb \
	0 0" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

# Create shell script for sshfs shares

function sshfs_commit() {
	#set -x
	local REMOTE_HOST=$(get_data RemoteHost)
	local REMOTE_PORT=$(get_data Port)
	local REMOTE_PATH=$(get_data RemotePath)
	local SOURCE_CODE=$(get_data SourceCode)
	local USERNAME=$(get_data Username)
	local PASSWORD=$(get_data Password)
	
	echo "$PASSWORD" \
	> $HOME/$CREDENTIALS/$1.sshfs
	
	echo "sshfs $USERNAME@$REMOTE_HOST:$REMOTE_PATH \
	-p $REMOTE_PORT \
	-o password_stdin \
	-o allow_other \
	-o StrictHostKeyChecking=no \
	$MOUNT_PATH/$SOURCE_CODE" \
	> $FSTAB_DIR/$1-sshfs.sh
	set +x
}

# Create fstab for ftp shares

function ftp_commit() {
	#set -x
	local REMOTE_HOST=$(get_data RemoteHost)
	local REMOTE_PORT=$(get_data Port)
	local SOURCE_CODE=$(get_data SourceCode)
	local USERNAME=$(get_data Username)
	local PASSWORD=$(get_data Password)
	
	echo -e  "machine $REMOTE_HOST\nlogin $USERNAME\npassword $PASSWORD" \
	> $HOME/.netrc
	
	echo "curlftpfs#$USERNAME:$PASSWORD@$REMOTE_HOST \
	$MOUNT_PATH/$SOURCE_CODE \
	$FTP_DEFAULTS"\
	> $FSTAB_DIR/$1.fstab
	set +x
}

# Create fstab for bind mounts

function bind_commit() {
	#set -x
	local TARGET=$(get_data Target)
	local SOURCE_CODE=$(get_data SourceCode)
	
	echo "$TARGET \
	$SOURCE_CODE \
	$BIND_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

# Write new fstab file for resume at boot

function write() {
	#set -x
	if [[ ! -f $FSTAB_DIR/fstab.orignial ]]; then
		cat /etc/fstab > $FSTAB_DIR/fstab.orignial
	fi
	
	if [[ -e $FSTAB_DIR/*.fstab ]]; then
		cat $FSTAB_DIR/fstab.orignial $FSTAB_DIR/*.fstab > $FSTAB_DIR/fullstab
	else
		cp $FSTAB_DIR/fstab.orignial $FSTAB_DIR/fullstab
	fi
	
	cat $FSTAB_DIR/fullstab > /etc/fstab
	set +x
}


# Creates any non-existant directories
create_missing_directory $FSTAB_DIR
create_missing_directory $HOME/$CREDENTIALS

#set -x
local SOURCE=$1

TYPE=$(get_data $SOURCE FSType)
echo $TYPE

if [[ $TYPE = device ]]; then
	device_commit $SOURCE
elif [[ $TYPE = smb ]]; then
	smb_commit $SOURCE
elif [[ $TYPE = sshfs ]]; then
	sshfs_commit $SOURCE
elif [[ $TYPE = ftp ]]; then
	ftp_commit $SOURCE
elif [[ $TYPE = bind ]]; then
	bind_commit $SOURCE
fi
write
echo "Commit Sucess: Wrote TYPE: $TYPE SOURCE: $SOURCE entry into fstab"
set +x