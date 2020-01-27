<?php

abstract class Paginator
{
    /* ATTRIBUTES */
    protected $pageIndex; // the index of the page to display
    protected $perPage; // the number of elements per page
    protected $cached; // the cached JSON term to paginate

    /* CONSTRUCTOR */
    public function __construct($pageIndex, $perPage, $cached)
    {
        $this->pageIndex = $pageIndex;
        $this->perPage = $perPage;
        $this->cachedTerm = $cached;
    }

    /* METHODS */
    abstract public function getDefaultPaginationSchema();
}
