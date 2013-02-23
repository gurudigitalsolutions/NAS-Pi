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
	useradd -d $MEDIA_HOME -m media
	echo "Please enter a password for the media account:"
	passwd media
}

DEPENDANCIES=(apache2 php5 php5-cli sshfs git curlftpfs samba smb-client)

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
	if [[ -z $(diff -q "$BASE"/$APACHE_INSTALL_CONF $APACHE_CONF) ]]; then
		echo "$APACHE_CONF is modified, would you like to overwrite?"
		echo -n " y/n? "
		read OVERWRITE
		case $OVERWRITE in
			y|Y|yes|YES)
				cat "$BASE"/$APACHE_INSTALL_CONF > $APACHE_CONF
				;;
		esac
	fi
}

MEDIA_HOME="/home/media"
PUBLIC_HTML=$MEDIA_HOME/"public_html"

function create_public_html
{
	if [[ ! -e $PUBLIC_HTML ]]; then
		mkdir $PUBLIC_HTML
		chown media:media $PUBLIC_HTML
	fi
	
}

NASPI_HOME=$MEDIA_HOME/"naspi"

function place_files
{
	if [[ ! -e $NASPI_HOME ]]; then
		mkdir $NASPI_HOME
		chown media:media $NASPI_HOME
	fi
	
	#cp -r backend $NASPI_HOME
	cp -r "$BASE"/cms $NASPI_HOME
	cp -r "$BASE"/modules $NASPI_HOME
	cp -r "$BASE"/public_html $MEDIA_HOME
	
	chmod ugo+rw $NASPI_HOME/modules/btguru/settings.cfg
	chmod ugo+rw $NASPI_HOME/modules/users/groups.txt
}

function create_data_directories
{
	DATA_DIRECTORIES=("modules/users/accounts" "modules/users/sessions" "modules/files/sources/data"
	
	for each_data_dir in ${DATA_DIRECTORIES[@]}; do

		if [[ ! -e $NASPI_HOME/${each_data_dir} ]]; then
			mkdir $NASPI_HOME/${each_data_dir}
			chmod ugo+rwx $NASPI_HOME/${each_data_dir}
			chown media:media $NASPI_HOME/${each_data_dir}
		fi

	done
	
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
