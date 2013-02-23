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

BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

E_NOTROOT=("1" "You must run this script as root.")
E_WRONGDIR=("2" "You must run this script in the directory that contains the NAS-Pi data.")

DEPENDANCIES=(apache2 php5 php5-cli sshfs git curlftpfs samba smb-client)

echo "NAS-Pi Installer"
echo "Copyright 2013 Guru Digital Solutions"

if [[ $(id -u) != 0 ]]; then
	echo ${E_NOTROOT[1]}
	exit ${E_NOTROOT[0]}
fi

DIRS_TO_CHECK=(backend cms modules public_html)

for each_dir in ${DIRS_TO_CHECK[@]}; do
	
	if [[ ! -d  $each_dir ]]; then
		echo ${E_WRONGDIR[1]}
		exit ${E_WRONGDIR[0]}
	fi
done

#####################################################################################
	
function create_media_account
{
	useradd -d /home/media -m media
	echo "Please enter a password for the media account:"
	passwd media
}

function install_dependencies
{
	echo "Installing dependencies for NAS-Pi"
	apt-get update
	apt-get install ${DEPENDANCIES[@]}
}

function configure_fuse
{
	echo -e "# mount_max = 1000\nuser_allow_other" > /etc/fuse.conf
}

function configure_apache
{
	APACHE_INSTALL_CONF="backend/etc/apache2/sites-available/default"
	APACHE_CONF="/etc/apache2/sites-available/default"
	if [[ diff -q "$BASE_DIR"/$APACHE_INSTALL_CONF $APACHE_CONF]]; then
		echo "$APACHE_CONF is modified, would you like to overwrite?"
		echo -n " y/n? "
		read OVERWRITE
		case $OVERWRITE in
			y|Y|yes|YES)
				cat "$BASE_DIR"/$APACHE_INSTALL_CONF > $APACHE_CONF
					;;
		esac
	fi
}

function create_public_html
{
	if [[ ! -e /home/media/public_html ]]; then
		mkdir /home/media/public_html
		chown media:media /home/media/public_html
	fi
	
}

function place_files
{
	if [[ ! -e /home/media/naspi ]]; then
		mkdir /home/media/naspi
		chown media:media /home/media/naspi
	fi
	
	#cp -r backend /home/media/naspi
	cp -r cms /home/media/naspi
	cp -r modules /home/media/modules
	cp -r public_html /home/media
}

function create_data_directories
{
	if [[ ! -e /home/media/naspi/modules/users/accounts ]]; then
		mkdir /home/media/naspi/modules/users/accounts
	fi
	if [[ ! -e /home/media/naspi/modules/users/sessions ]]; then
		mkdir /home/media/naspi/modules/users/sessions
	]]
	chown media:media /home/media/naspi/modules/users/accounts
	chown media:media /home/media/naspi/modules/users/sessions
	chmod ugo+rwx /home/media/naspi/modules/users/accounts
	chmod ugo+rwx /home/media/naspi/modules/users/sessions
	
	if [[ ! -e /home/media/naspi/modules/files/sources/data ]]; then
		mkdir /home/media/naspi/modules/files/sources/data
	fi
	chown media:media /home/media/naspi/modules/files/sources/data
	chmod ugo+rwx /home/media/naspi/modules/files/sources/data
	
	if [[ ! -e /etc/naspi ]]; then
		mkdir /etc/naspi
	fi
	
}

function place_backend_files
{
	cp -r backend/etc/naspi/* /etc/naspi
	chown root:root /etc/naspi -r
	
	cp backend/etc/init.d/naspid /etc/init.d/naspid
	chmod 0755 /etc/init.d/naspid
	
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
create_public_html
place_files
create_data_directories
place_backend_files
restart_deamons

echo "NAS-Pi has been installed."
