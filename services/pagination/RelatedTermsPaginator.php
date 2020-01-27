<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class RelatedTermsPaginator extends Paginator
{
    /* CONSTRUCTOR */
    public function __construct($pageIndex, $perPage, $cached)
    {
        parent::__construct($pageIndex, $perPage, $cached);
    }

    /* METHODS */
    public function getDefaultPaginationSchema()
    {
        $schema = new stdClass(); // related terms schema root

        // no pagination required for this elements
        $schema->term = $this->cachedTerm->term; // getting the term

        // 10 related terms per page by default
        $this->pageIndex = 1;
        $this->perPage = 10;

        $schema->related_terms = new stdClass();
        $schema->related_terms->count = count($this->cachedTerm->related_terms->terms);
        $schema->related_terms->terms = $this->getRelatedTermsPerPage();

        return $schema;
    }

    public function getRelatedTermsPaginationSchema()
    {
        $schema = new stdClass(); // related terms schema root
        $schema->term = $this->cachedTerm->term; // getting the term

        $schema->related_terms = new stdClass();
        $schema->related_terms->count = count($this->cachedTerm->related_terms->terms);
        $schema->related_terms->terms = $this->getRelatedTermsPerPage();

        return $schema;
    }

    private function getRelatedTermsPerPage()
    {
        $offset = ($this->pageIndex - 1) * $this->perPage;
        $upper = min($this->perPage + $offset, count($this->cachedTerm->related_terms->terms));

        $related_terms = [];
        for ($i = $offset; $i < $upper; $i++)
            array_push($related_terms, $this->cachedTerm->related_terms->terms[$i]);

        return $related_terms;
    }
}
