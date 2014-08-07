       <?php

            // Check if there is a post
            if (count($_POST)) {
                $username = isset($_POST['username']) ? $_POST['username'] : false;
                $password = isset($_POST['password']) ? $_POST['password'] : false;

                // Verify information and login
                $user->login($username, $password, $conn);
            }

       ?>

        <div class="container">
            <div class="starter-template">
                <h1>Hello, world!</h1>
                <p class="lead">Now you can start your own project with Bootstrap 3.2.0. This plugin is a fork from <a href="https://github.com/le717/brackets-html-skeleton">HTML Skeleton</a>.</p>
            </div>
            
            <div class='login'>
                <form action="" method="post" name='login_user'>
                    <label for="username">Username:</label>
                    <input type="text" id='username' name='username' placeholder="Enter username"></input><br>
                    <label for="password">Password:</label>
                    <input type="password" id='password' name='password' placeholder="Enter password"></input><br>
                    <button>Login</button>
                </form>
            </div>
        </div>