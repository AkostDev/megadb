<?php
namespace AKost\MegaDB;

/**
 * Class Entity
 * @package AKost\MegaDB
 * @author Alex Developer
 * @version 1.0.0
 * @license MIT
 */
class Entity
{
    private static $DB;
    private static $tblName;

    public function __construct($_tblName)
    {
        global $MC_DB;
        self::$DB = $MC_DB;

        self::$tblName = $_tblName;
    }

    function GetDBTableName()
    {
        return self::$tblName;
    }

    function Add($arFields)
    {
        return self::$DB->Add($arFields, self::$tblName);
    }

    static function Delete($id)
    {
        return self::$DB::Delete($id, self::$tblName);
    }

    static function GetByID($id)
    {
        return self::$DB::GetByID($id, self::$tblName);
    }

    static function GetList($arOrder, $arFilter, $arSelect, $limit = 0)
    {
        return self::$DB->GetList($arOrder, $arFilter, $arSelect, $limit, self::$tblName);
    }
}