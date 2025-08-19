<?php

// change_background.php

// Function to get a random helpdesk-related image URL
function getRandomImageUrl() {
    $images = [
        "https://images.unsplash.com/photo-1517245386804-bb52101b0e17?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1587620962725-abab7fe55159?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1542744166-e359e93a70c7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1519389950473-47ba0277781c?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1581093455648-2b35e3927d2e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1542744166-e359e93a70c7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
        "https://images.unsplash.com/photo-1542744166-e359e93a70c7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG00by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80",
    ];
    return $images[array_rand($images)];
}

// Read the content of helpdesk_front.php
$filePath = __DIR__ . '/helpdesk_front.php'; // Get the full path to helpdesk_front.php
$fileContent = file_get_contents($filePath);

// Generate a new random image URL
$newImageUrl = getRandomImageUrl();

// Replace the old image URL with the new one
$fileContent = preg_replace("/\\\$backgroundImageUrl = '[^']+';/", "\\\$backgroundImageUrl = '$newImageUrl';", $fileContent);

// Write the modified content back to helpdesk_front.php
file_put_contents($filePath, $fileContent);

// Output a success message (optional)
echo "Background image updated successfully.";

?>