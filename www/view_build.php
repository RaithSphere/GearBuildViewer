<?php
try {
    $dbFile = 'weapons.db';
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection error.");
}

// Get the build ID from the URL
$buildId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

if (!$buildId) {
    exit("Invalid build ID.");
}

// Fetch the build details from the database
$stmt = $pdo->prepare("SELECT * FROM builds WHERE id = :id");
$stmt->execute(['id' => $buildId]);
$build = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$build) {
    exit("Build not found. $buildId ");
}

$slotNames = [
    "1" => "Head",
    "2" => "Neck",
    "3" => "Shoulder",
    "4" => "Shirt",
    "5" => "Chest",
    "6" => "Waist",
    "7" => "Legs",
    "8" => "Feet",
    "9" => "Wrist",
    "10" => "Hands",
    "11" => "Finger1",
    "12" => "Finger2",
    "13" => "Trinket1",
    "14" => "Trinket2",
    "15" => "Main Hand",
    "16" => "Off Hand",
    "17" => "Ranged",
];

function customParseCSV($csvData) {
    $rows = [];
    $lines = explode("\n", trim($csvData));

    foreach ($lines as $line) {
        preg_match_all('/("([^"]|"")*"|[^,]+)/', $line, $matches);
        $rows[] = array_map(function($field) {
            return trim(trim($field, '"'), ' ');
        }, $matches[0]);
    }

    return $rows;
}

function parseCSV($csvData) {
    $rows = customParseCSV($csvData);
    array_shift($rows); // Remove header row

    return array_map(function($row) {
        return [
            'slot' => $row[0] ?? 'Unknown Slot',
            'item_id' => $row[1] ?? 'N/A',
            'name' => trim($row[2], '"'),
            'subtype' => $row[3] ?? 'NONE',
            'itemdiff' => $row[4] ?? 'NONE',
            'gems' => !empty($row[5]) ? array_map('trim', explode(';', $row[5])) : [],
        ];
    }, $rows);
}

function fetchGemDetails($gemIds, $pdo) {
    $gemDetails = [];
    $uniqueGemIds = array_unique($gemIds);
    $gemCounts = array_count_values($gemIds);

    if (!empty($uniqueGemIds)) {
        $placeholders = implode(',', array_fill(0, count($uniqueGemIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM gems WHERE gem_id IN ($placeholders)");

        try {
            $stmt->execute($uniqueGemIds);
            $fetchedGemDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($fetchedGemDetails as $gem) {
                $gemDetails[$gem['gem_id']] = $gem;
            }
        } catch (PDOException $e) {
            error_log("Error executing query: " . $e->getMessage());
            throw $e;
        }
    }

    foreach ($gemDetails as &$gem) {
        $gemId = $gem['gem_id'];
        $gem['count'] = $gemCounts[$gemId];
    }

    return $gemDetails;
}

// Parse the CSV data from the build
$items = parseCSV($build['csv_data']);

$weapons = [];
foreach ($items as $item) {
    $gems = $item['gems'];
    $stmt = $pdo->prepare("SELECT link FROM weapons WHERE item_id = :item_id");
    $stmt->execute(['item_id' => $item['item_id']]);
    $weaponData = $stmt->fetch(PDO::FETCH_ASSOC);

    $weaponLink = $weaponData['link'] ?? '';
    $gemDetails = fetchGemDetails($gems, $pdo);

    $weapons[] = [
        'slot' => $item['slot'],
        'item_id' => $item['item_id'],
        'name' => $item['name'],
        'link' => $weaponLink,
        'gems' => $gemDetails,
        'itemdiff' => $item['itemdiff'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Build - <?php echo htmlspecialchars($build['name']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #1c1c1c; /* Dark background */
            color: #e0e0e0; /* Light text */
            font-family: 'Arial', sans-serif; /* Use a clean font */
        }
        h1, h2, h3 {
            color: #ffcc00; /* Gold color for headings */
        }
        table {
            background-color: #333; /* Dark table background */
            border: 1px solid #444; /* Table border */
        }
        th {
            background-color: #444; /* Darker header */
            color: #ffcc00; /* Gold color for header text */
        }
        td {
            border: 1px solid #555; /* Cell border */
            color: #e0e0e0; /* Light color for table text */
        }
        a {
            color: #00ccff; /* Bright blue for links */
            text-decoration: none; /* No underline */
        }
        a:hover {
            text-decoration: underline; /* Underline on hover */
        }
        .btn {
            background-color: #ffcc00; /* Gold button */
            color: #1c1c1c; /* Dark text */
        }
        .btn:hover {
            background-color: #e0e0e0; /* Light on hover */
            color: #1c1c1c; /* Dark text */
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h1>Build: <?php echo htmlspecialchars($build['name']); ?></h1>
    <h2>Created by: <?php echo htmlspecialchars($build['creator']); ?></h2>

    <h3>Gear and Gems</h3>
    <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Slot</th>
                <th>Item ID</th>
                <th>Name</th>
                <th>Link</th>
                <th>Gems</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weapons as $weapon): ?>
                <tr>
                    <td><?php echo htmlspecialchars($slotNames[$weapon['slot']] ?? 'Unknown Slot'); ?></td>
                    <td><?php echo htmlspecialchars($weapon['item_id']); ?></td>
                    <td>
                        <?php 
                            echo htmlspecialchars(str_replace('"', '', html_entity_decode($weapon['name']))); ?><br> 
                        <span style="color: lime;">
                            <?php 
                                if ($weapon['itemdiff'] != 'Normal') {
                                    echo htmlspecialchars($weapon['itemdiff'] === 'AR' ? 'Ascended Raid' : $weapon['itemdiff']); 
                                }
                            ?>
                        </span>
                    </td>                    
                    <td>
                    <?php if (!empty($weapon['link']) && $weapon['link'] !== '#'): ?>
                        <a href="<?php echo htmlspecialchars($weapon['link']); ?>" target="_blank">View Item</a>
                    <?php else: ?>
                        <a href="https://db.ascension.gg/?search=<?php echo urlencode(htmlspecialchars($weapon['name'])); ?>">Search DB</a>
                    <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($weapon['gems'])) {
                            $gemOutput = [];
                            foreach ($weapon['gems'] as $gem) {
                                $gemOutput[] = htmlspecialchars($gem['name']) . " (Count: " . htmlspecialchars($gem['count']) . ")";
                            }
                            echo implode('<br>', $gemOutput);
                        } else {
                            echo 'None';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="index.php" class="btn btn-secondary">Back to Home</a>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
