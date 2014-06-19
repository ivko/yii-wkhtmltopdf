<?php

class WkhtpPage extends WkhtpObject implements WkhtpDocumentPart
{
	/** @var string */
	public $file;

	/** @var string */
	public $html;

	/** @var bool */
	public $isCover = FALSE;

	/** @var string */
	public $encoding;

	/** @var bool */
	public $usePrintMediaType = TRUE;

	/** @var string */
	public $styleSheet;

	/** @var string */
	public $javascript;

	/** @var int */
	public $zoom = 1;



	/**
	 * @param  Document
	 * @return string
	 */
	public function buildShellArgs(Wkhtmltopdf $document)
	{
		$file = $this->file;
		if ($file === NULL) {
			$file = $document->saveTempFile((string) $this->html);
		}

		return ($this->isCover ? ' cover ' : ' ')
			. escapeshellarg($file)
			. ($this->encoding ? ' --encoding ' . escapeshellarg($this->encoding) : '')
			. ($this->usePrintMediaType ? ' --print-media-type' : '')
			. ($this->styleSheet ? ' --user-style-sheet ' . escapeshellarg($this->styleSheet) : '')
			. ($this->javascript ? ' --run-script ' . escapeshellarg($this->javascript) : '')
			. ' --zoom ' . ($this->zoom * 1);
	}
    
    /**
	 * @param  Document
	 * @return string
	 */
	public function buildApiArgs(Wkhtmltopdf $document)
	{
        
		$args = get_object_vars($this);
        $args['type'] = 'page';
        return $args;
	}
}