<?php
namespace PicORM\tests\units;

use \atoum;

class Model extends atoum
{
    public function afterTestMethod($testMethod)
    {
        \PicORM\Model::getDataSource()->query('TRUNCATE brands');
        \PicORM\Model::getDataSource()->query('TRUNCATE cars');
        \PicORM\Model::getDataSource()->query('TRUNCATE car_have_tag');
        \PicORM\Model::getDataSource()->query('TRUNCATE tags');
    }

    //// DATA PROVIDERS /////
    public static function createAndSaveRawModel()
    {
        include_once __DIR__ . '/../scripts/tested_models.php';
        $testBrand = new \Brand();
        $testBrand->nameBrand = 'Acme';
        $testBrand->noteBrand = 10;
        $testBrand->save();

        $req = \PicORM\Model::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req->execute(array($testBrand->idBrand));
        $bddResult = $req->fetch(\PDO::FETCH_ASSOC);


        return array(array($testBrand, $bddResult));
    }

    public static function createAndSaveRawModelForUpdateDelete()
    {
        include_once __DIR__ . '/../scripts/tested_models.php';
        $testBrand = new \Brand();
        $testBrand->nameBrand = 'Acme';
        $testBrand->noteBrand = 10;
        $testBrand->save();


        return array($testBrand);
    }

    public static function createAndSaveRawModelWithOneToOneRelation()
    {
        include_once __DIR__ . '/../scripts/tested_models.php';

        $testBrand = new \Brand();
        $testBrand->nameBrand = 'Acme';
        $testBrand->noteBrand = 10;
        $testBrand->save();

        $car = new \Car();
        $car->nameCar = 'AcmeCarcreateAndSaveRawModelWithOneToOneRelation';
        $car->noteCar = '10';
        $car->setBrand($testBrand);
        $car->save();

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ? AND idCar = ?');
        $req->execute(array($testBrand->idBrand, $car->idCar));
        $resultBDD = $req->fetch(\PDO::FETCH_ASSOC);

        return array(
            array($testBrand, $car, $resultBDD)
        );
    }

    public static function createAndSaveRawModelWithManyToManyRelation()
    {
        include_once __DIR__ . '/../scripts/tested_models.php';

        $car = new \Car();
        $car->nameCar = 'AcmeCar';
        $car->noteCar = '10';
        $car->idBrand = 1;
        $car->save();

        $tags = array();

        $tag1 = new \Tag();
        $tag1->libTag = 'Sport';
        $tag1->save();
        $tag2 = new \Tag();
        $tag2->libTag = 'Family';
        $tag2->save();
        $tag3 = new \Tag();
        $tag3->libTag = 'Crossover';
        $tag3->save();
        $tags[] = $tag1;
        $tags[] = $tag2;
        $tags[] = $tag3;

        $car->setTag($tags);
        $car->save();

        // create test
        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM car_have_tag WHERE idCar = ?');
        $req->execute(array($car->idCar));
        $resultBDD = $req->fetch(\PDO::FETCH_ASSOC);

        return array(
            array($car, $tags, $resultBDD)
        );
    }

    public static function createAndSaveRawModelWithOneToManyRelation()
    {
        include_once __DIR__ . '/../scripts/tested_models.php';

        $testBrand = new \Brand();
        $testBrand->nameBrand = 'AcmeMult';
        $testBrand->noteBrand = 10;
        $testBrand->save();

        $car = new \Car();
        $car->nameCar = 'AcmeCar1';
        $car->noteCar = '10';

        $car2 = new \Car();
        $car2->nameCar = 'AcmeCar2';
        $car2->noteCar = '12';

        $car3 = new \Car();
        $car3->nameCar = 'AcmeCar3';
        $car3->noteCar = '15';

        $cars = array($car, $car2, $car3);

        $testBrand->setCar($cars);

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ?');
        $req->execute(array($testBrand->idBrand));
        $resultBDD = $req->fetch(\PDO::FETCH_ASSOC);

        return array(
            array($testBrand, $cars, $resultBDD)
        );
    }

    //// END DATA PROVIDERS /////

    /**
     * @dataProvider createAndSaveRawModelWithManyToManyRelation
     */
    public function testManyToManyRelationCreation($car, $tags, $resultBDD)
    {
        $this
            ->string($resultBDD['nb'])->isEqualTo("3");
    }

    /**
     * @dataProvider createAndSaveRawModelWithManyToManyRelation
     */
    public function testManyToManyRelation($car, $tags, $resultBDD)
    {
        $this
            ->if($tagsGet = $car->getTag())
            ->integer(count($tagsGet))->isEqualTo(3)
            ->string($tagsGet[0]->libTag)->isEqualTo($tags[0]->libTag)
            ->string($tagsGet[1]->libTag)->isEqualTo($tags[1]->libTag)
            ->string($tagsGet[2]->libTag)->isEqualTo($tags[2]->libTag);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOneToManyRelationCreation($testBrand, $testCars, $resultBDD)
    {
        $this->if($testBrand instanceof \Brand)
            ->and(!is_array($testCars))
            ->string($resultBDD['nb'])->isEqualTo('3');
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOneToManyRelation($testBrand, $testCars, $resultBDD)
    {

        $this
            ->if($cars = $testBrand->getCar())
            ->then
            ->string($cars[0]->nameCar)->isEqualTo($testCars[0]->nameCar)
            ->string($cars[1]->nameCar)->isEqualTo($testCars[1]->nameCar)
            ->string($cars[2]->nameCar)->isEqualTo($testCars[2]->nameCar);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToOneRelation
     */
    public function testOneToOneRelationCreation($testBrand, $car, $dbRes)
    {
        $this
            ->string($dbRes['nb'])->isEqualTo('1');
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToOneRelation
     */
    public function testOneToOneRelation($testBrand, $car, $dbRes)
    {
        $this
            // test get relation
            ->string($car->getBrand()->nameBrand)
            ->isEqualTo($testBrand->nameBrand)
            // test autoget field
            ->string(\Car::findOne(array('idCar' => $car->idCar))->nameBrand)
            ->isEqualTo($testBrand->nameBrand);
    }

    /**
     * @dataProvider createAndSaveRawModelForUpdateDelete
     */
    public function testDeleteModel($testBrand)
    {

        $this
            ->if($testBrand->delete());

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM brands WHERE idBrand = ?');
        $req->execute(array($testBrand->idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this
            ->string($res['nb'])->isEqualTo("0")
            ->boolean($testBrand->isNew())->isEqualTo(true);
    }

    /**
     * @dataProvider createAndSaveRawModelForUpdateDelete
     */
    public function testUpdateModel($testBrand)
    {
        $this
            ->if($testBrand->nameBrand = 'NEWNAME!')
            ->and($testBrand->noteBrand = '5')
            ->boolean($testBrand->save())->isEqualTo(true);

        $req = \PicORM\Model::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req->execute(array($testBrand->idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);


        $this->string($res['nameBrand'])->isEqualTo('NEWNAME!')
            ->string($res['noteBrand'])->isEqualTo('5');
    }

    /**
     * @dataProvider createAndSaveRawModel
     */
    public function testCreateModel($testBrand, $bddResult)
    {
        $this
            ->boolean($testBrand->isNew())->isEqualTo(false)
            ->variable($testBrand->idBrand)->isNotEqualTo(null)
            ->variable($bddResult)->isNotEqualTo(false)
            ->string($bddResult['nameBrand'])->isEqualTo('Acme')
            ->string($bddResult['noteBrand'])->isEqualTo('10');
    }

    public function testFormatClassnameToRelationName() {
        $method = new \ReflectionMethod('\PicORM\Model', 'formatClassnameToRelationName');
        $method->setAccessible(true);

        $this -> string($method->invoke(null,'Class'))->isEqualTo('class');
        $this -> string($method->invoke(null,'Namespace\To\The\Class'))->isEqualTo('class');
    }

    public function testFormatDatabaseNameMySQL() {
        $this -> string(\TestModel::formatDatabaseNameMySQL())->isEqualTo('`testbddmodel`.');
    }

    public function testFormatTableNameMySQL() {
        $this -> string(\TestModel::formatTableNameMySQL())->isEqualTo('`testbddmodel`.`testmodel`');
    }

    /**
     * @dataProvider createAndSaveRawModel
     */
    public function test__toJson($testBrand, $bddResult) {
        $this -> string($testBrand->__toJson())
                    -> isEqualTo('{"idBrand":"1","nameBrand":"Acme","noteBrand":10}');
    }


    /**
     * @dataProvider createAndSaveRawModelWithManyToManyRelation
     */
    public function testUnsetRelation($car, $tags, $resultBDD) {
        $car -> unsetTag($tags);

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM car_have_tag WHERE idCar = ?');
        $req->execute(array($car->idCar));
        $resultBDD = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> string($resultBDD['nb']) -> isEqualTo("0");
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToOneRelation
     */
    public function testAddRelationOneToOne($testBrand, $car, $dbRes) {
        $class = new \ReflectionClass(get_class($car));

        $property = $class->getProperty("_relations");
        $property->setAccessible(true);
        $relations = $property->getValue();

        $this -> boolean(isset($relations['brand'])) -> isEqualTo(true)
              -> integer($relations['brand']['typeRelation']) -> isEqualTo(\PicORM\Model::ONE_TO_ONE)
              -> string($relations['brand']['classRelation']) -> isEqualTo('Brand')
              -> string($relations['brand']['sourceField']) -> isEqualTo('idBrand')
              -> string($relations['brand']['targetField']) -> isEqualTo('idBrand')
              -> boolean(is_array($relations['brand']['autoGetFields'])) -> isEqualTo(true)
              -> boolean(count($relations['brand']['autoGetFields']) == 1) -> isEqualTo(true)
              -> string($relations['brand']['autoGetFields'][0]) -> isEqualTo("nameBrand");
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testAddRelationOneToMany($testBrand, $cars, $resultBDD) {
        $class = new \ReflectionClass(get_class($testBrand));

        $property = $class->getProperty("_relations");
        $property->setAccessible(true);
        $relations = $property->getValue();

        $this -> boolean(isset($relations['car'])) -> isEqualTo(true)
              -> integer($relations['car']['typeRelation']) -> isEqualTo(\PicORM\Model::ONE_TO_MANY)
              -> string($relations['car']['classRelation']) -> isEqualTo('Car')
              -> string($relations['car']['sourceField']) -> isEqualTo('idBrand')
              -> string($relations['car']['targetField']) -> isEqualTo('idBrand');
    }
}