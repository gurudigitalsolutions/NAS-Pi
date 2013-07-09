#!/usr/bin/expect -f

set timeout -1
spawn smbclient -L //10.42.0.100/ -U media
set pass "poop"
expect {
	password: {send "$pass\r"; exp_continue}
	eof exit
}
