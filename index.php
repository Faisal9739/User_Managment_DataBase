<?php
// Database connection
$host = 'localhost';
$db   = 'info';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $age = $_POST['age'];
    
    if ($id) {
        // Update existing record or insert with specific ID
        $stmt = $pdo->prepare("REPLACE INTO user (id, name, age) VALUES (?, ?, ?)");
        $stmt->execute([$id, $name, $age]);
    } else {
        // Insert new record with auto-generated ID
        $stmt = $pdo->prepare("INSERT INTO user (name, age) VALUES (?, ?)");
        $stmt->execute([$name, $age]);
    }
    
    // Redirect to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_update'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE user SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    // Return success response for AJAX
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Handle record deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$id]);
    
    // Redirect to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Get existing users
$stmt = $pdo->query("SELECT * FROM user");
$users = $stmt->fetchAll();

// Get next available ID for new records
$stmt = $pdo->query("SHOW TABLE STATUS LIKE 'user'");
$tableStatus = $stmt->fetch();
$nextId = $tableStatus['Auto_increment'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 30px 20px;
            color: #e2e8f0;
            font-family: 'Montserrat', sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPgogIDxkZWZzPgogICAgPHBhdHRlcm4gaWQ9InBhdHRlcm4iIHg9IjAiIHk9IjAiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+CiAgICAgIDxyZWN0IHg9IjAiIHk9IjAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgzMCwgNDEsIDU1LCAwLjEpIi8+CiAgICA8L3BhdHRlcm4+CiAgPC9kZWZzPgogIDxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjcGF0dGVybikiLz4KPC9zdmc+');
            opacity: 0.4;
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        header {
            text-align: center;
            padding: 40px 0;
            position: relative;
            margin-bottom: 30px;
        }

        header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #c9a145, transparent);
        }

        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 15px;
            color: #f0e6d2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
            font-weight: 600;
            background: linear-gradient(to right, #e6d3a7, #f0e6d2, #e6d3a7);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card {
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            padding: 40px;
            margin-bottom: 40px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(201, 161, 69, 0.15);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #c9a145, #d4b15f, #c9a145);
        }

        .card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: #f0e6d2;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
        }

        .card h2::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: #c9a145;
        }

        .form-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            border: 1px solid rgba(201, 161, 69, 0.3);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: rgba(15, 23, 42, 0.5);
            color: #f0e6d2;
            outline: none;
        }

        .input-group input:focus {
            border-color: #c9a145;
            box-shadow: 0 0 0 3px rgba(201, 161, 69, 0.2);
        }

        .input-group input::placeholder {
            color: #64748b;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        button {
            background: linear-gradient(to right, #c9a145, #d4b15f);
            color: #0f172a;
            border: none;
            padding: 16px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(201, 161, 69, 0.3);
            min-width: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
            font-family: 'Montserrat', sans-serif;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(201, 161, 69, 0.4);
        }

        button.secondary {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(201, 161, 69, 0.3);
            box-shadow: none;
        }

        button.secondary:hover {
            color: #f0e6d2;
            border-color: #c9a145;
        }

        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .records-count {
            background: linear-gradient(to right, #c9a145, #d4b15f);
            color: #0f172a;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 3px 10px rgba(201, 161, 69, 0.3);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        thead {
            background: linear-gradient(to right, #1e293b, #1a2438);
        }

        th {
            padding: 20px;
            text-align: left;
            color: #c9a145;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(201, 161, 69, 0.3);
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(201, 161, 69, 0.1);
            color: #e2e8f0;
            transition: background 0.3s;
        }

        tr:not(:last-child) td {
            border-bottom: 1px solid rgba(201, 161, 69, 0.1);
        }

        tr:hover td {
            background: rgba(30, 41, 59, 0.5);
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .status-toggle input {
            display: none;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #334155;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 3px;
            bottom: 3px;
            background-color: #64748b;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: linear-gradient(to right, #c9a145, #d4b15f);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
            background-color: white;
        }

        .status-text {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-left: 10px;
        }

        .status-active {
            background: rgba(201, 161, 69, 0.15);
            color: #d4b15f;
            border: 1px solid rgba(201, 161, 69, 0.3);
        }

        .status-inactive {
            background: rgba(220, 38, 38, 0.15);
            color: #f87171;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .action-btn {
            background: rgba(201, 161, 69, 0.15);
            border: 1px solid rgba(201, 161, 69, 0.3);
            color: #c9a145;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            padding: 8px 12px;
            border-radius: 6px;
            margin-right: 8px;
        }

        .action-btn:hover {
            background: rgba(201, 161, 69, 0.3);
            transform: translateY(-2px);
        }

        .delete-btn {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #f87171;
        }

        .delete-btn:hover {
            background: rgba(220, 38, 38, 0.3);
        }

        .notification {
            position: fixed;
            top: 30px;
            right: 30px;
            padding: 18px 30px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: rgba(21, 128, 61, 0.85);
        }

        .notification.error {
            background: rgba(185, 28, 28, 0.85);
        }

        .notification.info {
            background: rgba(30, 64, 175, 0.85);
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 900px) {
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            button {
                width: 100%;
            }
            
            th, td {
                padding: 15px;
                font-size: 14px;
            }
            
            .hide-mobile {
                display: none;
            }
            
            header h1 {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 600px) {
            .card {
                padding: 25px;
            }
            
            header h1 {
                font-size: 2.2rem;
            }
            
            .records-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Luxury User Management</h1>
        </header>
        
        <main>
            <div class="card">
                <h2><i class="fas fa-user-edit"></i> User Record Form</h2>
                <form id="userForm" method="POST" class="form-container">
                    <div class="input-group">
                        <label for="id"><i class="fas fa-fingerprint"></i> User ID</label>
                        <input type="number" id="id" name="id" placeholder="Enter ID (0 for auto)" min="0">
                    </div>
                    
                    <div class="input-group">
                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter full name" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="age"><i class="fas fa-birthday-cake"></i> Age</label>
                        <input type="number" id="age" name="age" placeholder="Enter age" min="1" max="120" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" id="submitBtn">
                            <i class="fas fa-plus-circle"></i> Save Record
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="records-header">
                    <h2><i class="fas fa-users"></i> User Records</h2>
                    <div class="records-count"><span id="recordCount"><?= count($users) ?></span> Records</div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Status</th>
                                <th class="hide-mobile">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTable">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['age']) ?></td>
                                <td>
                                    <label class="status-toggle">
                                        <input type="checkbox" class="status-checkbox" 
                                            data-id="<?= $user['id'] ?>" <?= $user['status'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="status-text <?= $user['status'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="hide-mobile">
                                    <button class="action-btn edit-btn" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['name']) ?>" data-age="<?= $user['age'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn delete-btn" data-id="<?= $user['id'] ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span>Record added successfully!</span>
    </div>

    <script>
        // DOM Elements
        const userForm = document.getElementById('userForm');
        const recordsTable = document.getElementById('recordsTable');
        const recordCount = document.getElementById('recordCount');
        const notification = document.getElementById('notification');
        const submitBtn = document.getElementById('submitBtn');
        const idInput = document.getElementById('id');

        // Toggle status handler
        document.querySelectorAll('.status-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                const status = this.checked ? 1 : 0;
                const row = this.closest('tr');
                const statusText = row.querySelector('.status-text');
                
                // Immediate UI update
                if (status) {
                    statusText.textContent = 'Active';
                    statusText.className = 'status-text status-active';
                } else {
                    statusText.textContent = 'Inactive';
                    statusText.className = 'status-text status-inactive';
                }
                
                // Send update to server
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);
                formData.append('status_update', 'true');
                formData.append('ajax', 'true');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Status updated successfully!', 'success');
                    } else {
                        showNotification('Failed to update status', 'error');
                        // Revert UI if update failed
                        this.checked = !this.checked;
                        updateStatusUI(this);
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error.message, 'error');
                    // Revert UI on error
                    this.checked = !this.checked;
                    updateStatusUI(this);
                });
            });
        });
        
        function updateStatusUI(checkbox) {
            const statusText = checkbox.closest('tr').querySelector('.status-text');
            if (checkbox.checked) {
                statusText.textContent = 'Active';
                statusText.className = 'status-text status-active';
            } else {
                statusText.textContent = 'Inactive';
                statusText.className = 'status-text status-inactive';
            }
        }
        
        // Edit button handler
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const age = this.getAttribute('data-age');
                
                // Populate the form
                document.getElementById('name').value = name;
                document.getElementById('age').value = age;
                idInput.value = id;
                
                // Change button to Update
                submitBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Update Record';
                
                // Scroll to form
                document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
                
                showNotification('Editing record ID: ' + id, 'info');
            });
        });
        
        // Delete button handler
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                
                if (confirm(`Are you sure you want to delete "${name}" (ID: ${id})?`)) {
                    // Show loading state
                    const originalBtnContent = this.innerHTML;
                    this.innerHTML = '<div class="spinner"></div>';
                    this.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('delete', 'true');
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(() => {
                        // Remove the row immediately
                        this.closest('tr').remove();
                        recordCount.textContent = parseInt(recordCount.textContent) - 1;
                        showNotification('Record deleted successfully', 'success');
                    })
                    .catch(error => {
                        showNotification('Error deleting record: ' + error.message, 'error');
                        this.innerHTML = originalBtnContent;
                        this.disabled = false;
                    });
                }
            });
        });
        
        // Show notification
        function showNotification(message, type) {
            const icon = type === 'success' ? 'fa-check-circle' : 
                         type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            
            notification.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        // Initialize the app
        document.addEventListener('DOMContentLoaded', () => {
            // Add subtle animation to cards
            document.querySelectorAll('.card').forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = 1;
                    card.style.transform = 'translateY(0)';
                }, 150 * index);
            });
        });
    </script>
</body>
</html>