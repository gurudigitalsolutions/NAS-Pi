#!/bin/bash

#-----------------------------------------------------------------------
#
# These are the functions responsible for creating new entries into the 
# existing fstab as well as individual fstab files in the FSTAB_DIR.  By 
# default the path is /etc/fstab.d  The mount specific options are kept in
# the CONFIGURATION files in /etc ~/.naspid.conf ~/.naspi/naspid.conf
#
#-----------------------------------------------------------------------

Source=$2

PROG=naspi
ENVARS=/etc/$PROG/envars

#
# Source environment variable file
#
#set -x
. $ENVARS
set +x
#
# Test user and group id for root
#
if [[ $(id -u) != 0 ]]&&[[ $(id -g) != 0 ]]; then
	echo -e "${E_ROOT[0]}"
	exit "${E_ROOT[1]}"
fi

#
# sources each of the 3 configuration file locations
#
CONFIG_SET=FALSE
CONFIG_PATHS=("/etc/naspi" '~' '~/naspi')

#set -x
for EACH_CONFIG in "${CONFIG_PATHS[@]}"; do
	
	if [[ -f $EACH_CONFIG/$PROG.conf ]]; then
		. "$EACH_CONFIG/$PROG.conf"
		CONFIG_SET=TRUE
	fi

done
set +x

#
# Source the errors file
#
#set -x
. $ERRORS
set +x
#
#
#
SOURCE_DATA="$INSTALL_DIR"/modules/files/sources

if [[ ! -x "$SOURCE_DATA"/sourcedata ]]; then
	log "${E_SOURCE[0]}" "${E_SOURCE[1]}"
	exit ${E_SOURCE[1]}
fi

#
# Creates the error log if missing
#
if [[ ! -e $LOG ]]; then
	touch $LOG
fi

set +x

#-----------------------------------------------------------------------
#
#	Common Script Functions
#
#	functions used by save_fstab and update_status
#
#-----------------------------------------------------------------------

log() #
# Logs messages and errors, if enabled, to file 
{
	#set -x
	
	if [[ $# -eq 1 ]]; then
		echo "[$(date +%m/%d\ %H:%M:%S)]" $1 >> $LOG
	
	# Log error messages if logging is enabled
	elif [[ $# -ge 2 ]] && [[ $E_LOGGING == TRUE ]];then
		echo "[$(date +%m/%d\ %H:%M:%S)|[ERROR $1]: ${@:2}" >> $LOG
	fi
	
	set +x
}

#
# Checks that a configuration was set
#
if [[ $CONFIG_SET = FALSE ]]; then
	log "${E_CONFIG[0]}" "${E_CONFIG[1]}"
	exit ${E_CONFIG[0]}
fi

create_missing_directory() #
# Create a directory if not already present
{
#set -x
	if [[ ! -e $1 ]]; then
		if [[ $# -eq 2 ]]; then
			mkdir -pm "$2" "$1"
		else
			mkdir -p "$1"
		fi
		if [[ $? -eq 0 ]];then
			echo "Created directory: $1"
		fi
	fi
set +x
}

get_data() #
# Run external script to query frontend for source information
{
#set -x
	"$SOURCE_DATA"/./sourcedata $1 $2
set +x
}

FSType=$(get_data $Source FSType)

Source_List=$(get_data)

#Device_Atrib="UUID Label Device FSType FindBy Title SourceCode Enabled HTTPShareEnabled HTTPShareAuthRequired"
	
#Smb_Atrib="RemoteHost RemotePath Username Password Title SourceCode Enabled HTTPShareEnabled HTTPShareAuthRequired FSType"

#Sshfs_Atrib="RemoteHost RemotePath Username Password Port Title SourceCode Enabled HTTPShareEnabled HTTPShareAuthRequired FSType"

#Ftp_Atrib="RemoteHost RemotePath Username Password Port Title SourceCode Enabled HTTPShareEnabled HTTPShareAuthRequired FSType"

#Bind_Atrib="SourceNode DestinationNode Title SourceCode Enabled HTTPShareEnabled HTTPShareAuthRequired OriginalSourceCode OriginalPath FSType"

#-----------------------------------------------------------------------
#
#	Update Fstab
#
#	Determines the file system type
#	Creates file system specific fstab entries
#	Catonates all files from fstab.d folder into /etc/fstab
#
#-----------------------------------------------------------------------

device_fstab() #
# Block device specifics
{
	#set -x
	local UUID=$(get_data $1 UUID)
	local Source_Code=$(get_data $1 SourceCode)

	echo "UUID=$UUID \
	$MOUNT_PATH/$Source_Code \
	$DEVICE_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

smb_fstab() #
# SMB specifics
{
#set -x
	create_missing_directory $CREDENTIALS 750

	local Remote_Host=$(get_data $1 RemoteHost)
	local Remote_Path=$(get_data $1 RemotePath)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)
	
	echo -e "username=$Username\npassword=$Password" \
	> $CREDENTIALS/$1.smb
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
	echo -e "//$Remote_Host/${Remote_Path#/} \
	$MOUNT_PATH/$Source_Code \
	$SMB_DEFAULTS$CREDENTIALS/$1.smb" \
	> $FSTAB_DIR/$1.fstab
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
set +x
}

function sshfs_fstab() #
# SSHFS specifics
{
#set -x
	create_missing_directory $CREDENTIALS 750
	
	local Remote_Host=$(get_data $1 RemoteHost)
	local REMOTE_PORT=$(get_data $1 Port)
	local Remote_Path=$(get_data $1 RemotePath)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)

	echo "$Password" \
	> $CREDENTIALS/$1.sshfs
	
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
	echo "sshfs $Username@$Remote_Host:$Remote_Path \
	-p $REMOTE_PORT \
	-o password_stdin \
	-o allow_other \
	-o StrictHostKeyChecking=no \
	$MOUNT_PATH/$Source_Code" \
	> $FSTAB_DIR/$1-sshfs.sh
	
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
set +x
}

ftp_fstab() #
# FTP specifics
{
	#set -x
	local Remote_Host=$(get_data $1 RemoteHost)
	local REMOTE_PORT=$(get_data $1 Port)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)
	
	echo -e  "machine $Remote_Host\nlogin $Username\npassword $Password" \
	> /root/.netrc
	
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
	echo "curlftpfs#$Username:$Password@$Remote_Host \
	$MOUNT_PATH/$Source_Code \
	$FTP_DEFAULTS"\
	> $FSTAB_DIR/$1.fstab

	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
set +x
}

bind_fstab() #
# Bind specifics
{
#set -x
	local Source_Code=$(get_data $1 SourceCode)
	local Original_Source_Code=$(get_data $1 OriginalSourceCode)
	local Original_Path=$(get_data $1 OriginalPath)
	
	echo "/${Original_Path#/}/$Original_Source_Code \
	$MOUNT_PATH/$Source_Code \
	$BIND_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab

	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} $Source"
	
set +x
}

function save_fstab() #
# Checks the file system type of source and creates/updates an fstab 
# entry
{
#set -x
	${FSType}_fstab $Source
	[[ $? -eq 0 ]]&& log "Saved source to $FSTAB_DIR/$Source"

	if [[ -f $FSTAB_DIR/$Source.fstab ]]; then
		cat $FSTAB_DIR/fstab.orignial $FSTAB_DIR/*.fstab > /etc/fstab
	fi
	[[ $? -eq 0 ]]|| log {E_FSTAB[0]} "{E_FSTAB[1]} main fstab"
set +x
}

#-----------------------------------------------------------------------
#
#	Update Status
#
#	Checks if a source is already mounted or unmounted
#	Checks the currents status of a source
#	Mount/unmount sources if the status has changed
#
#-----------------------------------------------------------------------

mount_control() #
# mounts/unmounts sources based on filesystem type
{
#set -x
	if [[ $FSType == sshfs ]];then
		
		if [[ $1 == unmount ]]; then
			fusermount -u $MOUNT_PATH/$Source

		elif [[ $1 == mount ]]; then 	
			SSHFS_SCRIPT=$(cat ${FSTAB_DIR}/$Source-sshfs.sh)
			$SSHFS_SCRIPT < $CREDENTIALS/$Source.sshfs
		fi
		
	elif [[ $1 == unmount ]]; then
		umount "$MOUNT_PATH/$Source"
		
	elif [[ $1 == mount ]]; then
		mount "$MOUNT_PATH/$Source"
	fi

	if [[ $? -ne 0 ]]; then
		log ${E_MOUNT[0]} "${E_MOUNT[1]} ${1}ing $MOUNT_PATH/$Source"
	else
		log "${1}ed $MOUNT_PATH/$Source successfully"
	fi
set +x
}

update_status() #
# Checks if the specified source is mounted and attempts to mount it if
# not already mounted
{
#set -x
	Mounted=$(mount -l | grep "on $MOUNT_PATH/$Source type ")
	Enabled=$(get_data $Source Enabled)

	if [[ X$Enabled != X ]]&&[[ X$Mounted == X ]];then
		create_missing_directory "$MOUNT_PATH/$Source"
		mount_control mount

	elif [[ X$Enabled != X ]]&&[[ X$Mounted != X ]];then
		mount_control unmount
		create_missing_directory "$MOUNT_PATH/$Source"
		mount_control mount

	elif [[ X$Enabled == X ]]&&[[ X$Mounted != X ]];then
		mount_control unmount

	fi

set +x
}

#-----------------------------------------------------------------------
#
#	Argument Interpreter
#
#	Determines which functions to call based on command line arguments
#
#-----------------------------------------------------------------------
#set -x
case $1 in
	save)
		save_fstab
		update_status
		;;
	*)
		log "${E_USAGE[0]}" "${E_USAGE[1]}"
		exit "${E_USAGE[0]}"
		;;
esac
