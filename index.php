<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bitly";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Function to shorten URL using Bitly API
function shortenUrl($longUrl, $title, $accessToken)
{
    $client = new Client();
    $response = $client->post('https://api-ssl.bitly.com/v4/bitlinks', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'long_url' => $longUrl,
            'title' => $title,
        ],
    ]);

    $data = json_decode($response->getBody(), true);

    return $data['link']; // Returns the shortened URL
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_FILES['inputFile']) && isset($_POST['operator'])) {
        $inputFile = $_FILES['inputFile']['tmp_name'];
        $selectedOperator = $_POST['operator'];


        switch ($selectedOperator) {
            case 'batelco':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'comviva':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'dipl':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'du':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'kenya':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'smartlink':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            case 'zain_bahrain':
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7';
                break;
            default:
                $accessToken = 'f4aa3c7254286519f41f5ceb957f85c4104e35f7'; // Default access token
                break;
        }


        processExcel($inputFile, $accessToken, $conn, $selectedOperator);
    } else {
        echo "Incomplete form data. Please select a file and an operator.";
    }
}

// Function to process Excel file
function processExcel($inputFile, $accessToken, $conn, $selectedOperator)
{
    try {

        $spreadsheet = IOFactory::load($inputFile);
        $sheet = $spreadsheet->getActiveSheet();


        $highestRow = $sheet->getHighestRow();


        $fileName = basename($_FILES["inputFile"]["name"]);
        $processDate = date("Y-m-d");


        for ($row = 2; $row <= $highestRow; $row++) {
            $title = $sheet->getCell('A' . $row)->getValue(); // Get the title from column A
            $content = $sheet->getCell('B' . $row)->getValue(); // Get the content from column B


            preg_match_all('/(https?:\/\/[^\s]+)/', $content, $matches);

            foreach ($matches[0] as $url) {
                // Append current date to the title
                $currentDate = date("Ymd");
                $titleWithDate = $title . '_' . $currentDate;
                $shortenedUrl = shortenUrl($url, $titleWithDate, $accessToken); // Shorten URL using Bitly API



                $content = str_replace($url, $shortenedUrl, $content);

                $stmt = $conn->prepare("INSERT INTO bitly_details (operator, process_date, title, long_url, shorten_url) VALUES (:operator, :process_date, :title, :long_url, :shorten_url)");
                $stmt->bindParam(':operator', $selectedOperator);
                $stmt->bindParam(':process_date', $processDate);
                $stmt->bindParam(':title', $titleWithDate);
                $stmt->bindParam(':long_url', $url);
                $stmt->bindParam(':shorten_url', $shortenedUrl);
                $stmt->execute();
            }



            $sheet->setCellValue('B' . $row, $content);
        }


        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '.xlsx"');
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
    <link rel="icon" type="image/x-icon" href="./icons8-url-50.png">
    <title>Bitly URL Shortener</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <style>
        /* Custom Styles */
        body {
            background-color: #E9724C;
            /* Light gray background */
        }

        .container {
            margin-top: 50px;
            /* Add some top margin */
        }

        .card {
            border: none;
            /* Remove default card border */
            border-radius: 15px;
            /* Add border-radius for rounded corners */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);


            /* Add shadow effect */
        }

        .card-header {
            background-color: #C5283D;
            /* Blue header background */
            color: #fff;
            /* White text */
            border-radius: 15px 15px 0 0;
            /* Rounded corners only on top */
        }

        .card-body {
            background-color: #FFC857;
            padding: 20px;
            border-radius: 0 0 15px 15px;
            /* Add padding */
        }

        .form-control,
        .btn {
            border-radius: 8px;
            /* Add border-radius to form elements */
        }

        @media (max-width: 768px) {

            /* Responsive styles for smaller screens */
            .container {
                margin-top: 20px;
                /* Reduce top margin */
            }

            .card {
                box-shadow: none;
                /* Remove shadow on smaller screens */
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title text-center mb-0">Bitly URL Shortener</h2>
                    </div>
                    <div class="card-body">
                        <form id="excelForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="inputFile" class="form-label">Upload Excel File (.xlsx, .xls)</label>
                                <input type="file" class="form-control" id="inputFile" name="inputFile" accept=".xlsx,.xls" required>
                            </div>
                            <div class="mb-3">
                                <label for="operator" class="form-label">Select Operator:</label>
                                <select class="form-select" id="operator" name="operator">
                                    <option value="batelco">Bahrain</option>
                                    <option value="comviva">Comviva</option>
                                    <option value="dipl">DIPL</option>
                                    <option value="du">DU</option>
                                    <option value="kenya">Kenya</option>
                                    <option value="smartlink">Smartlink</option>
                                    <option value="zain_bahrain">Zain-Bahrain</option>
                                </select>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" style="background-color: #481D24" type="submit" name="submit" id="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>
