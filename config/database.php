<?php

class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {

            $host = "localhost";
            $database = "hotel_management";
            $username = "root";
            $password = "";

            try {

                self::$connection = new PDO(
                    "mysql:host=$host;dbname=$database;charset=utf8mb4",
                    $username,
                    $password
                );

                self::$connection->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                self::$connection->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

            } catch (PDOException $e) {

                die(
                    "Database connection failed: " .
                    $e->getMessage()
                );
            }
        }

        return self::$connection;
    }
}