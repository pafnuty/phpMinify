<?php

    require "compressor.php";
    
    $c = new Compressor;
    
    $c->keep_line_breaks = false;
    $c->comment = array(
        "Hello!",
        "Compressor shrinks itself!"
    );
    
    $c->load(file_get_contents("compressor.php"));
    
    header("Content-Type: text/plain");
    print $c->run();