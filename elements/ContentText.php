<?php

namespace Guave;

class ContentText extends  \Contao\ContentText {

	/**
	 * Generate the content element
	 */
	protected function compile()
	{

		$this->import('Guave\S3', 'S3');

		if ($this->addImage && $this->singleSRC != '')
		{

			$objModel = \FilesModel::findByUuid($this->singleSRC);

			if (!is_file(TL_ROOT . '/' . $objModel->path)) {
				//try to load from s3
				$this->S3->loadFileFromS3($objModel->path);
			}

		}

		parent::compile();

	}

}