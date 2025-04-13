<?php

// Run the Python script and get the output
$output = shell_exec('python match_svm.py');

// Read JSON from the file
$json = file_get_contents('high_matches.json');

// Set response headers to indicate JSON output
header('Content-Type: application/json');

// Output the JSON
echo $json;

?>
