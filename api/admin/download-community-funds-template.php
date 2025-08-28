<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="community-funds-template.csv"');
header('Access-Control-Allow-Origin: *');

// Create CSV template with headers and sample data
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['type', 'description', 'amount', 'date']);

// Add sample data rows
fputcsv($output, ['income', 'NFT Sale Revenue', '1000.00', '2025-01-28']);
fputcsv($output, ['expense', 'Community Prize Pool', '500.00', '2025-01-28']);
fputcsv($output, ['income', 'Donation from User', '250.00', '2025-01-28']);
fputcsv($output, ['expense', 'Server Hosting Costs', '150.00', '2025-01-28']);

fclose($output);
?>
