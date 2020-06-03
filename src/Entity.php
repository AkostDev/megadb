<?php
namespace AKost\MegaDB;

use Exception;

/**
 * Class Entity
 * @package AKost\MegaDB
 * @author Alexander Kostylev
 * @version 1.0.0
 * @license MIT
 */
class Entity
{
    /**
     * @var DB Содержит в себе ссылку на соединение с базой данных
     */
    private static $DB;

    /**
     * @var string Название сущности
     */
    private static $tblName;

    /**
     * Entity constructor.
     * @param DB $con
     * @param $_tblName
     */
    public function __construct(DB $con, $_tblName) {
        try {
            // Проверяем существование таблицы в базе данных
            if ($con->TableExists($_tblName)) {
                self::$DB       = $con;
                self::$tblName  = $_tblName;
            }
            else {
                throw new Exception("Table '{$_tblName}' does not exists in '" . $con->getName() . "'");
            }
        }
        catch (Exception $e) {
            $con::$exception = $e;
        }
    }

    /**
     * @return string Возвращаем название сущности
     */
    function GetTableName() {
        return self::$tblName;
    }

    /**
     * Добавляем запись в таблицу
     * @param $arFields
     * @return mixed
     */
    function Add($arFields) {
        return self::$DB->Add($arFields, self::$tblName);
    }

    /**
     * Обновляем запись в таблице
     * @param $rowId
     * @param $arFields
     * @return mixed
     */
    function Update($rowId, $arFields) {
        return self::$DB->Update($rowId, $arFields, self::$tblName);
    }

    /**
     * Удаляем запись из таблицы
     * @param $id
     * @return bool
     */
    static function Delete($id) {
        return self::$DB::Delete($id, self::$tblName);
    }

    /**
     * Получаем запись из таблицы по ID
     * @param $id
     * @return DBResult|bool
     */
    static function GetByID($id) {
        return self::$DB::GetByID($id, self::$tblName);
    }

    /**
     * Получаем записи по фильтру
     * @param array $arFields
     * @return DBResult|bool
     */
    static function GetList($arFields = []) {
        return self::$DB->GetList(self::$tblName, $arFields);
    }
}