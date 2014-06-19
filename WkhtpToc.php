<?php

class WkhtpToc extends WkhtpObject implements WkhtpDocumentPart
{
	/** @var string */
	public $header = 'Table of contents';

	/** @var float */
	public $headersSizeShring = 0.9;

	/** @var string */
	public $indentationLevel = '1em';



	/**
	 * @param  Document
	 * @return string
	 */
	public function buildShellArgs(Wkhtmltopdf $document)
	{
		return ' toc --toc-header-text ' . escapeshellarg($this->header)
			. ' --toc-level-indentation ' . escapeshellarg($this->indentationLevel)
			. ' --toc-text-size-shrink ' . number_format($this->headersSizeShring, 4, '.', '');
	}
    
    public function buildApiArgs(Wkhtmltopdf $document)
	{
        $args = get_object_vars($this);
        $args['type'] = 'toc';
        return $args;
	}
}
