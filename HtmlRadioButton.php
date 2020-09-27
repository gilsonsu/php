<?php

class HtmlRadioButton {

	private $name;
	private $fields;
	private $values;
	private $checked;
	private $disabled;
	private $posicao;
	
	public function start() {

		$this->name = null;
		$this->fields = null;
		$this->values = null;
		$this->checked = null;
		$this->disabled = null;
		$this->posicao = "h";

		return;
	}

	public function horizontal(){$this->posicao = "h";}

	public function vertical(){$this->posicao = "v";}

	public function name($name = null) {$this->name = $name;return;}

	public function fields($fields = null) {$this->fields = $fields;return;}

	public function values($value = null) {$this->values = $value;return;}
	
	public function checked($valor = null) {$this->checked = $valor;return;}

	public function disabled($disabled = false) {$this->disabled = $disabled;return;}

	function show() {

		$radio = null;

		if (empty ( $this->values )) {
			return "NOT FOULD REGISTERS!";
		}
		
		$display = "display:inline;";
		
		if($this->posicao == "v" ){
			$display = null;
		}

		$totalValue = sizeof ( $this->values );

		for($i = 0; $i < $totalValue; $i ++) {

			$disabled = ($this->disabled == true)? "disabled=\"disabled\"":null;
			$checked =  ($this->checked == $this->values [$i])? "checked=\"checked\"":null;

			$radio .= "\n <li style=\"margin:0;padding:0;".$display."\">";
			$radio .= " <label><input type=\"radio\"  id=\"". strtolower($this->name)."_";
			$radio .= strtolower(str_replace(" ","_",$this->fields [$i])) ."\" name=\"". $this->name ."\" value=\"" . $this->values [$i] ."\" ";
			$radio .= $checked . " " . $disabled . " />" . $this->fields [$i] ."</label></li>";
			
			$selected = $checked = null;
		}

		$css = "margin:0;padding:0;list-style:none;".$display;

		$radio = "<ul style=\"".$css."\" >".$radio."</ul>";

		return $radio;
	}
}
?>
