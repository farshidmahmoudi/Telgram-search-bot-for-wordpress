<?php
$botToken = "API TOKEN";
$apiUrl = "https://api.telegram.org/bot$botToken/";

// Connect to the database
$mysqli = new mysqli("localhost", "DB UserName", "DB Pass", "DB Name");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the update from Telegram
$update = json_decode(file_get_contents("php://input"), true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    if ($text == "/start") {
        sendMessage($chatId, "Hello, welcome, please type what you are looking for");
    } else {
        // Assume the search query is the text message received
        $searchQuery = "%" . $mysqli->real_escape_string($text) . "%";

        // Prepare and execute the SQL query
        $stmt = $mysqli->prepare("SELECT post_title, id FROM wp_posts WHERE post_title LIKE ? LIMIT 10");
        $stmt->bind_param("s", $searchQuery);
        $stmt->execute();
        $result = $stmt->get_result();

        // Create an array for inline keyboard buttons
        $keyboard = ["inline_keyboard" => []];

        while ($row = $result->fetch_assoc()) {
            $keyboard["inline_keyboard"][] = [
                ["text" => htmlspecialchars($row['post_title']), "url" => "http://yoursite.com/?p=" . $row['id']]
            ];
        }

        // Send the response back to the user
        sendInlineKeyboard($chatId, "نتیجه جستجو :", $keyboard);
    }
}

$mysqli->close();

function sendMessage($chatId, $message, $parseMode = null) {
    global $apiUrl;
    $url = $apiUrl . "sendMessage?chat_id=" . $chatId . "&text=" . urlencode($message);
    if ($parseMode) {
        $url .= "&parse_mode=" . $parseMode;
    }
    file_get_contents($url);
}

function sendInlineKeyboard($chatId, $text, $keyboard) {
    global $apiUrl;
    $postFields = [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_markup' => json_encode($keyboard)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "sendMessage");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
