<?php
/**
 * Copyright (C) 2014 Arthur Halet
 *
 * This software is distributed under the terms and conditions of the 'MIT'
 * license which can be found in the file 'LICENSE' in this package distribution
 * or at 'http://opensource.org/licenses/MIT'.
 *
 * Author: Arthur Halet
 * Date: 08/03/2015
 */

namespace orange\cfhelper\connectors;


use orange\cfhelper\CfHelper;
use orange\cfhelper\connectors\AbstractConnector;
use orange\cfhelper\services\Service;

class DatabaseConnector extends AbstractConnector
{

    const TABLE_NAME = 'rest_proxify';
    const SENTENCE_PDO = "%s:host=%s;%sdbname=%s";
    const DBTYPE_PG = "(postgres|pgsql)";
    const DBTYPE_MYSQL = "(maria|my)";
    const DBTYPE_ORACLE = "(oracle|oci)";
    const DBTYPE_SQLITE = "sqlite";
    /**
     * @var \PDO;
     */
    private $connection;


    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function load()
    {
        $dbToFind = implode('|', [
            self::DBTYPE_ORACLE,
            self::DBTYPE_MYSQL,
            self::DBTYPE_PG,
            self::DBTYPE_SQLITE
        ]);
        $dbService = $this->serviceManager->getService('.*(db|database|(' . $dbToFind . ')).*');
        if ($dbService === null) {
            return;
        }
        $this->credentials = $this->parseDbFromService($dbService);
        $this->loadDatabaseFromDbParsed();
    }

    private function parseDbFromService(Service $service)
    {
        $toReturn = $this->parseFromService($service);

        if (!empty($toReturn['port'])) {
            $toReturn['port'] = sprintf("port=%s;", $toReturn['port']);
        } else {
            $toReturn['port'] = "";
        }
        $type = $service->getValue('.*(type).*');
        if (empty($toReturn['path'])) {
            $database = $service->getValue('.*(name|database|db).*');
        } else {
            $database = $toReturn['path'];
        }
        $toReturn['database'] = $database;
        if (empty($type)) {
            $type = $this->getDbTypeFromServiceName($service->getName());
        }
        $toReturn['sentencePdo'] = sprintf(self::SENTENCE_PDO, $type,
            $toReturn['host'], $toReturn['port'], $database);

        return $toReturn;
    }

    private function getDbTypeFromServiceName($serviceName)
    {
        if (preg_match('#.*' . self::DBTYPE_MYSQL . '.*#i', $serviceName)) {
            return "mysql";
        }
        if (preg_match('#.*' . self::DBTYPE_ORACLE . '.*#i', $serviceName)) {
            return "oci";
        }
        if (preg_match('#.*' . self::DBTYPE_PG . '.*#i', $serviceName)) {
            return "pgsql";
        }
        if (preg_match('#.*' . self::DBTYPE_SQLITE . '.*#i', $serviceName)) {
            return "sqlite";
        }
        return null;
    }

    private function loadDatabaseFromDbParsed()
    {
        if (is_array($this->credentials)) {
            $this->connection = new \PDO($this->credentials['sentencePdo'], $this->credentials['user'], $this->credentials['pass']);
        } else {
            $this->connection = new \PDO($this->credentials);
        }
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
}
