<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


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
# add ability to handle bulk deletion
// FIX: Ensure the deleteDirectory function is only declared once
if (!function_exists('deleteDirectory')) {
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return false;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dir/$file";
            if (is_dir($filePath)) {
                deleteDirectory($filePath);
            } else {
                if (!unlink($filePath)) {
                    echo "<p style='color: red;'>Failed to delete file: $filePath</p>";
                }
            }
        }
        return rmdir($dir);
    }
}

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (!empty($_POST['delete_items']) && is_array($_POST['delete_items'])) {
        // process each selected item
        foreach ($_POST['delete_items'] as $delete_target) {
            //sanitize with basename() and validate path
            $delete_target = basename($delete_target);
            $target_path = "$current_dir/$delete_target";
            // check if file or directory exists
            if (file_exists($target_path)) {
                //delete directory or file
                if (is_dir($target_path)) {
                    if (!deleteDirectory($target_path)) {
                        echo "<p style='color: red;'>Failed to delete directory: $delete_target</p>";
                    }
                } else {
                    if (!unlink($target_path)) {
                        echo "<p style='color: red;'>Failed to delete file: $delete_target</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>File/Folder not found: $delete_target</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>No files selected for deletion.</p>";
    }
    // redirect after the loop
    header("Location: Dashboard.php?dir=" . urlencode(str_replace($user_dir . '/', '', $current_dir)));
    exit();
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
<html lang="en">
<head> 
    <title>Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <div class="nav">
        <h2>File Manager</h2>
        <a href="Logout.php">Log out</a>
    </div>

    <h3>Current Directory: <?php echo htmlspecialchars(str_replace($user_dir, "", $current_dir)); ?></h3>
    
    <!-- Navigation (Go Back) -->
    <?php if ($current_dir !== $user_dir): ?>
        <a href="Dashboard.php?dir=<?php echo urlencode(dirname(str_replace($user_dir . '/', '', $current_dir))); ?>">
            <button>⬅ Go Back</button>
        </a>
    <?php endif; ?>

    <!-- Create Folder -->
    <form action="Dashboard.php?dir=<?php echo urlencode(str_replace($user_dir . '/', '', $current_dir)); ?>" method="POST">
        <input type="text" name="new_dir" placeholder="New folder name" required>
        <button type="submit">Create Folder</button>
    </form>

    <!-- File Upload -->
    <form action="Upload.php?dir=<?php echo urlencode(str_replace($user_dir . '/', '', $current_dir)); ?>" method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">⬆ Upload File</button>
    </form>

    <!-- Bulk Delete Form -->
    <form action="Dashboard.php" method="POST">
        <h3>All Files</h3>
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Type</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $item_path = "$current_dir/$item"; ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="delete_items[]" value="<?php echo htmlspecialchars($item); ?>">
                        </td>
                        <td>
                            <?php if (is_dir($item_path)): ?>
                                <i class="fas fa-folder folder-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-file file-icon"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (is_dir($item_path)): ?>
                                <a href="Dashboard.php?dir=<?php echo urlencode(str_replace($user_dir . '/', '', $item_path)); ?>">
                                    <?php echo htmlspecialchars($item); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($item); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="delete_selected">Delete Selected</button>
    </form>
</div>

</body>
</html>