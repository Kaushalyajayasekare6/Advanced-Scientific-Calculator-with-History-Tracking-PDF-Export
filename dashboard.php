<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require 'config/db.php';

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM history WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_POST['delete_id'], $user_id);
        $stmt->execute();
    } elseif (isset($_POST['update_id'])) {
        $stmt = $conn->prepare("UPDATE history SET expression = ?, result = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $_POST['expression'], $_POST['result'], $_POST['update_id'], $user_id);
        $stmt->execute();
    }
}

$history_query = $conn->prepare("SELECT * FROM history WHERE user_id = ? ORDER BY created_at DESC");
$history_query->bind_param("i", $user_id);
$history_query->execute();
$history_result = $history_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathGenius | Dashboard</title>
    <link rel="stylesheet" href="assets/css/dbord.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="user-panel panel">
            <div class="user-info">
                <div class="avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user-astronaut"></i>
                    </div>
                </div>
                <h2><?php echo htmlspecialchars($email); ?></h2>
                <div class="user-actions">
                    <button id="toggleMode" class="btn btn-theme">
                        <i class="fas fa-moon"></i> <span>Toggle Theme</span>
                    </button>
                    <a href="export_history.php" class="btn btn-export">
                        <i class="fas fa-file-export"></i> <span>Export CSV</span>
                    </a>
                    <a href="export_pdf.php" class="btn btn-pdf">
                        <i class="fas fa-file-pdf"></i> <span>Export PDF</span>
                    </a>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="calculator panel">
            <div class="calculator-header">
                <h3><i class="fas fa-calculator"></i> MathGenius Calculator</h3>
                <div class="calculator-mode">
                    <button class="mode-btn active" data-mode="basic">Basic</button>
                    <button class="mode-btn" data-mode="scientific">Scientific</button>
                </div>
            </div>
            <div class="calculator-display" id="display">
                <div class="calculation" id="calculation"></div>
                <div class="result" id="result">0</div>
            </div>
            <div class="calculator-buttons">
                <div class="button-group basic-buttons">
                    <button class="calc-btn operator" onclick="append('/')">/</button>
                    <button class="calc-btn operator" onclick="append('*')">×</button>
                    <button class="calc-btn operator" onclick="append('-')">-</button>
                    <button class="calc-btn operator" onclick="append('+')">+</button>
                    
                    <button class="calc-btn" onclick="append('7')">7</button>
                    <button class="calc-btn" onclick="append('8')">8</button>
                    <button class="calc-btn" onclick="append('9')">9</button>
                    <button class="calc-btn equals" onclick="calculate()">=</button>
                    
                    <button class="calc-btn" onclick="append('4')">4</button>
                    <button class="calc-btn" onclick="append('5')">5</button>
                    <button class="calc-btn" onclick="append('6')">6</button>
                    <button class="calc-btn clear" onclick="clearDisplay()">C</button>
                    
                    <button class="calc-btn" onclick="append('1')">1</button>
                    <button class="calc-btn" onclick="append('2')">2</button>
                    <button class="calc-btn" onclick="append('3')">3</button>
                    <button class="calc-btn backspace" onclick="backspace()">
                        <i class="fas fa-backspace"></i>
                    </button>
                    
                    <button class="calc-btn" onclick="append('0')">0</button>
                    <button class="calc-btn" onclick="append('.')">.</button>
                    <button class="calc-btn" onclick="append('(')">(</button>
                    <button class="calc-btn" onclick="append(')')">)</button>
                </div>
                
                <div class="button-group scientific-buttons" style="display: none;">
                    <button class="calc-btn scientific" onclick="scientificOperation('sqrt')">√</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('pow2')">x²</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('pow3')">x³</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('pow')">x^y</button>
                    
                    <button class="calc-btn scientific" onclick="scientificOperation('sin')">sin</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('cos')">cos</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('tan')">tan</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('log')">log</button>
                    
                    <button class="calc-btn scientific" onclick="scientificOperation('ln')">ln</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('pi')">π</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('e')">e</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('fact')">x!</button>
                    
                    <button class="calc-btn scientific" onclick="scientificOperation('percent')">%</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('exp')">EXP</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('mod')">mod</button>
                    <button class="calc-btn scientific" onclick="scientificOperation('rand')">RAND</button>
                </div>
            </div>
        </div>

        <div class="history-panel panel">
            <div class="history-header">
                <h3><i class="fas fa-history"></i> Calculation History</h3>
                <div class="history-actions">
                    <button onclick="clearAllHistory()" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <span>Clear All</span>
                    </button>
                </div>
            </div>
            <div class="history-list">
                <?php if ($history_result->num_rows > 0): ?>
                    <?php while ($row = $history_result->fetch_assoc()): ?>
                        <div class="history-item" data-id="<?php echo $row['id']; ?>">
                            <div class="history-content">
                                <span class="expression"><?php echo htmlspecialchars($row['expression']); ?></span>
                                <span class="result">= <?php echo htmlspecialchars($row['result']); ?></span>
                                <small class="date"><?php echo date('M j, H:i', strtotime($row['created_at'])); ?></small>
                            </div>
                            <div class="history-buttons">
                                <button class="btn-edit" onclick="editHistoryItem(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteHistoryItem(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn-use" onclick="useHistoryItem(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <div class="edit-form" style="display: none;">
                                <input type="text" class="edit-expression" value="<?php echo htmlspecialchars($row['expression']); ?>">
                                <input type="text" class="edit-result" value="<?php echo htmlspecialchars($row['result']); ?>">
                                <div class="edit-buttons">
                                    <button class="btn-save" onclick="saveHistoryItem(<?php echo $row['id']; ?>, this)">Save</button>
                                    <button class="btn-cancel" onclick="cancelEdit(this)">Cancel</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-history">
                        <i class="fas fa-calculator"></i>
                        <p>No calculation history yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/calculator.js"></script>
    <script>
        document.getElementById('toggleMode').addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('lightMode', isLight);
            const icon = document.querySelector('#toggleMode i');
            icon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
            document.querySelector('#toggleMode span').textContent = isLight ? 'Dark Mode' : 'Light Mode';
        });

        if (localStorage.getItem('lightMode') === 'true') {
            document.body.classList.add('light-mode');
            document.querySelector('#toggleMode i').className = 'fas fa-sun';
            document.querySelector('#toggleMode span').textContent = 'Dark Mode';
        }

        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                if (this.dataset.mode === 'basic') {
                    document.querySelector('.basic-buttons').style.display = 'grid';
                    document.querySelector('.scientific-buttons').style.display = 'none';
                } else {
                    document.querySelector('.basic-buttons').style.display = 'none';
                    document.querySelector('.scientific-buttons').style.display = 'grid';
                }
            });
        });

        function deleteHistoryItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                fetch('save_history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `delete_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = document.querySelector(`.history-item[data-id="${id}"]`);
                        item.style.animation = 'fadeOut 0.3s ease forwards';
                        setTimeout(() => {
                            item.remove();
                            checkEmptyHistory();
                        }, 300);
                    }
                });
            }
        }

        function useHistoryItem(id) {
            const item = document.querySelector(`.history-item[data-id="${id}"]`);
            const expression = item.querySelector('.expression').textContent;
            const result = item.querySelector('.result').textContent.replace('= ', '');
            
            document.getElementById('calculation').textContent = expression;
            document.getElementById('result').textContent = result;
            
            // Add animation feedback
            const display = document.querySelector('.calculator-display');
            display.classList.add('pulse');
            setTimeout(() => {
                display.classList.remove('pulse');
            }, 300);
        }

        function clearAllHistory() {
            if (confirm('Are you sure you want to clear all history?')) {
                fetch('clear_history.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const items = document.querySelectorAll('.history-item');
                        items.forEach((item, index) => {
                            setTimeout(() => {
                                item.style.animation = 'fadeOut 0.3s ease forwards';
                                setTimeout(() => item.remove(), 300);
                            }, index * 100);
                        });
                        
                        setTimeout(() => {
                            checkEmptyHistory();
                        }, items.length * 100 + 300);
                    }
                });
            }
        }

        function editHistoryItem(button) {
            const item = button.closest('.history-item');
            item.querySelector('.history-content').style.display = 'none';
            item.querySelector('.history-buttons').style.display = 'none';
            item.querySelector('.edit-form').style.display = 'block';
            item.querySelector('.edit-expression').focus();
        }

        function cancelEdit(button) {
            const item = button.closest('.history-item');
            item.querySelector('.history-content').style.display = 'flex';
            item.querySelector('.history-buttons').style.display = 'flex';
            item.querySelector('.edit-form').style.display = 'none';
        }

        function saveHistoryItem(id, button) {
            const item = button.closest('.history-item');
            const expression = item.querySelector('.edit-expression').value;
            const result = item.querySelector('.edit-result').value;

            if (!expression || !result) {
                alert('Both expression and result are required');
                return;
            }

            fetch('save_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_id=${id}&expression=${encodeURIComponent(expression)}&result=${encodeURIComponent(result)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function checkEmptyHistory() {
            if (document.querySelectorAll('.history-item').length === 0) {
                document.querySelector('.history-list').innerHTML = `
                    <div class="empty-history">
                        <i class="fas fa-calculator"></i>
                        <p>No calculation history yet</p>
                    </div>`;
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key >= '0' && e.key <= '9') {
                append(e.key);
            } else if (e.key === '.') {
                append('.');
            } else if (e.key === '+' || e.key === '-' || e.key === '*' || e.key === '/') {
                append(e.key);
            } else if (e.key === '(' || e.key === ')') {
                append(e.key);
            } else if (e.key === 'Enter') {
                calculate();
            } else if (e.key === 'Escape') {
                clearDisplay();
            } else if (e.key === 'Backspace') {
                backspace();
            }
        });
    </script>
</body>
</html>