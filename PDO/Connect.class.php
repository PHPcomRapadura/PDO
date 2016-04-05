<?php
class Connect
{
	public static $instance;
	
	private function __construct()
	{
		try {
			self::$instance = new PDO('mysql:host=localhost;dbname=db_inventory;', 'root', 'livre', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
			self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$instance->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
		} catch (PDOException $e) {
			die('Erro no banco de dados: '.$e->getMessage());
		}
	}
	
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			new Connect();
		}
		
		return self::$instance;
	}
}
