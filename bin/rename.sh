#!/bin/bash

# Requires tempfile binary

if [ $# -lt 2 ]; then
	echo "Usage: $0 <from> <to> [dir1 .. dir2]"
	exit 1
fi

from=$1
to=$2
shift 2

if [ $# -ne 0 ]; then
	dirs=$@	
else
	dirs="framework modules"
fi

temp=$(tempfile)

for i in $(find -L $dirs |grep -v .svn)
do
	grep $from $i 1>/dev/null 2>&1 
	if [ $? -eq 0 ]; then
		# some security, so we can apply rename multiple times
		sed "s/$to/$from/g" $i > $temp
		sed "s/$from/$to/g" $temp > $i

		echo "$i modified"
	fi
done
