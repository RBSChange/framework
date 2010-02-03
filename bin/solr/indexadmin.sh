#!/bin/bash
prog=${0##*/}
USAGE="
usage: $prog --clear[-backoffice|-frontoffice] | --import[-backoffice|-frontoffice] | --reset[-backoffice|-frontoffice] | --rebuild-spell | --optimize"
# parse args
if [[ "$#" -gt "0"  ]] 
then 
	while [ "$#" -gt "0" ]
	do
    	case $1 in
            --clear-frontoffice)
            	CLEARFRONT=1
            	;;
            --clear-backoffice)
            	CLEARBACK=1
            	;;
            --clear)
            	CLEARFRONT=1
            	CLEARBACK=1
            	;;
            --reset-frontoffice)
            	CLEARFRONT=1
            	IMPORTFRONT=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;
           --reset-backoffice)
            	CLEARBACK=1
            	IMPORTBACK=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;
           --reset)
            	CLEARFRONT=1
            	CLEARBACK=1
            	IMPORTFRONT=1
            	IMPORTBACK=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;
            --import-frontoffice)
            	IMPORTFRONT=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;
            --import-backoffice)
            	IMPORTBACK=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;
            --import)
                IMPORTFRONT=1
            	IMPORTBACK=1
            	REBUILDSPELL=1
            	OPTIMIZE=1
            	;;  
            --rebuild-spell)
            	REBUILDSPELL=1
            	;;
            --optimize)
            	OPTIMIZE=1
            	;;          	
        	*)
            	echo $USAGE
            	exit 1
            	;;
    	esac
    	shift
	done
	if [[ $CLEARFRONT == 1 ]]
	then
		php ${0%/*}/manageIndex.php --clear-frontoffice
		echo "-> Frontoffice Solr Index cleared"
	fi
	if [[ $CLEARBACK == 1 ]]
	then
		php ${0%/*}/manageIndex.php --clear-backoffice
		echo "-> Backoffice Solr Index cleared"
	fi
	if [[ $IMPORTFRONT == 1 ]]
	then
		echo "-> Frontoffice Indexing started..."
		php ${0%/*}/manageIndex.php --indexallfrontoffice
		echo "-> Indexing done!"
	fi
	if [[ $IMPORTBACK == 1 ]]
	then
		echo "-> Backoffice Indexing started..."
		php ${0%/*}/manageIndex.php --indexallbackoffice
		echo "-> Indexing done!"
	fi
	if [[ $OPTIMIZE == 1 ]]
	then
		echo -n "-> Optimizing index ... "
		php ${0%/*}/manageIndex.php --optimize
		echo " done"
	fi
	if [[ $REBUILDSPELL == 1 ]]
	then
		echo -n "-> Rebuilding SpellcheckIndex ... "
		php ${0%/*}/manageIndex.php --rebuildspell
		echo " done"
	fi
	exit 0
fi
echo $USAGE
exit 1
