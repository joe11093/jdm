<?php
include_once(dirname(__FILE__, 2) . '/classLoader.php');
include_once(dirname(__FILE__, 2) . '/utility.php');

class Parser
{
    /* ATTRIBUTES */
    private $term;

    private const LINE_SEPARATOR = "\r\n";
    private const ENTRY_SEPARATOR = ";";
    private const USELESS_RTS = [
      12,18,19,29,33,36,45,46,47,48,66,118,
      128,200,444,555,1000,1001,1002,2001
    ];

    /* CONSTRUCTOR */

    public function __construct()
    {
        $this->term = new Entity("e", "", "", "", "", "");
    }

    public function getTerm()
    {
        return $this->term;
    }

    public function stripTags($page)
    {
        return strip_tags($page);
    }

    public function filterWhitespace($page)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $page);
    }

    public function isDefinition($line)
    {
        return preg_match("/^\d+\..+(?:<br(\s)?(\/)?>)?/", $line);
    }

    public function extractDefinition($line)
    {
        $matches = [];
      	preg_match("/^(\d+)\.(.+)(?:<br(?:\s)?(?:\/)?>)?/", $line, $matches);

        if (!empty($matches)) //0 = whole line, 1 = number, 2 = content
        {
            $definition = new Definition($matches[1], trim($matches[2]));
            $this->term->addDefinition(
              $matches[1], // number
              trim($matches[2]) // content
            );
            return $definition;
        }

        return null;
    }

    public function isEntity($line)
    {
        return startsWith($line, "e;");
    }

    public function extractEntity($line, &$isFirst)
    {
        $arr_entity = explode(self::ENTRY_SEPARATOR, $line);

        if (count($arr_entity) == 5)
            $entity = new Entity(
              $arr_entity[0], // symbol
              $arr_entity[1], // id
              trim($arr_entity[2], "'"), // name
              $arr_entity[3], // type
              $arr_entity[4],  // weight
              "" // no formatted name provided
            );

        elseif (count($arr_entity) == 6)
            $entity = new Entity(
              $arr_entity[0], // symbol
              $arr_entity[1], // id
              trim($arr_entity[2], "'"), // name, will be replaced by formattedName
              $arr_entity[3], // type
              $arr_entity[4], // weight
              trim($arr_entity[5], "'") // formattedName
            );
        else
            return;

        if ($isFirst)
        {
  					$isFirst = false;
  					$this->term->setEntity($entity);
				}

        $this->term->addEntity(
          $arr_entity[0], // symbol
          $arr_entity[1], // id
          $entity->getName(), // name
          $arr_entity[3], // type
          $arr_entity[4], // weight
          "" // no formatted name required, name is provided directly from $entity
        );

        return $entity;
    }

    public function isRelationType($line)
    {
        return startsWith($line, "rt;");
    }

    public function extractRelationType($line)
    {
        $arr_relationType = explode(self::ENTRY_SEPARATOR, $line);

        if (!in_array($arr_relationType[1], self::USELESS_RTS))
          $this->term->addRelationType(
            $arr_relationType[0], // symbol
            $arr_relationType[1], // id
            $arr_relationType[2], // name
            $arr_relationType[3], // description
            $arr_relationType[4] // help
          );
    }

    public function isRelation($line)
    {
        return startsWith($line, "r;");
    }

    public function extractRelation($line)
    {
        $arr_relation = explode(self::ENTRY_SEPARATOR, $line);

        // we can modify the condition below to access the entering relations as well
        if ($arr_relation[2] == $this->term->getId() && !in_array($arr_relation[4], self::USELESS_RTS))
        {
            $this->term->addRelation(
              $arr_relation[0], // symbol
              $arr_relation[1], // id
              $arr_relation[2], // source
              $arr_relation[3], // destination
              $arr_relation[4], // type
              $arr_relation[5] // weight (weight type is derived from it)
            );
  			}
    }

    private function preprocess($page)
    {
        $extract = $this->stripTags($page);
        $extract = $this->filterWhitespace($extract);

        return $extract;
    }

    public function parse($page)
    {
        $page = $this->preprocess($page);
        $line = strtok($page, self::LINE_SEPARATOR);
      	$isFirst = true;

        while($line !== false)
        {
            if ($this->isDefinition($line))
                $this->extractDefinition($line);

            elseif ($this->isEntity($line))
                $this->extractEntity($line, $isFirst);

            elseif ($this->isRelationType($line))
                $this->extractRelationType($line);

            elseif ($this->isRelation($line))
                $this->extractRelation($line);

            $line = strtok(self::LINE_SEPARATOR);
        }

        $this->term->adaptRelationsNodes();
        return $this->term;
    }
}
