#!/bin/bash
#####################################################################################
#
#	NAS-Pi Add-On Module Install Script
#
#	This script will attempt to download an Add-On Module from the NAS-Pi repo,
#	then extract the archive and install it.
#
#####################################################################################

REPOURL="http://10.42.0.151:3000"
mkdir /tmp/naspi/downloads -p
cd /tmp/naspi/downloads
mkdir $1
cd $1

wget -O $1.tgz ${REPOURL}/addons/download/$1

echo "Download complete"

tar xvfz $1.tgz

echo "Extraction Complete"

/usr/share/naspi/pd/modules/addons/module-installer $1

echo "Installation complete"
