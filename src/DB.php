<?php
namespace shijunjun;
/**
 * 
 * @Date 2019年9月18日 下午3:10:41
 * @Author shijunjun
 * @Email jun_5197@163.com
 */
class DB implements IExIm
{
    protected $pdo = null;
    protected $setting = [];
    protected $sQuery = null;
    protected $success = null;
    
    /**
     * 构造函数
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $db_name
     * @param string $charset
     */
    public function __construct($host, $user, $password, $db_name,$port=3306, $charset = 'utf8mb4')
    {
        $this->settings = array(
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'password' => $password,
            'dbname'   => $db_name,
            'charset'  => $charset,
        );
        $this->connect();
    }
    
    /**
     * 创建 PDO 实例
     */
    protected function connect()
    {
        $dsn       = 'mysql:dbname=' . $this->settings["dbname"] . ';host=' .
        $this->settings["host"] . ';port=' . $this->settings['port'];
        $this->pdo = new \PDO($dsn, $this->settings["user"], $this->settings["password"],
            array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . (!empty($this->settings['charset']) ?
                    $this->settings['charset'] : 'utf8mb4')
            ));
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }
    
    /**
     * 返回一条数据
     * @param string $sql
     * @return array|mixed
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月18日 下午3:55:56
     */
    public function row(string $sql)
    {
        $this->execute($sql);
        if (empty($this->sQuery)){
            return [];
        }
        return $this->sQuery->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * 查询结果集
     * @param string $sql 要执行的sql语句
     * @return array|array
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月18日 下午3:56:10
     */
    public function query(string $sql)
    {
        $this->execute($sql);
        if (empty($this->sQuery)){
            return [];
        }
        return $this->sQuery->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * 迭代器
     * @param string $sql
     * @return array|\Generator
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月18日 下午3:56:46
     */
    public function yield(string $sql)
    {
        $this->execute($sql);
        if (empty($this->sQuery)){
            return [];
        }
        while ($row = $this->sQuery->fetch(\PDO::FETCH_ASSOC)){
            yield $row;
        }
    }
    
    /**
     * 执行SQL语句
     * @param string $statement
     * @throws \PDOException
     * @author shijunjun
     * @email jun_5197@163.com
     * @date 2019年9月18日 下午3:39:25
     */
    public function execute(string $statement)
    {
        $this->sQuery = null;
        try {
            if (is_null($this->pdo)) {
                $this->connect();
            }
            $this->sQuery = @$this->pdo->prepare($statement);
            $this->success = $this->sQuery->execute();
        } catch (\PDOException $e) {
            // 服务端断开时重连一次
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                $this->closeConnection();
                $this->connect();
                try {
                    $this->sQuery = $this->pdo->prepare($statement);
                    $this->success = $this->sQuery->execute();
                } catch (\PDOException $ex) {
                    $this->rollBackTrans();
                    throw $ex;
                }
            } else {
                $this->rollBackTrans();
                $msg = $e->getMessage();
                $err_msg = "SQL:{$statement} | {$msg}";
                $exception = new \PDOException($err_msg, (int)$e->getCode());
                throw $exception;
            }
        }
    }
    
    /**
     * 开始事务
     */
    public function beginTrans()
    {
        try {
            if (is_null($this->pdo)) {
                $this->connect();
            }
            return $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            // 服务端断开时重连一次
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                $this->closeConnection();
                $this->connect();
                return $this->pdo->beginTransaction();
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * 关闭连接
     */
    public function closeConnection()
    {
        $this->pdo = null;
    }
    
    /**
     * 提交事务
     */
    public function commitTrans()
    {
        return $this->pdo->commit();
    }
    
    /**
     * 事务回滚
     */
    public function rollBackTrans()
    {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return true;
    }
}