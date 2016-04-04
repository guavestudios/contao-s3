<?php

namespace Guave;

class ContentImage extends  \Contao\ContentImage {

	/**
	 * Generate the content element
	 */
	public function generate()
	{


		if ($this->singleSRC == '')
		{
			return '';
		}

		$this->import('Guave\S3', 'S3');

		$objFile = \FilesModel::findByUuid($this->singleSRC);

		if ($objFile->path)
		{

			if (!is_file(TL_ROOT . '/' . $objFile->path)) {
				//try to load from s3
				$this->S3->loadFileFromS3($objFile->path);
			}

		}

		return parent::generate();

	}

}