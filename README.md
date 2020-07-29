# DataImportToMySQL
Tool for importing data to MySQL
We use:
- errormsg.trait.php for error output,
- mysqlwork.class.php for working with the MySQL database.

Order of work:
1. Creating an object MySQLWork to work with database
2. Creating the DataImportToMySQL object
3. Setting import parameters:
   - import_table_info => table_name(fild1,fild2,...fildN) = > name and fields of the table to insert data in
   - rows_empty_allowed_cnt => number of empty rows to skip (title and title of source data)
   - truncate_table => whether to clear the table before importing
4. Passing data for import
5. Importing
Items 2-3 and 4-5 can be combined.

Functions:
 - __construct - creating object
 - set_task - setting import parameters(parameters are passed as an associative array)
 - import - data import
 - get_data - preview data to be imported
 - reset_data - deleting data for import
 - set_data - setting data to import

# DataImportToMySQL
Инструмент для импорта данных в MySQL.
Для работы используются:
 - errormsg.trait.php вывод ошибок,
 - mysqlwork.class.php работа с базой MySQL.
 
Порядок работы:
1. Создаем объект MySQLWork для работы с базой
2. Создаем объект DataImportToMySQL
3. Задаем параметры импорта:
   - import_table_info => table_name(fild1,fild2,...fildN) => имя и поля таблицы для вставки данных
   - rows_empty_allowed_cnt => колличество пустых рядов, которое нужно пропустить (титул и заглавие исходных данных)
   - truncate_table => нужно ли очищать таблицу перед импортом
4. Передаем данные для импорта
5. Импортируем
Пункты 2-3 и 4-5 могут быть объеденены.

Открытые функции:
 - __construct - создание объекта
 - set_task - задание параметров импорта(параметры передаются ввиде ассоциативного массива)
 - import - импрорт данных 
 - get_data - просмотр данных для импорта
 - reset_data - удаление данных для импорта
 - set_data - задание данных для импорта
