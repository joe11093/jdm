<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class SearchController
{
    /* ATTRIBUTES */
    private $base;
    private $searchEntry;
    private $cache;
    private $parser;
    private $response;

    private const CACHE_ROOT_DIRECTORY = "cache";

    /* CONSTRUCTOR */
    public function __construct($base)
    {
        $this->base = $base;
        $this->cache = new Cache(self::CACHE_ROOT_DIRECTORY);
        $this->parser = new Parser();
    }

    /* METHODS */
    public function getBaseURL()
    {
        return $this->base;
    }

    public function getSearchEntry()
    {
        return $this->searchEntry;
    }

    public function setSearchEntry($searchEntry)
    {
        $this->searchEntry = $searchEntry;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function downloadTermPage()
    {
        $cURL = curl_init();
        $options = [
          CURLOPT_URL => $this->base . "?gotermsubmit=Chercher&gotermrel=" .
              urlencode(iconv("UTF-8", "ISO-8859-1", $this->searchEntry)) . "&rel=",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => []
        ];

        curl_setopt_array($cURL, $options);
        $htmlPage = curl_exec($cURL);
        curl_close($cURL);

        return utf8_encode($htmlPage);
    }

    public function termExists($htmlPage)
    {
        return preg_match("/<([\s\/])?CODE>/i", $htmlPage);
    }

    public function process($params)
    {
        if (!isset($params['term']))
        {
            $this->setResponse(json_encode(['error' => 0, 'message' => "No search term provided"]));
            return $this->response;
        }

        $this->searchEntry = $params['term'];

        if (!isset($params['sort']))
            $category = 'weight';

        else $category = $params['sort'];

        if (!$this->cache->containsValid($this->searchEntry, $category))
        {
            $htmlPage = $this->downloadTermPage();

            if (!$this->termExists($htmlPage)) {

                $this->setResponse(json_encode(['error' => 1, 'message' => "No results for " . $this->searchEntry]));
                return $this->response;
            }

            $term = $this->parser->parse($htmlPage);

            if ($category == "weight")
                $term->sortRelationsByDescWeight();

            elseif ($category == "alpha")
                $term->sortRelationsByFrLexicOrder();


            $this->cache->commit($term);
            $this->cache->save($this->searchEntry, $category);
        }

        $term = $this->cache->load($this->searchEntry, $category);

        if ($term != null)
        {
            if (isset($params['type']))
            {
                $typeId = $params['type'];

                // removing all relation types except that relation type
                foreach ($term->rts as $key => $rt)
                {
                    if ($rt->id != $typeId)
                        unset($term->rts[$key]);
                }

                // removing relations not having that type
                if (!property_exists($term, "rt_$typeId"))
                {
                    $this->setResponse(json_encode(['error' => 2, 'message' => "No relations of type $typeId for " . $this->searchEntry]));
                    return $this->response;
                }

                foreach (get_object_vars($term) as $name => $value)
                {
                    if (startsWith($name, "rt_") && !endsWith($name, "_$typeId"))
                        unset($term->$name);
                }
            }

            $paginator = new Paginator(1, 5, $term);
            $initialPagesSchema = $paginator->getInitialPagesSchema();

            $this->setResponse(json_encode($initialPagesSchema));
        }

        return $this->response;
    }
}
