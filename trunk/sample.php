<?php
    $text = isset($_POST["q"]) ? $_POST["q"] : file_get_contents("compressor.php");
    if(strlen($text) > 256 * 1024)
        die("Too much data, sorry");
?>

<form method=post>
    <textarea name=q style="width: 100%; height: 200px"><?php echo $text; ?></textarea>
    <br>
    <small>(Entered text will not be saved anywhere)</small>
    <br>
    <input type=submit>
</form>

<h3>Result:</h3>

<textarea style="width: 100%; height: 300px"><?php

  require "compressor.php";
    
    $c = new Compressor;
    
    $c->keep_line_breaks = false;
    $c->comment = array(
        "Hello!",
        "This is your custom comment!"
    );
    
    $c->load($text);           
    print htmlspecialchars($c->run());

?></textarea>


  