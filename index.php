
<!DOCTYPE html>
<html>
<head>
	
<meta charset="utf-8">
<title>Login</title>
<link rel="stylesheet" href="registeration/css/style.css" />	
</head>

<body>

<?php
	require('registeration/db.php');
	session_start();
    // If form submitted, insert values into the database.
    if (isset($_POST['username'])){
        $username = $_POST['username'];
        $password = $_POST['password'];
	//Checking is user existing in the database or not
        $query = "SELECT * FROM `users` WHERE username='$username' and password='$password'";
		$result = mysql_query($query) or die(mysql_error());
		$rows = mysql_num_rows($result);
        if($rows==1){
			$_SESSION['username'] = $username;
			header("Location:1.php"); 
            }else{
				echo "<div class='res'>
				<center><h3>Username/Password is incorrect!!! try again..</h3><br/>Click here to <a href='index.php'><h3>Login</h3></a></center></div>";
				}
    }else{
?>
<div class="form">
<center><h1 style="color:#78b941;">LOG-IN</h1></center>
<form name="form" action"" method="post" id="log" >
<input type="text" name="username" placeholder="Username" required />
<input type="password" name="password" placeholder="Password" required/>
<input name="submit" type="submit" value="Login" />
</form>
<p>Not registered yet? <a href='registration.php'>Register Here</a></p>
</div>
<?php } ?>
</body>
</html>
