<!DOCTYPE html>
<html lang=ru>
<meta charset=utf-8>
<head>
  <title>Project - Test DataImportToMySQL</title>
</head>
<body>

<?php
require_once 'connection.php';
require_once 'mysqlwork.class.php';
require_once 'dataimporttomysql.class.php';

error_reporting(E_ALL);
if(ini_set('display_errors', 1)===false)
  echo "ERROR INI SET";

$MW = new MySQLWork(HOST, USER, PASSWORD, DATABASE);

if(!$MW->mysqliTest()){
  goto END;
}

# recreate test table
$MW->query('DROP TABLE IF EXISTS TEST;');
$query = 'CREATE TABLE `TEST` (
  `idTEST` int(11) NOT NULL AUTO_INCREMENT,
  `A` varchar(45) DEFAULT NULL,
  `C` varchar(45) DEFAULT NULL,
  `D` varchar(45) DEFAULT NULL,
  `E` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idTEST`)
);';

$MW->query($query);

$DI = new DataImportToMySQL($MW);

$task_options = [
  'import_table_info' => 'TEST(A,C,D,E)',
  'truncate_table' => false
];

$DI->set_task($task_options);

# 1. data for insert
echo "<h3>1. Data for Import</h3><pre>";
$data = [
  [1, '2020-03-20', 'John', '1111111'],
  [2, '2020-03-21', 'Lina', '2222222'],
  [3, '2020-03-22', 'Loss', '3516451654654']
];
var_dump($data);
$DI->set_data($data);
$DI->import();

echo "<h3>data in table TEST</h3>";
$arr = $MW->arraySQL('SELECT * FROM TEST;');
var_dump($arr);


# 2. data for insert
echo "<h3>2. Data for Import</h3>";
$data = [
  ['A' => 4, 'C' => '2020-03-23', 'D' => 'Kant', 'E' => '654654654654'],
  ['A' => 5, 'C' => '2020-03-24', 'D' => 'Mira', 'E' => '645654654654654'],
  ['A' => 6, 'C' => '2020-03-25', 'D' => 'Tonn', 'E' => '79879'],
];
var_dump($data);
$DI->import($data);

$data = [];
$DI->import($data, true);

echo "<h3>data in table TEST</h3>";
$arr = $MW->arraySQL('SELECT * FROM TEST;');
var_dump($arr);

echo "</pre>";

$MW->close();

END:
?>

</body>
</html>
