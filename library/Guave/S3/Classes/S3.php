<?php

namespace Guave\S3\Classes;

use Aws\S3\S3Client;

class S3 {

	/**
	 * @var S3|null
	 */
	private static $instance = null;

	/**
	 * @var S3Client|null
	 */
	private static $client = null;

	/**
	 * @var string|null
	 */
	private static $bucket = null;
	private static $region = null;

	/**
	 * @var null|\User
	 */
	private static $user = null;

	/**
	 * @var array
	 */
	private static $regions = array(
		'us-east-1' => 's3.amazonaws.com',
		'us-west-2' => 's3-us-west-2.amazonaws.com',
		'us-west-1' => 's3-us-west-1.amazonaws.com',
		'eu-west-1' => 's3-eu-west-1.amazonaws.com',
		'eu-central-1' => 's3.eu-central-1.amazonaws.com',
		'ap-southeast-1' => 's3-ap-southeast-1.amazonaws.com',
		'ap-southeast-2' => 's3-ap-southeast-2.amazonaws.com',
		'ap-northeast-1' => 's3-ap-northeast-1.amazonaws.com',
		'sa-east-1' => 's3-sa-east-1.amazonaws.com',
	);

	protected function __construct() {


		self::$bucket = \Config::get('s3Bucket');
		self::$region = \Config::get('s3Region');
		self::$user = \BackendUser::getInstance();

		$client = S3Client::factory(array(
			'key'    => \Config::get('awsAccessKey'),
			'secret' => \Config::get('awsSecretKey'),
			'region' => self::$region
		));
		self::$client = $client;

	}

	/**
	 * @return S3|null
	 */
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new S3();
		}
		return self::$instance;
	}

	/**
	 * contao hook for file uploads
	 * @param array $arrFiles
	 */
	public static function postUpload($arrFiles, $updateTimestamp = true){

		if(!self::isS3Enabled()) {
			return;
		}

		foreach($arrFiles as $file) {
			try {
				$resource = fopen(TL_ROOT.'/'.$file, 'r');
				$upload = self::$client->upload(self::$bucket, $file, $resource, 'public-read');
				if($upload) {
					$timestampFile = \Config::get('uploadPath').'/timestamp.txt';
					if($file != $timestampFile) {
						\Message::addInfo('file '.$file.' loaded to s3');
					}
				}
			} catch(Exception $e) {
				die($e->getMessage());
			}
		}

		if($updateTimestamp) {
			$time = time();
			self::setTimestamp($time);
			self::setS3Timestamp($time);
		}
	}

	/**
	 * load file from S3 to locale
	 * @param string $path
	 * @param bool $returnString
	 * @return bool|string
	 */
	public static function loadFileFromS3($path, $returnString = false) {

		if(!self::isS3Enabled()) {
			return false;
		}

		$file = @file_get_contents('https://'.self::$regions[self::$region].'/'.self::$bucket.'/'.urlencode($path));
		if($returnString) {
			return $file;
		}
		$dir = dirname($path);
		if($file) {
			$folder = new \Folder($dir);
			$handle = fopen(TL_ROOT.'/'.$path, 'w+');
			fwrite($handle, $file);
			fclose($handle);
			return true;
		}
		return false;
	}

	/**
	 * check if all files in db also exists in filesystem, else it load it from s3
	 */
	public static function checkAllFilesOnS3() {

		if(!self::isS3Enabled()) {
			return false;
		}

		$files = \FilesModel::findAll();
		if($files) {
			foreach($files as $file) {
				if (!is_file(TL_ROOT . '/' . $file->path)) {
					//try to load from s3
					self::loadFileFromS3($file->path);
				}
			}
		}
	}

	public static function syncAllFilesToS3() {

		if(!self::isS3Enabled()) {
			return false;
		}

		$act = \Input::get('act');
		if($act == 'sync') {

			@ini_set('max_execution_time', 0);

			if(!self::$user->hasAccess('f6', 'fop')) {
				return;
			}

			$objFiles = new \RecursiveIteratorIterator(
				new \Filter\SyncExclude(
					new \RecursiveDirectoryIterator(
						TL_ROOT . '/' . \Config::get('uploadPath'),
						\FilesystemIterator::UNIX_PATHS|\FilesystemIterator::FOLLOW_SYMLINKS|\FilesystemIterator::SKIP_DOTS
					)
				), \RecursiveIteratorIterator::SELF_FIRST
			);

			$uploadFiles = array();

			/**
			 * @var $objFile \SplFileInfo
			 */
			foreach ($objFiles as $objFile) {

				if($objFile->isDir()) {
					continue;
				}

				$path = str_replace(TL_ROOT.'/','',$objFile->getPath()).'/'.$objFile->getFilename();
				if(!self::checkIfFileExistOnS3($path)) {
					$uploadFiles[] = $path;
				}
			}

			if($uploadFiles) {
				self::postUpload($uploadFiles);
			}
		}




	}

	public static function syncAllFilesFromS3() {

		if(!self::isS3Enabled()) {
			return false;
		}

		@ini_set('max_execution_time', 0);

		\Message::addInfo('syncAllFilesFromS3');

		$client = self::$client;
		$iterator = $client->getIterator('ListObjects', array('Bucket' => self::$bucket));
		foreach ($iterator as $object) {

			if(!is_file(TL_ROOT.'/'.$object['Key'])) {
				$pathInfo = pathinfo($object['Key']);
				if(!file_exists(TL_ROOT.'/'.$pathInfo['dirname'])) {
					@mkdir(TL_ROOT.'/'.$pathInfo['dirname'], 0777, true);
					@chmod(TL_ROOT.'/'.$pathInfo['dirname'], 0777, true);
				}

				self::loadFileFromS3($object['Key']);
			}

		}

	}

	/**
	 * @param string $path
	 */
	public static function checkIfFileExistOnS3($path) {
		$headers = @get_headers('https://'.self::$regions[self::$region].'/'.self::$bucket.'/'.$path);
		if(strpos($headers[0],'200')===false) {
			return false;
		} else {
			return true;
		}
	}

	public static function moveFile($src, $destination, $dca) {

		if(!self::isS3Enabled()) {
			return false;
		}

		try {
			self::deleteFile($src);
			self::postUpload(array($destination));
		} catch(Exception $e) {
			die($e->getMessage());
		}

	}

	/**
	 * delete file from s3
	 * @param string $path
	 */
	public static function deleteFile($path) {

		if(!self::isS3Enabled()) {
			return false;
		}

		try {
			self::$client->deleteObject(
				array(
					'Bucket' => self::$bucket,
					'Key' => $path
				)
			);
			\Message::addInfo(sprintf('delete file %s from S3', $path));
		} catch(Exception $e) {
			die($e->getMessage());
		}

		$time = time();
		self::setTimestamp($time);
		self::setS3Timestamp($time);

	}

	/**
	 * check if s3 support is enabled
	 * @return boolean
	 */
	public static function isS3Enabled() {
		return \Config::get('enableS3');
	}

	public static function setTimestamp($time)
	{

		$timestamp = \Config::get('uploadPath').'/timestamp.txt';
		$handle = fopen(TL_ROOT.'/'.$timestamp, 'w+');
		fwrite($handle, $time);
		fclose($handle);

	}

	public static function setS3Timestamp($time)
	{
		self::getTimestamp();
		$timestampFile = \Config::get('uploadPath').'/timestamp.txt';
		self::postUpload(array($timestampFile), false);
	}

	public static function getS3Timestamp()
	{
		$timestampFile = \Config::get('uploadPath').'/timestamp.txt';
		$s3Timestamp = self::loadFileFromS3($timestampFile, true);
		if(!$s3Timestamp) {
			$s3Timestamp = self::getTimestamp();
			self::setS3Timestamp($s3Timestamp);
		}
		return (int)$s3Timestamp;
	}
	
	public static function getTimestamp() {
		$timestamp = (int)@file_get_contents(\Config::get('uploadPath').'/timestamp.txt');
		if($timestamp <= 0) {
			$timestamp = time();
			self::setTimestamp($timestamp);
			return 0;
		}
		return $timestamp;
	}

	/**
	 * @return bool check if there new files on S3
	 */
	public static function checkForNewFiles()
	{

        if(!self::isS3Enabled()) {
            return false;
        }

		$timestamp = self::getTimestamp();
		$s3Timestamp = self::getS3Timestamp();

//		var_dump(array(date('d.m.Y H:i:s',$timestamp), date('d.m.Y H:i:s',$s3Timestamp)));

		if($s3Timestamp > $timestamp) {
			return true;
		}

		return false;


	}

	public static function syncFromS3IfTimestamp()
	{
        if(!self::isS3Enabled()) {
            return;
        }

		if(self::checkForNewFiles()) {
			self::syncAllFilesFromS3();
		}
	}

}