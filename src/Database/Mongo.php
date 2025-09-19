<?php

namespace App\Database;

use App\Config\Config;
use MongoDB\Client;
use MongoDB\Database as MongoDatabase;
use MongoDB\Collection as MongoCollection;

class Mongo
{
  private static ?Client $client = null;
  private static ?MongoDatabase $db = null;

  public static function client(): Client
  {
    if (!self::$client) {
      $uri = Config::string('MONGODB_URI', 'mongodb+srv://crm_user:VrA8QKkwunwwQPuO@cluster0.nwagcg.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
      self::$client = new Client($uri, [
        'retryWrites' => true,
        'w' => 'majority'
      ]);
    }
    return self::$client;
  }

  public static function db(): MongoDatabase
  {
    if (!self::$db) {
      $dbName = Config::string('MONGODB_DB', 'allcashhomebuyersnetwork');
      self::$db = self::client()->selectDatabase($dbName);
    }
    return self::$db;
  }

  public static function collection(string $name): MongoCollection
  {
    return self::db()->selectCollection($name);
  }
}


