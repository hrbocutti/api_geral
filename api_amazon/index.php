<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, shrink-to-fit=no, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>Api Amazon - Integrador</title>

  <!-- Bootstrap Core CSS -->
  <link href="css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="css/simple-sidebar.css" rel="stylesheet">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

      </head>

      <body>

        <div id="wrapper">

          <!-- Sidebar -->
          <div id="sidebar-wrapper">
            <ul class="sidebar-nav">
              <li class="sidebar-brand">
                <a href="#">
                  Amazon Marketplace
                </a>
              </li>
              <li>
                <a href="#newProd">Novos Produtos</a>
              </li>

            </ul>
          </div>
          <!-- /#sidebar-wrapper -->

          <!-- Page Content -->

          <div id="page-content-wrapper">
            <div class="container-fluid">
              <div class="row">
              <?php
                if (isset($_GET['erroSkuEmpty']) && $_GET['erroSkuEmpty'] == '1') {

                 echo "
                  <div class='alert alert-danger'>
                    <strong>Erro!</strong> Não foi infomado uma 'SKU' valida.
                  </div>";
                }

                if (isset($_GET['successSku']) && $_GET['successSku'] == '1') {
                  if (isset($_GET['feedId']) && $_GET['feedId'] != '') {
                    $feedId = $_GET['feedId'];
                  }
                 echo "<div class='alert alert-success'>
                        <strong>Sucesso!</strong> Feed enviado com sucesso. Feed ID:".$feedId."
                      </div>";
                }
              ?>
                <div class="col-lg-6">
                  <h1>Novos Produtos Amazon</h1>
                  <div class="col-lg-12">
                  <form action="adicionaProdutoAmazon.php" id="form" method="post">
                      <div class="form-group">
                        <label for="sku">SKU</label>
                        <textarea name="sku" id="sku" class="form-control" rows="3" data-toggle="tooltip" title="sku (xxxxx,xxxxx) Sem espaço"></textarea>
                      </div>

                      <button type="submit" class="btn btn-default" oncomplete="">Enviar</button>
                    </form>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div id="chart_div"></div>
                </div>
              </div>
            </div>
          </div>
          <!-- /#page-content-wrapper -->

        </div>
        <!-- /#wrapper -->

        <div class="modal modal-fullscreen fade" id="myModal" tabindex="-1" role="dialog">
          <div class="modal-dialog">
            <div class="modal-content">
              <center>
              <h1>Aguarde !</h1>
               <?php
                  $num = rand(0,1);
                  if ($num == 0) {
                    echo "<img src='https://d13yacurqjgara.cloudfront.net/users/12755/screenshots/1037374/hex-loader2.gif'>";
                  }
                  if($num == 1){
                    echo "<img src='https://d13yacurqjgara.cloudfront.net/users/107759/screenshots/2436386/copper-loader.gif'>";
                  }
               ?>
              </center>
            </div><!-- /.modal-content -->
          </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <!-- jQuery -->
        <script src="js/jquery.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="js/bootstrap.min.js"></script>

        <!-- Google Charts -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">

        <!-- Menu Toggle Script -->
          $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
          });
          $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip(); 
          });

          $( "#form" ).submit(function( event ) {
              $('#myModal').modal('show');
          });

          <!--Load the AJAX API-->
          // Load the Visualization API and the corechart package.
          google.charts.load('current', {'packages':['corechart']});

          // Set a callback to run when the Google Visualization API is loaded.
          google.charts.setOnLoadCallback(drawChart);

          // Callback that creates and populates a data table,
          // instantiates the pie chart, passes in the data and
          // draws it.
          function drawChart() {

            // Create the data table.
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Topping');
            data.addColumn('number', 'Slices');
           <?php
           if ( isset($_GET['qntEnvi']) && isset($_GET['qntErro'])  &&
                $_GET['qntEnvi'] != '' &&  $_GET['qntErro'] != '')
           {
              $qntEnviados = $_GET['qntEnvi'] - $_GET['qntErro'] ;
              $qntErros = $_GET['qntErro'];
           }

            echo "data.addRows([
                    ['Enviados', ".$qntEnviados."],
                    ['Com Erros', ".$qntErros."]
                  ]);";
           ?>

            // Set chart options
            var options = {'title':'Envio Produtos Amazon',
                           'width':400,
                           'height':300};

            // Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
            chart.draw(data, options);
          }


        </script>

      </body>

      </html>
