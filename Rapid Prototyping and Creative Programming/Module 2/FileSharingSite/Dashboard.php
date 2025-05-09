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
            $error = "Failed to create folder. Check permissions.";
        }
    } else {
        $error = "Folder already exists.";
    }
} else {
    $error = "Invalid folder name.";
}

#list files and directories
$items = array_diff(scandir($current_dir), ['.', '..']);
?>

<!DOCTYPE html>
<html lang = "en"> 
<head> 
<title>User login</title>
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
</head>
<body>
<div class="container">
    <div class="nav">
        <!-- <div class="nav-options">
            <input type="text" placeholder="Enter file name" </a>
            <button tyle = "submit" name ="submit" value="submit">Search</button>
        </div> -->
        <!-- <div class="nav-options"> -->
        <!-- <div class="dropdown">
            <a class="active" href="#home">Profile<i class="fas fa-chevron-down arrow"></i></a>
            <button class="dropbtn">Dropdown</button>
            <div class="dropdown-links">
                <a href="login.html">Log out</a>
                <a href="#">Dropbox<i class="fas fa-link"></i></a>
                <a href="#">OneDrive<i class="fas fa-link"></i></a>
            </div>
        </div>
        </div> -->
        <div class="nav-options">
            <a href="logout.php">Log out</a>
        </div>
    </div>
    <div class="page">
        <p1>All files</p1></i>
            <!-- <a class="active" href="#home">Profile<i class="fas fa-chevron-down arrow"></i></a> -->
        <div class="file-select">
        <div class="dropdown">
            <a class="active" href="#home">Create New <i class="fas fa-plus-circle"></i></a>
            <!-- <button class="dropbtn">Dropdown</button> -->
            <div class="dropdown-links">
                <form action="dashboard.php" method="POST">
                    <input type="text" name="new_dir" placeholder="New folder name" required>
                    <button type="submit">Create Folder</button>
                </form>
                <!-- this will use the upload.php file to upload a file -->
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="file" required>
                    <button type="submit">Upload File</button>
                </form>
            </div>
        </div>
        </div>
            <!-- <button class="dropbtn">Dropdown</button>
        <div class="dropdown-links">
            <a href="login.html">Log out</a>
            <a href="#">Dropbox<i class="fas fa-link"></i></a>
            <a href="#">OneDrive<i class="fas fa-link"></i></a>
        </div>
    </div> -->

        <p>DOCUMENT NAME</p>
        <form action="/action_page.php">
            <input type="file" id="myFile" name="filename">
            <button tyle = "submit" name ="submit" value="submit">Upload file to...</button>
            <button tyle = "submit" name ="submit" value="submit">Download</button>
            <button tyle = "submit" name ="submit" value="submit">Delete file</button>
          </form>
          <!-- It might make sense to do a way to dynamically list all of a users files like this: -->
           <h2>All Files</h2>
           <ul>
                <?php foreach ($items as $item): ?>
                    <?php $item_path = "$current_dir/$item"; ?>
                    <li>
                        <?php if (is_dir($item_path)): ?>
                        <!-- use this to navigate directories -->
                         <!-- https://www.php.net/manual/en/function.urlencode.php -->
                        <a href="dashboard.php?dir=<?php echo urlencode(str_replace($user_dir . '/', '', $item_path)); ?>">
                            [Dir] <?php echo htmlspecialchars($item); ?>
                        </a>
                    <?php else: ?>
                        <!-- https://www.php.net/manual/en/function.htmlspecialchars.php to read on htmlspecialchars-->
                        <?php echo htmlspecialchars($item); ?>
                        <a href="dashboard.php?download=<?php echo urlencode($item); ?>">Download</a>
                        <form action="dashboard.php?dir=<?php echo urlencode(str_replace($user_dir . '/', '', $current_dir)); ?>" method="POST" style="display: inline;">
                            <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($item); ?>">
                            <button type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</body>
</html>

