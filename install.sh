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

DEPENDENCIES=( samba smbclient apache2 php5 php5-cli php5-curl apache2-mpm-itk sshfs git curlftpfs netcat-openbsd)

USER="naspi"
#~ WWWUSER="naspi"

WWW="/usr/share/naspi"
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
#
#
function create_naspi_user
{
	if [[ -z $(cat /etc/group | grep $USER) ]]; then
		useradd -M -r -s /bin/bash -U $USER
	fi
}

#
#
#
function configure_apache
{
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
#
#
function place_files
{
	echo "Placing the front end files in $WWW/"
	
	cp -r frontend/cms "$WWW"
	cp -r frontend/modules "$WWW"
	cp -r frontend/public_html "$WWW"
	
	chown -R "naspi:naspi" "$WWW"
	
	chmod 777 "$WWW"/modules/btguru/settings.cfg
	chmod 777 "$WWW"/modules/users/groups.txt
	chmod 755 "$WWW"/modules/files/sources/sourcedata
	
	if [[ ! -e /var/www/nas-pi ]]; then
		ln -s $WWW/public_html /var/www/nas-pi
	fi
}

#
#
#
function configure_fuse
{
	
	if [[ ! -e $FUSE ]]; then
		cp "backend${FUSE}" "${FUSE}"
	else
		compare_files "backend${FUSE}" "${FUSE}"
	fi
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
			chown "$USER:$USER" "$WWW"/${empty}
		fi

	done
	touch $WWW/log/error.log
	chown "$USER:$USER" "$WWW/log/error.log"

}

#
#
#
function place_backend_files
{
	echo "Placing backend files"
	[[ -e $ETC ]] || mkdir -p -m 755 $ETC
	
	cp -r backend${ETC}/* ${ETC}	
	cp backend${INIT} ${INIT}
	cp backend${BIN} ${BIN}
	cp backend${PDINIT} ${INIT}
	
	cp -r backend${WWW}/* ${WWW}
	chmod 0755 $BIN
	chmod 0755 $INIT
	chmod 0755 ${WWW}/pd/pd.php
	chown naspi:naspi ${WWW}/pd -R
	
	update-rc.d naspid defaults
	update-rc.d naspi-pd defaults
}

#
#
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
echo "NAS-Pi has been installed."
