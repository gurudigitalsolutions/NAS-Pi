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

DEPENDENCIES=( samba smbclient apache2 php5 php5-cli sshfs git curlftpfs netcat-openbsd)

EMPTY_DIR=("$WWW-ROOT/log" "modules/users/accounts" "modules/users/sessions" "modules/files/sources/data" )

USER="naspid"

WWW="/usr/share/naspi"
SITE="/etc/apache2/sites-available/nas-pi"
ETC="/etc/naspi"
INIT="/etc/init.d/naspid"
BIN="/usr/bin/naspid"
FUSE="\
# Set the maximum number of FUSE mounts allowed to non-root users.
# The default is 1000.
#
#mount_max = 1000

# Allow non-root users to specify the 'allow_other' or 'allow_root'
# mount options.
#
user_allow_other"

BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
E_ROOT=("\nYou must run this script as root.\n" "10")
E_DEP=("\nYou have unmet dependancies.\nUse apt-get install " "11")


EMPTY_DIR=("modules/users/accounts" "modules/users/sessions" "modules/files/sources/data" )

#####################################################################################
echo "NAS-Pi Installer"
echo "Copyright 2013 Guru Digital Solutions"

START_DIR=$(pwd)

cd $BASE

# Test user and group id for root
[[ $(id -u) != 0 ]]&&[[ $(id -g) != 0 ]]&& echo -e "${E_ROOT[0]}"&& exit "${E_ROOT[1]}"

#####################################################################################

#
#
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
#
#
function configure_apache
{
	if [[ ! -e $SITE ]]; then
		echo "Creating $SITE"
		cp backend${SITE} $SITE
		a2ensite nas-pi
	fi

	if [[ $default = TRUE ]];then
		unset default
		a2dissite 000-default
	
	elif [[ -e /etc/apache2/sites-enabled/000-default ]]; then
		echo -e "\nNOTICE: 000-default is already configured"
		echo "Answering no will require you to configure your own NaneVirtualHost file"
		echo -n "Would you like to disable 000-default y/n? "
		read configure
	
		case $configure in
			y|Y|yes|YES)
				a2dissite 000-default
				a2ensite nas-pi
				service apache2 restart
				;;
			*)
				a2ensite nas-pi
				echo "Manually configure apache2's sites-avalible"
				;;
		esac
	fi


	
	if [[ $(diff -q backend${SITE} $SITE 2>/dev/null) ]]; then
		echo "$SITE is modified, would you like to overwrite?"
		echo -n " y=overwrite/n=do nothing/m=move to .old? "
		read OVERWRITE
		
		case $OVERWRITE in
			y|Y|yes|YES)
				cat backend${SITE} > ${SITE}
				a2ensite nas-pi
				service apache2 restart
				;;

			m|M)
				mv $SITE $SITE.old
				a2ensite nas-pi
				service apache2 restart
				;;				
		esac	
	fi
}

#
#
#
function place_files
{
	echo "Placing the front end files in $WWW/"
	
	cp -r frontend/cms "$WWW"
	cp -r frontend/modules "$WWW"
	cp -r frontend"$WWW/public_html" $WWW
	touch $WWW/log/error.log
	
	chown -R "root:www-data" "$WWW/public_html"
	chown "root:www-data" "$WWW/log/error.log"
	
	chmod 777 "$WWW"/modules/btguru/settings.cfg
	chmod 777 "$WWW"/modules/users/groups.txt
	chmod 755 "$WWW"/modules/files/sources/sourcedata
}

#
#
#
function configure_fuse
{
	echo "$FUSE" > /etc/fuse.conf
}


#
#
#
function create_empty_directories
{	
	echo "Creating empty directories in $WWW"
	for empty in ${EMPTY_DIR[@]}; do
		
		if [[ ! -e "$WWW"/${empty} ]];then
			mkdir -p -m 775 "$WWW"/${empty}
			chown "ROOT":"www-data" "$WWW"/${empty}
		fi

	done
	

}

#
#
#
function place_backend_files
{
	echo "Placing backend files"
	[[ -e $ETC ]]|| mkdir -p -m 755 $ETC
	if [[ ! -e $WWW/logs ]]; then
		mkdir -p -m 775 $WWW/logs
		chown "root":"www-data" $WWW/logs
	fi
	
	cp -r backend${ETC}/* ${ETC}	
	cp backend${INIT} ${INIT}
	cp backend${BIN} ${BIN}
	
	chmod 0755 $BIN
	chmod 0755 $INIT
	
	update-rc.d naspid defaults
}

#
#
#
install_dependencies
configure_apache
place_files
create_empty_directories
place_backend_files
service naspid start
cd $START_DIR
echo "NAS-Pi has been installed."
