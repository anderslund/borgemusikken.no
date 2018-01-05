#!/usr/bin/env bash
echo "Copying files to borgemusikken.no PROD"
sftp borgegmr@cpanel44.proisp.no:/home/borgegmr << EOF
put -r /Users/anders/git/borgemusikken.no/public_html
exit
EOF