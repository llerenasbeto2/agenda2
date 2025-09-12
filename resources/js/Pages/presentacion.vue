<script setup>

import Darkmode from '@/Components/Darkmode.vue';

</script>
<template>
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/agenda2.css">
    <title>Agenda de Escenarios Educativos</title>
                                <!-- DARK MODE -->
                                <div>
                                <Darkmode />
                                </div>
  </head>
  <body>

<header>
  <nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href=""><img src="img/logo.png"></a><span class="text-light">Agenda de&nbsp;<strong><em>Escenarios Educativos</em></strong></span>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav mr-auto mb-2 mb-md-0">
        </ul>
        <form class="d-flex">
          <button class="btn btn-outline-light" type="submit"><i class="far fa-user"></i> Iniciar Sesión</button>
        </form>
      </div>
    </div>
  </nav>

<div class="container">
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-light justify-content-end small">
    <li class="breadcrumb-item"><a class="text-dark text-decoration-none" href="#">Condiciones de uso</a></li>
    <li class="breadcrumb-item"><a class="text-dark text-decoration-none" href="#">Tarifas</a></li>
    <li class="breadcrumb-item"><a class="text-dark text-decoration-none" href="#">Contacto</a></li>
  </ol>
</nav>

<nav class="nav bg-primary text-light principal small">
  <a class="nav-link text-light" href="#">INICIO</a>
  <a class="nav-link text-light" href="#">SOLICITAR EVENTO</a>
  <a class="nav-link text-light" href="#">ESCENARIOS DISPONIBLES</a>
  <a class="nav-link text-light" href="#" tabindex="-1" aria-disabled="true">QUEJAS Y SUGERENCIAS</a>
</nav>


</div>

</header>
<main>
<h1>holaaa aqui vamos a poner el diseño</h1>
<h1>sdvsrrfew</h1>
</main>

<br>
<!-- FOOTER -->
<footer class="bg-dark bolder">
  <div class="container text-light small text-center">
    <p>&copy;Derechos Reservados Universidad de Colima &middot; Página desarrollada por la <a href="http://dgre.ucol.mx" class="text-light">Dirección General de Recursos Educativos</a></p>
  </div>
</footer>

   
  </body>

</template>