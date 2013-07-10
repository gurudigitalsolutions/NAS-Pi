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
# Source the errors file
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

#
# Logs messages and errors, if enabled, to file
#
function log() {
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

#
# Create a directory if not already present
#
function create_missing_directory() {
#set -x
	if [[ ! -e $1 ]]; then
		
		if [[ x$2 != x ]]; then
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

#
# Run external script to query frontend for source information
#
function get_data() {
	
	#set -x
	"$SOURCE_DATA"/./sourcedata $1 $2
	set +x
}
FSType=$(get_data $Source FSType)

#
#
#
function source_attributes() {
#set -x
	Attrs=$(get_data $Source)
}
	#DEVICE
#UUID
#Label
#Device
#FSType
#FindBy
#Title
#SourceCode
#Enabled
#HTTPShareEnabled
#HTTPShareAuthRequired
	#
	#UUID=$(get_data $1 UUID)
	#Source_Code=$(get_data $1 SourceCode)
	
	#SMB
#RemoteHost
#RemotePath
#Username
#Password
#Title
#SourceCode
#Enabled
#HTTPShareEnabled
#HTTPShareAuthRequired
#FSType
	#
	#Remote_Host=$(get_data $1 RemoteHost)
	#Remote_Path=$(get_data $1 RemotePath)
	#Source_Code=$(get_data $1 SourceCode)
	#Username=$(get_data $1 Username)
	#Password=$(get_data $1 Password)
	
	#SSHFS
#RemoteHost
#RemotePath
#Username
#Password
#Port
#Title
#SourceCode
#Enabled
#HTTPShareEnabled
#HTTPShareAuthRequired
#FSType
#
	#Remote_Host=$(get_data $1 RemoteHost)
	#REMOTE_PORT=$(get_data $1 Port)
	#Remote_Path=$(get_data $1 RemotePath)
	#Source_Code=$(get_data $1 SourceCode)
	#Username=$(get_data $1 Username)
	#Password=$(get_data $1 Password)

	#FTP
#RemoteHost
#RemotePath
#Username
#Password
#Port
#Title
#SourceCode
#Enabled
#HTTPShareEnabled
#HTTPShareAuthRequired
#FSType
#
	#Remote_Host=$(get_data $1 RemoteHost)
	#REMOTE_PORT=$(get_data $1 Port)
	#Source_Code=$(get_data $1 SourceCode)
	#Username=$(get_data $1 Username)
	#Password=$(get_data $1 Password)

	#BIND
#SourceNode
#DestinationNode
#Title
#SourceCode
#Enabled
#HTTPShareEnabled
#HTTPShareAuthRequired
#OriginalSourceCode
#OriginalPath
#FSType
#
	#Source_Code=$(get_data $1 SourceCode)
	#Original_Source_Code=$(get_data $1 OriginalSourceCode)
	#Original_Path=$(get_data $1 OriginalPath)

#-----------------------------------------------------------------------
#
#	Update Fstab
#
#	Determines the file system type
#	Creates file system specific fstab entries
#	Catonates all files from fstab.d folder into /etc/fstab
#
#-----------------------------------------------------------------------

#
# Makes decisions based on share types
#
function device_fstab() {		
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
function smb_fstab() {
	#set -x
	local Remote_Host=$(get_data $1 RemoteHost)
	local Remote_Path=$(get_data $1 RemotePath)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)
	
	echo -e "username=$Username\npassword=$Password" \
	> $CREDENTIALS/$1.smb

	echo -e "//$Remote_Host/${Remote_Path#/} \
	$MOUNT_PATH/$Source_Code \
	$SMB_DEFAULTS$CREDENTIALS/$1.smb" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Create shell script for sshfs shares
#
function sshfs_fstab() {
	#set -x
	local Remote_Host=$(get_data $1 RemoteHost)
	local REMOTE_PORT=$(get_data $1 Port)
	local Remote_Path=$(get_data $1 RemotePath)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)
	
	echo "$Password" \
	> $CREDENTIALS/$1.sshfs
	
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
function ftp_fstab() {
	#set -x
	local Remote_Host=$(get_data $1 RemoteHost)
	local REMOTE_PORT=$(get_data $1 Port)
	local Source_Code=$(get_data $1 SourceCode)
	local Username=$(get_data $1 Username)
	local Password=$(get_data $1 Password)
	
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
function bind_fstab() {
	#set -x
	local Source_Code=$(get_data $1 SourceCode)
	local Original_Source_Code=$(get_data $1 OriginalSourceCode)
	local Original_Path=$(get_data $1 OriginalPath)
	
	echo "/${Original_Path#/}/$Original_Source_Code \
	$MOUNT_PATH/$Source_Code \
	$BIND_DEFAULTS" \
	> $FSTAB_DIR/$1.fstab
	set +x
}

#
# Write new fstab file for resume at boot
#
function create_fstab() {
#set -x
	if [[ -f $FSTAB_DIR/$Source.fstab ]]; then
		cat $FSTAB_DIR/fstab.orignial $FSTAB_DIR/*.fstab > $FSTAB_DIR/fullstab
	fi
	cat $FSTAB_DIR/fullstab > /etc/fstab
set +x
}

#
# Checks the file system type of source and creates/updates an fstab 
# entry
#
function save_fstab() {
#set -x
	create_missing_directory $CREDENTIALS 750
	Write_Log="Wrote FSType: $FSType to $FSTAB_DIR/$Source"
	
	${FSType}_fstab
	log "$Write_Log.$FSType"
	create_fstab

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

#
# mounts/unmounts sources based on filesystem type
#
function mount_control() {
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
		log ${E_MOUNT[0]} "${E_MOUNT[1]}"
	else
		log "${1}ed $MOUNT_PATH/$Source successfully"
	fi
set +x
}

#
# Checks if the specified source is mounted and attempts to mount it if
# not already mounted
#
function update_status() {
#set -x
	Mounted=$(mount -l | grep "on $MOUNT_PATH/$Source type ")
	Enabled=$(get_data $Source Enabled)

	if [[ X$Enabled != X ]]&&[[ X$Mounted == X ]];then
		create_missing_directory "$MOUNT_PATH/$Source"
		mount_control mount

	elif [[ X$Enabled != X ]]&&[[ X$Mounted != X ]];then
		mount_control unmount
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
