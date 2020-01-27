<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class SearchController
{
    /* ATTRIBUTES */
    private $domain; // the domain of the web site searched by the client
    private $searchEntry; // the search entry provided by the client
    private $searchStrategy; // the search strategy to handle the client's search entry
    private $cache; // the cache to use for caching and retrieving cached content
    private $response; // the response to send back to the client

    private const CACHE_ROOT_DIRECTORY = "cache"; // a default cache root directory

    /* CONSTRUCTOR */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /* METHODS */
    public function getDomain()
    {
        return $this->domain;
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

    public function getSearchStrategy()
    {
        return $this->searchStrategy;
    }

    public function setSearchStrategy($searchStrategy)
    {
        $this->searchStrategy = $searchStrategy;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function process($params)
    {
        if(!$this->validateSearchEntry($params))
            return $this->response;

        // search entry valid
        $this->searchEntry = strtolower($params['term']);

        /*
        * sort category obtained from params (default = "weight")
        * used only with exact search type
        */
        $sort = null;

        /*
        * orientation category obtained from params (default = "exiting")
        * used only with exact search type
        */
        $orientation = null;

        /*
        * relation type used for fetching related terms to the search entry
        */
        $relationType = null;

        $parsed = null; // parsed representation of the search entry
        $cached = null; // cached representation of the search entry

        // initializing the cache, to be complete according to search strategy
        $this->cache = new Cache(self::CACHE_ROOT_DIRECTORY, [], "");

        $interpretation = $this->interpretSearchEntry();

        if (gettype($interpretation) == "string") // exact search strategy
        {
            $strategyName = "exact";

            if ($this->validateRelationSortCategory($params))
                $sort = strtolower($params['sort']);

            else
                $sort = "weight"; // default case

            if ($this->validateOrientationCategory($params))
                $orientation = strtolower($params['orientation']);

            else
              $orientation = "exiting"; // default case

            $this->searchStrategy = new ExactSearchStrategy(
              $this->domain,
              "?gotermsubmit=Chercher&gotermrel=",
              $this->searchEntry
            );

            // initializing the cache according to the exact search strategy
            $this->cache->setPathComponents(["terms", $sort]);
            $this->cache->setTerm($this->searchEntry);
            $this->cache->setPolicy(new TermsCachePolicy($this->searchEntry));
        }

        else // $x r_<type> <blabla> strategy
        {
            $relationType = strtolower($interpretation[1]);
            $destinationTerm = trim($interpretation[2]);
            $strategyName = "related_term";

            $this->searchStrategy = new SearchByRelatedTermStrategy(
              $this->domain,
              "?gotermsubmit=Chercher&gotermrel=",
              $destinationTerm,
              $relationType
            );

            // initializing the cache according to the $x r_<type> <blabla> strategy
            $this->cache->setPathComponents(["related_terms", $relationType]);
            $this->cache->setTerm($destinationTerm);
            $this->cache->setPolicy(new RelatedTermsCachePolicy($destinationTerm, $relationType));
        }

        // if the search entry isn't already cached
        if ($this->cache->containsValidCachedTerm() == false)
        {
            // check the parsed results of the search strategy
            if (($parsed = $this->handleSearchStrategy()) == false)
                return $this->response;

            // set the parsed term target of the cache
            $this->cache->setTerm($parsed);
            $this->cache->getPolicy()->setTerm($parsed);

            // sort only if we're doing an exact search
            if ($strategyName == "exact")
                $this->handleRelationsSorting($parsed, $sort, $orientation);

            // cache the parsed results according to the caching policy
            $this->handleCaching();
        }

        // if the search entry is already cached
        $cached = $this->cache->load();

        // if the cache has been deleted
        if ($cached == null)
            $this->setResponse(json_encode(['error' => 3, 'message' => "Couldn't fetch " . $this->cache->getTerm() . " from server."]));

        /*
        * if we wanted to filter according to relation types,
        * we do it from the cache, not on the parsed.
        * used only with exact search strategy
        */
        if ($strategyName == "exact")
        {
            if ($this->validateRelationTypeFilter($cached, $params) == false)
                return $this->response;
        }

        else if ($strategyName == "related_term")
        {
            if ($cached->related_terms->count == 0)
            {
                $this->cache->remove();
                return json_encode([
                  'error' => 2,
                  'message' => "No relation of type " . $relationType . " exists for " . $cached->term->name]);
            }
        }

        $this->setResponse(json_encode($this->handlePagination($cached, $strategyName, $orientation)));

        return $this->response;
    }

    public function validateSearchEntry($params)
    {
        if (!isset($params['term']))
        {
            $this->setResponse(json_encode(['error' => 0, 'message' => "No search entry provided"]));
            return false;
        }

        return true;
    }

    public function interpretSearchEntry()
    {
        $matches = [];

        if (preg_match('/^\$x\s+(r_[\w\d>-]+)\s+([^\s].+)$/i', $this->searchEntry, $matches)) // $x r_<type> <blabla>
            return $matches;

        else // Exact Search Strategy
            return "exact";
    }

    public function validateRelationSortCategory($params)
    {
        return isset($params['sort']);
    }

    public function validateOrientationCategory($params)
    {
        return isset($params['orientation']);
    }

    public function validateRelationTypeFilter($cached, $params)
    {
        if (isset($params['type']))
            return $this->handleRelationTypeFiltering($cached, $params['type']);

        return true; // by default we have no type filtering
    }

    public function handleSearchStrategy()
    {
        $results = $this->searchStrategy->getResult();

        if (gettype($results) == "string" && strpos($results, "error") != false)
        {
            $this->setResponse($results);
            return false;
        }

        return $results;
    }

    public function handleRelationTypeFiltering(&$cached, $typeId)
    {
        // checking if the cached has any relation of the required type filter
        if (!property_exists($cached, "rt_$typeId"))
        {
            $this->setResponse(json_encode(['error' => 2, 'message' => "No relations of type $typeId for " . $this->cache->getTerm()]));
            return false;
        }

        // removing all relation types except the relation type specified by the filter
        $filtered = [];

        foreach($cached->rts->types as $relationType)
        {
            if($relationType->id == $typeId)
            {
                array_push($filtered, $relationType);
                break;
            }
        }

        $cached->rts->types = $filtered;

        // removing relations that are not of that type
        foreach (get_object_vars($cached) as $name => $value)
            if (startsWith($name, "rt_") && !endsWith($name, "_$typeId"))
                unset($cached->$name);

        return true;
    }

    public function handleRelationsSorting($parsed, $sort, $orientation)
    {
        if ($sort == "weight")
            $parsed->sortRelationsByDescWeight($orientation);

        elseif ($sort == "alpha")
            $parsed->sortRelationsByFrLexicOrder($orientation);
    }

    public function handlePagination($cached, $strategyName, $orientation)
    {
        if ($strategyName == "exact") // exact search strategy
            $paginator = new TermPaginator(1, 5, $cached, $orientation);

        else // $x r_<type> <blabla> strategy
            $paginator = new RelatedTermsPaginator(1, 5, $cached);

        return $paginator->getDefaultPaginationSchema();
    }

    public function handleCaching()
    {
        $this->cache->commit();
        $this->cache->save();
    }
}
