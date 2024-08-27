<?php
try {
    $dbFile = 'weapons.db';
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection error.");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS weapons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    level INTEGER,
    quality TEXT,
    class TEXT,
    subclass TEXT,
    slot TEXT,
    link TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS gems (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gem_id TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    quality TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS builds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    creator TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    csv_data TEXT
)");

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
    global $slotNames;
    $rows = customParseCSV($csvData);
    array_shift($rows);

    return array_map(function($row) use ($slotNames) {
        if (count($row) < 6) {
            return null;
        }

        return [
            'slot' => $slotNames[$row[0]] ?? 'Unknown Slot',
            'item_id' => $row[1],
            'name' => trim($row[2], '"'),
            'subtype' => $row[3] ?? 'NONE',
            'enchant_id' => $row[4] ?? 'NONE',
            'gems' => $row[5] ?? 'NONE',
        ];
    }, $rows);
}

function fetchGemData($gemId, $pdo) {
    $url = "https://db.ascension.gg/?item=$gemId&xml";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $xml = simplexml_load_string($response);
        if ($xml && isset($xml->item)) {
            $item = $xml->item;

            $gemData = [
                'gem_id' => (string)$item->attributes()->id,
                'name' => (string)$item->name,
                'quality' => (string)$item->quality,
            ];

            $stmt = $pdo->prepare("SELECT * FROM gems WHERE gem_id = :gem_id");
            $stmt->execute(['gem_id' => $gemData['gem_id']]);
            $existingGem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingGem) {
                $stmt = $pdo->prepare("INSERT INTO gems (gem_id, name, quality) VALUES (:gem_id, :name, :quality)");
                $stmt->execute($gemData);
            }
        }
    }
}

function fetchWeaponData($itemIds, $names, $slots, $gems, $pdo) {
    $weapons = [];
    foreach ($itemIds as $index => $itemId) {
        $stmt = $pdo->prepare("SELECT * FROM weapons WHERE item_id = :item_id");
        $stmt->execute(['item_id' => $itemId]);
        $weapon = $stmt->fetch(PDO::FETCH_ASSOC);

        $gemIds = array_unique(array_map('trim', explode(';', $gems[$index])));
        foreach ($gemIds as $gemId) {
            if (!empty($gemId) && $gemId !== 'NONE') {
                fetchGemData($gemId, $pdo);
            }
        }

        if ($weapon) {
            $weapons[] = $weapon;
        } else {
            $url = "https://db.ascension.gg/?item=$itemId&xml";
            $response = @file_get_contents($url);

            if ($response !== false) {
                $xml = simplexml_load_string($response);
                if ($xml && isset($xml->item)) {
                    $item = $xml->item;

                    $weaponData = [
                        'item_id' => (string)$item->attributes()->id,
                        'name' => trim((string)$item->name, '"'),
                        'level' => (string)$item->level,
                        'quality' => (string)$item->quality,
                        'class' => (string)$item->class,
                        'subclass' => (string)$item->subclass,
                        'slot' => $slots[$index],
                        'link' => (string)$item->link,
                    ];

                    $stmt = $pdo->prepare("INSERT INTO weapons (item_id, name, level, quality, class, subclass, slot, link) VALUES (:item_id, :name, :level, :quality, :class, :subclass, :slot, :link)");
                    $stmt->execute($weaponData);
                    $weapons[] = $weaponData;
                } else {
                    $weaponData = [
                        'item_id' => $itemId,
                        'name' => $names[$index],
                        'level' => 'N/A',
                        'quality' => 'N/A',
                        'class' => 'N/A',
                        'subclass' => $slots[$index],
                        'slot' => $slots[$index],
                        'link' => "#",
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO weapons (item_id, name, level, quality, class, subclass, slot, link) VALUES (:item_id, :name, :level, :quality, :class, :subclass, :slot, :link)");
                    $stmt->execute($weaponData);
                    $weapons[] = $weaponData;
                }
            } else {
                $weaponData = [
                    'item_id' => $itemId,
                    'name' => $names[$index],
                    'level' => 'N/A',
                    'quality' => 'N/A',
                    'class' => 'N/A',
                    'subclass' => $slots[$index],
                    'slot' => $slots[$index],
                    'link' => "#",
                ];
                
                $stmt = $pdo->prepare("INSERT INTO weapons (item_id, name, level, quality, class, subclass, slot, link) VALUES (:item_id, :name, :level, :quality, :class, :subclass, :slot, :link)");
                $stmt->execute($weaponData);
                $weapons[] = $weaponData;
            }
        }
    }
    return $weapons;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csvData = filter_input(INPUT_POST, 'csvData', FILTER_SANITIZE_STRING);
    $buildName = filter_input(INPUT_POST, 'buildName', FILTER_SANITIZE_STRING);
    $creatorName = filter_input(INPUT_POST, 'creatorName', FILTER_SANITIZE_STRING);

    $stmt = $pdo->prepare("INSERT INTO builds (name, creator, csv_data) VALUES (:name, :creator, :csv_data)");
    $stmt->execute(['name' => $buildName, 'creator' => $creatorName, 'csv_data' => $csvData]);

    $buildId = $pdo->lastInsertId();

    $items = parseCSV($csvData);

    $itemIds = array_column($items, 'item_id');
    $names = array_column($items, 'name');
    $slots = array_column($items, 'slot');
    $gems = array_column($items, 'gems');
    $weapons = fetchWeaponData($itemIds, $names, $slots, $gems, $pdo);

    $shareableLink = "http://viewer.raith.one/view_build.php?id=$buildId";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gear Share</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h1>Gear Share</h1>
    <form method="post">
        <div class="form-group">
            <label for="buildName">Build Name:</label>
            <input type="text" id="buildName" name="buildName" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="creatorName">Creator:</label>
            <input type="text" id="creatorName" name="creatorName" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="csvData">Paste Item Data (CSV format):</label>
            <textarea id="csvData" name="csvData" rows="10" class="form-control" required></textarea>
        </div>

        <input type="submit" value="Save Build" class="btn btn-primary">
    </form>

    <?php if (isset($weapons)): 
        header("Location: view_build.php?id=$buildId");
        ?>
        
        <h2 class="mt-4">Weapon Data</h2>
        <?php if (count($weapons) > 0): ?>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Slot</th>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Sub Type</th>
                        <th>Gems</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weapons as $weapon): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($weapon['slot']); ?></td>
                            <td><?php echo htmlspecialchars($weapon['item_id']); ?></td>
                            <td><?php echo htmlspecialchars($weapon['name']); ?></td>
                            <td><?php echo htmlspecialchars($weapon['subclass']); ?></td>
                            <td>
                                <?php
                                $gemIds = array_unique(array_map('trim', explode(';', $weapon['gems'])));
                                $gemNames = [];
                                foreach ($gemIds as $gemId) {
                                    if (!empty($gemId)) {
                                        $stmt = $pdo->prepare("SELECT name FROM gems WHERE gem_id = :gem_id");
                                        $stmt->execute(['gem_id' => $gemId]);
                                        $gem = $stmt->fetch(PDO::FETCH_ASSOC);
                                        if ($gem) {
                                            $gemNames[] = htmlspecialchars($gem['name']);
                                        }
                                    }
                                }
                                echo implode(', ', $gemNames);
                                ?>
                            </td>
                            <td><a href="<?php echo htmlspecialchars($weapon['link']); ?>" target="_blank">View Item</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h3>Share Your Build</h3>
            <p>Share this link: <a href="<?php echo htmlspecialchars($shareableLink); ?>"><?php echo htmlspecialchars($shareableLink); ?></a></p>
        <?php else: ?>
            <p>No weapons found for the provided Item IDs.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
