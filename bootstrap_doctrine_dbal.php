<?php

namespace external;

defined('MOODLE_INTERNAL') || die;

class doctrine {

    /**
     * @var string
     */
    const HOME = 'DoctrineDBAL-2.3.2';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $_conn;

    /**
     * private c'tor
     */
    private function __construct() {
        // empty
    }

    /**
     * Moodle-size given SQL by expanding (e.g.) {user} to mdl_user
     * @param $sql
     * @return string
     */
    public static function mdlize($sql) {
        global $CFG;
        return preg_replace('/\{([a-z][a-z0-9_]*)\}/', $CFG->prefix . '$1', $sql);
    }

    /**
     * accessor
     * @return \Doctrine\DBAL\Connection
     */
    public static function get_connection() {
        global $CFG;
        if (!empty(self::$_conn)) {
            return self::$_conn;
        }
        require $CFG->dirroot . '/' . self::HOME . '/Doctrine/Common/ClassLoader.php';
        $classLoader = new \Doctrine\Common\ClassLoader('Doctrine', $CFG->dirroot . '/' . self::HOME);
        $classLoader->register();
        $config = new \Doctrine\DBAL\Configuration();
        $map = array(
            'mysql' => 'pdo_mysql',
            'mysqli' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
        );
        $connectionParams = array(
            'dbname' => $CFG->dbname,
            'user' => $CFG->dbuser,
            'password' => $CFG->dbpass,
            'host' => $CFG->dbhost,
            'driver' => $map[$CFG->dbtype],
        );
        self::$_conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        return self::$_conn;
    }

}
