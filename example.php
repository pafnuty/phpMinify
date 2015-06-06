<?php
$minifyCode = false;
if ($_POST['phpcode']) {
    $text = $_POST['phpcode'];
    if(strlen($text) > 256 * 1024) {
        $minifyCode = 'Слишком большой кусок кода, я с таким не справлюсь :(';
    } else {
        require "phpMinify.php";

        $minifier = new PhpMinify;
        $minifier->load($text);           
        $minifyCode = htmlspecialchars($minifier->run());
    }
}

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Php Mimifier</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="page-header">
              <h1>PHP Minify <small>онлайн минификатор для php кода</small></h1>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <form method="POST" role="form" accept-charset="utf-8">
                    
                        <div class="form-group">
                            <textarea class="form-control" name="phpcode" id="phpcode" rows="5" placeholder="Вставьте сюда свой PHP код"></textarea>
                        </div>
                        <?php if ($minifyCode): ?>
                            
                            <div class="form-group">
                                <h3>Ваш код:</h3>
                                <textarea class="form-control" cols="30" rows="10"><?php
                                    echo $minifyCode;
                                ?></textarea>
                            </div>
                            
                        <?php endif ?>
                        <div class="form-group">
                            <div class="alert alert-warning">
                                <small><b class="text-danger">Ваш код нигде не сохраняется!</b> Если минифицированный код окажется неработоспособным — вы не сможете восстановить его исходник, если сами не сохраните его.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Уменьшить!</button> 
                    </form>
                </div>
            </div>
        </div>      
    </body>
</html>