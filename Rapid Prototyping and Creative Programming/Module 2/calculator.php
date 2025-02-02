<!-- calculator.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Calculator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .result {
            margin-top: 1rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>PHP Calculator</h1>
    <form action="<?= $_SERVER['PHP_SELF']?>" method="GET">
        <!-- First Number-->
        <div class="form-group">
            <label for="num1">First Number:</label>
            <input type="number" name="num1" id="num1" required>
        </div>
        <!-- Second Number-->
        <div class="form-group">
            <label for="num2">Second Number:</label>
            <input type="number" name="num2" id="num2" required>
        </div>
        <!-- Operation -->
        <div class="form-group">
            <label>Operation:</label>
            <div>
                <label>
                    <input type="radio" name="operation" value="add" required> Add
                </label>
                <label>
                    <input type="radio" name="operation" value="subtract"> Subtract
                </label>
                <label>
                    <input type="radio" name="operation" value="multiply"> Multiply
                </label>
                <label>
                    <input type="radio" name="operation" value="divide"> Divide
                </label>
            </div>
        </div>
        <button type="submit">Calculate</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['num1'], $_GET['num2'], $_GET['operation'])) {
        $num1 = (float)$_GET['num1'];
        $num2 = (float)$_GET['num2'];
        $operation = $_GET['operation'];
        $result = null;

        switch ($operation) {
            case 'add':
                $result = $num1 + $num2;
                break;
            case 'subtract':
                $result = $num1 - $num2;
                break;
            case 'multiply':
                $result = $num1 * $num2;
                break;
            case 'divide':
                if ($num2 != 0) {
                    $result = $num1 / $num2;
                } else {
                    $result = "Error: Division by zero.";
                }
                break;
            default:
                $result = "Invalid operation.";
        }

        echo '<div class="result">Result: ' . htmlspecialchars($result) . '</div>';
    }
    ?>
</body>
</html>
