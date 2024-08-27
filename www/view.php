<?php
// Database connection
try {
    $dbFile = 'weapons.db';
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection error.");
}

// Fetch data from weapons table
$weaponsStmt = $pdo->query("SELECT * FROM weapons");
$weapons = $weaponsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch data from gems table
$gemsStmt = $pdo->query("SELECT * FROM gems");
$gems = $gemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch data from builds table
$buildsStmt = $pdo->query("SELECT * FROM builds");
$builds = $buildsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Database Data</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h1>Database Data Overview</h1>
    
    <h2>Weapons</h2>
    <?php if (count($weapons) > 0): ?>
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Item ID</th>
                    <th>Name</th>
                    <th>Level</th>
                    <th>Quality</th>
                    <th>Class</th>
                    <th>Subclass</th>
                    <th>Slot</th>
                    <th>Gems</th>
                    <th>Link</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weapons as $weapon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($weapon['id']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['item_id']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['name']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['level']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['quality']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['class']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['subclass']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['slot']); ?></td>
                        <td><?php echo htmlspecialchars($weapon['gems']); ?></td>
                        <td><a href="<?php echo htmlspecialchars($weapon['link']); ?>" target="_blank">View Item</a></td>
                        <td><?php echo htmlspecialchars($weapon['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No weapons found in the database.</p>
    <?php endif; ?>

    <h2>Gems</h2>
    <?php if (count($gems) > 0): ?>
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Gem ID</th>
                    <th>Name</th>
                    <th>Quality</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gems as $gem): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($gem['id']); ?></td>
                        <td><?php echo htmlspecialchars($gem['gem_id']); ?></td>
                        <td><?php echo htmlspecialchars($gem['name']); ?></td>
                        <td><?php echo htmlspecialchars($gem['quality']); ?></td>
                        <td><?php echo htmlspecialchars($gem['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No gems found in the database.</p>
    <?php endif; ?>

    <h2>Builds</h2>
    <?php if (count($builds) > 0): ?>
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Creator</th>
                    <th>Created At</th>
                    <th>CSV Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($builds as $build): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($build['id']); ?></td>
                        <td><?php echo htmlspecialchars($build['name']); ?></td>
                        <td><?php echo htmlspecialchars($build['creator']); ?></td>
                        <td><?php echo htmlspecialchars($build['created_at']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($build['csv_data'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No builds found in the database.</p>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
