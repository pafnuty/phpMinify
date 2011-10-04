<?php

    class Compressor {
        static $RESERVED_VARS = array(
            '$GLOBALS' => 1,
            '$_ENV' => 1, 
            '$_SERVER' => 1, 
            '$_SESSION' => 1, 
            '$_REQUEST' => 1, 
            '$_GET' => 1, 
            '$_POST' => 1, 
            '$_FILES' => 1, 
            '$_COOKIE' => 1,
            '$HTTP_RAW_POST_DATA' => 1,
            '$this' => 1            
        );
        const BLOCK_ROOT = 0;
        const BLOCK_CLASS = 1;
        const BLOCK_FUNC = 2;
        const BLOCK_MISC = 3;
        
        
        public $comment = null;
        public $keep_line_breaks = false;
    
        private $tokens = array();
        
        function load($text) {         
            $this->add_tokens($text);
        }
        
        function run() {
            $this->shrink_var_names();
            $this->remove_public_modifier();

            return $this->generate_result();
        }
        
        private function generate_result() {
            $result = "<?php\n";
            
            if($this->comment) {
                foreach($this->comment as $line) {
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
        
        private function remove_public_modifier() {
            for($i = 0; $i < count($this->tokens) - 1; $i++) {
                if($this->tokens[$i][0] == T_PUBLIC)
                    $this->tokens[$i] = $this->tokens[$i + 1][1][0] == '$' ? array(T_VAR, "var") : array(-1, "");
            }            
        }
        
        private function shrink_var_names() {
            $stat = array();
            $indices = array();
            
            $block_stack = array();
            $pending_block = self::BLOCK_MISC;
            $global_clause = false;
            
            for($i = 0; $i < count($this->tokens); $i++) {                
                list($type, $text) = $this->tokens[$i];
                
                if($type == T_CLASS)
                    $pending_block = self::BLOCK_CLASS;
                
                if($type == T_FUNCTION)
                    $pending_block = self::BLOCK_FUNC;                
                
                if($type == T_GLOBAL)
                    $global_clause = true;                                
                
                if($type == T_VARIABLE) {                                                            
                    if($global_clause)
                        continue;                    
                    
                    if(isset(self::$RESERVED_VARS[$text]))
                        continue;                    

                    $current_block = self::BLOCK_ROOT;
                    foreach($block_stack as $item) {
                        if($item != self::BLOCK_MISC) {
                            $current_block = $item;
                            break;
                        }
                    }                    
                    
                    if($current_block == self::BLOCK_ROOT)
                        continue;
                    
                    if($i > 0) {
                        $prev_token = $this->tokens[$i - 1];                        
                        if(in_array($prev_token[0], array(T_DOUBLE_COLON, T_VAR, T_PUBLIC, T_PROTECTED, T_PRIVATE)))
                            continue;
                        if($prev_token[0] == T_STATIC && $current_block == self::BLOCK_CLASS)
                            continue;                        
                    }
                    
                    $indices[] = $i;
                    if(!isset($stat[$text]))
                        $stat[$text] = 0;
                    $stat[$text]++;
                }                
                               
                if($type == -1) {
                    if($text == "{") {
                        array_unshift($block_stack, $pending_block);
                        $pending_block = self::BLOCK_MISC;
                    } 
                    
                    if($text == "}")            
                        array_shift($block_stack);                    
                    
                    if($text == "}" || $text == ";")
                        $global_clause = false;                    
                }
            }
            
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
                $this->tokens[$index][1] = '$' . $aliases[$name];
            }
        }        
               
        private function add_tokens($text) {            
            $tokens = token_get_all(trim($text));
            if(!count($tokens))
                return;
            
            if(is_array($tokens[0]) && $tokens[0][0] == T_OPEN_TAG)
                array_shift($tokens);
                
            $last = count($tokens) - 1;
            if(is_array($tokens[$last]) && $tokens[$last][0] == T_CLOSE_TAG)
                array_pop($tokens);
                   
            $pending_whitespace = count($this->tokens) ? "\n" : "";
            
            foreach($tokens as $t) {
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
    
   