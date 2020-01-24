<?php
class Relation
{
    /* ATTRIBUTES */
    private $symbol; // r
    private $id; // rid
    private $source; // node1
    private $destination; // node2
    private $type; // type
    private $weight; // w
    private $weightType; // wt (derived from w)

    /* CONSTRUCTOR */
    public function __construct($symbol, $id, $source, $destination, $type, $weight)
    {
        $this->symbol = $symbol;
        $this->id = $id;
        $this->source = $source;
        $this->destination = $destination;
        $this->type = $type;
        $this->setWeight($weight);
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

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getWeightType()
    {
        return $this->weightType;
    }

    private function setWeight($weight)
    {
        if ($weight < 0)
        {
            settype($weight, "int");
            $this->setWeightType("n");
            $weight *= -1;
            $weight = strval($weight);
        }

        else
          $this->setWeightType("p");

        $this->weight = $weight;
    }

    private function setWeightType($weightType)
    {
        $this->weightType = $weightType;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
