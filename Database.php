<?php

class DataBase {
	
	private $db;

	public function __construct($file = 'config.ini'){

		if(!$settings = parse_ini_file($file, TRUE)) throw new exception('Unable to open ' . $file . '.');

		$dsn = 'mysql:host=' . $settings['database']['host'] . ';dbname=' . $settings['database']['db'] . ';charset=' . $settings['database']['charset'];
		$opt = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		$this->db = new PDO($dsn, $settings['database']['user'], $settings['database']['password'], $opt);

	}
	
	public function query($sql, $params = []){
		$stmt = $this->db->prepare($sql);

		if( !empty($params) ){
			foreach($params as $key => $value){
				$stmt->bindValue(":$key", $value);
			}
		}

		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getAll($table, $sql = ''){
		return $this->query("SELECT * FROM $table" . $sql);
	}

	public function getValue($value, $table, $sql = ''){
		$result = $this->query("SELECT $value FROM $table WHERE $sql");
		return $result[0];
	}

	public function UserSearchRow($table, $telegram_id){
		if($this->query("SELECT * FROM $table WHERE `telegram_id` LIKE $telegram_id")){
			return true;
		}
		return false;
	}

	public function InstertRow($params){
		$this->query("INSERT INTO `telegram_users` (telegram_id, username, first_name, status) VALUES ( :telegram_id, :username, :first_name, :status )", $params);
	}


	public function QuerryStatusUpdate($params){
		$this->query('UPDATE `telegram_users` SET `username` = :username, `first_name` = :first_name, `status`= :status WHERE `telegram_id` = :telegram_id',  $params);
	}

}

?>