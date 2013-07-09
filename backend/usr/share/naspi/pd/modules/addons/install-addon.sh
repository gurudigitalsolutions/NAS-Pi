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

wget -O $1 ${REPOURL}/addons/downloads/$1

echo "Download complete"
