<?php

//overwrite ContentText
$GLOBALS['TL_CTE']['texts']['text'] = 'Guave\ContentText';
$GLOBALS['TL_CTE']['texts']['image'] = 'Guave\ContentImage';

$GLOBALS['TL_HOOKS']['postUpload'][] = array('Guave\S3\Classes\S3', 'postUpload');
$GLOBALS['TL_HOOKS']['generatePage'][] = array('Guave\S3\Classes\S3', 'syncFromS3IfTimestamp');