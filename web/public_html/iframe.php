<?php 
  include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'autoload.php';
  $userId = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';
  ini_set( 'session.cookie_httponly', 1 );
  if(!$userId){
    setcookie('user_id',uniqid(),time()+10000000);
  }
  if(isset($_POST['enviar'])){
    $resposta = new FeedbackResponse($_POST, $userId); 
    $resposta->save();
    $msg = 'Obrigado pelo seu feedback';
    echo '<html><body style="background: #fff !important;"><br><br><br><center style="font-family: \'Open Sans Condensed\', sans-serif;"><strong>'.$msg.'</strong></center></body></html>';
    exit;
  }
?>
<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <style>
            body {
                padding-top: 50px;
                padding-bottom: 20px;
            }
        </style>
        <link rel="stylesheet" href="css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="css/main.css">

        <!--[if lt IE 9]>
            <script src="js/vendor/html5-3.6-respond-1.4.2.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="<?php echo _PROJECT_URL_; ?>" target="_blank"><?php echo _PROJECT_NAME_; ?></a>
        </div>
        
      </div>
    </nav>


    <div class="container">
      <!-- Example row of columns -->
      <div class="row parentVerOriginal">
        <div class="col-md-12">
          <h2>O que você achou do bloqueio destes conteúdos ?</h2>
          <p>Sua opinião irá nos ajudar a melhorar ainda mais o nosso algoritmo.</p>
          <input type="button" class="float-r btn btn-warning ver-original" value="Ver todas as imagens originais">
          <input type="button" class="float-r btn btn-warning ocultar-original" value="Ocultar todas as imagens originais">
          <div class="clearfix"></div>

          <form action="" method="post" class="form-style-8">
            <input type="hidden" name="enviar">
            <input type="hidden" name="data" id="data">
            <ul id="fotos_map">

            </ul>
            <textarea name="mensagem" placeholder="Digite aqui sua opinião" style="width: 100%;resize: none;height: 100px;margin-bottom:10px;"></textarea>
            <input type="submit" value="Enviar" class="btn btn-success" style="width: 100%;">
          </form>
          <br><br><br><br><br><br>

        </div>
      </div>

    </div> <!-- /container -->        
        <script src="js/vendor/jquery-1.11.2.min.js"></script>
        <script type="text/template" id="liTemplate">
          <li class="row parentVerOriginal">
            <div class="imagem col-md-6 col-sm-12 coluna">
              <div class="select">
                <label> <input type="radio" name="certo_errado[{{id}}]" value="1"> Certo </label>
                <label> <input type="radio" name="certo_errado[{{id}}]" value="2"> Errado </label>
              </div>
              <img src="http://localhost/puc/tcc/web/temp/dt.png" class="imgSrc">
              <input type="hidden" class="url" name="url[{{id}}]" value="">
            </div>
            <div class="original col-md-6 col-sm-12 coluna img-original original-hidden">
              <h3>Original:</h3>
              <input type="button" class="btn btn-warning ver-original" value="Ver original">
              <input type="button" class="btn btn-warning ocultar-original" value="Ocultar original">
              <img src="http://localhost/puc/tcc/web/temp/dt.png" class="imgSrcOriginal">
            </div>
          </li>
        </script>
        <script>
          (function(){
          	Array.prototype.unique = function() {
			    var unique = [];
			    for (var i = 0; i < this.length; i++) {
			        if (unique.indexOf(this[i]) == -1) {
			            unique.push(this[i]);
			        }
			    }
			    return unique;
			};
            if(window.parent === window || !window.parent){
              return;
            }
            var liTemplate = $('#liTemplate').html();
            var index = 0;
            function preencherDados(srcs)
            {
              var $append = $('#fotos_map');
              $append.empty(true);
              srcs.map(function(url){
                var _li = $(liTemplate.replace(/\{\{id\}\}/g,index));
                _li.find('.imgSrc').attr('src',url);
                _li.find('.imgSrcOriginal').attr('src',url+'?&porn-block-show-image=1&porn-block-show-dont-redirect=1');
                _li.find('.url').val(url);
                $append.append(_li);
                index++;
              });
            }

            window.addEventListener("message", function(e){
              if(e.data){
                if(e.data.evento == 'avaliarImagens'){
                  var srcs = e.data.srcs.unique();
                  preencherDados(srcs);
                }
              }
            },false);
          })();
          </script>
        <script src="js/vendor/bootstrap.min.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>
