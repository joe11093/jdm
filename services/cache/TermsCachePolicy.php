<?php
require_once(dirname(__FILE__, 3) . '/classLoader.php');

class TermsCachePolicy extends CachePolicy
{
    public function __construct($term)
    {
        parent::__construct($term);
    }

    public function commitTerm()
    {
        $this->constructed->term = $this->term->toArray();
    }

    public function commitDefinitions()
    {
        $this->constructed->defs = new stdClass();
        $this->constructed->defs->count = $this->term->getDefinitionsCount();
        $this->constructed->defs->definitions = [];

        foreach($this->term->getDefinitions() as &$definition)
            array_push($this->constructed->defs->definitions, $definition->toArray());
    }

    public function commitRelationTypes()
    {
        $this->constructed->rts = new stdClass();
        $this->constructed->rts->count = $this->term->getRelationTypesCount();
        $this->constructed->rts->types = [];

        foreach($this->term->getRelationTypes() as &$relationType)
            array_push($this->constructed->rts->types, $relationType->toArray());
    }

    public function commitRelations()
    {
        foreach ($this->term->getRelationTypes() as &$relationType)
        {
          $this->constructed->{"rt_".$relationType->getId()} = new stdClass();

          $this->constructed->{"rt_".$relationType->getId()}->exiting = new stdClass();
          $exitingRelations = [];
          foreach($this->term->getExitingRelations() as &$relation)
          {
              if ($relation->getType() == $relationType->getId())
                  array_push($exitingRelations, $relation->toArray());
          }

          $this->constructed->{"rt_".$relationType->getId()}->exiting->count = count($exitingRelations);
          $this->constructed->{"rt_".$relationType->getId()}->exiting->relations = $exitingRelations;

          $this->constructed->{"rt_".$relationType->getId()}->entering = new stdClass();
          $enteringRelations = [];
          foreach($this->term->getEnteringRelations() as &$relation)
          {
              if ($relation->getType() == $relationType->getId())
                  array_push($enteringRelations, $relation->toArray());
          }

          $this->constructed->{"rt_".$relationType->getId()}->entering->count = count($enteringRelations);
          $this->constructed->{"rt_".$relationType->getId()}->entering->relations = $enteringRelations;

          $this->constructed->{"rt_".$relationType->getId()}->count = count($exitingRelations) + count($enteringRelations);
        }
    }

    public function commitRelatedTerms()
    {
        // does nothing
    }
}
