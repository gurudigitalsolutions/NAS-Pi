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
INSTALL="/usr/share/naspi"

SITE="/etc/apache2/sites-available/nas-pi"

LOG="/var/log/naspid.log"

ETC="/etc/naspi"
INIT="/etc/init.d/naspid"
BIN="/usr/bin/naspid"
FSTAB="/etc/fstab.d/"

PDINIT="/etc/init.d/naspi-pd"
PDBIN="/usr/share/naspi/pd/pd.php"
FUSE="/etc/fuse.conf"
TMP="/tmp/nas-pi-install"

BASE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

ERRORS="$INSTALL/errors"

ENVARS="$ETC/envars"

EMPTY_DIR="$INSTALL log modules/users/accounts modules/users/sessions modules/files/sources/data"

#-----------------------------------------------------------------------
#
# Functions
#
#-----------------------------------------------------------------------
install_dependencies() # Checks dependancies and installs any missing
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
		${T}apt-get install $need$t
	
		if [[ $? -eq 1 ]];then
			echo "[ERROR $E_DEP[0]}] ${E_DEP[1]} $need"
			exit ${E_DEP[0]}
		fi
	fi
set +x
}


#-----------------------------------------------------------------------
compare_files () # Compare two files for any differences. If differences 
# are found then has dialoge for replacing, backup, or ignoring
{
	if [[ $(diff -q $1 $2 2>/dev/null) ]]; then
		
		echo -e "  [ NOTICE ]\n$2 may need to be configured, do that now?"
		echo -n " [y]es [n]o [m]ove to .old ? "
		read response
		
		case $response in
			
			[y,Y]|[y,Y][e,E][s,S])
				echo "  [ CONFIGURING FUSE FOR USERS ]"
				${T}cat $1 $t> $2
				$3;$4;$5
				;;

			m|M)
				${T}mv $2 $2.old$t
				${T}cat $1 $t> $2
				$3;$4;$5
				;;
			*)
				echo "  [ WARNING ] You must make a valid choice!"
				compare_files "$@"
				;;
		esac	
	fi
}

#-----------------------------------------------------------------------
create_naspi_user() # Creates the user which Apache2 will run as
{
	if [[ -z $(cat /etc/group | grep $APACHE_USER) ]]; then
		echo "  [ CREATING APACHE2 SYSTEM USER ]"
		${T}useradd -M -r -s /bin/bash -U $APACHE_USER$t
	fi
}

#-----------------------------------------------------------------------
configure_apache() # Places virtual host file and enables the site
{
	echo "  [ CONFIGURING APACHE2 ]"
	
	if [[ ! -e $SITE ]]; then
		echo "  [ ADDING NAS-Pi TO SITES-AVAILIBLE ]"
		${T}cp backend${SITE} $SITE$t
		${T}a2ensite nas-pi $t&>/dev/null
	fi

	if [[ $default = TRUE ]];then
		unset default
		${T}a2dissite 000-default $t&>/dev/null
	
	elif [[ -e /etc/apache2/sites-enabled/000-default ]]; then
		echo -e "\n  [ NOTICE ]\n 000-default is already configured and installed on apache2."
		echo "NAS-Pi requires / in a virtualhost. In order for NAS-Pi to work with other sites you will"
		echo "to use a NamedVirtualHost for other sites or alternate IPs once NAS-Pi is installed"
		echo -n " [ DISABLE? ] 000-default [y]es [n]o ? "
		read configure
	
		case $configure in
			y|Y|yes|YES)
				${T}a2dissite 000-default $t&>/dev/null
				${T}a2ensite nas-pi $t&>/dev/null
				;;
			*)
				${T}a2ensite nas-pi$t
				echo "Manually configure apache2\'s sites-avalible"
				;;
		esac
	fi

	compare_files "backend${SITE}" "$SITE" "a2ensite nas-pi"
}

#-----------------------------------------------------------------------
create_empty_directories() # Creates/modifies frontend folders
{
#set -x
	for empty in $EMPTY_DIR;do
		if [[ ! -e $INSTALL/$empty ]]; then
			echo "  [ CREATING $INSTALL/$empty ]"
			${T}mkdir -p -m 755 $INSTALL/$empty	$t
		fi
	done
}

place_files() # Places all the frontend files into the install location
{
#set -x
	echo "  [ PLACING FRONTEND FILES INTO $INSTALL/ ]"
	
	${T}cp -r frontend/cms "$INSTALL"$t
	${T}cp -r frontend/modules "$INSTALL"$t
	${T}cp -r frontend/public_html "$INSTALL"$t
		
	echo "  [ ADJUSTING FILE/FOLDER PERMISSIONS ]"
	${T}chmod 777 "$INSTALL"/modules/users/groups.txt$t
	${T}chmod 755 "$INSTALL"/modules/files/sources/sourcedata$t

	if [[ ! -e /var/www/nas-pi ]]; then
		echo "  [ LINKING $INSTALL/public_html to /var/www/nas-pi ]"
		${T}ln -s $INSTALL/public_html /var/www/nas-pi$t
	fi
set +x
}

#-----------------------------------------------------------------------
configure_fuse() # Enables users to use fuse mounts
{
	if [[ ! -e $FUSE ]]; then
		echo "  [ CONFIGURING FUSE FOR USERS ]"
		${T}mv $FUSE $FUSE-bak$t
		${T}cp "backend${FUSE}" "${FUSE}"$t
		${T}chmod 540 $FUSE$t
	else
		compare_files "backend${FUSE}" "${FUSE}"
	fi
}

#-----------------------------------------------------------------------
place_backend_files () # Places all backend files and create apache error log
{
#set -x
	echo "  [ PLACING BACKEND FILES IN /etc and /usr ]"
	
	[[ -e $ETC ]] || mkdir -p -m 755 $ETC
	[[ -e $FSTAB ]] || mkdir -p -m 755 $FSTAB

	${T}cp -r backend${ETC}/* ${ETC}	$t
	${T}cp backend${INIT} ${INIT}$t
	${T}cp backend${BIN} ${BIN}$t
	${T}cp backend${PDINIT} ${PDINIT}$t
	${T}cp -r backend${INSTALL_DIR}/* ${INSTALL_DIR}$t

	echo "  [ ADJUSTING OWNERSHIPS ]"
	
	${T}chown -R $APACHE_USER:$APACHE_USER $INSTALL$t
	
	${T}chmod 0755 $BIN$t
	${T}chmod 0755 $INIT$t
	${T}chmod 0755 ${INSTALL_DIR}/pd/pd.php$t
		
	echo "  [ UPDATING INIT DAEMON ]"
	
	${T}update-rc.d naspid defaults $t&> /dev/null 
	${T}update-rc.d naspi-pd defaults $t&> /dev/null 
set +x
}

#-----------------------------------------------------------------------
set_envars()  # Creates an envars file for minimal configuration sourcing
{
#set -x
	echo "  [ SETTING ENVIROMENT VARIABLES ]"

	set -f;IFS=$'\n'
	a=($(cat $1))
	c=${#a[@]}
	unset IFS;set +f

	exec 3<$1
	t=$(grep -c '^' $1)
	c=0

	read -u3 line
	until [[ $c -eq $t ]];do
		if [[ x$(echo $line|grep '^#') != x ]];then
			Body="$Body$line \n"
		else
			Body="$Body$(eval "echo \"$line\"") \n"
		fi
		((c++))
		read -u3 line
	done
	exec 3<&-

	${T}echo -e "Body" $t > $ENVARS
set +x
}

#-----------------------------------------------------------------------
fstab_backup()  # Create an original backup of fstab
{
#set -x
	if [[ ! -f $FSTAB/fstab.orignial ]]; then
		echo "  [ CREATING FSTAB BACKUP ]"
		${T}cp /etc/fstab $FSTAB/fstab.orignial$t
	fi
set +x
}

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

if [[ $1 == '--test' ]];then
	echo "  [ Test Mode ]"
	T=echo\ \'
	t=\'
fi

if [[ $(id -u) != 0 ]]&&[[ $(id -g) != 0 ]]; then
	echo "[ERROR $E_ROOT[0]}] ${E_ROOT[1]}"
	${T}exit "${E_ROOT[0]}"$T
fi
install_dependencies
create_naspi_user
configure_apache
create_empty_directories
place_files
place_backend_files
set_envars envars
fstab_backup
echo "  [ RESTARTING SERVICES ]"
${T}service apache2 restart $t&>/dev/null
${T}service naspi-pd restart$t
cd $START_DIR
echo "  [ NAS-Pi SUCCESSFULLY INSTALLED ]"
