<?php
	class Manager extends Connect{
		public function showQuery($query, $params){
			$keys = array();
			$values = array();
			 
			# build a regular expression for each parameter
			foreach ($params as $key=>$value){
				if (is_string($key)){
					$keys[] = '/:'.$key.'/';
				}else{
					$keys[] = '/[?]/';
				}
				 
				if(is_numeric($value)){
					$values[] = intval($value);
				}else{
					$values[] = '"'.$value .'"';
				}
			}
		 
			$query = preg_replace($keys, $values, $query, 1, $count);
			return $query;
		}
		
		//Metódo de inserção modo normal, apenas uma tabela...
		public function insert_common($table, $data, $query_extra){
			//criando o objeto pdo...
			$pdo = parent::get_instance();

			//pegando apenas os nomes dos campos, apartir das chaves dos arrays...
			$fields = implode(", ", array_keys($data));

			//pegando os noms dos campos para usar pra substituição de valores
			$values = ":".implode(", :", array_keys($data));
			
			//preparando query apartir dos campos($fields) e os parametros de valores nomeados($values)
			
			$query = "INSERT INTO $table ($fields) VALUES ($values)";

			//se a consulta precisar de algo mais..
			if($query_extra != ""){
				$query_extra .= $query_extra;
			}

			//echo $query;
			//continuação da preparação da query...
			$statement = $pdo->prepare($query);

			if (!$statement) {
			    echo "\PDO::errorInfo():\n";
			    print_r($dbh->errorInfo());
			}

			//filtrando valores para serem inseridos, tecnica segura para evitar SQL Injection...
			foreach ($data as $key => $value) {
				$data[$key] = filter_var($value);
			}

			//substituindo os parametros nomeados pelos verdadeiros valores, ex: ":name" por "Alessandro"
			
			foreach ($data as $key => $value){
				//$parameters[":$key"] = $value;
				$statement->bindValue(":$key", $value, PDO::PARAM_STR);
			}
			
			//executando a query já com seus valores
			if($statement->execute()){
				//se der certo retorna o id do elemento inserido...
				return $pdo->lastInsertId();
			}else{
				//se não der certo, retornará false...
				return false;
			}

		}

		public function select_common($table, $fields, $filters, $query_extra){
			//criando o objeto pdo...
			$pdo = parent::get_instance();


			$query = "SELECT ";
			if($fields != null){
				$query .= implode(", ", $fields);
			}else{
				$query .= "*";
			}

			$query .= " FROM $table";

			if($filters != null){
				$query .= " WHERE ";
				foreach ($filters as $key => $value) {
					$query .= "$key=:$key AND ";
				}

				$query = substr($query, 0, -4);
			}

			//se a consulta precisar de algo mais..
			if($query_extra != ""){
				$query .= $query_extra;
			}

			
			//preparando consulta
			$statement = $pdo->prepare($query);
			
			//substituindo os parametros pelos reais valores dos filtros, caso haja...
			if($filters != null){
				//filtrando valores para serem inseridos, tecnica segura para evitar SQL Injection...
				foreach ($filters as $key => $value) {
					$filters[$key] = filter_var($value);
				}
				foreach ($filters as $key => $value) {
					$statement->bindValue(":$key", $value, PDO::PARAM_STR);
				}
			}

			//executando consulta
			$statement->execute();

			//$statement->debugDumpParams();


			//preparando resultado
			$data;
			if($statement->rowCount()){
				while($result = $statement->fetch(PDO::FETCH_ASSOC)){
					$data[] = $result;
				}
			}else{
				return false;
			}

			//retornando resultado da busca
			return $data;

		}

		public function select_special($tables, $relationships, $filters, $query_extra){
			//criando o objeto pdo...
			$pdo = parent::get_instance();


			$query = "SELECT ";
			
			//informando colunas a serem selecionadas
			foreach ($tables as $table=>$fields){
				if(!empty($fields)){
					foreach ($fields as $each_field){
						$query .= "$table.$each_field, ";
					}
				}else{
					$query .= "$table.*, "; //quando as colunas nao forem informadas
				}
			}

			//removendo ultima "," 
			$query = substr($query, 0, -2);
			
			//inner join's
			$tables_names = array_keys($tables);
			
			$query .= " FROM ".implode(" INNER JOIN ", $tables_names);
			
			//relacionamentos
			$query .= " ON ";
			foreach($relationships as $foreign=>$primary){
				$query .= "$foreign=$primary AND "; 
			}
			//removendo ultimo "AND"
			$query = substr($query, 0, -4);
			
			//filtros
			if(isset($filters)){
				$query .= " WHERE ";
				foreach($filters as $field=>$value){
					$query .= "$field=? AND ";
				}
				//removendo ultimo "AND"...
				$query = substr($query, 0, -4);
			}

			//se a consulta precisar de algo mais..
			if($query_extra != ""){
				$query .= $query_extra;
			}

			//echo $query;

			//preparando consulta
			$statement = $pdo->prepare($query);
		

			//substituindo os parametros pelos reais valores dos filtros, caso haja...
			if(isset($filters)){
				//filtrando valores para serem inseridos, tecnica segura para evitar SQL Injection...
				foreach ($filters as $key => $value) {
					$filters[$key] = filter_var($value);
				}/*
				foreach ($filters as $field => $value) {
					$statement->bindValue(":$field", $value, PDO::PARAM_STR);
				}*/
				$i = 1;
				foreach ($filters as $key => $value){
					//$parameters[":$key"] = $value;
					$statement->bindValue($i, $value, PDO::PARAM_STR);
					$i++;
				}
			}
			//$statement->debugDumpParams();

			//echo $query;
			//executando consulta
			$statement->execute();

			//preparando resultado
			$data = "";
			if($statement->rowCount()){
				while($result = $statement->fetch(PDO::FETCH_ASSOC)){
					$data[] = $result;
				}
			}else{
				return false;
			}

			//retornando resultado da busca
			return $data;
		}

		//Metódo de atualização modo normal, apenas uma tabela...
		public function update_common($table, $data, $filters, $query_extra){
			//criando o objeto pdo...
			$pdo = parent::get_instance();

			//valores a serem atualizados
			$new_values = "";
			foreach ($data as $key => $value) {
				$new_values .= "$key=:$key, ";
			}
			//removendo ultima "," da query
			$new_values = substr($new_values, 0, -2);

			//filtros
			foreach ($filters as $key => $value) {
				$filters_up = "$key=:$key AND ";
			}
			//removendo ultimo "AND";
			$filters_up = substr($filters_up, 0, -4);

			//preparando query apartir dos campos($fields) e os parametros de valores nomeados($values)
			$query = "UPDATE $table SET $new_values WHERE $filters_up;";


			//se a consulta precisar de algo mais..
			if($query_extra != ""){
				$query .= $query_extra;
			}

			//echo $query,'<br>';

			//continuação da preparação da query...
			$statement = $pdo->prepare($query);

			if (!$statement) {
			    echo "\PDO::errorInfo():\n";
			    print_r($dbh->errorInfo());
			}

			//filtrando valores para serem inseridos, tecnica segura para evitar SQL Injection...
			foreach ($data as $key => $value) {
				$data[$key] = filter_var($value);
			}

			//substituindo os parametros nomeados pelos verdadeiros valores, ex: ":name" por "Alessandro"
			foreach ($data as $key => $value){
				//$parameters[":$key"] = $value;
				$statement->bindValue(":$key", $value, PDO::PARAM_STR);
			}

			//substituindo os parametros dos filtros nomeados pelos verdadeiros valores, ex: ":name" por "Alessandro"
			foreach ($filters as $key => $value){
				//$parameters[":$key"] = $value;
				$statement->bindValue(":$key", $value, PDO::PARAM_STR);
			}
			
			//$statement->debugDumpParams();

			//executando a query já com seus valores
			if($statement->execute()){
				//se der certo retorna true...
				return true;
			}else{
				//se não der certo, retornará false...
				return false;
			}

		}

		//Metódo de atualização modo normal, apenas uma tabela...
		public function delete_common($table, $filters, $query_extra){
			//criando o objeto pdo...
			$pdo = parent::get_instance();

			
			
			//filtros
			foreach ($filters as $key => $value) {
				$filters_delete = "$key=:$key AND ";
			}
			//removendo ultimo "AND";
			$filters_delete = substr($filters_delete, 0, -4);

			//preparando query apartir dos campos($fields) e os parametros de valores nomeados($values)
			$query = "DELETE FROM $table WHERE $filters_delete;";


			//se a consulta precisar de algo mais..
			if($query_extra != ""){
				$query .= $query_extra;
			}

			//echo $query,'<br>';

			//continuação da preparação da query...
			$statement = $pdo->prepare($query);

			if (!$statement) {
			    echo "\PDO::errorInfo():\n";
			    print_r($dbh->errorInfo());
			}

			

			//substituindo os parametros dos filtros nomeados pelos verdadeiros valores, ex: ":name" por "Alessandro"
			foreach ($filters as $key => $value){
				//$parameters[":$key"] = $value;
				$statement->bindValue(":$key", $value, PDO::PARAM_STR);
			}
			
			//$statement->debugDumpParams();

			//executando a query já com seus valores
			if($statement->execute()){
				//se der certo retorna true...
				return true;
			}else{
				//se não der certo, retornará false...
				return false;
			}

		}


	}
?>