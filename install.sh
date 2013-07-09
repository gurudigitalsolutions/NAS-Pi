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

DEPENDENCIES=( samba smbclient apache2 php5 php5-cli php5-curl apache2-mpm-itk sshfs git curlftpfs netcat-openbsd)

APACHE_USER="naspi"
INSTALL_DIR="/usr/share/naspi"

SITE="/etc/apache2/sites-available/nas-pi"

LOG="/var/log/naspid.log"

ETC="/etc/naspi"
INIT="/etc/init.d/naspid"
BIN="/usr/bin/naspid"

PDINIT="/etc/init.d/naspi-pd"
PDBIN="/usr/share/naspi/pd/pd.php"
FUSE="/etc/fuse.conf"

BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

ERRORS=$INSTALL_DIR/errors

ENVARS=$ETC/envars

EMPTY_DIR=("log" "modules/users/accounts" "modules/users/sessions" "modules/files/sources/data" )

#-----------------------------------------------------------------------
#
# Begin installer
#
#-----------------------------------------------------------------------

echo "NAS-Pi Installer"
echo "Copyright 2013 Guru Digital Solutions"

START_DIR=$(pwd)

cd $BASE

. $ERRORS

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
	echo "  [ CHECKING DEPENDANCIES ]"
	for dep in ${DEPENDENCIES[@]}; do

		dpkg -s $dep 2>/dev/null >/dev/null
		if [[ $? = 1 ]];then
			need="$need$dep "
			[[ $dep = apache2 ]]&& export default=TRUE
		fi
	done

	if [[ -n $need ]];then
		apt-get install $need
	
		if [[ $? = 1 ]];then
			unset default
			echo -e "${E_DEP[0]} $need"
			exit ${E_DEP[1]}
		fi
	fi
}

#
# Compare two files for any differences. If differences are found then
# has dialoge for replacing, backup, or ignoring
#
function compare_files
{
	if [[ $(diff -q $1 $2 2>/dev/null) ]]; then
		
		echo "$2 may need to be configured, do that now?"
		echo -n " [y]es [n]o [m]ove to .old ? "
		read response
		
		case $response in
			
			[y,Y]|[y,Y][e,E][s,S])
				cat $1 > $2
				$3;$4;$5
				;;

			m|M)
				mv $2 $2.old
				cat $1 > $2
				$3;$4;$5
				;;				
		esac	
	fi
}

#
# Creates the user which Apache2 will run as
#
function create_naspi_user
{
	echo "  [ CREATING APACHE2 SYSTEM USER ]"
	if [[ -z $(cat /etc/group | grep $APACHE_USER) ]]; then
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
		cp backend${SITE} $SITE
		a2ensite nas-pi
	fi

	if [[ $default = TRUE ]];then
		unset default
		a2dissite 000-default
	
	elif [[ -e /etc/apache2/sites-enabled/000-default ]]; then
		echo -e "\nNOTICE: 000-default is already configured and or installed NAS-Pi"
		echo "requires \, in order for that to work with other sites you will"
		echo "to use NamedVirtualHost for other sites once NAS-Pi is installed"
		echo -n "Disable 000-default [y]es [n]o ? "
		read configure
	
		case $configure in
			y|Y|yes|YES)
				a2dissite 000-default
				a2ensite nas-pi
				;;
			*)
				a2ensite nas-pi
				echo "Manually configure apache2's sites-avalible"
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
	echo "  [ CREATING FRONTEND FOLDERS ]"
	for empty in ${EMPTY_DIR[@]};do
		if [[ ! -e $INSTALL_DIR/$empty ]]; then
			mkdir -p -m 755 $INSTALL_DIR/$empty
			chown $APACHE_USER:$APACHE_USER $INSTALL_DIR/$empty
		fi
	done
}

#
# Places all the frontend files into the install location
#
function place_files
{
	echo "  [ PLACING FRONTEND FILES INTO $INSTALL_DIR/ ]"
	
	cp -r frontend/cms "$INSTALL_DIR"
	cp -r frontend/modules "$INSTALL_DIR"
	cp -r frontend/public_html "$INSTALL_DIR"
	
	chown -R "naspi:naspi" "$INSTALL_DIR"
	
	#chmod 777 "$INSTALL_DIR"/modules/btguru/settings.cfg
	chmod 777 "$INSTALL_DIR"/modules/users/groups.txt
	chmod 755 "$INSTALL_DIR"/modules/files/sources/sourcedata
	
	if [[ ! -e /var/www/nas-pi ]]; then
		echo "  [ LINKING $INSTALL_DIR/public_html to /var/www/nas-pi ]"
		ln -s $INSTALL_DIR/public_html /var/www/nas-pi
	fi
}

#
# Enables users to use fuse mounts
#
function configure_fuse
{
	echo "  [ CONFIGURING FUSE ]"
	
	if [[ ! -e $FUSE ]]; then
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
	echo "  [ PLACING BACKEND FILES ]"
	
	[[ -e $ETC ]] || mkdir -p -m 755 $ETC
	
#	TODO

	#set -f;IFS=$n
	#list=($(ls backend${ETC} -R) $(ls backend${INSTALL_DIR})
	#list=($list[@] backend${INIT} backend${BIN} backend${PDINIT}
	#unset IFS;set +f
	#
	#for each in ${list[@]};do
		#if [[ X$(echo $each|grep :) != X ]];then
			#path=${each%:}
		#elif [[ $each != naspid.conf ]]||[[ ;then
			#if [[ -f $path/$each ]]
				#echo "cp $path/$each ${path#$BASE/backend}/$each"
		#fi
	#done
	
#	TODO	

	cp -r backend${ETC}/* ${ETC}	
	cp backend${INIT} ${INIT}
	cp backend${BIN} ${BIN}
	cp backend${PDINIT} ${PDINIT}
	cp -r backend${INSTALL_DIR}/* ${INSTALL_DIR}

	echo "  [ ADJUSTING OWNERSHIPS OF BACKEND FILES ]"
	
	chmod 0755 $BIN
	chmod 0755 $INIT
	chmod 0755 ${INSTALL_DIR}/pd/pd.php
	chown naspi:naspi ${INSTALL_DIR}/pd -R
	
	echo "  [ UPDATING INIT DAEMON ]"
	
	update-rc.d naspid defaults
	update-rc.d naspi-pd defaults
}

#
# Creates an envars file for minimal configuration sourcing
#
function set_envars() {
	#set -x
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
# Run each function
#
install_dependencies
create_naspi_user
configure_apache
place_files
create_empty_directories
place_backend_files
set_envars
service apache2 restart
service naspid restart
cd $START_DIR
echo "  [ NAS-Pi SUCCESSFULLY INSTALLED ]"
