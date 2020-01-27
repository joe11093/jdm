<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class ExactSearchStrategy extends SearchStrategy
{
    /* ATTRIBUTES */
    private $parser;

    /* CONSTRUCTOR */
    public function __construct($domain, $options, $searchEntry)
    {
        parent::__construct($domain, $options, $searchEntry);
        $this->parser = new Parser();
    }

    /* METHODS */
    public function constructRepresentation()
    {
        $cURL = curl_init();
        $options = [
          CURLOPT_URL => $this->domainRootPath . $this->options .
              urlencode(iconv("UTF-8", "ISO-8859-1", $this->searchEntry)),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => []
        ];

        curl_setopt_array($cURL, $options);
        $htmlPage = curl_exec($cURL);
        curl_close($cURL);

        return utf8_encode($htmlPage);
    }

    public function termExists()
    {
        $this->representation = $this->constructRepresentation();
        return preg_match("/<([\s\/])?CODE>/i", $this->representation);
    }

    public function getResult()
    {
        if (!$this->termExists())
            return json_encode([
              'error' => 1,
              'message' => "The search entry " . $this->searchEntry . " doesn't return results"]);

        return $this->parser->parse($this->representation);
    }
}
