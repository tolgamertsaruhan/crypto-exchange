<head>
	<meta charset="utf-8">
	<script src="js/script.js" defer></script>
	<title></title>
	<style>
		*{
			box-sizing: border-box;
		}

		body {
			margin: 0;
			padding: 0;
		}

		.navbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background-color: green;
			color: #fff;
		}

		.brand-title {
			margin-left: 35px;
		}
		.brand-title img{
			display: block;
			height: 60px;
			border-radius: 15px;
		}

		.brand-title img:hover{
    		transform: scale(0.9);
		}

		.navbar-links ul {
			margin: 0;
			padding:  0;
			display:  flex;
			margin-right: 35px;
		}

		.navbar-links li{
			list-style: none;
		}
		
		.navbar-links li a{
			text-decoration: none;
			color: #fff;
			padding: 1rem;
			display: block;
			transition: 0.5s;
    		cursor: pointer; 
		}
		.navbar-links li:hover {
			background-color: #555;
			transform: scale(0.9);
		}
		.navbar-links li-red{
			list-style: none;
		}
		.navbar-links li-red a{
			text-decoration: none;
			color: #6b1d1d;
			padding: 1rem;
			display: block;
			transition: 0.5s;
    		cursor: pointer; 
		}
		.navbar-links li-red:hover {
			background-color: #ff0000;
			transform: scale(0.9);
		}

		.toggle-button {
			position: absolute;
			top: .75rem;
			right: 1rem;
			display: none;
			flex-direction: column;
			justify-content: space-between;
			width: 30px;
			height: 21px;
		}

		.toggle-button .bar {
			height: 3px;
			width:  100%;
			background-color: #fff;
			border-radius:  10px;
		}

		@media (max-width: 400px) {
			.toggle-button {
				display: flex;
			}

			.navbar-links {
				display: none; 
				width: 100%;
			}

			.navbar {
				flex-direction: column;
				align-items: flex-start;
			}

			.navbar-links ul {
				width: 100%;
				flex-direction: column;
			}
			.navbar-links li {
				text-align: center;
			}
			.navbar-links li a {
				padding: .5rem 1rem;
			}
			.navbar-links li-red {
				text-align: center;
			}
			.navbar-links li-red a {
				padding: .5rem 1rem;
			}

			.navbar-links.active {
				display: flex;
			}
		}
	</style>

</head>
<body>
	<nav class="navbar">
		<div class="brand-title"><label><a href="admin-main.php"> <img src="logo.png"></a></label></div>
		<a href="admin-main.php" class="toggle-button"></a>
			<span class="bar"></span>
			<span class="bar"></span>
			<span class="bar"></span>
			

		<div class="navbar-links">
			<ul>
				<li><a href="admin-main.php">Main Page</a></li>
				<li><a href="user-management.php">User Management</a></li>
				<li><a href="profile-admin.php">Profile</a></li>
				<li-red><a href="index.php">Log Out</a></li-red>
			</ul>
		</div>
	</nav>			
</body>
</html>
		</div>
	</nav>			
</body>
</html>