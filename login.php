<?php
// Include database connection file
$servername = "localhost";
$username = "root";
$password = "password"; // Update this if needed
$dbname = "voting_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$role = $voter_id = $first_name = $last_name = $party_id = $constituency_id = $password = '';
$error = '';

// Start session
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];

    if ($role === 'voter') {
        $voter_id = $conn->real_escape_string($_POST['voter_id']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $password = $conn->real_escape_string($_POST['password']);

        if ($voter_id && $first_name && $last_name && $password) {
            $query = "SELECT * FROM voters WHERE voter_id = ? AND first_name = ? AND last_name = ? AND password = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $voter_id, $first_name, $last_name, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $_SESSION['voter_id'] = $voter_id;
                header("Location: voter_dashboard.php");
                exit;
            } else {
                $error = "Invalid Voter credentials.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    } elseif ($role === 'party') {
        $party_id = $conn->real_escape_string($_POST['party_id']);
        $password = $conn->real_escape_string($_POST['password']);

        if ($party_id && $password) {
            $query = "SELECT * FROM parties WHERE party_id = ? AND password = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $party_id, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $_SESSION['party_id'] = $party_id;
                header("Location: party.php");
                exit;
            } else {
                $error = "Invalid Party credentials.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    } elseif ($role === 'constituency') {
        $constituency_id = $conn->real_escape_string($_POST['constituency_id']);
        $password = $conn->real_escape_string($_POST['password']);

        if ($constituency_id && $password) {
            $query = "SELECT * FROM constituencies WHERE constituency_id = ? AND password = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $constituency_id, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $_SESSION['constituency_id'] = $constituency_id;
                header("Location: constituency_admin.php");
                exit;
            } else {
                $error = "Invalid Constituency credentials.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-card {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .login-card .nav-item {
            width: 33.33%;
            text-align: center;
        }
        .tab-content {
            margin-top: 20px;
        }
        .view-results-btn {
            margin-top: 30px; /* Spacing from login card */
            text-align: center;
        }
        .view-results-btn a {
            background-color: #e74c3c; /* Red color */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .view-results-btn a:hover {
            background-color: #c0392b; /* Darker red on hover */
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="login-card">
            <h1 class="text-center mb-4">Login</h1>

            <!-- Nav Tabs for Role Selection -->
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link active" id="voter-tab" href="#voter" data-bs-toggle="pill">Voter</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="party-tab" href="#party" data-bs-toggle="pill">Party</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="constituency-tab" href="#constituency" data-bs-toggle="pill">Constituency</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content mt-4">
                <!-- Voter Tab -->
                <div class="tab-pane fade show active" id="voter">
                    <form method="POST" action="">
                        <input type="hidden" name="role" value="voter">
                        <div class="mb-3">
                            <label for="voter_id" class="form-label">Voter ID</label>
                            <input type="text" class="form-control" id="voter_id" name="voter_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>

                <!-- Party Tab -->
                <div class="tab-pane fade" id="party">
                    <form method="POST" action="">
                        <input type="hidden" name="role" value="party">
                        <div class="mb-3">
                            <label for="party_id" class="form-label">Party ID</label>
                            <input type="text" class="form-control" id="party_id" name="party_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>

                <!-- Constituency Tab -->
                <div class="tab-pane fade" id="constituency">
                    <form method="POST" action="">
                        <input type="hidden" name="role" value="constituency">
                        <div class="mb-3">
                            <label for="constituency_id" class="form-label">Constituency ID</label>
                            <input type="text" class="form-control" id="constituency_id" name="constituency_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mt-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- View Results Button (separate, below the login card) -->
        <div class="view-results-btn">
            <a href="results.php" class="btn">View Results</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
