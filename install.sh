#!/bin/bash
#
#	NAS-Pi Installation Script
#
###############################################################################
#
#	Copyright 2013, Brian Murphy
#	www.gurudigitalsolutions.com
#
###############################################################################


BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

E_NOTROOT=("1" "You must run this script as root.")
E_WRONGDIR=("2" "You must run this script in the directory that contains the NAS-Pi data.")

USER_OWNER='media:media'

ROOT_OWNER='ROOT_OWNER'

DIRS_TO_CHECK=(backend cms modules public_html)

DEPENDANCIES=(apache2 php5 php5-cli sshfs git curlftpfs samba smbclient)

MEDIA_HOME="/home/media"

PUBLIC_HTML=$MEDIA_HOME/"public_html"

NASPI_HOME=$MEDIA_HOME/"naspi"

DATA_DIRECTORIES=("modules/users/accounts" "modules/users/sessions" "modules/files/sources/data"

SITES_AVAILABLE="/etc/apache2/sites-available"

DEFAULT_SITE=$SITES_AVAILABLE"/default"

ETC_PATH="/etc/naspi"

INIT="backend/etc/init.d/naspid"
INIT_PATH="/etc/init.d/naspid"
#####################################################################################
echo "NAS-Pi Installer"
echo "Copyright 2013 Guru Digital Solutions"

if [[ $(id -u) != 0 ]]; then
	echo ${E_NOTROOT[1]}
	exit ${E_NOTROOT[0]}
fi

#####################################################################################
	
function create_media_account
{
	if [[ ! -e $NASPI_HOME ]]; then
		mkdir $NASPI_HOME
		chown $USER_OWNER $NASPI_HOME
	fi
	
	useradd -d $MEDIA_HOME -m media
	echo "Please enter a password for the media account:"
	passwd media
}

function install_dependencies
{
	for each_depend in ${DEPENDANCIES[@]}; do

		if [[ -z $(dpkg -l | grep " $each_depend ") ]]; then
			INSTALL=(${INSTALL[@]} $each_depend)
		else
			echo "Skipping $each_depend, package already installed"
		fi
	
	done
	
	if [[ ${#INSTALL[@]} -gt 0 ]]; then
		echo "Installing dependencies for NAS-Pi"
		apt-get update
		apt-get install ${INSTALL[@]}
	else
		echo "All dependencies for NAS-Pi met"
	fi
}

function configure_fuse
{
	echo -e "# mount_max = 1000\nuser_allow_other" > /etc/fuse.conf
}

function configure_apache
{
	if [[ -d ${SITES_AVAILABLE} ]]; then
		
		if [[ -z $(diff -q ${BASE}${DEFAULT_SITE} ${DEFAULT_SITE}) ]]; then
			echo "${DEFAULT_SITE} is modified, would you like to overwrite?"
			echo -n " y/n? "
			read OVERWRITE
			
			case $OVERWRITE in
				y|Y|yes|YES)
					cat ${BASE}${DEFAULT_SITE} > ${DEFAULT_SITE}
					;;
			esac
		
		else
			cat ${BASE}${DEFAULT_SITE} > ${DEFAULT_SITE}
		fi
	
	else
		
		mkdir $SITES_AVAILABLE
		cp ${BASE}${DEFAULT_SITE} > ${DEFAULT_SITE}
	
	fi
}

function place_files
{
	#cp -r backend $NASPI_HOME
	cp -r "$BASE"/cms $NASPI_HOME
	cp -r "$BASE"/modules $NASPI_HOME
	cp -r "$BASE"/public_html $MEDIA_HOME
	
	chown $USER_OWNER $PUBLIC_HTML
	chmod ugo+rw $NASPI_HOME/modules/btguru/settings.cfg
	chmod ugo+rw $NASPI_HOME/modules/users/groups.txt
}

function create_data_directories
{	
	for each_data_dir in ${DATA_DIRECTORIES[@]}; do

		if [[ ! -e $NASPI_HOME/${each_data_dir} ]]; then
			mkdir $NASPI_HOME/${each_data_dir}
			chmod ugo+rwx $NASPI_HOME/${each_data_dir}
			chown $USER_OWNER $NASPI_HOME/${each_data_dir}
		fi

	done
	

}

function place_backend_files
{
	if [[ ! -e ${ETC_PATH} ]]; then
		mkdir ${ETC_PATH}
	fi
	
	cp -r backend${ETC_PATH}/* ${ETC_PATH}	
	cp backend/${INIT_PATH} ${INIT_PATH}
	
	chmod 0755 ${INIT_PATH}
	
	update-rc.d naspid defaults
}

function restart_daemons
{
	service apache2 restart
	service naspid start
}

#Create 'media' user:
[[ $(grep "^media:" /etc/passwd) ]] || create_media_account

#Install dependencies:
install_dependencies
configure_fuse
configure_apache
place_files
create_data_directories
place_backend_files
restart_deamons

echo "NAS-Pi has been installed."
