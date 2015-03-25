# What it does #
  * removes all comments
  * removes all whitespace
  * shrinks variable names (only variables, properties are not touched)
  * removes the "public" keyword
  * adds your custom comment (copyright, license, etc)

# How to use #
```
require "compressor.php";
    
$c = new Compressor;

$c->keep_line_breaks = false;
$c->comment = array(
    "Hello!",
    "This is your custom comment!"
);

$c->load(file_get_contents("IN"));           
file_put_contents("OUT", $c->run());
```