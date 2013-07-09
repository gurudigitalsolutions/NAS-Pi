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
LOCATION="/usr/share/naspi"

SITE="/etc/apache2/sites-available/nas-pi"
ETC="/etc/naspi"
INIT="/etc/init.d/naspid"
BIN="/usr/bin/naspid"

PDINIT="/etc/init.d/naspi-pd"
PDBIN="/usr/share/naspi/pd/pd.php"
FUSE="/etc/fuse.conf"

BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
E_ROOT=("\nYou must run this script as root.\n" "10")
E_DEP=("\nYou have unmet dependancies.\nUse apt-get install " "11")


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

#
# Test user and group id for root
#
if [[ $(id -u) != 0 ]]&&[[ $(id -g) != 0 ]]; then
	echo -e "${E_ROOT[0]}"
	exit "${E_ROOT[1]}"
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
	if [[ -z $(cat /etc/group | grep $APACHE_USER) ]]; then
		useradd -M -r -s /bin/bash -U $APACHE_USER
	fi
}

#
# Places the naspid virtual host file and enables the site
#
function configure_apache
{
	echo "[CONFIGURING APACHE2]"
	
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
# Places all the frontend files into the install location
#
function place_files
{
	echo "[PLACING FRONTEND FILES INTO $LOCATION/]"
	
	cp -r frontend/cms "$LOCATION"
	cp -r frontend/modules "$LOCATION"
	cp -r frontend/public_html "$LOCATION"
	
	chown -R "naspi:naspi" "$LOCATION"
	
	chmod 777 "$LOCATION"/modules/btguru/settings.cfg
	chmod 777 "$LOCATION"/modules/users/groups.txt
	chmod 755 "$LOCATION"/modules/files/sources/sourcedata
	
	if [[ ! -e /var/www/nas-pi ]]; then
		ln -s $LOCATION/public_html /var/www/nas-pi
	fi
}

#
# Enables users to use fuse mounts
#
function configure_fuse
{
	echo "[CONFIGURING FUSE]"
	
	if [[ ! -e $FUSE ]]; then
		cp "backend${FUSE}" "${FUSE}"
	else
		compare_files "backend${FUSE}" "${FUSE}"
	fi
}


#
# Creates all necessary install directories
#
function create_empty_directories ()
{	
	echo "[CREATING EMPTY FRONTEND DIRECTORIES]"
	
	for empty in ${EMPTY_DIR[@]}; do
		
		if [[ ! -e "$LOCATION"/${empty} ]];then
			echo "creating $LOCATION/${empty}"
			mkdir -p -m 775 "$LOCATION"/${empty}
			chown "$APACHE_USER:$APACHE_USER" "$LOCATION"/${empty}
		fi

	done


}

#
# Places all backend files and create apache error log
#
function place_backend_files
{
	echo "[PLACING BACKEND FILES]"
	
	[[ -e $ETC ]] || mkdir -p -m 755 $ETC
	
	cp -r backend${ETC}/* ${ETC}	
	cp backend${INIT} ${INIT}
	cp backend${BIN} ${BIN}
	cp backend${PDINIT} ${PDINIT}
	cp -r backend${LOCATION}/* ${LOCATION}

	echo "[ADJUSTING OWNERSHIPS OF BACKEND FILES]"
	
	chmod 0755 $BIN
	chmod 0755 $INIT
	chmod 0755 ${LOCATION}/pd/pd.php
	chown naspi:naspi ${LOCATION}/pd -R
	
	echo "[UPDATING INIT DAEMON]"
	
	update-rc.d naspid defaults
	update-rc.d naspi-pd defaults
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
service apache2 restart
service naspid restart
cd $START_DIR
echo "[NAS-Pi SUCCESSFULLY INSTALLED]"
