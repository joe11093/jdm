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
    private $enteringRelations = []; // entering relations in which the entity participates;
    private $exitingRelations = []; // exiting relations in which the entity participates;

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

    public function &getEnteringRelations()
    {
        foreach($this->enteringRelations as $relation)
            yield $relation;
    }

    public function getEnteringRelationsCount()
    {
        return count($this->enteringRelations);
    }

    public function &getExitingRelations()
    {
        foreach($this->exitingRelations as $relation)
            yield $relation;
    }

    public function getExitingRelationsCount()
    {
        return count($this->exitingRelations);
    }

    public function addRelation($symbol, $id, $source, $destination, $type, $weight)
    {
        $relation = new Relation($symbol, $id, $source, $destination, $type, $weight);

        if ($destination == $this->getId())
            array_push($this->enteringRelations, $relation);
        else
            array_push($this->exitingRelations, $relation);
    }

    public function adaptRelationsNodes()
    {
        foreach ($this->getEnteringRelations() as &$relation)
        {
            $source = $this->getEntityById($relation->getSource());
            if ($source != null)
                $relation->setSource($source->getName());

            $relation->setDestination($this->getName());
        }

        foreach ($this->getExitingRelations() as &$relation)
        {
            $relation->setSource($this->getName());

            $destination = $this->getEntityById($relation->getDestination());
            if($destination != null)
                $relation->setDestination($destination->getName());
        }
    }

    public function sortRelationsByDescWeight($orientation)
    {
        if ($orientation == "entering")
            usort($this->enteringRelations, ["Entity", "compareRelationsByDescWeight"]);
        elseif ($orientation == "exiting")
            usort($this->exitingRelations, ["Entity", "compareRelationsByDescWeight"]);

        elseif ($orientation == "both")
        {
            usort($this->enteringRelations, ["Entity", "compareRelationsByDescWeight"]);
            usort($this->exitingRelations, ["Entity", "compareRelationsByDescWeight"]);
        }
    }

    public static function compareRelationsByDescWeight($rel1, $rel2)
    {
        return $rel2->getWeight() <=> $rel1->getWeight();
    }

    public function sortRelationsByFrLexicOrder($orientation)
    {
        $previousLocale = setlocale(LC_ALL, 0);

        if (setlocale(LC_ALL, "fr_FR.utf8") !== false)
        {
            if ($orientation == "entering")
                usort($this->enteringRelations, function($rel1, $rel2) {
                    return strcoll(strtolower($rel1->getSource()), strtolower($rel2->getSource()));
                });

            elseif ($orientation == "exiting")
                usort($this->exitingRelations, function($rel1, $rel2) {
                    return strcoll(strtolower($rel1->getDestination()), strtolower($rel2->getDestination()));
                });

            elseif ($orientaton = "both")
            {
                usort($this->enteringRelations, function($rel1, $rel2) {
                    return strcoll(strtolower($rel1->getSource()), strtolower($rel2->getSource()));
                });

                usort($this->exitingRelations, function($rel1, $rel2) {
                    return strcoll(strtolower($rel1->getDestination()), strtolower($rel2->getDestination()));
                });
            }

            setlocale(LC_ALL, $previousLocale);
        }
    }

    public function toArray()
    {
        return array_filter(get_object_vars($this), function($att) {
            return gettype($att) != "array";
        });
    }
}
