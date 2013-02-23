#!/bin/bash

TEMP="/tmp/naspid.temp"
PORT=31415
echo "$@" > $TEMP && nc 127.0.0.1 $PORT < $TEMP && rm $TEMP
