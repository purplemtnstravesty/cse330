<?php
session_start();
/*reference https://www.php.net/manual/en/security.filesystem.php 
for best practices on filesystem security

This PHP uses the following general flow:
1)Session Handling and Login Check
2)User Directory Assignment
3)Directory Navigation
4)Directory Creation
5)File Upload Handling
6)File Validation
7)Redirect to dashboard.php after Upload and the user's current directory

*/

#checks if a user is logged in; if not, redirects to login page
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

#user gets directory under /var/www/user_files/
$username = $_SESSION['username'];
$user_dir = "/var/www/user_files/$username";
$current_dir = $user_dir;


/*
3) Directory Navigation
Adds a stricter check for valid directory names using ctype_alnum 
and a preg_match pattern. This ensures that dir cannot include special 
characters or patterns like ../.
*/

if (isset($_GET['dir'])) {
    $dir = $_GET['dir'];

    if (!ctype_alnum($username) || !preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $dir)) {
        die("Bad username/filename");
    }

    $safe_dir = realpath("$user_dir/$dir");
    if (strpos($safe_dir, realpath($user_dir)) === 0) {
        $current_dir = $safe_dir;
    }
}

/*
4) Directory Creation
Ensures the user's root directory (user_dir) exists
*/
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0770, true);
    chown($user_dir, 'apache');
}

/*
5)File Upload Handling
Uses basename() to clean the file name before validation. This 
reduces the risk of invalid file paths being passed to the preg_match check.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file_name = basename($_FILES['file']['name']);
    

#file name is valid
if (!ctype_alnum($username) || !preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $file_name)) {
    die("Bad username/filename");
}
#creates file in current directory
    $target_path = "$current_dir/$file_name";

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        header('Location: dashboard.php?dir=' . urlencode(str_replace($user_dir . '/', '', $current_dir)));
    } else {
        echo "File upload failed.";
    }
}
?>
    
