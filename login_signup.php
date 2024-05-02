<?php
require_once 'login.php';

// connect to db
$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) die($conn->connect_error);

echo file_get_contents('frontend/login_signup.html');

// check sign up input
if (isset($_POST['sUsername']) && isset($_POST['sEmail']) && isset($_POST['sPassword'])) {
    // sanitize inputs
    $username = sanitizeString($_POST['sUsername']);
    $email = sanitizeString($_POST['sEmail']);
    $password = $_POST['sPassword'];

    // check if username exists
    if (searchUsername($username)) { // studentID alr exists
        echo '<script>alert("Username is already taken.");</script>';
    }
    else { // username valid
        // server side form input validation
        if (validateUserData($username, $email, $password)) {
            // get token with hash
            $token = password_hash($password, PASSWORD_DEFAULT);
            
            // add to db
            insertDB($username, $email, $token);
            
            // start session, set session vars
            session_start();
            $_SESSION['initiated'] = true;
            $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
            $_SESSION['username'] = $username;
            $conn->close();

            // redirect to first page
            header('Location: dashboard.php');
            exit();
        }
    }
}

// check log in input
if (isset($_POST['lUsername']) && isset($_POST['lPassword'])) {
    //sanitize inputs
    $username = sanitizeString($_POST['lUsername']);
    $password = $_POST['lPassword'];

    // query for user credentials
    $stmt = $conn->prepare('SELECT * FROM user_accounts WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array(MYSQLI_NUM);
    $stmt->close();
    
    // check password
    if (password_verify($password, $row[2])) { // if tokens match
        // start session, set session vars
        session_start();
        $_SESSION['initiated'] = true;
        $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        $_SESSION['username'] = $username;
        $conn->close();

        // redirect to first page
        header('Location: dashboard.php');
        exit();
    }
    else echo '<script>alert("Incorrect Log In.");</script>';
}

$conn->close();

// add to db function
function insertDB($username, $email, $token) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO user_accounts VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $email, $token);
    $stmt->execute();
    $stmt->close();
}

// return if username alr in db
function searchUsername($username) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM user_accounts WHERE username=?');
    $stmt->bind_param('i', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// server side form input validation
function validateUserData($username, $email, $password) {
    // Validate Username
    if ($username === "" || preg_match('/[^a-zA-Z0-9_-]/', $username)) {
        echo "<script>alert('Login/Signup failed');</script>";
        return false;
    }
    // Validate Email
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Login/Signup failed');</script>";
        return false;
    }
    // Validate Password
    if ($password === "" || strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        echo "<script>alert('Login/Signup failed');</script>";
        return false;
    }
    return true;
}


// sanatize strings
function sanitizeString($var) {
    $var = stripslashes($var);
    $var = strip_tags($var);
    $var = htmlentities($var);
    return $var;
}
?>