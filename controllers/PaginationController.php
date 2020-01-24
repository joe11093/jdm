<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class PaginationController
{
    /* ATTRIBUTES */
    private $searchEntry;
    private $target;
    private $cache;
    private $paginator;
    private $response;

    private const CACHE_ROOT_DIRECTORY = "cache";

    /* CONSTRUCTOR */
    public function __construct()
    {
        $this->cache = new Cache(self::CACHE_ROOT_DIRECTORY);
    }

    /* METHODS */
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
        if (!isset($params['term']))
        {
            $this->setResponse(json_encode(['error' => 0, 'message' => "No search term provided"]));
            return $this->response;
        }

        if (!isset($params['target']))
        {
            $this->setResponse(json_encode(['error' => 1, 'message' => "No target for pagination provided"]));
            return $this->response;
        }

        if (!isset($params['page']))
        {
            $this->setResponse(json_encode(['error' => 2, 'message' => "No page index for pagination provided"]));
            return $this->response;
        }

        if (!isset($params['per_page']))
        {
            $this->setResponse(json_encode(['error' => 3, 'message' => "No entries per page for pagination provided"]));
            return $this->response;
        }

        $this->searchEntry = $params['term'];
        $this->target = $params['target'];

        $cached = $this->cache->load($this->searchEntry, "weight");
        if ($cached == null)
            $cached = $this->cache->load($this->searchEntry, "alpha");

        $this->paginator = new Paginator($params['page'], $params['per_page'], $cached);

        if ($this->target == "definition")
        {
        		$definitions = $this->paginator->getDefinitionsPageSchema();
        		$this->setResponse(json_encode($definitions));
      	}

        elseif ($this->target == "relation")
        {
            if (!isset($params['type']))
            {
                $this->setResponse(json_encode(['error' => 4, 'message' => "No type of relations provided for pagination"]));
                return $this->response;
            }

            if (!isset($params['sort']))
            {
                $this->setResponse(json_encode(['error' => 5, 'message' => "No sorting order for entries provided for pagination"]));
                return $this->response;
            }

            $type = $params['type'];
            $category = $params['sort'];
            $cached = $this->cache->load($this->searchEntry, $category);
            if ($cached == null)
            {
                $this->setResponse(json_encode(['error' => 6, 'message' => "No cached results for specified sorting order provided for pagination"]));
                return $this->response;
            }

            $this->paginator = new Paginator($params['page'], $params['per_page'], $cached);

            $relations = $this->paginator->getRelationsPageSchema($type);
        		$this->setResponse(json_encode($relations));
      	}

        return $this->response;
    }
}
