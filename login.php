<?php 
session_start(); 
include "contact.php";
include "price-updater.php";

if (isset($_POST['username']) && isset($_POST['password'])) {

	function validate($data){
       $data = trim($data);
	   $data = stripslashes($data);
	   $data = htmlspecialchars($data);
	   return $data;
	}

	$username = validate($_POST['username']);
	$password = validate($_POST['password']);

	if (empty($username)) {
		header("Location: index.php?error=Username is required");
	    exit();
	}else if(empty($password)){
        header("Location: index.php?error=Password is required");
	    exit();
	}else{
		$sql = "SELECT * FROM users WHERE username='$username' AND pword='$password'";

		$result = mysqli_query($conn, $sql);

		if (mysqli_num_rows($result) === 1) {
			$row = mysqli_fetch_assoc($result);
            if ($row['username'] === $username && $row['pword'] === $password && $row['user_type'] === "0") {
				$_SESSION["USERNAME"] = $username;
				$_SESSION["PASSWORD"] = $password;
				$_SESSION["ID"] = $row['userid'];
				$_SESSION["TYPE"] = $row['user_type'];
				updateCryptoPrices($conn);
            	header("Location: user-main.php");
		        exit();
            }else if($row['username'] === $username && $row['pword'] === $password && $row['user_type'] === "1")  {
				$_SESSION["USERNAME"] = $username;
				$_SESSION["PASSWORD"] = $password;
				$_SESSION["ID"] = $row['userid'];
				$_SESSION["TYPE"] = $row['user_type'];
				updateCryptoPrices($conn);
                header("Location: admin-main.php");
		        exit();
            }else{
				header("Location: index.php?error=Incorect Username or Password");
		        exit();
			}
		}else{
			header("Location: index.php?error=Incorect Username or Password");
	        exit();
		}
	}
	
}else{
	header("Location: index.php");
	exit();
}