<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MightyPHP | Exception</title>
    <style>
        .container {
            padding: 2rem;
        }

        .card {
            border: 1px grey solid;
            margin: 1rem;
            padding: 1rem;
            border-radius: 10px;
            display: flex;
        }

        .number {
            margin-right: 1rem;
        }

        p {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo $message; ?></h1>
    <?php
    $index = 0;
    foreach ($stacks as $stack) {
        $file = $stack['file'] ?? '';
        $line = $stack['line'] ?? '';
        $class = $stack['class'] ?? '';
        $function = $stack['function'] ?? '';
        $index++;
        echo
        "<div class='card'>
                <div class='number'>
                    <p># $index</p>
                </div>
                <div>
                    <p>File: $file</p>
                    <p>Line: $line</p>
                    <p>Class: $class</p>
                    <p>Function: $function</p>
                </div>
            </div>";
    }
    ?>
</div>
</body>
</html>