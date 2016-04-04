<?php

$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'enableS3';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{s3_legend},enableS3;';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['enableS3'] = 'awsAccessKey,awsSecretKey,s3Region,s3Bucket';

$GLOBALS['TL_DCA']['tl_settings']['fields']['enableS3'] = array(
	'label'                   => $GLOBALS['TL_LANG']['tl_settings']['enables3'],
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['awsAccessKey'] = array(
	'label'                   => $GLOBALS['TL_LANG']['tl_settings']['awsAccessKey'],
	'inputType'				  => 'text',
	'eval' 					  => array('mandatory' => true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['awsSecretKey'] = array(
	'label'                   => $GLOBALS['TL_LANG']['tl_settings']['awsSecretKey'],
	'inputType'				  => 'text',
	'eval' 					  => array('mandatory' => true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['s3Region'] = array(
	'label'                   => $GLOBALS['TL_LANG']['tl_settings']['s3Region'],
	'inputType'				  => 'select',
	'options'                 => array(
		'' => '-',
		'us-east-1' => 'US Standard',
		'us-west-2' => 'US West (Oregon)',
		'us-west-1' => 'US West (N. California)	',
		'eu-west-1' => 'EU (Ireland)',
		'eu-central-1' => 'EU (Frankfurt)',
		'ap-southeast-1' => 'Asia Pacific (Singapore)',
		'ap-southeast-2' => 'Asia Pacific (Sydney)',
		'ap-northeast-1' => 'Asia Pacific (Tokyo)',
		'sa-east-1' => 'South America (Sao Paulo)',
	),
	'eval' 					  => array('mandatory' => true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['s3Bucket'] = array(
	'label'                   => $GLOBALS['TL_LANG']['tl_settings']['s3Bucket'],
	'inputType'				  => 'text',
	'eval' 					  => array('mandatory' => true, 'tl_class'=>'w50')
);