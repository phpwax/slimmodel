## Slim-Model

### A lighter version of WaxModel

Boasting a similar feature set to the original Wax Database component, Slim-Model is ruthlessly small, whilst keeping
many of the features of the original gargantuan library.

This package delegates all the heavy lifting to Doctrine/DBAL and throws out the concept of complex objects for models
and fields. Instead, everything is a plain PHP object.

### What Slim-Model Can Do

#### Model construction, passing in a connection

First up you need to create a new model that extends `Wax\SlimModel\Model\Base`

It will look something like the below...

    ....
    use Wax\SlimModel\Model\Base;

    class Example extends Base {
      protected $table        = "example";
      protected $primary_key  = "id";

      public function setup() {
        $this->define("id",   "integer",  ["autoincrement"=>true]);
        $this->define("title","string",   []);
      }

    }

Note that there's no automatic fields in this package. You need to define a primary key.

Now that you have a model, we can get to work. The only assumption is that you have a DBALConnection object ready to pass in.
In reality you will probably want to delgate object creation to your application to avoid continually passing around the connection object.
For this example and to show that the module works without any tight coupling we're going to pass in the connection on construct.

#### Inserts

Inserts to the database just take an array of properties.

    $model = new Example($db_connection);
    $result = $model->insert(["title"=>"Hello World"]);

#### Updates

Updates take a primary key and some data to update.

    $model = new Example($db_connection);
    $result = $model->update(1, ["title"=>"Hello Again"]);

#### Deleting Rows

Select a row by properties and delete...

    $model = new Example($db_connection);
    $result = $model->delete(["id"=>1]);

#### Finding Rows

This is just a quick helper to fetch a database row by primary key; for example:

    $model = new Example($db_connection);
    $result = $model->find(1]);
    // returns ["id"=>1, "title"=>"Hello Again"]

To do any more advanced queries, use the query builder that ships with DBAL. You can get a builder object by doing the following:

    $queryBuilder = $db_connection->createQueryBuilder();

And then make a query like below:

      $queryBuilder
          ->select('u.id', 'u.name')
          ->from('users', 'u')
          ->where('u.email = ?')
          ->setParameter(1, $userInputEmail)
      ;

### And What it Can't Do

Magic.

Data is returned as simple objects. If you need anything more complicated then write helper methods to transform.

Joins and advanced filters can be created by using the functionality in Querybuilder. For example here's a query that mimics a CMS style router.

    $url = "/content/an-example-page";
    $query = $db_connection->createQueryBuilder();
    $query->select("*")
          ->from("wildfire_url_map","u")
          ->leftjoin("u", "wildfire_content", "c", "u.destination_id = c.id")
          ->where("u.origin_url = :url")
          ->andwhere("u.status = 1")
          ->setParameter("url","/".$url);
    $result = $query->execute()->fetch();

### Notes on field types for defines.

Note that these are not compatible with older style field definitions such as `CharField`, `IntegerField` etc.

All Doctrine DBAL types are available including guid (YAY!), so check the api docs for details at:

http://www.doctrine-project.org/api/dbal/2.4/namespace-Doctrine.DBAL.Types.html







