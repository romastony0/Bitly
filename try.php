<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;

// Hardcoded Bitly Access Token
$accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bitly";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Function to shorten URL using Bitly API
function shortenUrl($longUrl, $accessToken)
{
    $client = new Client();
    $response = $client->post('https://api-ssl.bitly.com/v4/shorten', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'long_url' => $longUrl,
        ],
    ]);

    $data = json_decode($response->getBody(), true);

    return $data['link']; // Returns the shortened URL
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle form submission
    if (isset($_FILES['inputFile'])) {
        $inputFile = $_FILES['inputFile']['tmp_name'];

        // Process the Excel file
        processExcel($inputFile, $accessToken, $conn);
    } else {
        echo "No file uploaded.";
    }
}

// Function to process Excel file
function processExcel($inputFile, $accessToken, $conn)
{
    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($inputFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Get the highest row number
        $highestRow = $sheet->getHighestRow();

        // Extract file name to set operator and process date
        $fileName = basename($_FILES["inputFile"]["name"]);
        $operator = "batelco";
        $processDate = date("Y-m-d");

        // Iterate through each row
        // Iterate through each row
        for ($row = 2; $row <= $highestRow; $row++) {
            $title = $sheet->getCell('A' . $row)->getValue(); // Get the title from column A
            $content = $sheet->getCell('B' . $row)->getValue(); // Get the content from column B

            // Extract URLs from content
            preg_match_all('/(https?:\/\/[^\s]+)/', $content, $matches);

            foreach ($matches[0] as $url) {
                $shortenedUrl = shortenUrl($url, $accessToken); // Shorten URL using Bitly API

                // Replace the original URL with the shortened URL in the content
                $content = str_replace($url, $shortenedUrl, $content);

                // Insert data into the database
                $stmt = $conn->prepare("INSERT INTO bitly_details (operator, process_date, title, long_url, shorten_url) VALUES (:operator, :process_date, :title, :long_url, :shorten_url)");
                $stmt->bindParam(':operator', $operator);
                $stmt->bindParam(':process_date', $processDate);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':long_url', $url);
                $stmt->bindParam(':shorten_url', $shortenedUrl);
                $stmt->execute();
            }

            // Update the content in the Excel sheet
            $sheet->setCellValue('B' . $row, $content);
        }


        // Offer the file for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="output.xlsx"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        echo "Error processing the Excel file: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel URL Shortener</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Add custom styles here if needed */
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Excel URL Shortener</h2>
                    </div>
                    <div class="card-body">
                        <form id="excelForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" class="form-control" id="inputFile" name="inputFile" accept=".xlsx,.xls" required aria-label="Upload">
                                <button class="btn btn-outline-secondary" type="submit" name="submit" id="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>