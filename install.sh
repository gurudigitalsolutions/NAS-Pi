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

E_NOTROOT = 1
E_WRONGDIR = 2

echo "NAS-Pi Installer"
echo "Copyright 2013 Guru Digital Solutions"

if [[ $(id -u) != 0 ]]; then
	echo "You must run this script as root."
	exit $E_NOTROOT
fi

if [[ ! -d backend ]]; then
	echo "You must run this script in the directory that contains the NAS-Pi data."
	exit $E_WRONGDIR
fi

if [[ ! -d cms ]]; then
	echo "You must run this script in the directory that contains the NAS-Pi data."
	exit $E_WRONGDIR
fi

if [[ ! -d modules ]]; then
	echo "You must run this script in the directory that contains the NAS-Pi data."
	exit $E_WRONGDIR
fi

if [[ !-d public_html ]]; then
	echo "You must run this script in the directory that contains the NAS-Pi data."
	exit $E_WRONGDIR
fi

#####################################################################################


	
#Create data directories:
	#exit
	#mkdir naspi/modules/files/sources/data
	#sudo chmod ugo+rwx naspi/modules/files/sources/data
	#mkdir naspi/modules/users/accounts
	#mkdir naspi/modules/users/sessions
	#sudo chmod ugo+rwx naspi/modules/users/accounts
	#sudo chmod ugo+rwx naspi/modules/users/sessions

#Create backend directories:
	#sudo mkdir /etc/naspi

#Copy backend config files:
	#cd ~/naspi
	#sudo cp -r backend/etc/naspi/* /etc/naspi
	#sudo cp backend/etc/init.d/naspid /etc/init.d/naspid
	#sudo cp backend/usr/bin/naspid /usr/bin/naspid
	
	
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
	apt-get install apache2 php5 php5-cli samba sshfs git
}

function configure_fuse
{
	echo -e "# mount_max = 1000\nuser_allow_other" > /etc/fuse.conf
}

function configure_apache
{
	if [[ -e /etc/apache2/sites-available/default ]]; then
		rm /etc/apache2/sites-available/default
	fi
	
	cp backend/etc/apache2/sites-available/default /etc/apache2/sites-available/default
	
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
	
	chmod ugo+rw /home/media/naspi/modules/btguru/settings.cfg
	
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
