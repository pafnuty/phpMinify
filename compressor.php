<?php

    class Compressor {
        public $comment = null;
        public $keep_line_breaks = false;
    
        private $tokens = array();
        
        function load($text) {         
            $this->add_tokens($text);
        }
        
        function run() {
            $this->shrink_var_names();
            $this->remove_public_modifier();
            $this->remove_bounding_tags();

            return $this->generate_result();
        }
        
        private function generate_result() {
            $result = "<?php\n";
            
            if($this->comment) {
                foreach(is_array($this->comment) ? $this->comment : explode("\n", $this->comment) as $line) {
                    $result .= "# " . trim($line) . "\n";
                }
            }
            $result .= "# Shrunk with http://code.google.com/p/php-compressor/\n";
            
            foreach($this->tokens as $t) {
                $text = $t[1];
                
                if(!strlen($text))
                    continue;                          

                if(preg_match("~^\\w\\w$~", $result[strlen($result) - 1] . $text[0]))
                    $result .= " ";

                $result .= $text;                
            }

            return $result;
        }
        
        private function remove_bounding_tags() {        
            if($this->tokens[0][0] == T_OPEN_TAG)
                $this->tokens[0] = array(-1, "");
        
            for($i = 1; $i < count($this->tokens); $i++) {
                if($this->tokens[$i][0] == T_OPEN_TAG && $this->tokens[$i - 1][0] == T_CLOSE_TAG) {
                    $this->tokens[$i] = array(-1, "");
                    $this->tokens[$i - 1] = array(-1, "");
                }
            }
            
            $last = count($this->tokens) - 1;
            if($this->tokens[$last][0] == T_CLOSE_TAG)
                $this->tokens[$last] = array(-1, "");            
        }

        private function remove_public_modifier() {
            for($i = 0; $i < count($this->tokens) - 1; $i++) {
                if($this->tokens[$i][0] == T_PUBLIC)
                    $this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? array(T_VAR, "var") : array(-1, "");
            }            
        }
        
        private function shrink_var_names() {
            $stat = array();
            $indices = array();
            $exclusions = array(
                '$_ENV' => 1, 
                '$_SERVER' => 1, 
                '$_SESSION' => 1, 
                '$_REQUEST' => 1, 
                '$_GET' => 1, 
                '$_POST' => 1, 
                '$_FILES' => 1, 
                '$_COOKIE' => 1,
                '$this' => 1
            );
            
            for($i = 0; $i < count($this->tokens); $i++) {                
                if($this->tokens[$i][0] != T_VARIABLE)
                    continue;
                    
                $name = $this->tokens[$i][1];
                
                if($i > 0) {                    
                    if(in_array($this->tokens[$i - 1][0], array(T_DOUBLE_COLON, T_VAR, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC))) {
                        $exclusions[$name] = 1;
                        continue;
                    }
                }
                
                $indices[] = $i;

                if(!isset($stat[$name]))
                    $stat[$name] = 0;
                $stat[$name]++;
            }
            foreach(array_keys($exclusions) as $name) {
                unset($stat[$name]);
            }
            unset($exclusions);           
            
            arsort($stat);
            
            $aliases = array();
            $i = 0;
            foreach(array_keys($stat) as $name) {
                $aliases[$name] = $this->encode_id($i);
                $i++;
            }
            unset($stat);
            
            foreach($indices as $index) {
                $name = $this->tokens[$index][1];
                if(isset($aliases[$name]))
                    $this->tokens[$index][1] = '$' . $aliases[$name];
            }
        }                
               
        private function add_tokens($text) {
            $pending_whitespace = "";
            foreach(token_get_all($text) as $t) {
                if(!is_array($t))
                    $t = array(-1, $t);
                
                if($t[0] == T_COMMENT || $t[0] == T_DOC_COMMENT)
                    continue;
                
                if($t[0] == T_WHITESPACE) {
                    $pending_whitespace .= $t[1];
                    continue;
                }
                        
                if($this->keep_line_breaks && strpos($pending_whitespace, "\n") !== false) {
                    $this->tokens[] = array(-1, "\n");
                }
                    
                $this->tokens[] = $t;        
                $pending_whitespace = "";
            }
        }
    
        private function encode_id($value) {                                
            $result = "";            
            
            if($value > 52) {
                $result .= $this->encode_id_digit($value % 53);
                $value = floor($value / 53);
            }            
            
            while($value > 62) {
                $result .= $this->encode_id_digit($value % 63);
                $value = floor($value / 63);
            }
            
            $result .= $this->encode_id_digit($value);
            return $result;
        }
        
        private function encode_id_digit($digit) {
            if($digit < 26)
                return chr(65 + $digit);
            if($digit < 52)
                return chr(71 + $digit);
            if($digit == 52)
                return "_";
            return chr($digit - 5);
        }    
    }
    
   