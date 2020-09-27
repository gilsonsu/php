<?php

class HtmlCheckBox {

	private $name;
	private $fields;
	private $values;
	private $checked;
	private $disabled;

	public function start() {
		$this->name = null;
		$this->fields = null;
		$this->values = null;
		$this->checked = null;
		$this->disabled = null;
		return;
	}

	public function name($name = null) {
		$this->name = $name;
		return;
	}

	public function fields($fields = null) {
		$this->fields = $fields;
		return;
	}

	public function values($value = null) {
		$this->values = $value;
		return;
	}

	public function checked($value = null) {
		$this->checked = $value;
		return;
	}

	public function disabled($disabled = false) {
		$this->disabled = $disabled;
		return;
	}

	public function show() {
		$content = null;
		if (empty ( $this->values )) {
			return "NOT FOULD REGISTERS!";
		}
		$total = sizeof ( $this->values );
		for($i = 0; $i < $total; $i ++) {
			$disabled = ($this->disabled == true)?"disabled=\"disabled\"":null;
			$checked = null;
			if (! empty ( $this->checked )) {
				$key = @array_search ( $this->values [$i], $this->checked );
				$checked = null;
				if ($key !== false) {
					$checked = "checked=\"checked\"";
				}
			}
			$content .= "\n<li><label>";
			$content .= " <input type=\"checkbox\" "; 
			$content .= " id=\"". $this->name.$i ."\" " ;
			$content .= " name=\"". $this->name ."[]\" ";
			$content .= " value=\"" . $this->values [$i] ."\" "; 
			$content .= $checked." " ;
			$content .= $disabled."/>"; 
			$content .= $this->fields [$i] ."</label></li>";
			$checked = null;
		}
		return "<ul id=\"".$this->name."\" style=\"list-style-type:none;margin:0;padding:0;\">".$content."</ul>";
	}
}
?>
