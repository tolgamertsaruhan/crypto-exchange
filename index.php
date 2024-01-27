<?php session_start(); ?>
<?php session_destroy(); ?>
<!DOCTYPE html>
<html lang="en">
<style>
    header.header{
    font:bold 34px/35px "Lucida Grande",Arial, Helvetica, sans-serif;
    color: green;
}
</style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="img-main" style="text-align: center; margin: 20px">
        <img src="logo.png" width="150" height="150">
    </div>
    <div class="card">
        <header class="header" style="text-align: center; margin-top: 15px;">Log In Page
        </header>
        <div class="card-body" style="width: 95%; margin: auto;">
            <form method="post" action="login.php" enctype="multipart/form-data">
                <?php if (isset($_GET['error'])) { ?>
                    <p class="error" style="width: 100%"><?php echo $_GET['error']; ?></p>
                <?php }?>
                <div class="form-group">
                    <label for="text">Username</label>
                    <input type="text" class="form-control" id="text" name="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <button type="submit" name="login" class="btn btn-success">Log In</button>
                <a href="signup.php" class="link-secondary">Sign Up</a>
            </form>
        </div>
    </div>
</body>
</html>