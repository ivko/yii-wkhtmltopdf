<?php
class WkhtpClient extends Wkhtmltopdf {

    private $response = -1;
    private $responseHeaders = array();
    
    private $url = null;

    public function __construct($tmpDir, $url = null)
	{
        parent::__construct($tmpDir);
        $this->url = $url;
	}
    
    private function buildRequestArgs()
	{
        $args = array(
            'pages' => array(),
            'dpi' => $this->dpi,
            'margin' => $this->margin,
            'orientation' => $this->orientation,
            'size' => $this->size,
            'title' => $this->title,
            'encoding' => $this->encoding
        );
    
		if ($this->header !== NULL) {
			$args['header'] = $this->header->buildApiArgs($this);
		}

        if ($this->footer !== NULL) {
			$args['footer'] = $this->footer->buildApiArgs($this);
		}
        
		foreach ($this->pages as $page) {
			$args['pages'][] = $page->buildApiArgs($this);
		}
        
        return $args;
	}
    
    protected function convert() {
        $this->openProcess($this->url, $this->pipes);
    }
    
    protected function openProcess($url, &$pipes)
	{
        $ch = curl_init();

        $pipes[1] = fopen($this->saveTempFile(''), "w+");
        $pipes[2] = fopen($this->saveTempFile(''), "w+");
        
        //Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead. 
        //Apache does not need it, but it is safe to use it there as well.
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, $pipes[1]);
        curl_setopt($ch, CURLOPT_STDERR, $pipes[2]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->buildRequestArgs()));
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");
        $this->response = curl_exec($ch);
        $this->responseHeaders = curl_getinfo($ch);
        curl_close($ch);
        fseek($pipes[1], 0);
        fseek($pipes[2], 0);
        return $ch;
	}

	protected function close()
	{
		stream_get_contents($this->pipes[1]); // wait for process
		$error = stream_get_contents($this->pipes[2]);
        if ($this->response === false) {
			throw new Apex_Exception($error);
		}
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
		foreach ($this->tmpFiles as $file) {
			unlink($file);
		}
	}
}