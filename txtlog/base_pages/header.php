<!DOCTYPE html>
<html>
  <head>
    <title><?=$this->getPageTitle() ?? ''?></title>
    <link rel="stylesheet" href="/css/bulma.min.css?v=1">
    <link rel="stylesheet" href="/css/main.css?v=1">
    <script src="/scripts/jquery-3.7.1.min.js?v=1"></script>
    <script src="/scripts/main.js?v=1"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>

  <body>
    <nav class="navbar navcustom" role="navigation" aria-label="main navigation">
      <div class="navbar-brand">
        <a class="navbar-item" href="/">Home</a>
        <a class="navbar-item" href="/account">Account</a>
        <a class="navbar-item" id="dark"><img src="/images/lightdark.png"></a>
      </div>
    </nav>
