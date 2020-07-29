<?php
require_once 'errormsg.trait.php';
/**
 * Class DataImportToMySQL
 *
 * @version 1.0.0
 */
class DataImportToMySQL{
  use ErrorMessage;

  # class MySQLWork
  protected $MW = NULL;

  protected $task_options = [];

  protected $magic = 75000;

  protected $data_array = [];

  protected $import_table_name = '';

  /**
   * DataImportToMySQL constructor
   *
   * @param MySQLWork $MW        MySQLWork
   * @param array  $task_options array with import task options
   * @throws Exception  MySQLWork
   * @throws Exception  mysqli connection not set
   * @throws Exception  set_task_options
   */
  public function __construct(MySQLWork $MW, array $task_options = []) {
    if(!$MW->mysqliTest()){
      throw new Exception("mysqli connection not set");
    }

    $this->MW = $MW;

    if(count($task_options) > 0){
      $this->set_task($task_options);
    }
  }

  /**
   * Set import task_options from array
   *
   * @param array $task_options   task options
   *        import_table_info   - table_name(fild1,fild2,...fildN)
   *        rows_empty_allowed_cnt - if we mast save empty rows when importing XLS tables
   *        truncate_table      - if set TRUE will be truncated table from import_table_info
   * @throws Exception  Array task_options not set
   * @throws Exception  Not set import_table_info
   * @throws Exception  set_template_array
   */
  public function set_task(array $task_options) : bool {
    if(count($task_options) == 0){
      return $this->err("Array task_options not set", 1, true);
    }

    if( !array_key_exists('import_table_info', $task_options) || $task_options['import_table_info'] == ''){
      return $this->err("Not set import_table_info in the array of parameters for the import task", 1, true);
    }

    $this->task_options = [];

    # table where data was inserted
    $this->import_table_name = trim(substr($task_options['import_table_info'], 0, strpos($task_options['import_table_info'], "(")));
    if(!$this->check_table_import()){
      return false; // error message without change
    }

    $this->task_options['import_table_info']      = $this->MW->test($task_options['import_table_info']);
    $this->task_options['rows_empty_allowed_cnt'] = $task_options['rows_empty_allowed_cnt'] ?? 0 ;
    $this->task_options['truncate_table']         = $task_options['truncate_table']         ?? TRUE;

    return $this->ok();
  }

  /**
   * Checking the existence of the table for import
   *
   * @return bool
   */
  protected function check_table_import() : bool {
    # check table on exist
    $result = $this->MW->query("SHOW TABLES LIKE '$this->import_table_name';");
    if($result->num_rows == 0) {
      $result->free();
      return $this->err("The table '$this->import_table_name' does not exist data cannot be imported", 1, true);
    }
    $result->free();

    return $this->ok();
  }

  /**
   * Format array row in string expr for insertion
   *
   * @return string;
   */
  protected final function insertArrStr(array $rgArr) : string {
  /*$regex = <<<'END'
  /
    (
      (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
      |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
      |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
      |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
      ){1,100}                      # ...one or more times
    )
  | ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
  | ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
  /x
  END;
  # удаляет 4-х байтовые символы, например, смайлики
  $text='Babulitta👵🏼🌈 Иришка💄👱🏻‍♀️ Папа💕 Александрия❤️😍 Мамуля💖'
  echo '<h3>' . preg_replace('/((?:[\xF0-\xF7][\x80-\xBF]{3}){1,100})/x', '', $text) . '</h3>';
  */
      $str="(";
      foreach ($rgArr as $sValue)
        $str .= ($str == "(" ? "" : ",") . "'". $this->MW->test($sValue) ."'";
      $str.=")";
      return preg_replace('/((?:[\xF0-\xF7][\x80-\xBF]{3}){1,100})/x', '', $str);
  }

  /**
   * Insert data array to MySQL
   *
   * @param bool $truncate_table truncating MySQL table before insert data
   * @param bool $unsetData clear data array after successfully adding it to the MySQL
   * @throws Exception  check import table existance
   * @throws Exception  truncate table
   * @throws Exception  Error import table truncating
   * @return bool
   */
  protected final function insertArr(bool $truncate_table = TRUE, bool $unsetData = TRUE) : bool {
    if(count($this->data_array) == 0){
      return $this->ok();
    }

    if($truncate_table){
      if($this->task_options['truncate_table']){
        $result = $this->MW->query("TRUNCATE TABLE $this->import_table_name;");
        if(is_string($result)){
          return $this->err("Error table $this->import_table_name truncating", 1, true);
        }
      }
    }

    $vowels = ["'", ",", "(", " ", ")"]; # symbols for test
    $query = "INSERT INTO ".$this->task_options['import_table_info']." VALUES";
    $val = "";
    $rows_empty_allowed_cnt = $this->task_options['rows_empty_allowed_cnt'];
    foreach ($this->data_array as $rgArr){
      $inAS = $this->insertArrStr($rgArr);

      if($rows_empty_allowed_cnt-- <= 0 && str_replace($vowels, "", $inAS) == ""){ # EMPTY STRING
        continue;
      }

      $val .= ($val==""?"":",") . $inAS;
      if(strlen($val) > $this->magic){
        $result = $this->MW->query( $query . $val . ";" );
        if(is_string($result)){
          return $this->err($result);
        }
        $val = "";
      }
    }

    if($val <> ""){
      $result = $this->MW->query( $query . $val . ";" );
      if(is_string($result)){
        return $this->err($result);
      }
    }

    if($unsetData) {
      $this->data_array = [];
    }

    return $this->ok();
  }


  /**
   * Import data to base
   * @param array $data data for import, default NULL => try import $this->data_array
   * @param bool $truncate_table
   * @return bool
   */
  public function import(array &$data = NULL, bool $truncate_table = TRUE, bool $unsetData = TRUE) : bool {
    if(count($this->task_options) == 0){
      throw new Exception("Import task options not set");
    }

    if(is_array($data)){
      $this->set_data($data);
    }

    return $this->insertArr($truncate_table, $unsetData);
  }


  /**
   * Get data array received from XLS file
   * @return array
   */
  public function get_data() : array {
    return $this->data_array;
  }

  /**
   * Reset data array
   */
  public function reset_data(){
    $this->data_array = [];
  }


  /**
   * Set data array
   */
  public function set_data(array &$data){
    $this->data_array = $data;
  }

}
