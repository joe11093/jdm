<?php
require_once(dirname(__FILE__, 2) . '/classLoader.php');
require_once(dirname(__FILE__, 2) . '/utility.php');

class Parser
{
    /* ATTRIBUTES */
    private $term;

    private const LINE_SEPARATOR = "\r\n";
    private const ENTRY_SEPARATOR = ";";
    // public const USELESS_RTS = [
    //   12,18,19,29,33,36,45,46,47,48,66,118,
    //   128,200,444,555,1000,1001,1002,2001
    // ];

    public const RELATION_TYPES = [
      "r_associated" => 0,
      "r_raff_sem" => 1,
      "r_raff_morpho" => 2,
      "r_domain" => 3,
      "r_pos" => 4,
      "r_syn" => 5,
      "r_isa" => 6,
      "r_anto" => 7,
      "r_hypo" => 8,
      "r_has_part" => 9,
      "r_holo" => 10,
      "r_locution" => 11,
      "r_agent" => 13,
      "r_patient" => 14,
      "r_lieu" => 15,
      "r_instr" => 16,
      "r_carac" => 17,
      "r_has_magn" => 20,
      "r_has_antimagn" => 21,
      "r_family" => 22,
      "r_carac-1" => 23,
      "r_agent-1" => 24,
      "r_instr-1" => 25,
      "r_patient-1" => 26,
      "r_domain-1" => 27,
      "r_lieu-1" => 28,
      "r_lieu_action" => 30,
      "r_action_lieu" => 31,
      "r_sentiment" => 32,
      "r_manner" => 34,
      "r_meaning/glose" => 35,
      "r_telic_role" => 37,
      "r_agentif_role" => 38,
      "r_verbe-action" => 39,
      "r_action-verbe" => 40,
      "r_conseq" => 41,
      "r_causatif" => 42,
      "r_adj-verbe" => 43,
      "r_verbe-adj" => 44,
      "r_time" => 49,
      "r_object>mater" => 50,
      "r_mater>object" => 51,
      "r_successeur-time" => 52,
      "r_make" => 53,
      "r_product_of" => 54,
      "r_against" => 55,
      "r_against-1" => 56,
      "r_implication" => 57,
      "r_quantificateur" => 58,
      "r_masc" => 59,
      "r_fem" => 60,
      "r_equiv" => 61,
      "r_manner-1" => 62,
      "r_agentive_implication" => 63,
      "r_has_instance" => 64,
      "r_verb_real" => 65,
      "r_similar" => 67,
      "r_set>item" => 68,
      "r_item>set" => 69,
      "r_processus>agent" => 70,
      "r_variante" => 71,
      "r_syn_strict" => 72,
      "r_is_smaller_than" => 73,
      "r_is_bigger_than" => 74,
      "r_accomp" => 75,
      "r_processus>patient" => 76,
      "r_verb_ppas" => 77,
      "r_cohypo" => 78,
      "r_verb_ppre" => 79,
      "r_processus>instr" => 80,
      "r_der_morpho" => 99,
      "r_has_auteur" => 100,
      "r_has_personnage" => 101,
      "r_can_eat" => 102,
      "r_has_actors" => 103,
      "r_deplac_mode" => 104,
      "r_has_interpret" => 105,
      "r_color" => 106,
      "r_cible" => 107,
      "r_symptomes" => 108,
      "r_predecesseur-time" => 109,
      "r_diagnostique" => 110,
      "r_predecesseur-space" => 111,
      "r_successeur-space" => 112,
      "r_social_tie" => 113,
      "r_tributary" => 114,
      "r_sentiment-1" => 115,
      "r_linked-with" => 116,
      "r_foncteur" => 117,
      "r_but" => 119,
      "r_but-1" => 120,
      "r_own" => 121,
      "r_own-1" => 122,
      "r_verb_aux" => 123,
      "r_predecesseur-logic" => 124,
      "r_successeur-logic" => 125,
      "r_isa-incompatible" => 126,
      "r_incompatible" => 127,
      "r_require" => 129,
      "r_is_instance_of" => 130,
      "r_is_concerned_by" => 131,
      "r_symptomes-1" => 132,
      "r_units" => 133,
      "r_promote" => 134,
      "r_circumstances" => 135,
      "r_has_auteur-1" => 136,
      "r_processus>agent-1" => 137,
      "r_processus>patient-1" => 138,
      "r_processus>instr-1" => 139,
      "r_compl_agent" => 149,
      "r_beneficiaire" => 150,
      "r_descend_de" => 151,
      "r_domain_subst" => 152,
      "r_prop" => 153,
      "r_activ_voice" => 154,
      "r_make_use_of" => 155,
      "r_is_used_by" => 156,
      "r_adj-nomprop" => 157,
      "r_nomprop-adj" => 158,
      "r_adj-adv" => 159,
      "r_adv-adj" => 160,
      "r_homophone" => 161,
      "r_potential_confusion" => 162,
      "r_concerning" => 163,
      "r_adj>nom" => 164,
      "r_nom>adj" => 165,
      "r_opinion_of" => 166,
      "r_translation" => 333,
      "r_aki" => 666,
      "r_wiki" => 777,
      "r_annotation_exception" => 997,
      "r_annotation" => 998,
      "r_inhib" => 999,
      "r_raff_sem-1" => 2000
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

        if (in_array($arr_relationType[1], self::RELATION_TYPES))
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

        if (in_array($arr_relation[4], self::RELATION_TYPES))
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
