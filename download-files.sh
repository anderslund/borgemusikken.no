#!/bin/bash
echo "Copying files to borgemusikken.no PROD"
sftp -i ~/.ssh/andersl borgegmr@cpanel44.proisp.no:/home/borgegmr << EOF
cd public_html/
lcd public_html
mget bmk*
get PlancakeEmailParser.php
get wp-blog-header.php
!rm -r *.log

lcd wp-content/themes/salient-child/includes
get wp-content/themes/salient-child/includes/bmk-functions-oppmote.php
lcd ../tribe-events/widgets/
get wp-content/themes/salient-child/tribe-events/widgets/list-widget.php

lcd ../../
get wp-content/themes/salient-child/bmk-profil.php
get wp-content/themes/salient-child/functions.php
get wp-content/themes/salient-child/header.php
get wp-content/themes/salient-child/secure-page-sidebar.php
get wp-content/themes/salient-child/secure-page.php
get wp-content/themes/salient-child/style.css
exit
EOF
