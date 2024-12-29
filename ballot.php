<?php
// ballot.php
session_start();
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "voting_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit();
}

$voter_id = $_SESSION['voter_id'];

// Fetch constituency of the voter
$query = "SELECT constituency_id, has_voted FROM voters WHERE voter_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();

if (!$voter) {
    echo "Voter not found.";
    exit();
}

$constituency_id = $voter['constituency_id'];
$hasVoted = $voter['has_voted'];

// Fetch candidates from the same constituency
$candidateQuery = "SELECT c.candidate_id, c.experience, c.votes_received, p.party_name, 
                          CONCAT(v.first_name, ' ', v.last_name) AS candidate_name
                   FROM candidates c
                   JOIN parties p ON c.party_id = p.party_id
                   JOIN voters v ON c.voter_id = v.voter_id
                   WHERE c.constituency_id = ?";

$stmt = $conn->prepare($candidateQuery);
$stmt->bind_param("i", $constituency_id);
$stmt->execute();
$candidateResult = $stmt->get_result();

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$hasVoted) {
    // Get the candidate_id from the form input
    $selected_candidate_id = $_POST['candidate_id'];

    // Mark the voter as having voted
    $markVotedQuery = "UPDATE voters SET has_voted = 1 WHERE voter_id = ?";
    $markVotedStmt = $conn->prepare($markVotedQuery);
    $markVotedStmt->bind_param("i", $voter_id);
    $markVotedStmt->execute();

    // Increment the vote count for the selected candidate
    $updateVotesQuery = "UPDATE candidates 
                         SET votes_received = votes_received + 1 
                         WHERE candidate_id = ?";
    $updateVotesStmt = $conn->prepare($updateVotesQuery);
    $updateVotesStmt->bind_param("i", $selected_candidate_id);
    $updateVotesStmt->execute();

    // Get the candidate's name to show in the success message
    $candidateNameQuery = "SELECT CONCAT(v.first_name, ' ', v.last_name) AS candidate_name
                           FROM candidates c
                           JOIN voters v ON c.voter_id = v.voter_id
                           WHERE c.candidate_id = ?";
    $candidateStmt = $conn->prepare($candidateNameQuery);
    $candidateStmt->bind_param("i", $selected_candidate_id);
    $candidateStmt->execute();
    $candidateResult = $candidateStmt->get_result();
    $candidate = $candidateResult->fetch_assoc();
    $candidate_name = $candidate['candidate_name'];

    header("Location: ballot.php?success=1&candidate_name=" . urlencode($candidate_name));
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ballot</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/ballot.css">
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="text-center">Ballot Paper </h1>
            <a href="ballot.php?logout=1" class="btn btn-logout">Logout</a>
        </div>

        <?php if (isset($_GET['success']) && isset($_GET['candidate_name'])): ?>
            <div class="alert alert-success">
                Voted candidate: <?php echo htmlspecialchars($_GET['candidate_name']); ?> successfully!
            </div>
        <?php endif; ?>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Candidate Name</th>
                    <th>Party</th>
                    <th> </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($candidate = $candidateResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                        <td><?php echo htmlspecialchars($candidate['party_name']); ?></td>
                        <td>
                            <?php if (!$hasVoted): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Vote</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-success" disabled>Voted</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
