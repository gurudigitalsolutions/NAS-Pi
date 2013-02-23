#!/bin/bash

# These are the global functions used throughout naspid.

# Make directory if it doesn't exist

function create_missing_directory() {
	#set -x
	if [[ ! -e $1 ]] && [[ ! -d $1 ]]; then
		echo "Created directory: $1"
		mkdir -p "$1"
	fi
	set +x
}

# Run external script to query web interface for data

function get_data() {
	#set -x
	$GET_DATA $1 $2
	set +x
}

# Removes unused share files

function remove_source() {
	echo "Remove source not implemented."
}
