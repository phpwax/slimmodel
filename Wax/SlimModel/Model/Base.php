<?php
namespace Wax\SlimModel\Model;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DBALException;


class Base {
    protected $db;
    protected $table;
    protected $columns      = [];
    protected $primary_key  = "id";

    public    $freeze = false;

    public function __construct($db = false) {
      $this->setDB($db);
      $this->setup();
    }

    public function setDB($db) {
        $this->db = $db;
    }

    public function setTable($table) {
      $this->table = $table;
    }

    public function setup(){}

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
      return $result;
    }


}
