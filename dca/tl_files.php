<?php


//Delete file form s3
$GLOBALS['TL_DCA']['tl_files']['config']['ondelete_callback'][] = array('Guave\S3\Classes\S3','deleteFile');

//sync files from s3 if timestamp is newer to local on filemanager loading
$GLOBALS['TL_DCA']['tl_files']['config']['onload_callback'][] = array('Guave\S3\Classes\S3','syncFromS3IfTimestamp');

//sync all files from local to s3 when sync called
$GLOBALS['TL_DCA']['tl_files']['config']['onload_callback'][] = array('Guave\S3\Classes\S3','syncAllFilesToS3');

