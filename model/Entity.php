<?php
require_once("Definition.php");
require_once("RelationType.php");
require_once("Relation.php");

class Entity
{
    /* ATTRIBUTES */
    private $symbol; // e
    private $id; // eid
    private $name; // name
    private $type; // type
    private $weight; // w

    private $definitions = []; // entity definitions
    private $entities = []; // related terms
    private $relationTypes = []; // types of relations in which the entity participates
    private $relations = []; // relations in which the entity participates;

    /* CONSTRUCTOR */
    public function __construct($symbol, $id, $name, $type, $weight, $formattedName)
    {
        $this->symbol = $symbol;
        $this->id = $id;

        if (!empty($formattedName))
          $this->name = $formattedName;
        else
          $this->name = $name;

        $this->type = $type;
        $this->weight = $weight;
    }

    /* METHODS */
    public function getSymbol()
    {
        return $this->symbol;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setEntity($entity)
    {
        $this->id = $entity->id;
        $this->name = $entity->name;
        $this->type = $entity->type;
        $this->weight = $entity->weight;
    }

    public function &getDefinitions()
    {
        foreach($this->definitions as $definition)
            yield $definition;
    }

    public function getDefinitionsCount()
    {
        return count($this->definitions);
    }

    public function addDefinition($number, $content)
    {
        array_push($this->definitions, new Definition($number, $content));
    }

    public function &getEntities()
    {
        foreach($this->entities as $entity)
            yield $entity;
    }

    public function getEntitiesCount()
    {
        return count($this->entities);
    }

    public function getEntityById($id)
    {
        if (!array_key_exists($id, $this->entities))
            return null;

        return $this->entities[$id];
    }

    public function addEntity($symbol, $id, $name, $type, $weight, $formattedName)
    {
        $this->entities[$id] = new Entity($symbol, $id, $name, $type, $weight, $formattedName);
    }

    public function &getRelationTypes()
    {
        foreach($this->relationTypes as $relationType)
            yield $relationType;
    }

    public function getRelationTypesCount()
    {
        return count($this->relationTypes);
    }

    public function addRelationType($symbol, $id, $name, $description, $help)
    {
        array_push($this->relationTypes, new RelationType($symbol, $id, $name, $description, $help));
    }

    public function &getRelations()
    {
        foreach($this->relations as $relation)
            yield $relation;
    }

    public function getRelationsCount()
    {
        return count($this->relations);
    }

    public function addRelation($symbol, $id, $source, $destination, $type, $weight, $isEntering)
    {
        $relation = new Relation($symbol, $id, $source, $destination, $type, $weight);
        $relation->setIsEntering($isEntering);
        array_push($this->relations, $relation);
    }

    public function adaptRelationsNodes()
    {
        foreach ($this->getRelations() as &$relation)
        {
            $source = $this->getEntityById($relation->getSource());
            if ($source != null)
              $relation->setSource($source->getName());

            $destination = $this->getEntityById($relation->getDestination());
            if ($destination != null)
              $relation->setDestination($destination->getName());
        }
    }

    public function sortRelationsByDescWeight()
    {
        usort($this->relations, function ($rel1, $rel2) {
          return $rel2->getWeight() <=> $rel1->getWeight();
        });
    }

    public function sortRelationsByFrLexicOrder()
    {
        $previousLocale = setlocale(LC_ALL, 0);

        if (setlocale(LC_ALL, "fr_FR.utf8") !== false)
        {
            usort($this->relations, function($rel1, $rel2) {

              if (strtolower($this->getName()) == strtolower($rel1->getSource())) // exiting relation
                  return strcoll(strtolower($rel1->getDestination()), strtolower($rel2->getDestination()));

              else // entering relation
                  return strcoll(strtolower($rel1->getSource()), strtolower($rel2->getSource()));
            });

            setlocale(LC_ALL, $previousLocale);
        }
    }

    public function toArray()
    {
        $filtered = array_filter(get_object_vars($this), function($att) {

          return gettype($att) != "array";

        });

        return $filtered;
    }
}
