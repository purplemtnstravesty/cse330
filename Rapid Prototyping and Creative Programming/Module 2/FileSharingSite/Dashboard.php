<?php

#checks if a user is logged in; if not, redirects to login page
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

#user gets directory under /var/www/user_files/
$username = $_SESSION['username'];
$user_dir = "/var/www/user_files/$username";
$current_dir = $user_dir;

#handles navigation between directories and uses realpath as security measure
if (isset($_GET['dir'])) {
    $safe_dir = realpath("$user_dir/" . $_GET['dir']);
    if (strpos($safe_dir, realpath($user_dir)) === 0) {
        $current_dir = $safe_dir;
    }
}

#check if directory exists
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0770, true);
    chown($user_dir, 'apache');
}

#secure file download handling
if (isset($_GET['download'])) {
    $file = basename($_GET['download']); // basename() prevents directory traversal
    $file_path = realpath("$current_dir/$file");

    //Security check if the file is inside user's directory
    if ($file_path !== false && strpos($file_path, realpath($user_dir)) === 0 && file_exists($file_path)) {
        //set headers for file download
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($file_path) . "\"");
        readfile($file_path);
        exit();
    } else {
        die("Unauthorized access.");
    }
}

#file and directory deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $delete_target = basename($_POST['delete_file']);
    $target_path = "$current_dir/$delete_target";

    if (file_exists($target_path)) {
        /*added a fix to delete non-empty directories 
        recursively delete all files and subdirectories before 
        removing the directory itself.*/
        function deleteDirectory($dir) { 
            if (!is_dir($dir)) return;
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = "$dir/$file";
                is_dir($filePath) ? deleteDirectory($filePath) : unlink($filePath);
            }
            return rmdir($dir);
        }
        if (is_dir($target_path)) {
            if (rmdir($target_path)) {
                $message = "Directory deleted.";
            } else {
                $error = "Directory is not empty or cannot be deleted.";
            }
        } else {
            unlink($target_path);
            $message = "File deleted.";
        }
    } else {
        $error = "File/Directory not found.";
    }
}

#create new directory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_dir'])) {
    $new_dir = basename($_POST['new_dir']);
    if (preg_match('/^[a-zA-Z0-9 _-]+$/', $new_dir)) {
        $new_dir_path = "$current_dir/$new_dir";
        if (!is_dir($new_dir_path)) {
            mkdir($new_dir_path, 0770);
            chown($new_dir_path, 'apache');
            $message = "Directory created successfully.";
        } else {
            $error = "Failed to create directory. Check permissions.";
        }
    } else {
        $error = "Directory already exists.";
    }
} else {
    $error = "Invalid directory name.";
}

#list files and directories
$items = array_diff(scandir($current_dir), ['.', '..']);
?>