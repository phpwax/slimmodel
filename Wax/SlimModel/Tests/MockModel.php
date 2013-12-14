<?php
namespace Wax\SlimModel\Tests;
use Wax\SlimModel\Model\Base;

class MockModel extends Base {
  protected $table        = "example";
  protected $primary_key  = "id";

  public function setup() {
    $this->define("id",   "integer",  ["autoincrement"=>true]);
    $this->define("title","string",   []);

  }

}
