#!/bin/bash
export d=$(date +"%Y.%m.%d.%H.%M")
while test $# -gt 0; do
    case "$1" in
        -f)
            shift
            if test $# -gt 0; then
                export FILE=$1
            else
                exit 1
            fi
            shift
            ;;
        -e)
            shift
            if test $# -gt 0; then
                export EXCLUDE=''
                IFS=';' read -ra ADDR <<< "$1"
                for i in "${ADDR[@]}"; do
                    export EXCLUDE="$EXCLUDE --exclude=$i"
                done
            fi
            shift
            ;;
        *) 
            break
            ;;
    esac
done

export DIR=$(dirname ${FILE})
export NAME=$(basename ${FILE})
export ARCHIVE="${NAME}.${d}.tar.gz"
export COMMAND="tar -czf $ARCHIVE"

if [ "$EXCLUDE" != "" ]
then
    export COMMAND="$COMMAND $EXCLUDE ${DIR}/${NAME}"
fi

echo $COMMAND
eval $COMMAND

php -q upload.php "$ARCHIVE"

echo "rm $ARCHIVE"
eval "rm $ARCHIVE"
