<?php
/**
 * Zend Db(Pdo) Rip.
 */
class Db extends PDO
{
    const INT_TYPE    = 0;
    const BIGINT_TYPE = 1;
    const FLOAT_TYPE  = 2;
    
    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Db::INT_TYPE, Db::BIGINT_TYPE, or Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
        Db::INT_TYPE    => Db::INT_TYPE,
        Db::BIGINT_TYPE => Db::BIGINT_TYPE,
        Db::FLOAT_TYPE  => Db::FLOAT_TYPE,
        'INT'                => Db::INT_TYPE,
        'INTEGER'            => Db::INT_TYPE,
        'MEDIUMINT'          => Db::INT_TYPE,
        'SMALLINT'           => Db::INT_TYPE,
        'TINYINT'            => Db::INT_TYPE,
        'BIGINT'             => Db::BIGINT_TYPE,
        'SERIAL'             => Db::BIGINT_TYPE,
        'DEC'                => Db::FLOAT_TYPE,
        'DECIMAL'            => Db::FLOAT_TYPE,
        'DOUBLE'             => Db::FLOAT_TYPE,
        'DOUBLE PRECISION'   => Db::FLOAT_TYPE,
        'FIXED'              => Db::FLOAT_TYPE,
        'FLOAT'              => Db::FLOAT_TYPE
    );
    
    private static $_defaultConfig = null;
	private $lastSql = '';
    /**
     * Fetch mode
     */
    protected $_fetchMode = PDO::FETCH_ASSOC;

    protected $config;
    
    // constructor
    public function __construct($config = null)
    {
        $dsn = 'mysql:host=' . $config->host . ';dbname=' . $config->db;
        $this->config = $config;
        if (empty($this->config->options)) {
            $config->options = array();
        }

        try {
            parent::__construct($dsn, $this->config->user, $this->config->password, $this->config->options);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->query("SET NAMES 'UTF8'");
        } catch (PDOException $e) 
        {
            return false;
        }

    }
    

    // static method
    public static function getInstance($config = null, $name = 'default')
    {
        static $instance = array();
        
        if (!$config && !self::$_defaultConfig) {
            throw new Db_Exception('No default config assigned');
        }
        
        $config = !$config ? self::$_defaultConfig : $config;

        if (empty($instance[$name])) {
            $instance[$name] = new Db($config);
        }
        return $instance[$name];
    }
            
    public static function setDefaultConfig($config)
    {
        self::$_defaultConfig = $config;
    }   

    // query
    public function query($sql, $bind = array())
    {
        if (!is_array($bind)) {
            $bind = array($bind);
        }

        // prepare and execute the statement with profiling
        $stmt = $this->prepare($sql);
        
        $time_s = microtime(true);
        $stmt->execute($bind);
        $time_e = microtime(true);

        $sql = sprintf('%s | %s | %08f', $stmt->queryString, join(', ', $bind), $time_e - $time_s);
        
        $this->lastSql = $sql;
        // return the results embedded in the prepared statement object
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    public function insertAllSql($sql){
        $stmt = $this->prepare($sql);
        $stmt->execute();
    }
    
    // insert
    public function insert($table, array $bind, $ignore = false)
    {
        $cols = array();
        $vals = array();

        foreach ($bind as $col => $val) {
            $cols[] = $col;
            $vals[] = '?';
        }

        $ignore = $ignore ? 'IGNORE' : '';
        $sql = "INSERT $ignore INTO " . $table . ' (' . implode(', ', $cols) . ') ' . 'VALUES (' . implode(', ', $vals) . ')';
        $stmt = $this->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }
    
    public function replace($table, array $bind)
    {
        $cols = array();
        $vals = array();

        foreach ($bind as $col => $val) {
            $cols[] = $col;
            $vals[] = '?';
        }

        $sql = "REPLACE INTO " . $table . ' (' . implode(', ', $cols) . ') ' . 'VALUES (' . implode(', ', $vals) . ')';

        $stmt = $this->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }

    // update
    public function update($table, array $bind, $where = '')
    {
        $set = array();
        foreach ($bind as $col => $val) {
            $set[] = "$col = ?";
        }
        
        $where = $this->_whereExpr($where);
        $sql = "UPDATE $table SET " . implode(', ', $set) . (($where) ? " WHERE $where" : '');

        $stmt = $this->query($sql, array_values($bind));
        $result = $stmt->rowCount();
        return $result;
    }
    
    public function delete($table, $where = '')
    {
        $where = $this->_whereExpr($where);
        $sql = "DELETE FROM $table " . (($where) ? " WHERE $where" : '');

        $stmt = $this->query($sql);
        $result = $stmt->rowCount();

        return $result;
    }

    /**
     * Fetches all SQL result rows as a sequential array.
     * Uses the current fetchMode for the adapter.
     */
    public function fetchAll($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }

        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll($fetchMode);

        return $result;
    }

    /**
     * Fetches the first row of the SQL result.
     * Uses the current fetchMode for the adapter.
     */
    public function fetchRow($sql, $bind = array(), $fetchMode = null)
    {
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }

        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetch($fetchMode);

        return $result;
    }

    /**
     * Fetches all SQL result rows as an associative array.
     *
     * The first column is the key, the entire row array is the
     * value.  You should construct the query to be sure that
     * the first column contains unique values, or else
     * rows with duplicate values in the first column will
     * overwrite previous data.
     */
    public function fetchAssoc($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tmp = array_values(array_slice($row, 0, 1));
            $data[$tmp[0]] = $row;
        }
        return $data;
    }

    /**
     * Fetches the first column of all SQL result rows as an array.
     *
     * The first column in each row is used as the array key.
     */
    public function fetchCol($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }

    /**
     * Fetches all SQL result rows as an array of key-value pairs.
     *
     * The first column is the key, the second column is the
     * value.
     */
    public function fetchPairs($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $data[$row[0]] = $row[1];
        }
        return $data;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     */
    public function fetchOne($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchColumn(0);
        return $result;
    }


    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }
            return implode(', ', $value);
        }

        if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
            $quotedValue = '0';
            switch ($this->_numericDataTypes[$type]) {
                case Db::INT_TYPE: // 32-bit integer
                    $quotedValue = (string) intval($value);
                    break;
                case Db::BIGINT_TYPE: // 64-bit integer
                    // ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
                    // are not supported here, because these are string
                    // literals, not numeric literals.
                    if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                        (string) $value, $matches)) {
                        $quotedValue = $matches[1];
                    }
                    break;
                case Db::FLOAT_TYPE: // float or decimal
                    $quotedValue = sprintf('%F', $value);
            }
            return $quotedValue;
        }

        return $this->_quote($value);
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the orignal text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (strpos($text, '?') != false) {
                    $text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }
    
    public function buildLimit($page, $perpage)
    {
        $offset = ($page - 1) * $perpage;
        $offset = max(0, $offset);

        return " LIMIT $offset, $perpage";
    }

    protected function _whereExpr($where)
    {
        if (empty($where)) {
            return $where;
        }

        if (!is_array($where)) {
            $where = preg_replace('/^\s*?where/i', '', $where);
            $where = array($where);
        }

        foreach ($where as &$term) {
            $term = '(' . $term . ')';
        }

        $where = implode(' AND ', $where);
        
        return $where;
    }

    public function setFetchMode($mode)
    {
        switch ($mode) {
            case PDO::FETCH_LAZY:
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_NUM:
            case PDO::FETCH_BOTH:
            case PDO::FETCH_NAMED:
            case PDO::FETCH_OBJ:
                $this->_fetchMode = $mode;
                break;
            default:
                throw new Db_Exception("Invalid fetch mode '$mode' specified");
                break;
        }
    }
    function getLastSql(){
    	return $this->lastSql;
    }
}

class Db_Exception extends Exception
{
    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
