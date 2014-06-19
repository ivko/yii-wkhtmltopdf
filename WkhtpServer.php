<?php

class WkhtpServer extends Wkhtmltopdf {

    public function load($args)
	{
        if (!isset($args['pages'])) {
            return false;
        }
        if ($args['header']) {
            $this->getHeader()->setVars($args['header']);
            unset($args['header']);
        }
        if ($args['footer']) {
            $this->getFooter()->setVars($args['footer']);
            unset($args['footer']);
        }
        if ($args['pages']) {
            foreach ($args['pages'] as $page) {
                switch ($page['type']) {
                    case 'toc':
                        $this->addToc()->setVars($page);
                        break;
                    case 'page':
                        $this->addHtml($page['html'])->setVars($page);
                        break;
                }
            }
            unset($args['pages']);
        }
        $this->setVars($args);
        return true;
    }
}