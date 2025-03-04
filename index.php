<?php 
    include("connection.php");
    include("login.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="assets\style.css">
    </head>
    <body>
        
        <div id="form">
            <div class="logo">
                <img src="resources\ensao_logo.png" alt="Logo de l'école" style="width: 250px; height: auto;">
            </div>
            <form name="form" action="login.php" onsubmit="return isvalid()" method="POST">
                <div class="input-group">
                   <label for="email">Email</label>
                   <input type="text" id="user" name="user" placeholder="Votre email" required>
                </div>
                <div class="input-group">
                   <label for="password">Password</label>
                   <input type="password" id="pass" name="pass" placeholder="Votre mot de passe" required>
                </div>
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <input type="submit" id="btn" value="LOG IN" name = "submit"/>
            </form>
          
        </div>
        <script>
            function isvalid(){
                var user = document.form.user.value;
                var pass = document.form.pass.value;
                if(user.length=="" && pass.length==""){
                    alert(" Username and password field is empty!!!");
                    return false;
                }
                else if(user.length==""){
                    alert(" Username field is empty!!!");
                    return false;
                }
                else if(pass.length==""){
                    alert(" Password field is empty!!!");
                    return false;
                }
                
            }
        </script>
    </body>
</html>