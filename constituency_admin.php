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

if (!isset($_SESSION['constituency_id'])) {
    header("Location: login.php");
    exit();
}

$constituency_id = $_SESSION['constituency_id'];

// Fetch constituency details
$query = "SELECT constituency_name FROM constituencies WHERE constituency_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $constituency_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $constituency = $result->fetch_assoc();
    $constituency_name = $constituency['constituency_name'];
} else {
    echo "Constituency not found.";
    exit();
}

// Fetch candidates for this constituency
$candidates_query = "SELECT candidates.candidate_id, voters.first_name, voters.last_name, parties.party_name, candidates.experience 
                     FROM candidates
                     JOIN voters ON candidates.voter_id = voters.voter_id
                     JOIN parties ON candidates.party_id = parties.party_id
                     WHERE candidates.constituency_id = ?";
$candidates_stmt = $conn->prepare($candidates_query);
$candidates_stmt->bind_param("s", $constituency_id);
$candidates_stmt->execute();
$candidates_result = $candidates_stmt->get_result();

// Fetch voters for this constituency
$voters_query = "SELECT voter_id, first_name, last_name FROM voters WHERE constituency_id = ?";
$voters_stmt = $conn->prepare($voters_query);
$voters_stmt->bind_param("s", $constituency_id);
$voters_stmt->execute();
$voters_result = $voters_stmt->get_result();

// Handle adding a candidate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_candidate'])) {
    $voter_id = $_POST['voter_id'];
    $party_id = $_POST['party_id'];
    $experience = $_POST['experience'];

    // Check if the voter is already a candidate in the same constituency
    $check_query = "SELECT * FROM candidates WHERE voter_id = ? AND constituency_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ss", $voter_id, $constituency_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<script>alert('This person is already a candidate in this constituency!'); window.location.href='constituency_admin.php';</script>";
    } else {
        // Check if there is already a candidate from the same party in the constituency
        $party_check_query = "SELECT * FROM candidates WHERE party_id = ? AND constituency_id = ?";
        $party_check_stmt = $conn->prepare($party_check_query);
        $party_check_stmt->bind_param("ss", $party_id, $constituency_id);
        $party_check_stmt->execute();
        $party_check_result = $party_check_stmt->get_result();

        if ($party_check_result->num_rows > 0) {
            echo "<script>alert('This party already has a candidate in this constituency!'); window.location.href='constituency_admin.php';</script>";
        } else {
            // Find the highest candidate ID and increment it
            $id_query = "SELECT MAX(candidate_id) AS max_id FROM candidates";
            $id_result = $conn->query($id_query);
            $new_candidate_id = $id_result->fetch_assoc()['max_id'] + 1;

            // Insert the new candidate
            $insert_query = "INSERT INTO candidates (candidate_id, voter_id, party_id, constituency_id, experience) 
                             VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("issss", $new_candidate_id, $voter_id, $party_id, $constituency_id, $experience);
            $stmt->execute();

            echo "<script>alert('Candidate added successfully.'); window.location.href='constituency_admin.php';</script>";
        }
    }
}

// Handle deleting a candidate
if (isset($_GET['delete_candidate_id'])) {
    $candidate_id = $_GET['delete_candidate_id'];

    // Delete the candidate
    $delete_query = "DELETE FROM candidates WHERE candidate_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $candidate_id);
    $stmt->execute();

    echo "<script>alert('Candidate deleted successfully.'); window.location.href='constituency_admin.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constituency Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; }
        .navbar { margin-bottom: 2rem; }
        .card { margin-bottom: 1rem; }
        .table th, .table td { text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Election Conductor</a>
        <div class="ml-auto">
            <form action="logout.php" method="POST">
                <button type="submit" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center mb-4">Welcome to <?php echo htmlspecialchars($constituency_name); ?> Constituency Dashboard</h1>

        <div class="card">
            <div class="card-header"><h3>Constituency Information</h3></div>
            <div class="card-body">
                <p><strong>Constituency ID:</strong> <?php echo htmlspecialchars($constituency_id); ?></p>
                <p><strong>Constituency Name:</strong> <?php echo htmlspecialchars($constituency_name); ?></p>
            </div>
        </div>

        <h3>Candidates in Your Constituency</h3>
        <?php if ($candidates_result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Party</th>
                        <th>Experience</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($candidate = $candidates_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($candidate['candidate_id']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['first_name'] . " " . $candidate['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['party_name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['experience']); ?> years</td>
                            <td>
                                <a href="edit_candidate.php?candidate_id=<?php echo htmlspecialchars($candidate['candidate_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?delete_candidate_id=<?php echo htmlspecialchars($candidate['candidate_id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this candidate?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No candidates found.</div>
        <?php endif; ?>

        <button class="btn btn-primary mt-4" data-toggle="modal" data-target="#addCandidateModal">Add New Candidate</button>

        <!-- Add Candidate Modal -->
        <div class="modal fade" id="addCandidateModal">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <div class="modal-header">
                        <h5>Add Candidate</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="voter_id">Voter ID</label>
                            <input type="text" id="voter_id" name="voter_id" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="party_id">Party ID</label>
                            <input type="text" id="party_id" name="party_id" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="experience">Experience</label>
                            <input type="number" id="experience" name="experience" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_candidate" class="btn btn-success">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
