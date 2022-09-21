#!/bin/bash
echo "Copying files to borgemusikken.no PROD"
sftp -i ~/.ssh/andersl borgegmr@cpanel44.proisp.no:/home/borgegmr << EOF
put -r ./public_html
exit
EOF
