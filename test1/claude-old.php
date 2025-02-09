<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Benchmark Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php
    $conn = new mysqli("localhost", "root", "", "2025_benchmarking_test1");
    
    // Read description from file
    $long_description = file_exists('deskripsi.txt') ? file_get_contents('deskripsi.txt') : 'ini adalah deskripsi yang terisi';
    
    // Create tables if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS user1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255),
        email VARCHAR(255),
        deskripsi TEXT
    )");
    
    $conn->query("CREATE TABLE IF NOT EXISTS user2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255),
        email VARCHAR(255),
        deskripsi TEXT
    )");

    function benchmark($operation) {
        global $conn, $long_description;
        $results = [];
        
        // Reset tables
        $conn->query("TRUNCATE TABLE user1");
        $conn->query("TRUNCATE TABLE user2");
        
        switch($operation) {
            case 'create':
                // Benchmark CREATE
                $start_time = microtime(true);
                for($i = 1; $i <= 1000; $i++) {
                    $stmt = $conn->prepare("INSERT INTO user1 (nama, email, deskripsi) VALUES (?, ?, '-')");
                    $name = "User $i";
                    $email = "user$i@example.com";
                    $stmt->bind_param("ss", $name, $email);
                    $stmt->execute();
                    $stmt->close();
                }
                $end_time = microtime(true);
                $results['user1'] = $end_time - $start_time;
                
                $start_time = microtime(true);
                for($i = 1; $i <= 1000; $i++) {
                    $stmt = $conn->prepare("INSERT INTO user2 (nama, email, deskripsi) VALUES (?, ?, ?)");
                    $name = "User $i";
                    $email = "user$i@example.com";
                    $stmt->bind_param("sss", $name, $email, $long_description);
                    $stmt->execute();
                    $stmt->close();
                }
                $end_time = microtime(true);
                $results['user2'] = $end_time - $start_time;
                break;
                
            case 'read':
                // Insert data first
                for($i = 1; $i <= 1000; $i++) {
                    $stmt = $conn->prepare("INSERT INTO user1 (nama, email, deskripsi) VALUES (?, ?, '-')");
                    $name = "User $i";
                    $email = "user$i@example.com";
                    $stmt->bind_param("ss", $name, $email);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO user2 (nama, email, deskripsi) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $name, $email, $long_description);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Benchmark READ
                $start_time = microtime(true);
                $result = $conn->query("SELECT * FROM user1");
                while($row = $result->fetch_assoc()) {
                    // Simulate data processing
                    $data = $row;
                }
                $end_time = microtime(true);
                $results['user1'] = $end_time - $start_time;
                
                $start_time = microtime(true);
                $result = $conn->query("SELECT * FROM user2");
                while($row = $result->fetch_assoc()) {
                    // Simulate data processing
                    $data = $row;
                }
                $end_time = microtime(true);
                $results['user2'] = $end_time - $start_time;
                break;
                
            case 'update':
                // Insert data first
                for($i = 1; $i <= 1000; $i++) {
                    $stmt = $conn->prepare("INSERT INTO user1 (nama, email, deskripsi) VALUES (?, ?, '-')");
                    $name = "User $i";
                    $email = "user$i@example.com";
                    $stmt->bind_param("ss", $name, $email);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO user2 (nama, email, deskripsi) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $name, $email, $long_description);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Benchmark UPDATE
                $start_time = microtime(true);
                $new_name = "Updated User";
                $stmt = $conn->prepare("UPDATE user1 SET nama = ?");
                $stmt->bind_param("s", $new_name);
                $stmt->execute();
                $stmt->close();
                $end_time = microtime(true);
                $results['user1'] = $end_time - $start_time;
                
                $start_time = microtime(true);
                $stmt = $conn->prepare("UPDATE user2 SET nama = ?");
                $stmt->bind_param("s", $new_name);
                $stmt->execute();
                $stmt->close();
                $end_time = microtime(true);
                $results['user2'] = $end_time - $start_time;
                break;
                
            case 'delete':
                // Insert data first
                for($i = 1; $i <= 1000; $i++) {
                    $stmt = $conn->prepare("INSERT INTO user1 (nama, email, deskripsi) VALUES (?, ?, '-')");
                    $name = "User $i";
                    $email = "user$i@example.com";
                    $stmt->bind_param("ss", $name, $email);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO user2 (nama, email, deskripsi) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $name, $email, $long_description);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Benchmark DELETE
                $start_time = microtime(true);
                $conn->query("DELETE FROM user1");
                $end_time = microtime(true);
                $results['user1'] = $end_time - $start_time;
                
                $start_time = microtime(true);
                $conn->query("DELETE FROM user2");
                $end_time = microtime(true);
                $results['user2'] = $end_time - $start_time;
                break;
                
            case 'all':
                // Benchmark all operations
                $results['create'] = benchmark('create');
                $results['read'] = benchmark('read');
                $results['update'] = benchmark('update');
                $results['delete'] = benchmark('delete');
                break;
        }
        
        return $results;
    }

    $results = null;
    if(isset($_POST['operation'])) {
        $results = benchmark($_POST['operation']);
    }
    ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-center mb-8">CRUD Benchmark Test</h1>
            
            <!-- <?php if(file_exists('deskripsi.txt')): ?>
            <div class="mb-6 p-4 bg-gray-50 rounded">
                <h2 class="font-bold mb-2">Current Description Content:</h2>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($long_description); ?></p>
            </div>
            <?php else: ?>
            <div class="mb-6 p-4 bg-yellow-50 rounded">
                <p class="text-yellow-600">Warning: deskripsi.txt not found. Using default description.</p>
            </div>
            <?php endif; ?> -->
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <button type="submit" name="operation" value="create" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Create
                </button>
                <button type="submit" name="operation" value="read"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                    Read
                </button>
                <button type="submit" name="operation" value="update"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                    Update
                </button>
                <button type="submit" name="operation" value="delete"
                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                    Delete
                </button>
                <button type="submit" name="operation" value="all"
                    class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
                    All CRUD
                </button>
            </form>

            <?php if($results): ?>
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">Results:</h2>
                <?php if(isset($results['user1'])): ?>
                <div class="mb-4">
                    <canvas id="benchmarkChart"></canvas>
                </div>
                <script>
                    new Chart(document.getElementById('benchmarkChart'), {
                        type: 'bar',
                        data: {
                            labels: ['User1 Table (Empty Desc)', 'User2 Table (Long Desc)'],
                            datasets: [{
                                label: 'Execution Time (seconds)',
                                data: [<?php echo $results['user1']; ?>, <?php echo $results['user2']; ?>],
                                backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(255, 99, 132, 0.5)'],
                                borderColor: ['rgb(54, 162, 235)', 'rgb(255, 99, 132)'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($results as $operation => $times): ?>
                    <div class="bg-gray-100 p-4 rounded">
                        <h3 class="font-bold mb-2 capitalize"><?php echo $operation; ?></h3>
                        <canvas id="chart_<?php echo $operation; ?>"></canvas>
                        <script>
                            new Chart(document.getElementById('chart_<?php echo $operation; ?>'), {
                                type: 'bar',
                                data: {
                                    labels: ['User1 Table (Empty Desc)', 'User2 Table (Long Desc)'],
                                    datasets: [{
                                        label: 'Execution Time (seconds)',
                                        data: [<?php echo $times['user1']; ?>, <?php echo $times['user2']; ?>],
                                        backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(255, 99, 132, 0.5)'],
                                        borderColor: ['rgb(54, 162, 235)', 'rgb(255, 99, 132)'],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        </script>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>