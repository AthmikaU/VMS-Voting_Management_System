<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "password"; 
$dbname = "voting_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'];
    $constituency_id = $_POST['constituency'];
    $experience = $_POST['experience'];

    // Update the candidate's constituency in the candidates table
    $update_candidate_query = "UPDATE candidates SET constituency_id = ?, experience = ? WHERE candidate_id = ?";
    $stmt = $conn->prepare($update_candidate_query);
    $stmt->bind_param("sss", $constituency_id, $experience, $candidate_id);
    $stmt->execute();

    // Redirect to party dashboard after update
    header("Location: party.php");
    exit();
}
?>
