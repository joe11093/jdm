<?php
include_once(dirname(__FILE__, 2) . '/utility.php');
class AutocompletionController
{
    /* ATTRIBUTES */
    private $dataSource;
    private $suggestions = [];
    private $response;

    private const LINE_SEPARATOR = "\r\n";
    private const SUGGESTIONS_LIMIT = 10;

    /* CONSTRUCTOR */
    public function __construct($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /* METHODS */
    public function getDataSource()
    {
        return $this->dataSource;
    }
    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getSuggestions($term)
    {
        return file_get_contents_chunked($this->dataSource, 1024, function($chunk, &$handle, $i) use ($term) {

          if(count($this->suggestions) >= self::SUGGESTIONS_LIMIT)
              return;

          // $lines = explode(self::LINE_SEPARATOR, $chunk);
          //
          // foreach($lines as $line)
          // {
          //     if (stripos($line, $term) === 0)
          //         array_push($this->suggestions, $line);
          // }

          if (stripos($chunk, $term) === 0)
              array_push($this->suggestions, $chunk);
        });
    }

    public function process($params)
    {
      if (!file_exists($this->dataSource))
      {
          $this->setResponse(json_encode(['error' => 0, 'message' => "Invalid data source \"{$this->dataSource}\" provided for autocompletion"]));
          return $this->response;
      }

      if (!isset($params['term']))
      {
          $this->setResponse(json_encode(['error' => 1, 'message' => "No search term provided for autocompletion"]));
          return $this->response;
      }

      $this->getSuggestions($params['term']);
      $this->setResponse(json_encode($this->suggestions));

      return $this->response;
    }
}
