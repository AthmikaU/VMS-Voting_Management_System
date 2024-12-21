CREATE TABLE IF NOT EXISTS votes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id INT NOT NULL,
    candidate_id INT NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES voters(voter_id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id),
    UNIQUE (voter_id) -- Ensures each voter can only vote once.
);




UPDATE candidates
SET votes_received = votes_received + 1
WHERE candidate_id = ?;

-- if ($hasVoted) {
--     echo '<button class="btn btn-secondary" disabled>Vote</button>';
-- } else {
--     echo '<button class="btn btn-success">Vote</button>';
-- }
