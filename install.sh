#!/bin/bash
#-----------------------------------------------------------------------
#
#	NAS-Pi Installation Script
#
#-----------------------------------------------------------------------
#
#	Copyright 2013, Brian Murphy
#	www.gurudigitalsolutions.com
#
#-----------------------------------------------------------------------




#-----------------------------------------------------------------------
#
# Set Installation variables
#
#-----------------------------------------------------------------------

DEPENDENCIES="samba smbclient apache2 php5 php5-cli php5-curl apache2-mpm-itk sshfs curlftpfs netcat-openbsd unzip"

APACHE_USER="naspi"
INSTALL_DIR="/usr/share/naspi"

SITE="/etc/apache2/sites-available/nas-pi"

LOG="/var/log/naspid.log"

ETC="/etc/naspi"
INIT="/etc/init.d/naspid"
BIN="/usr/bin/naspid"
FSTAB="/etc/fstab.d/"

PDINIT="/etc/init.d/naspi-pd"
PDBIN="/usr/share/naspi/pd/pd.php"
FUSE="/etc/fuse.conf"

BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

ERRORS=$INSTALL_DIR/errors

ENVARS=$ETC/envars

EMPTY_DIR="$INSTALL_DIR log modules/users/accounts modules/users/sessions modules/files/sources/data"

#-----------------------------------------------------------------------
#
# Begin installer
#
#-----------------------------------------------------------------------

echo "  [ NAS-Pi Installer ]"
echo "  [ Copyright 2013 Guru Digital Solutions ]"

START_DIR=$(pwd)

cd $BASE

. backend/$ERRORS

#
# Test user and group id for root
#
if [[ $(id -u) != 0 ]]&&[[ $(id -g) != 0 ]]; then
	echo "[ERROR $E_ROOT[0]}] ${E_ROOT[1]}"
	exit "${E_ROOT[0]}"
fi

#-----------------------------------------------------------------------
#
# Functions
#
#-----------------------------------------------------------------------

#
# Checks dependancies and installs any missing
#
function install_dependencies
{
#set -x
	echo "  [ CHECKING DEPENDANCIES ]"
	for dep in $DEPENDENCIES; do

		dpkg -s $dep &>/dev/null
		if [[ $? -eq 1 ]];then
			need="$need$dep "
			[[ $dep = apache2 ]]&& export default=TRUE
		fi
	done

	if [[ x$need != x ]];then
		apt-get install $need
	
		if [[ $? -eq 1 ]];then
			echo "[ERROR $E_DEP[0]}] ${E_DEP[1]} $need"
			exit ${E_DEP[0]}
		fi
	fi
set +x
}

#
# Compare two files for any differences. If differences are found then
# has dialoge for replacing, backup, or ignoring
#
function compare_files
{
	if [[ $(diff -q $1 $2 2>/dev/null) ]]; then
		
		echo -e "  [ NOTICE ]\n$2 may need to be configured, do that now?"
		echo -n " [y]es [n]o [m]ove to .old ? "
		read response
		
		case $response in
			
			[y,Y]|[y,Y][e,E][s,S])
				echo "  [ CONFIGURING FUSE FOR USERS ]"
				cat $1 > $2
				$3;$4;$5
				;;

			m|M)
				mv $2 $2.old
				cat $1 > $2
				$3;$4;$5
				;;
			*)
				echo "  [ WARNING ] You must make a valid choice!"
				compare_files "$@"
				;;
		esac	
	fi
}

#
# Creates the user which Apache2 will run as
#
function create_naspi_user
{
	if [[ -z $(cat /etc/group | grep $APACHE_USER) ]]; then
		echo "  [ CREATING APACHE2 SYSTEM USER ]"
		useradd -M -r -s /bin/bash -U $APACHE_USER
	fi
}

#
# Places the naspid virtual host file and enables the site
#
function configure_apache
{
	echo "  [ CONFIGURING APACHE2 ]"
	
	if [[ ! -e $SITE ]]; then
		echo "  [ ADDING NAS-Pi TO SITES-AVAILIBLE ]"
		cp backend${SITE} $SITE
		a2ensite nas-pi &>/dev/null
	fi

	if [[ $default = TRUE ]];then
		unset default
		a2dissite 000-default &>/dev/null
	
	elif [[ -e /etc/apache2/sites-enabled/000-default ]]; then
		echo -e "\n  [ NOTICE ]\n 000-default is already configured and installed on apache2."
		echo "NAS-Pi requires / in a virtualhost. In order for NAS-Pi to work with other sites you will"
		echo "to use a NamedVirtualHost for other sites or alternate IPs once NAS-Pi is installed"
		echo -n " [ DISABLE? ] 000-default [y]es [n]o ? "
		read configure
	
		case $configure in
			y|Y|yes|YES)
				a2dissite 000-default &>/dev/null
				a2ensite nas-pi &>/dev/null
				;;
			*)
				a2ensite nas-pi
				echo "Manually configure apache2\'s sites-avalible"
				;;
		esac
	fi

	compare_files "backend${SITE}" "$SITE" "a2ensite nas-pi"
}

#
# Creates and modifies the permissions of frontend folders
#
function create_empty_directories() {
#set -x
	for empty in $EMPTY_DIR;do
		if [[ ! -e $INSTALL_DIR/$empty ]]; then
			echo "  [ CREATING $INSTALL_DIR/$empty ]"
			mkdir -p -m 755 $INSTALL_DIR/$empty	
		fi
	done
}

#
# Places all the frontend files into the install location
#
function place_files
{
#set -x
	echo "  [ PLACING FRONTEND FILES INTO $INSTALL_DIR/ ]"
	
	cp -r frontend/cms "$INSTALL_DIR"
	cp -r frontend/modules "$INSTALL_DIR"
	cp -r frontend/public_html "$INSTALL_DIR"
		
	chmod 777 "$INSTALL_DIR"/modules/users/groups.txt
	chmod 755 "$INSTALL_DIR"/modules/files/sources/sourcedata

	echo "  [ ADJUSTING FILE/FOLDER PERMISSIONS ]"
	if [[ ! -e /var/www/nas-pi ]]; then
		echo "  [ LINKING $INSTALL_DIR/public_html to /var/www/nas-pi ]"
		ln -s $INSTALL_DIR/public_html /var/www/nas-pi
	fi
set +x
}

#
# Enables users to use fuse mounts
#
function configure_fuse
{
	if [[ ! -e $FUSE ]]; then
		echo "  [ CONFIGURING FUSE FOR USERS ]"
		mv $FUSE $FUSE-bak
		cp "backend${FUSE}" "${FUSE}"
	else
		compare_files "backend${FUSE}" "${FUSE}"
	fi
}

#
# Places all backend files and create apache error log
#
function place_backend_files ()
{
#set -x
	echo "  [ PLACING BACKEND FILES IN /etc and /usr ]"
	
	[[ -e $ETC ]] || mkdir -p -m 755 $ETC
	[[ -e $FSTAB ]] || mkdir -p -m 755 $FSTAB

	cp -r backend${ETC}/* ${ETC}	
	cp backend${INIT} ${INIT}
	cp backend${BIN} ${BIN}
	cp backend${PDINIT} ${PDINIT}
	cp -r backend${INSTALL_DIR}/* ${INSTALL_DIR}

	echo "  [ ADJUSTING OWNERSHIPS ]"
	
	chown -R $APACHE_USER:$APACHE_USER $INSTALL_DIR
	
	chmod 0755 $BIN
	chmod 0755 $INIT
	chmod 0755 ${INSTALL_DIR}/pd/pd.php
		
	echo "  [ UPDATING INIT DAEMON ]"
	
	update-rc.d naspid defaults &>/dev/null
	update-rc.d naspi-pd defaults &>/dev/null
set +x
}

#
# Creates an envars file for minimal configuration sourcing
#
function set_envars() {
#set -x
	echo "  [ SETTING ENVIROMENT VARIABLES ]"
	
	HEAD="\
# This file is created during installation. Please make certain of what 
# you are changing when modifying this file"

	BODY="
# Base install directory
INSTALL_DIR=$INSTALL_DIR

# Location of error code file
ERRORS=$ERRORS
# Non-root user error code
E_ROOT=(${E_ROOT[0]} \"${E_ROOT[1]}\")

# Location of log file
LOG=$LOG

# Apache2 Virtual Host name
SITE=$SITE
# User site runs as
APACHE_USER=$APACHE_USER

# Array of dependancies
DEPENDENCIES=(${DEPENDENCIES[@]})"

	echo -e "$HEAD\n$BODY" > $ENVARS
set +x
}

#
# Create an original backup of fstab
#
function fstab_backup() {
#set -x
	if [[ ! -f $FSTAB/fstab.orignial ]]; then
		echo "  [ CREATING FSTAB BACKUP ]"
		cat /etc/fstab > $FSTAB/fstab.orignial
	fi
set +x
}
#
# Run each function
#
install_dependencies
create_naspi_user
configure_apache
create_empty_directories
place_files
place_backend_files
set_envars
fstab_backup
echo "  [ RESTARTING SERVICES ]"
service apache2 restart &>/dev/null
service naspi-pd restart
cd $START_DIR
echo "  [ NAS-Pi SUCCESSFULLY INSTALLED ]"
