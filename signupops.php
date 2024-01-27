<?php
session_start(); 
include "contact.php";

if (isset($_POST['username']) && isset($_POST['password'])) { 

    if (empty($_POST['username'])) {
		header("Location: signup.php?error=Username is required");
	    exit();
	}else if(empty($_POST['password'])){
        header("Location: signup.php?error=Password is required");
	    exit();
	}else{
        $username = $_POST['username'];
        $sql1 = "SELECT * FROM users WHERE username='$username'";

        $result = mysqli_query($conn, $sql1);
        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            if ($row['username'] === $username) {
                header("Location: signup.php?error=The same username exists in another member. Please try a new username.");
                exit();
            }
        }

        $usertype = "0";
        $sql2 = "INSERT INTO users(username, pword, user_type) VALUES(?, ?, ?)";
        $stmt= $conn->prepare($sql2);
        $stmt->bind_param('sss', $_POST['username'], $_POST['password'], $usertype);
        $stmt->execute();
        //$result = mysqli_query($conn, $control);

        //$insert = $db_name->prepare("INSERT INTO user SET username=:username, password=:password, user_type=:user_type");
        //$control = $insert->execute(array("username" => $_POST[username], "password" => $_POST[password], "user_type" => "2"));

        if($stmt){
            header("Location:registration.php");
            exit();
        }else{
            echo"error";
        }
	}

    
}else{
	header("Location: signup.php");
	exit();
}
?>