<?php

namespace Wax\SlimModel\Model;

class Base {
    protected $db;
    protected $table;
    protected $columns = [];
    protected $primary_key;

    public function __construct($db = false) {
      $this->setDB($db);
    }

    public function setDB($db) {
        $this->db = $db;
    }

    public function setTable($table) {
      $this->table = $table;
    }

    public function all() {
      $sql = "SELECT * FROM `$this->table`";
      $result = $this->db->fetchAll($sql);
      return $result;
    }

    public function find($id) {
      $sql = "SELECT * FROM `$this->table` WHERE `id` = ?";
      $result = $this->db->fetchAssoc($sql, [$id]);
      return $result;
    }

    public function delete($id) {
      return $this->db->delete($this->table, ['id' => $id]);
    }

    public function insert($params=[]) {
      return $this->db->insert($this->table, $params);
    }

    public function update($id, $params=[]) {
      return $this->db->update($this->table, $params, ['id' => $id]);
    }

    public function define($name, $type="string", $options=[]) {
      $this->columns[$name] = ["type"=>$type, "options"=>$options];
    }

    public function syncdb() {
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

      $table->setPrimaryKey(array("id"));
      $queries = $schema->getMigrateFromSql($original_schema, $platform);
      foreach ($queries as $query) {
        $this->db->query($query);
      }
    }


}
