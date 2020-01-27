<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');

class PaginationController
{
    /* ATTRIBUTES */
    private $searchEntry; // the search term whose results we want to paginate
    private $target; // the element in the schema that we want to paginate
    private $cache; // the cached from which we retrieve the schemas
    private $paginator; // the paginator used to paginate the target elements
    private $page; // the page index
    private $perPage; // the number of elements per page
    private $sort; // the sorting category provided for relations pagination
    private $type; // the type category provided for the relations pagination
    private $orientation; // the orientation parameter provided for relations pagination
    private $response; // the returned response

    private const CACHE_ROOT_DIRECTORY = "cache";

    /* CONSTRUCTOR */
    public function __construct()
    {

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

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getPaginator()
    {
        return $this->paginator;
    }

    public function setPaginator($paginator)
    {
        $this->paginator = $paginator;
    }

    public function getOrientation()
    {
        return $this->orientation;
    }

    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
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

    public function validateTarget($params)
    {
        if (!isset($params['target']))
        {
            $this->setResponse(json_encode(['error' => 1, 'message' => "No target for pagination provided"]));
            return false;
        }

        return true;
    }

    public function validatePageIndex($params)
    {
        if (!isset($params['page']))
        {
            $this->setResponse(json_encode(['error' => 2, 'message' => "No page index for pagination provided"]));
            return false;
        }

        return true;
    }

    public function validatePerPageIndex($params)
    {
        if (!isset($params['per_page']))
        {
            $this->setResponse(json_encode(['error' => 3, 'message' => "No per page index for pagination provided"]));
            return false;
        }

        return true;
    }

    public function validateSortCategory($params)
    {
        if (!isset($params['sort']))
        {
            $this->setResponse(json_encode(['error' => 4, 'message' => "No sort category for pagination provided"]));
            return false;
        }

        return true;
    }

    public function validateOrientationCategory($params)
    {
        if (!isset($params['orientation']))
        {
            $this->setResponse(json_encode(['error' => 5, 'message' => "No orientation category for pagination provided"]));
            return false;
        }

        return true;
    }

    public function validateRelationTypeCategory($params)
    {
        if (!isset($params['type']))
        {
            $this->setResponse(json_encode(['error' => 6, 'message' => "No relation type category for pagination provided"]));
            return false;
        }

        return true;
    }

    public function process($params)
    {
        /* VALIDATING THE GET PARAMETERS */
        /* ============================= */
        if(!$this->validateSearchEntry($params))
            return $this->response;

        // search entry valid
        $this->searchEntry = strtolower($params['term']);

        if(!$this->validateTarget($params))
            return $this->response;

        // target valid
        $this->target = strtolower($params['target']);

        if(!$this->validatePageIndex($params))
            return $this->response;

        // page index valid
        $this->page = strtolower($params['page']);

        if(!$this->validatePerPageIndex($params))
            return $this->response;

        // per_page index valid
        $this->perPage = strtolower($params['per_page']);

        if(!$this->validateSortCategory($params))
            return $this->response;

        // sort category valid
        $this->sort = strtolower($params['sort']);

        if(!$this->validateOrientationCategory($params))
            return $this->response;

        // orientation category valid
        $this->orientation = strtolower($params['orientation']);

        if(!$this->validateRelationTypeCategory($params))
            return $this->response;

        // relation type category valid
        $this->type = strtolower($params['type']);

        /* INITIALIZING THE CACHE, TO BE COMPLETED ACCORDING TO PROVIDED PARAMETERS */
        $this->cache = new Cache(self::CACHE_ROOT_DIRECTORY, [], "");

        if ($this->target == "definition" || $this->target == "relation")
        {
            $this->cache->setPathComponents(["terms", $this->sort]);
            $this->cache->setTerm($this->searchEntry);
            $this->cache->setPolicy(new TermsCachePolicy($this->searchEntry));

            $cached = $this->cache->load();
            if ($cached == null)
            {
                $this->setResponse(json_encode(['error' => 8, 'message' => "{$this->cache->getPath()} doesn't exist in the cache"]));
                return $this->response;
            }

            $this->paginator = new TermPaginator($this->page, $this->perPage, $cached, $this->orientation);
        }

        else // related_terms
        {
            $this->cache->setPathComponents(["related_terms", $this->type]);
            $this->cache->setTerm($this->searchEntry);
            $this->cache->setPolicy(new RelatedTermsCachePolicy($this->searchEntry, $this->type));

            $cached = $this->cache->load();
            if ($cached == null)
            {
                $this->setResponse(json_encode(['error' => 7, 'message' => "{$this->cache->getPath()} doesn't exist in the cache"]));
                return $this->response;
            }

            $this->paginator = new RelatedTermsPaginator($this->page, $this->perPage, $cached);
        }

        /* PAGINATION HANDLING */
        if ($this->target == "definition")
        {
            $definitions = $this->paginator->getDefinitionsPaginationSchema();
            $this->setResponse(json_encode($definitions));
        }

        elseif ($this->target == "relation")
        {
            $schema = $this->paginator->getRelationsPaginationSchema($this->type);
            $this->setResponse(json_encode($schema));
        }

        elseif ($this->target == "related_terms")
        {
            $schema = $this->paginator->getRelatedTermsPaginationSchema();
            $this->setResponse(json_encode($schema));
        }

        return $this->response;
    }
}
