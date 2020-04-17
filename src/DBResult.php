<?
namespace AKost\MegaDB;

/**
 * Class DBResult
 * @package MegaCoder
 * @author Alex Developer
 * @version 1.0.0
 * @license MIT
 */
class DBResult
{
    /**
     * @var \mysqli_result
     */
    private $res;

    /**
     * MC_DB_Result constructor.
     * @param \mysqli_result $result
     */
    public function __construct($result)
    {
        $this->res = $result;
    }

    /**
     * DB destructor
     */
    public function __destruct()
    {
        mysqli_free_result($this->res);
    }

    /**
     * @return array|null
     */
    public function GetNext()
    {
        return mysqli_fetch_assoc($this->res);
    }

    /**
     * Вернёт все записи
     * @return mixed
     */
    public function GetAll()
    {
        return mysqli_fetch_all($this->res,MYSQLI_ASSOC);
    }

    /**
     * Возвращает запись в виде экземпляра класса
     * @param string $class_name
     * @param array $params
     * @return object|stdClass
     */
    public function GetNextObject($class_name = "stdClass", $params = array())
    {
        return empty($params)
            ? mysqli_fetch_object($this->res, $class_name)
            : mysqli_fetch_object($this->res, $class_name, $params);
    }

    /**
     * @return mixed
     */
    public function GetRow()
    {
        return mysqli_fetch_row($this->res);
    }

    /**
     * Вернёт количество записей выборки
     * @return int
     */
    public function ResCount()
    {
        return mysqli_num_rows($this->res);
    }
}