<?php
namespace Wax\SlimModel\Tests;
use Doctrine\DBAL\DriverManager;
use Wax\SlimModel\Model\IncludeManager;


class ModelEventsTest extends \PHPUnit_Framework_TestCase {

  public $db;

  public function setup() {
    $params = ['driver' => 'pdo_sqlite','memory' => true];
    $this->db = DriverManager::getConnection($params);
  }

  public function test_include_manager_trigger() {
    $setup = new MockModel($this->db);
    $setup->insert(["title"=>"Hello World"]);

    $model = new MockModel($this->db);
    $model->add_include("many", ["table"=>"jointable"]);

    $mock_includer = $this->getMockBuilder('Wax\SlimModel\Model\IncludeManager')
                          ->setMethods(["postFetch"])
                          ->getMock();
    $mock_includer->expects($this->once())
                  ->method('postFetch')
                  ->with($this->isInstanceOf("Wax\SlimModel\Model\ModelEventArgs"));
    $model->includeManager = $mock_includer;
    $model->find(1);

  }

  public function test_migrate_manager_trigger() {
    $this->setExpectedException('Doctrine\DBAL\DBALException');

    $model = new MockModel($this->db);
    $mock_migrator = $this->getMockBuilder('Wax\SlimModel\Model\MigrateManager')
                          ->setMethods(["onSchemaException"])
                          ->getMock();
    $mock_migrator->expects($this->once())
                  ->method('onSchemaException')
                  ->with($this->isInstanceOf("Wax\SlimModel\Model\ModelEventArgs")) ;

    $model->migrateManager = $mock_migrator;
    $model->insert(["title"=>"Hello World"]);

  }


}