
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Registration</title>
<link rel="stylesheet" href="registeration/css/style.css" />
</head>
<body>
<?php
	require('registeration/db.php');
    // If form submitted, insert values into the database.
    if (isset($_POST['username'])){
        $username = $_POST['username'];
		$email = $_POST['email'];
        $password = $_POST['password'];
		$trn_date = date("Y-m-d H:i:s");
        
       
        $query = "INSERT into `users` (username, password, email, trn_date) VALUES ('$username','$password', '$email', '$trn_date')";
        $result = mysql_query($query);
       
     if($result){
            echo "<div class='reg'><center><h3>You are registered successfully.</h3><br/>Click here to <a href='index.php'><h3>Login</h3></a></center></div>";
        }
    }else{
?>
<div class="form">
<center><h1 style="color:#78b941;">Registration</h1></center>
<form name="registration" action="" method="post">
<input type="text" name="username" placeholder="Username" required />
<input type="email" name="email" placeholder="Email" required />
<input type="password" name="password" placeholder="Password" required />
<input type="submit" name="submit" value="Register" />
</form>
</div>
<?php } ?>
</body>
</html>
