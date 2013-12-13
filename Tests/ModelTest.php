<?php
namespace Wax\SlimModel\Tests;
use Doctrine\DBAL\DriverManager;


class ModelTest extends \PHPUnit_Framework_TestCase {

  public $db;

  public function setup() {
    $params = ['driver' => 'pdo_sqlite','memory' => true];
    $this->db = DriverManager::getConnection($params);
  }

  public function test_create_db() {
    $this->setExpectedException('Doctrine\DBAL\DBALException');
    $model = new MockModel($this->db);
    $res = $model->insert(["name"=>"test"]);
  }

}
