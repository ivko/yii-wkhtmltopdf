<?php

interface WkhtpDocumentPart {
	/**
	 * @param  Document
	 * @return string
	 */
	function buildShellArgs(Wkhtmltopdf $document);
    
    function buildApiArgs(Wkhtmltopdf $document);
}
