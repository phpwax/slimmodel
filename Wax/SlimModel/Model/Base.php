<?php
namespace Wax\SlimModel\Model;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DBALException;
use Doctrine\Common\EventManager;



class Base {
    protected $db;
    protected $table;
    protected $columns      = [];
    protected $primary_key  = "id";
    protected $events;

    public    $freeze = false;
    public    $includes = false;

    public function __construct($db = false, EventManager $eventManager = null) {
      $this->setDB($db);
      $this->setup();
      if(!$eventManager) $this->events = new EventManager();
    }

    public function setDB($db) {
        $this->db = $db;
    }

    public function setTable($table) {
      $this->table = $table;
    }

    public function setup(){}

    public function add_include($type, $options = []) {
      if(!isset($options["table"])) throw new \InvalidArgumentException("Table must be specified in an include");
      if(!isset($options["join"])) $options["join"] = $this->table."_".$options["table"];
      if(!isset($options["key"])) $options["key"] = "id";
      if(!isset($options["join_key"])) $options["join_key"] = "id";
      if(!isset($options["as"])) $options["as"] = $options["table"];
      if(!isset($options["join_left_key"])) $options["join_left_key"] = $this->table."_id";
      if(!isset($options["join_right_key"])) $options["join_right_key"] = $options["table"]."_id";
      $options["origin"] = $this->table;
      $this->includes[$type][] = $options;
    }

    /* The following methods all hit the database connection */

    public function all() {
      return $this->execute(function(){
        $sql = "SELECT * FROM `$this->table`";
        $result = $this->db->fetchAll($sql);
        return $result;
      });
    }

    public function find($id) {
      return $this->execute(function() use($id){
        $sql = "SELECT * FROM `$this->table` WHERE `$this->primary_key` = ?";
        return $this->db->fetchAssoc($sql, [$id]);
      });
    }

    public function delete($filters) {
      return $this->execute(function() use($filters){
        return $this->db->delete($this->table, $filters);
      });
    }

    public function insert($params=[]) {
      return $this->execute(function() use($params){
        return $this->db->insert($this->table, $params);
      });
    }

    public function update($id, $params=[]) {
      return $this->execute(function() use($id, $params){
        return $this->db->update($this->table, $params, [$this->primary_key => $id]);
      });
    }

    public function define($name, $type="string", $options=[]) {
      $this->columns[$name] = ["type"=>$type, "options"=>$options];
    }

    protected function migrate() {
      /* Database preparation commands */
      $platform = $this->db->getDatabasePlatform();
      $sm = $this->db->getSchemaManager();
      $original_schema = $sm->createSchema();
      $schema = new Schema();


      /* Now use the Schema object to create a table */
      if(!$schema->hasTable($this->table)) $table = $schema->createTable($this->table);
      else $table = $schema->getTable($this->table);

      foreach($this->columns as $name=>$options) {
        $table->addColumn($name,   $options["type"],  $options["options"]);
      }

      $table->setPrimaryKey(array($this->primary_key));
      $queries = $schema->getMigrateFromSql($original_schema, $platform);
      foreach ($queries as $query) {
        $this->db->query($query);
      }
    }

    protected function execute($callable) {
      if(!$this->db) throw new ConnectionException("No database Connection Specified", 1);
      try {
        $result = $callable();
      } catch (DBALException $e) {
        if($this->freeze) throw $e;
        $exception = $e->getPrevious();
        $error = $exception->errorInfo;
        switch($error[0]) {
          case "HY000":
            try {
              $this->migrate();
              $result = $callable();
            } catch (Exception $e) {
              throw new SchemaException("Invalid Schema", 1);
            }
          break;

        }
      }
      return $this->post_process($result);
    }

  protected function post_process($resultset) {
    if(!$this->includes) return $resultset;
    if(!count($resultset)) return $resultset;
    if(!isset($this->includes["many"])) return $resultset;
    foreach($this->includes["many"] as $inc) {
      $resultset = $this->include_many($resultset, $inc);
    }
    return $resultset;
  }

  protected function include_many($resultset, $options) {
    if(!is_array($resultset)) return $resultset;
    foreach($resultset as $res) {
      $index[]= $res[$options["key"]];
    }
    $jq = $this->db->createQueryBuilder();
    $jq->select("l.id as lkey, r.*")
       ->from($options["origin"],"l")
       ->leftjoin("l", $options["join"], "j", "j.{$options['join_left_key']} = l.{$options['key']}")
       ->leftjoin("l", $options["table"],"r", "r.{$options['join_key']} = j.{$options['join_right_key']}")
       ->where($jq->expr()->in("l.{$options['key']}", $index))
       ->andwhere("r.{$options['join_key']} IS NOT NULL");
    $joins = $jq->execute()->fetchAll();
    array_walk($resultset, function(&$value, $key, $params){
      $options = $params["options"];
      foreach($params["joins"] as $row) {
        if($row["lkey"]==$value[$options["key"]]) {
          unset($row["lkey"]);
          $value[$options["as"]][] = $row;
        }
      }
    },["joins"=>$joins,"options"=>$options]);
    return $resultset;
  }




}
