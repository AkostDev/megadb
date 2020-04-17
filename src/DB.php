<?php
namespace AKost\MegaDB;

use Exception;

/**
 * Class DB
 * @package MegaCoder
 * @author Alex Developer
 * @version 1.0.0
 * @license MIT
 */
class DB
{
    /**
     * @var false|\mysqli Содержит в себе соединение с БД
     */
    private $connect;
    /**
     * @var string Название БД
     */
    private $dbName;
    /**
     * @var \mysqli_stmt Служит для подготовки запросов
     */
    private static $stmt;

    private static $exception;

    /**
     * DB constructor.
     * Устанавливаем соеденение с базой данных MySQL
     * @param string $host Имя хоста или IP-адрес
     * @param string $username Имя пользователя MySQL
     * @param string $pass Пароль
     * @param string $db Название БД
     * @param string $default_charset Кодировка БД
     */
    public function __construct($host, $username, $pass, $db = '', $default_charset = 'utf8mb4')
    {
        try {
            // Устанавливаем соединение с БД
            // Если не удалось установить соединение - выбрасываем исключение
            if (!$this->connect = mysqli_connect($host, $username, $pass, $db)) {
                throw new Exception("Connection error - " . mysqli_connect_errno());
            }

            // Устанавливаем кодировку UTF-8 Mb4 (чтоб вставлять смайлы)
            mysqli_set_charset($this->connect, $default_charset);

            // Инициализирует запрос и возвращает объект для использования в mysqli_stmt_prepare
            self::$stmt = mysqli_stmt_init($this->connect);
        }
        catch (Exception $e) {
            echo '<p>' . $e->getMessage() . '</p>';
        }

        $this->dbName = $db;
    }

    /**
     * Закрываем соединение с БД
     * DB destructor
     */
    public function __destruct()
    {
        //self::$stmt = null;
        mysqli_stmt_close(self::$stmt);
        mysqli_close($this->connect);
    }

    /**
     * Возвращает имя базы данных или false, в случае, если не задано
     * @return bool|string
     */
    public function getName()
    {
        return empty($this->dbName) ? false : $this->dbName;
    }

    /**
     * Запрос к базе данных
     * @param $strQuery
     * @param int $resultmode
     * @return bool|DBResult
     */
    public function Query($strQuery, $resultmode = MYSQLI_STORE_RESULT)
    {
        $res = mysqli_query($this->connect, $strQuery, $resultmode);
        return is_bool($res) ? $res : new DBResult($res);
    }

    /**
     * Проверка существования таблицы
     * @param $tableName
     * @return bool
     */
    public function tableExists($tableName)
    {
        $res = $this->Query("SHOW TABLES LIKE '{$tableName}'");

        return ($res->ResCount() > 0);
    }

    /**
     * Получение списка таблиц в базе данных
     * @return mixed
     */
    public function getTableList()
    {
        $dbName = $this->dbName;
        $resT = $this->Query("SELECT * FROM information_schema.tables WHERE table_schema='{$dbName}' ORDER BY TABLE_ROWS DESC, TABLE_NAME ASC");
        return $resT->GetAll();
    }

    /**
     * Получение типов данных полей таблицы
     * @param $tableName
     * @return bool
     */
    private function getTypes($tableName)
    {
        $arResult = false;

        try {
            $resT = $this->Query("SHOW COLUMNS FROM {$tableName}");
            if ($resT->ResCount()) {
                while ($arT = $resT->GetNext()) {
                    $type = $arT['Type'];
                    $typeChar = "s";

                    // числа
                    // int, smallint, ...
                    if (stripos($type, "int") !== false) {
                        $typeChar = "i";
                    }

                    // числа с плавающей точкой
                    if (
                        stripos($type, "float") !== false ||
                        stripos($type, "double") !== false
                    ) {
                        $typeChar = "d";
                    }

                    $arResult[$arT['Field']] = $typeChar;
                }
            }
            else {
                throw new Exception("Error getting table fields");
            }
        }
        catch (Exception $e) {
            echo '<p>' . $e->getMessage() . '</p>';
        }

        return $arResult;
    }

    /**
     * Добавление записи в таблицу
     * @param $arFields
     * @param $tblName
     * @return mixed
     */
    public function Add($arFields, $tblName)
    {
        $arAddValues = $arrAddFields = $arValues = array();
        $str_types = '';

        // Получаем типы полей таблицы
        $arTypes = $this->getTypes($tblName);

        // Формируем данные для запроса
        foreach ($arFields as $k => $v) {
            $arrAddFields[] = "`" . $k . "`";
            $arAddValues[] = '?';
            $arValues[] = $v;
            $str_types .= $arTypes[$k];
        }

        // Формируем SQL для вставки данных
        $sql = "INSERT INTO {$tblName} (" . implode(', ', $arrAddFields) . ") ";
        $sql .= "VALUES (" . implode(', ', $arAddValues) . ");";

        try {
            if (!mysqli_stmt_prepare(self::$stmt, $sql)) {
                throw new Exception('');
            }
            if (!mysqli_stmt_bind_param(self::$stmt, $str_types, ...$arValues)) {
                throw new Exception('');
            }
            if (!mysqli_stmt_execute(self::$stmt)) {
                throw new Exception('');
            }
        } catch (Exception $e) {
            self::$exception = $e;
            return false;
        }

        return mysqli_stmt_insert_id(self::$stmt);
    }

    /**
     * Обновление записи в таблице по id
     * @param $rowId
     * @param $arFields
     * @param $tblName
     * @return int|string|false
     */
    public function Update($rowId, $arFields, $tblName)
    {
        $strUpdate = $str_types = "";
        $arValues = array();

        // Получаем типы полей таблицы
        $arTypes = $this->getTypes($tblName);

        foreach ($arFields as $k => $v) {
            if (!empty($strUpdate)) $strUpdate .= ", ";
            $strUpdate .= $k . "=?";
            $arValues[] = $v;
            $str_types .= $arTypes[$k];
        }

        $arValues[] = $rowId;

        try {
            if (!mysqli_stmt_prepare(self::$stmt, "UPDATE {$tblName} SET {$strUpdate} WHERE id=?")) {
                throw new Exception('');
            }
            if (!mysqli_stmt_bind_param(self::$stmt, $str_types . 'i', ...$arValues)) {
                throw new Exception('');
            }
            if (!mysqli_stmt_execute(self::$stmt)) {
                throw new Exception('');
            }
        } catch (Exception $e) {
            self::$exception = $e;
            return false;
        }

        return mysqli_stmt_affected_rows(self::$stmt);
    }

    /**
     * Удаление записи из таблицы
     * @param $rowId
     * @param $tblName
     * @return bool
     */
    public static function Delete($rowId, $tblName)
    {
        mysqli_stmt_prepare(self::$stmt, "DELETE FROM {$tblName} WHERE id=?");
        mysqli_stmt_bind_param(self::$stmt, "i", intval($rowId));

        return mysqli_stmt_execute(self::$stmt);
    }

    /**
     * Получение записи из таблицы по id
     * @param $rowId
     * @param $tblName
     * @return DBResult | bool
     */
    public static function GetByID($rowId, $tblName)
    {
        try {
            if (!mysqli_stmt_prepare(self::$stmt, "SELECT * FROM {$tblName} WHERE id=?")) {
                throw new Exception('Failed to prepare request');
            }
            if (!mysqli_stmt_bind_param(self::$stmt, "i", intval($rowId))) {
                throw new Exception('Failed to bind variables to query parameters');
            }
            if (!mysqli_stmt_execute(self::$stmt)) {
                throw new Exception('Failed to execute prepared request');
            }
        } catch (Exception $e) {
            self::$exception = $e;
            return false;
        }

        return new DBResult(mysqli_stmt_get_result(self::$stmt));
    }

    /**
     * Получение записей по фильтру
     * @param $arOrder
     * @param $arFilter
     * @param $arSelect
     * @param $limit
     * @param $tblName
     * @return DBResult | bool
     */
    public function GetList($arOrder, $arFilter, $arSelect, $limit, $tblName)
    {
        // Если поля для выборки не указаны, то берем все поля
        if (empty($arSelect)) $arSelect = array('*');

        // Формируем SQL запрос
        $sql = "SELECT " . implode(', ', $arSelect) . " FROM {$tblName}";

        // Получаем все типы полей таблицы
        $arTypes = $this->getTypes($tblName);
        // Формируем массив для подготовки фильтра
        $arRes = array(
            'TYPES'     => '',
            'VALUES'    => array()
        );
        self::prepareFilter($arFilter, $arTypes, $arRes, count($arFilter) - 2);

        // Если фильтр не пустой, то добавляем условия в SQL запрос
        if (!empty($arRes['VALUES'])) {
            $sql .= ' WHERE ' . $arRes['SQL'];
        }

        // Если указаны параметры сортировки
        if (!empty($arOrder)) {
            $sql .= " ORDER BY ";
            $i = 0;
            foreach ($arOrder as $key => $type) {
                if ($i > 0) {
                    $sql .= ", ";
                }
                $sql .= $key . " " . $type;
                $i++;
            }
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        try {
            if (!mysqli_stmt_prepare(self::$stmt, $sql)) {
                throw new Exception('Failed to prepare request');
            }

            if (!empty($arRes['VALUES'])) {
                if (!mysqli_stmt_bind_param(self::$stmt, $arRes['TYPES'], ...$arRes['VALUES'])) {
                    throw new Exception('Failed to bind variables to query parameters');
                }
            }

            if (!mysqli_stmt_execute(self::$stmt)) {
                throw new Exception('Failed to execute prepared request');
            }
        } catch (Exception $e) {
            self::$exception = $e;
            return false;
        }

        return new DBResult(mysqli_stmt_get_result(self::$stmt));
    }

    /**
     * Подготовка запроса для GetList-а
     * @param $arFilter
     * @param $arTypes
     * @param $arResult
     * @param $cnt
     */
    private static function prepareFilter($arFilter, $arTypes, &$arResult, $cnt) {
        $arSim = array('<=', '>=', '<', '>', '!=');
        $logic = ' AND ';
        $conditionLogic = ' AND ';
        $i = 0;

        foreach ($arFilter as $field => $value) {
            if (is_array($value)) {
                if ($i > 0) {
                    $arResult['SQL'] .= $conditionLogic;
                }
                $arResult['SQL'] .= count($value) > 1 ? '(' : '';
                self::prepareFilter($value, $arTypes, $arResult, (count($value) - 2));
                $arResult['SQL'] .= count($value) > 1 ? ')' : '';

                $i++;
            }
            else {
                if ($field === 'LOGIC') {
                    $conditionLogic = ' ' . $value . ' ';

                    continue;
                }

                $operator = '=';

                // Проверяем, есть ли в значении спецсиволы
                foreach ($arSim as $sim) {
                    if (strpos($value, $sim) !== false) {
                        $operator = $sim;
                        $value = str_replace($sim, '', $value);

                        break;
                    }
                }

                $arResult['VALUES'][] = $value;
                $arResult['TYPES'] .= $arTypes[$field];
                $arResult['SQL'] .= $field . $operator . '?';

                if ($cnt > -1) {
                    $arResult['SQL'] .= $logic;
                    $cnt--;
                }
            }
        }
    }

    /**
     * В случае ошибки возвращает поля с описанием ошибки
     * @return array | bool
     */
    public function GetError()
    {
        return is_null(self::$exception) ? false : array(
            'message'   => self::$exception->getMessage(),
            'code'      => self::$exception->getCode(),
            'file'      => self::$exception->getFile(),
            'line'      => self::$exception->getLine(),
        );
    }
}
