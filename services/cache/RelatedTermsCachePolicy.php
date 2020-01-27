<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class RelatedTermsCachePolicy extends CachePolicy
{
    private $relationType; // the relationType that will determine

    public function __construct($term, $relationType)
    {
        parent::__construct($term);
        $this->relationType = $relationType;
    }

    public function commitTerm()
    {
        $this->constructed->term = $this->term->toArray();
    }

    public function commitDefinitions()
    {
        // does nothing
    }

    public function commitRelationTypes()
    {
        // does nothing
    }

    public function commitRelations()
    {
        // does nothing
    }

    public function commitRelatedTerms()
    {
        $this->constructed->related_terms = new stdClass();

        $relatedTerms = [];
        $count = 0;

        foreach($this->term->getEnteringRelations() as &$relation)
        {
            if ($relation->getType() == Parser::RELATION_TYPES[$this->relationType])
            {
                array_push($relatedTerms, [
                  "term" => $relation->getSource(),
                  "relationType" => $this->relationType
                ]);
            }
        }

        $this->constructed->related_terms->count = count($relatedTerms);
        $this->constructed->related_terms->terms = $relatedTerms;
    }
}
