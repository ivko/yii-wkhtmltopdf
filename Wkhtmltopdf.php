<?php

class Wkhtmltopdf extends WkhtpObject
{
	/** @var string	NULL means autodetect */
	public static $executable;

	/** @var array	possible executables */
	public static $executables = array(
        'wkhtmltopdf-i386',
        'wkhtmltopdf-osx',
        'wkhtmltopdf-amd64');
	/** @var int */
	public $dpi = 300;

	/** @var array */
	public $margin = array(0,0,0,0);

	/** @var string */
	public $orientation = 'portrait';

	/** @var string */
	public $size = 'A4';

	/** @var string */
	public $title;

	/** @var string */
	public $encoding = 'utf-8';

	/** @var bool */
	public $usePrintMediaType = TRUE;

	/** @var string */
	public $styleSheet;

	/** @var PageMeta */
	protected $header;

	/** @var PageMeta */
	protected $footer;

	/** @var array */
	protected $pages = array();

	/** @var string */
	public $tmpDir;

	/** @var array */
	protected $tmpFiles = array();

	/** @var resource */
	protected $p;

	/** @var array */
	protected $pipes;

	/**
	 * @param string
	 */
	public function __construct($tmpDir)
	{
        //putenv("FONTCONFIG_PATH=/home/emanage/etc/fonts");
		$this->tmpDir = $tmpDir;
	}

	/**
	 * @return PageMeta
	 */
	public function getHeader()
	{
		if ($this->header === NULL) {
			$this->header = new WkhtpPageMeta('header');
		}
		return $this->header;
	}

	/**
	 * @return PageMeta
	 */
	public function getFooter()
	{
		if ($this->footer === NULL) {
			$this->footer = new WkhtpPageMeta('footer');
		}
		return $this->footer;
	}

	/**
	 * @param  string
	 * @param  bool
	 * @return Page
	 */
	public function addHtml($html, $isCover = FALSE)
	{
		$this->pages[] = $page = $this->createPage();
		$page->html = $html;
		$page->isCover = $isCover;
		return $page;
	}

	/**
	 * @param string
	 * @param bool
	 * @return Page
	 */
	public function addFile($file, $isCover = FALSE)
	{
		$this->pages[] = $page = $this->createPage();
		$page->file = $file;
		$page->isCover = $isCover;
		return $page;
	}

	/**
	 * @param  string
	 * @return Toc
	 */
	public function addToc($header = NULL)
	{
		$this->pages[] = $toc = new WkhtpToc;
		if ($header !== NULL) {
			$toc->header = $header;
		}
		return $toc;
	}

	/**
	 * @param  IDocumentPart
	 * @return Document
	 */
	public function addPart(WkhtpDocumentPart $part)
	{
		$this->pages[] = $part;
		return $this;
	}

	/**
	 * @return Page
	 */
	private function createPage()
	{
		$page = new WkhtpPage;
		$page->encoding = $this->encoding;
		$page->usePrintMediaType = $this->usePrintMediaType;
		$page->styleSheet = $this->styleSheet;
		return $page;
	}

	/**
	 * @param  string
	 * @return string
	 */
	public function saveTempFile($content)
	{
		do {
			$file = $this->tmpDir . '/' . md5($content . '.' . lcg_value()) . '.html';
		} while (file_exists($file));
		file_put_contents($file, $content);
		return $this->tmpFiles[] = $file;
	}

    public function headersTransfer($filename, $length)
    {
        if (headers_sent()) {
            throw new Apex_Exception("Headers already sent");
        }
        header("Content-Description: File Transfer");
        header("Cache-Control: public; must-revalidate, max-age=0");
        header("Pragme: public");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate('D, d m Y H:i:s') . " GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octec-stream", false);
        header("Content-Type: application/download", false);
        header("Content-Type: application/pdf", false);
        header("Content-Length: ". $length);
        header('Content-Disposition: attachment; filename="' . basename($filename) .'";');
        header("Content-Transfer-Encoding: binary"); 
    }

    public function headersEmbed($filename, $length)
	{
        if (headers_sent()) {
            throw new Apex_Exception("Headers already sent");
        }
        header("Content-type: application/pdf");
        header("Cache-control: public, must-revalidate, max-age=0");
        header("Pragme: public");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate('D, d m Y H:i:s') . " GMT");
        header("Content-Length: " . $length);
        header('Content-Disposition: inline; filename="' . basename($filename) .'";');
    }
	/**
	 * Send headers and outputs PDF document to browser.
	 * @throws Apex_Exception
	 */
	public function send($filename = 'file.pdf', $embed = false)
	{
		$this->convert();
		$output = fgets($this->pipes[1], 5);
        $meta   = stream_get_meta_data($this->pipes[1]);
		if ($output === '%PDF') {
            ob_start();
            echo $output;
            fpassthru($this->pipes[1]);
            if ($embed) {
                $this->headersEmbed($filename, ob_get_length());
            } else {
                $this->headersEmbed($filename, ob_get_length());
            }
            // flush all output
            ob_end_flush();
            ob_flush();
            flush();
		}
		$this->close();
	}

	/**
	 * Save PDF document to file.
	 * @param  string
	 * @throws Apex_Exception
	 */
	public function save($file)
	{
		$f = fopen($file, 'w');
		$this->convert();
		stream_copy_to_stream($this->pipes[1], $f);
		fclose($f);
		$this->close();
	}

	/**
	 * Returns PDF document as string.
	 * @return string
	 */
	public function __toString()
	{
		try {
			$this->convert();
			$s = stream_get_contents($this->pipes[1]);
			$this->close();
			return $s;
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_ERROR);
		}
	}

	protected function convert()
	{
		if (self::$executable === NULL) {
			self::$executable = $this->detectExecutable();
		}

		if (self::$executable === FALSE) {
			throw new Apex_Exception('Cannot found Wkhtmltopdf executable');
		}

		$m = $this->margin;
		$cmd = self::$executable . ' -q '
        // --no-header-line --no-footer-line --disable-smart-shrinking --disable-internal-links
			. ' -T ' . escapeshellarg($m[0])
			. ' -R ' . escapeshellarg($m[1])
			. ' -B ' . escapeshellarg($m[2])
			. ' -L ' . escapeshellarg($m[3])
			. ' --dpi ' . escapeshellarg($this->dpi)
			. ' --page-size ' . escapeshellarg($this->size)
			. ' --orientation ' . escapeshellarg($this->orientation)
			. ' --title ' . escapeshellarg($this->title);

		if ($this->header !== NULL) {
			$cmd .= ' ' . $this->header->buildShellArgs($this);
		}
		if ($this->footer !== NULL) {
			$cmd .= ' ' . $this->footer->buildShellArgs($this);
		}
		foreach ($this->pages as $page) {
			$cmd .= ' ' . $page->buildShellArgs($this);
		}

		$this->p = $this->openProcess($cmd . ' -', $this->pipes);
	}

	/**
	 * Returns path to executable.
	 * @return string
	 */
	protected function detectExecutable()
	{
        $path = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Wkhtmltopdf' . DIRECTORY_SEPARATOR . 'bin');
		foreach (self::$executables as $exec) {
            $exec = $path . DIRECTORY_SEPARATOR . $exec;
			if (proc_close($this->openProcess("$exec -v", $tmp)) === 1) {
				return $exec;
			}
		}
        return FALSE;
	}

	protected function openProcess($cmd, & $pipes)
	{
		static $spec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		return proc_open($cmd, $spec, $pipes);
	}

	protected function close()
	{
		stream_get_contents($this->pipes[1]); // wait for process
		$error = stream_get_contents($this->pipes[2]);
		if (proc_close($this->p) > 0) {
			throw new Apex_Exception($error);
		}
		foreach ($this->tmpFiles as $file) {
			unlink($file);
		}
	}
}
