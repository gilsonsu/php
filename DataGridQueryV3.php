<?php

class DataGridQueryMaster{

	public $arr;
	public $flag = 0;

	public $objCnn;
	public $query;

}

class DataGridQueryV3 {

	public $config;
	public $table;
	public $column;

	public $dg;
		
	function __construct(){

		$this->dg = new DataGridQueryMaster();
		
		$this->config = new DataGridQueryConfig($this->dg);
		$this->table = new DataGridQueryTable($this->dg);
		$this->column = new DataGridQueryColumn($this->dg);	
	}

	public function render() {

		$navegador = null;

		$this->methodGET ();

		if($this->dg->arr["limit"]!=0){

			//1 pega o total de resultado de uma query.
			$totalResult = $this->selectTotalResult ();

			//2 Calcula o total de pagina e retorna a proxima posicao.
			$posicao = $this->calcTotalPage ( $totalResult );

			//Verfica se tem permissão para mostrar o navegador.
			if ($this->dg->arr["showNavigator"] == true || $this->dg->arr["showNavigator"] === null) {
				$navigator = $this->showNavigator();
			} else { $navigator = null;
			}

			//3 Concatena o comando LIMIT com a posicao e o limite de resultados.
			$this->applyPaging ( $posicao, $this->dg->arr["limit"] );
		}

		//4 Executa a pesquisa.
		$result = $this->executQuery ( $this->dg->query );
		$this->valueTable = $result;

		//5 Gerar as colunas.
		$table = $this->generateColumns ();

		//6 Gera as linhas da tabela com o resultado da pesquisa.
		$table .= $this->generateLines ( $result );
				
		//Retorna a tabela com todos e dados e com o navegador de paginação.
		return $table . $navigator;
	}

	/**
	 * Inser na string da query o LIMIT para fazer a paginação.
	 *
	 * @param int $start posicao da pesquisa
	 * @param unknown_type $limit
	 */
	private function applyPaging($start, $limit) {

		$this->dg->query = $this->dg->query . " LIMIT $start, $limit";

		return;
	}

	/**
	 * Executa a query no banco.
	 *
	 */
	private function executQuery() {
		$result = $this->dg->objCnn->query($this->dg->query);
		//Retora o resultado se existir.
		if ($result) {
			return $result;
		}
		return false;
	}

	/**
	 * Selecta o total de resultados da de uma pesquisa.
	 *
	 */
	private function selectTotalResult() {

		if(empty($this->dg->query)){
			return 0;
		}

		$total = 0;
		$sql = $this->convertStringQuery ( $this->dg->query );
		
		$result = $this->dg->objCnn->query ( $sql );
		if ($result) {
			$row  = $result->fetch_assoc();
			return $row ["TOTAL"];
		}

		return $total;
	}

	/**
	 * Calcula o total de paginas quer vai ser disponível no navegador.
	 *
	 */
	private function calcTotalPage($qtdResultado) {

		$this->quantidade = $qtdResultado;

		//verifica se a divisão é exata
		if (($this->quantidade % $this->dg->arr["limit"]) == 0) {
			$qdtPaginas = ($this->quantidade / $this->dg->arr["limit"]);
		} else {
			$qdtPaginas = ($this->quantidade / $this->dg->arr["limit"]) + 1;
		}

		//logica para o next
		if ($this->posicaoPagina) {

			if ($this->posicaoPagina <= $qdtPaginas) {

				$this->posicao = $this->dg->arr["limit"] * ($this->posicaoPagina - 1);
				$this->pagina = $this->posicaoPagina;
				$this->anterior = $this->posicaoPagina - 1;
				$this->posterior = $this->posicaoPagina + 1;
					
			}
			if ($this->posicaoPagina > $qdtPaginas) {
				$this->pagina = 1;
				$this->anterior = 0;
				$this->posterior = 2;
				$this->posicao = 0;
			}
		} else {

			$this->pagina = 1;
			$this->anterior = 0;
			$this->posterior = 2;
			$this->posicao = 0;
		}

		return $this->posicao;
	}

	/**
	 * Mostra o navegador da paginação.
	 *
	 */
	private function showNavigator() {

		$newValueGet = null;

		/**
		 * Verifica se o endereço da pesquisa ja possui "?", casa haja ele é substituido pelo "&"
		 * Formata a string do da Url.
		 */
		$url = $_SERVER ['REQUEST_URI'];
		$result_01 = strstr ( $url, "?" ); //Verifica se existe algum parâmetro na URL.

		if ($result_01) {

			$url = explode ( "?", $url ); //Quebra a url na "?"
			$clearUrl = $url [0]; //Url separada.


			//Verifica se possui o parâmentro da pagina.
			$result_02 = strstr ( $url [1], "pagina=" );

			if ($result_02) {

				//Retira o parâmentro da página.
				$result_03 = strstr ( $url [1], "&" );

				if ($result_03) {

					$valueGet = explode ( "&", $url [1] );
					$valueGet [0] = null;
					$newValueGet = implode ( "&", $valueGet );
				}
					
			} else {

				//Caso não haja o parâmentro da página.
				$newValueGet = "&" . $url [1];
			}

		} else {

			$clearUrl = $url;
		}

		$stringBack = $clearUrl . "?pagina=" . $this->anterior . $newValueGet;
		$stringNext = $clearUrl . "?pagina=" . $this->posterior . $newValueGet;
		$target = !empty($this->dg->arr["navLinkTarget"])? "#".$this->dg->arr["navLinkTarget"]:null;

		$navegador = "<a href=\"" . $stringBack . $target."\" class=\"datagrid-browser-button-left\"><<</a>\n";
		$navegador .= "\t\t<label class=\"datagrid-browser-text\">  $this->pagina  </label>\n";
		$navegador .= "\t\t<a href=\"" . $stringNext . $target ."\" class=\"datagrid-browser-button-right\" >>></a>\n";

		//Layout do navegador//----------------------------------------------------------
		$layoutNav = "<table class=\"datagrid-browser\">\n\t<tr>\n";
		$layoutNav .= "\t\t<td class=\"datagrid-browser-text\">Total de resultados encontrados: ";
		$layoutNav .= $this->quantidade . " </td>\n" . "\t\t<td class=\"datagrid-browser-button\">" . $navegador . "\t\t</td>\n\t</tr>\n</table>\n";

		return $layoutNav;
	}

	/**
	 * Gera a coluna.
	 *
	 */
	private function generateColumns() {

		//tag da tabela------------------------------------------------
		$table = "\n\r<table";
		
		$table .= " width=\"" . (!empty($this->dg->arr["widthTable"])?$this->dg->arr["widthTable"]:"100%") ."\" ";
		$table .= " height=\"" . (!empty($this->dg->heightTable)?$this->dg->heightTable:0)."\" ";
		$table .= " border=\"" . (!empty($this->dg->arr["sizeBorder"])?$this->dg->arr["sizeBorder"]:0). "\" ";
		$table .= " id=\"" . (!empty($this->dg->id)?$this->id:null). "\"";
		$table .= " class=\"" . (!empty($this->dg->arr["cssClassTable"])?$this->dg->arr["cssClassTable"]:null)."\" ";
		$table .= " style=\"" . (!empty($this->dg->cssStyleTable)?$this->dg->cssStyleTable:null). "\" ";
	
		$table .= ">\n<thead>\n<tr>\n";

		for($i = 1; $i <= $this->dg->flag; $i ++) {

			$table .= "\t<th";
			//$table .=  !empty($this->dg->arr["alignTableTitle"][$i])? " align=\"" . $this->dg->arr["alignTableTitle"][$i] ."\"" : null;
			$table .=  !empty($this->dg->arr["cssClassTable"][$i])? " class=\"" . $this->dg->arr["cssClassTable"][$i] . "\"" : null;
			$table .=  !empty ($this->dg->arr["cssStyleTableTitle"][$i] ) ? "  style=\"" . $this->dg->arr["cssStyleTableTitle"][$i]. "\""  : null; 
			$table .=  !empty ($this->dg->arr["widthColumn"][$i] ) ? " width=\"" . $this->dg->arr["widthColumn"][$i]. "\""   : null;  
			$table .= ">";
			$table .=  !empty ( $this->dg->arr["titleColumn"][$i] ) ? $this->dg->arr["titleColumn"][$i] : null;
			$table .=  "</th>\n";

		}

		$table .= "</tr>\n</thead>\n";
		return $table;
	}
	
	private function generateLines($result) {

		$a = 0;
		$table = $tbody = $line = null;

		if (! empty ( $this->valueTable )) {
			
			while ( $rows = $result->fetch_assoc ( ) ) {
				
				$a ++;

				$table .= "<tr>\n";

				for($i = 1; $i <= $this->dg->flag; $i ++) {

					$typeC = ! empty ( $this->dg->arr["typeColumn"][$i] ) ? $this->dg->arr["typeColumn"][$i] : null;
					
					// Table Pk
					$value1 = ! empty ( $rows [$this->dg->arr["keyTable"]] ) ? $rows [$this->dg->arr["keyTable"]] : null;
					
					$value2 = ! empty ( $this->dg->arr["nameColumnTable"][$i] ) ? $this->dg->arr["nameColumnTable"][$i] : null;
					$value3 = ! empty ( $rows [$value2] ) ? trim($rows [$value2]) : null;

					if($value3 != null){					
						//Define o tipo da coluna.
						$line = $this->defineTypeColumn ( $typeC, $value1, $value3, $i );

					}else{
						if(!empty($this->dg->arr["valueNull"][$i])){
							$line = "<div align=\"center\">".$this->dg->arr["valueNull"][$i]."</div>";
						}else{
							$line = "<div align=\"center\"></div>";
						}
					}
					
					$table .= "\t<td";
					$table .=  !empty($this->dg->arr["alignColumn"] [$i])? " align=\"" . $this->dg->arr["alignColumn"] [$i] . "\"" : null;
					$table .=  !empty($this->dg->arr["cssStyleTableColumn"])? " class=\"" . $this->dg->arr["cssStyleTableColumn"]. "\"" : null;
					$table .=  !empty($this->dg->arr["styleColumn"][$i])? " style=\"" . $this->dg->arr["styleColumn"][$i] . "\"" : null;

					$table .=  " id=\"".$a."-".$i."\" >". $line . "</td>\n";
				}
				$table .= "</tr>\n";
			}
			#mysql_free_result ( $this->valueTable );
		}

		$tbody .= "<tbody>\n" . $table . "</tbody>\n</table>\n";
		return $tbody;
	}

	private function defineTypeColumn($type_column = null, $value_key_column, $value_column, $indice) {
				
		$linkUrl = ! empty ( $this->dg->arr["linkUrl"] [$indice] ) ? $this->dg->arr["linkUrl"] [$indice] : null;
		$pageTarget = isset ( $this->dg->arr["pageTarget"] [$indice] ) ? "#" . $this->dg->arr["pageTarget"] [$indice] : null;
		
		$imageUrl = ! empty ( $this->dg->arr["imageUrl"] [$indice] ) ? $this->dg->arr["imageUrl"] [$indice] : null;
		$linkTarget = ! empty ( $this->dg->arr["linkTarget"] [$indice] ) ? $this->dg->arr["linkTarget"] [$indice] : null;
		
		$linkName = ! empty ( $this->dg->arr["linkName"] [$indice] ) ? $this->dg->arr["linkName"] [$indice] : null;
		$linkOnclick = ! empty ( $this->dg->arr["linkOnclick"] [$indice] ) ? $this->dg->arr["linkOnclick"] [$indice] : null;
		

		if (strcasecmp ( $type_column, 'checkbox' ) == 0) {
			return "<input name=\"" . $this->id . "[]\" type=\"checkbox\" value=\"" . $value_key_column . "\"  />";
		}

		if (strcasecmp ( $type_column, 'link' ) == 0) {

		    if(empty($linkUrl)) {
		            return "<a href=\"".$value_key_column."\" target=\"".
		  		    $this->dg->arr["linkTarget"] [$indice] . "\"  name=\"$linkName\"  onclick=\"$linkOnclick\" >" . $value_column . "</a>";
		    }
		    
			if (stripos ( $linkUrl, '?' )) {
				return "<a href=\"" . $linkUrl . "&" . $this->id . "=" . $value_key_column .
				$pageTarget . "\" target=\"" . $this->dg->arr["linkTarget"] [$indice] . "\"  name=\"$linkName\" onclick=\"$linkOnclick\">" . $value_column . "</a>";
			}
			return "<a href=\"" . $linkUrl . "?" . $this->id . "=" .
			$value_key_column . $pageTarget . "\" target=\"" .
			$this->arr["linkTarget"] [$indice] . "\"  name=\"$linkName\"  onclick=\"$linkOnclick\" >" . $value_column . "</a>";
		}
		
		
		if (strcasecmp ( $type_column, 'link2' ) == 0) {
		
			return "<a href=\"" . $value_column."\" target=\"" .
					$this->arr["linkTarget"] [$indice] . "\" name=\"$linkName\"  onclick=\"$linkOnclick\">" . $value_column . "</a>";
		}

		if (strcasecmp ( $type_column, 'imagelink' ) == 0) {
		    
		    if(empty($linkUrl)){ 
		        
		        return "<a href=\"" . $value_key_column . "\" target=\"". $linkTarget . "\"  name=\"$linkName\"  onclick=\"$linkOnclick\"><img src=\"" .
		        $this->dg->arr["imageUrl"] [$indice] . "\" border=\"0\" /></a>";
		    
		    }

			if (stripos ( $linkUrl, '?' )) {

	
				return "<a href=\"" . $linkUrl . "&" . $this->dg->arr["idDataGrid"] . "=" .
				$value_key_column . $pageTarget . "\" target=\"" . $linkTarget . "\"  name=\"$linkName\"   onclick=\"$linkOnclick\">
				<img src=\"" . $imageUrl . "\" border=\"0\" /></a>";
			}

			
			return "<a href=\"" . $linkUrl . "?" . $this->dg->arr["idDataGrid"] . "=" . $value_key_column .
			$pageTarget . "\" target=\"". $linkTarget . "\"  name=\"$linkName\"  onclick=\"$linkOnclick\"><img src=\"" .
			$this->dg->arr["imageUrl"] [$indice] . "\" border=\"0\" /></a>";
		}

		if (strcasecmp ( $type_column, 'radio' ) == 0) {
			return "<input type=\"radio\" name=\"" . $this->dg->arr["idDataGrid"] . "\" id=\"" .
			$this->id . "\" value=\"" . $value_key_column . "\" />";
		}

		if (strcasecmp ( $type_column, 'imagefield' ) == 0) {
			return "<input type=\"image\" name=\"" . $this->dg->arr["idDataGrid"] . "\" id=\"" . $this->dg->arr["idDataGrid"] .
			"\" value=\"" . $value_key_column . "\" src=\"" . $this->dg->arr["imageUrl"] [$indice] . "\" />";
		}
		// Se não for definido o tipo text o valor é limitanto a 90 caracteres.
		
		if (empty ( $type_column )) {
			$contentNew = substr ( $value_column, 0, 70 );
			if(strlen($value_column) > 50){
				return $contentNew." ...";
			}
			return $contentNew;
		}

		return;
	}
	
	private function convertStringQuery($sql) {

		if(empty($sql)){
			$sql = "where 1";
		}else{
			$arr = explode ( $this->dg->arr["refDataBaseTable"], $sql);
		}
		return " SELECT COUNT(*) AS TOTAL FROM " . $this->dg->arr["refDataBaseTable"]." ".$arr[1];
	}

	private function methodGET() {
		$this->posicaoPagina = ! empty ( $_GET ["pagina"] ) ? $_GET ["pagina"] : null;
		return;
	}
}

class DataGridQueryConfig {

	private $dg;

	function __construct($dg)
	{
		$this->dg = $dg;
	}

	public function name($value = null){ $this->dg->arr["idDataGrid"] = $value; }

	public function query($value = null) {$this->dg->query = !empty($value)?trim($value):null;return;}
	public function pageRowLimit($value = 0) {$this->dg->arr["limit"] = $value;return;}

	public function refDatabaseId($value = null) {$this->dg->objCnn = $value;return;}
	public function refDatabaseTable($value = null) {$this->dg->arr["refDataBaseTable"] = $value;return;}
	public function refDataBaseTablePrimarykey($value = null){ $this->dg->arr["keyTable"] = $value;return;}
}

class DataGridQueryTable {

	
	private $dg;

	function __construct($dg)
	{
		$this->dg = $dg;
	}

	public function navigator($value = null){$this->dg->arr["showNavigator"] = $value;return;}
	public function navigatorLinkTarget($value = null){$this->dg->arr["navLinkTarget"] = $value;return;}
	public function navLinkTarget($value = "lista") { $this->dg->arr["navLinkTarget"] = $value;return;}
	public function borderSize($value = null) {return $this->dg->arr["sizeBorder"] = $value;}
	public function width($value = null) {$this->dg->arr["widthTable"] = $value;}
	public function cssClass($value = null) {$this->dg->arr["cssClassTable"] = $value;}
	public function titleAlign($value = null) {$this->dg->arr["alignTableTitle"] = $value;}
	
}

class DataGridQueryColumn {

	
	private $dg;

	function __construct($dg)
	{
		$this->dg = $dg;
	}

	// COLUNAS - CONFIG
	
	public function start() { $this->dg->flag++;return; }
	
	public function refDataBaseTableColumn($value = null) { $this->dg->arr["nameColumnTable"][$this->dg->flag] = $value;return; }

	// HTML ATTRIBUTS
	public function title($value = null) {$this->dg->arr["titleColumn"][$this->dg->flag] = $value;return;}
	public function type($value = null) {$this->dg->arr["typeColumn"][$this->dg->flag] = $value;return;}
	public function align($value = null) {$this->dg->arr["alignColumn"] [$this->dg->flag] = $value;return;}
	public function width($value = null) {$this->dg->arr["widthColumn"] [$this->dg->flag] = $value;return;}
	public function dafultValue($value = null) {$this->dg->arr["valueNullColumn"] [$this->flag] = $value;return;}

	// CSS
	public function style($value = null) {$this->dg->arr["styleColumn"] [$this->dg->flag] = $value;return;}
		
	public function linkUrl($value = null) {$this->dg->arr["linkUrl"] [$this->dg->flag] = $value;return;}
	public function linkTarget($value = null) {$this->dg->arr["linkTarget"] [$this->dg->flag] = $value;return;}
	public function linkName($value = null) {$this->dg->arr["linkName"] [$this->dg->flag] = $value;return;}
	public function linkOnclick($value = null) {$this->dg->arr["linkOnclick"] [$this->dg->flag] = $value;return;}
	
	public function imageUrl($value = null) {$this->dg->arr["imageUrl"] [$this->dg->flag] = $value;return;}
	public function cssClassLine($value = null) {$this->dg->arr["cssClassTable_line"] = $value;return;}
	public function cssClassTableLine($value = null) {$this->dg->arr["cssStyleTable_lint"] [$this->dg->flag] = $value;return;}
	public function cssClassTableColumn($value = null) {$this->dg->arr["cssStyleTableColumn"] [$this->dg->flag] = $value;return;}
	public function pageTarget($value = null) {$this->dg->arr["pageTarget"] [$this->dg->flag] = $value;return;}
	public function valueNull($value = null){$this->dg->arr["valueNull"] = $value;} // Valor para campo quando ele for nulo.
}

?>
