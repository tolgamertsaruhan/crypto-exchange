<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <?php include "contact.php"; ?>
    <title>Purchase</title>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <h1>Purchase Form Page</h1>

    <!- $_GET['purchaseID'] ile satın alınan coin id ulaşılabilir. ->
    <!- $_SESSION['ID'] ile satın alımı yapan kullanıcı id ulaşılabilir. Kullanıcının diğer değerlerine database üzerinden çekip ulaşmak yerine login de tanımlanan session lar ile de erişilebilir. ->
</body>
</html>