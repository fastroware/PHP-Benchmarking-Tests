<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Benchmark Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <?php
    $conn = new mysqli("localhost", "root", "", "2025_benchmarking_test1");
    
    // Read and truncate description
    $long_description = file_exists('deskripsi.txt') ? 
        substr(file_get_contents('deskripsi.txt'), 0, 65535) : 
        'ini adalah deskripsi yang terisi';
    
    // Create tables with TEXT columns
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

    function benchmark($operation, $data_count) {
        global $conn, $long_description;
        $results = [];
        
        // Reset tables
        $conn->query("TRUNCATE TABLE user1");
        $conn->query("TRUNCATE TABLE user2");
        
        switch($operation) {
            case 'create':
                // Benchmark CREATE
                $start_time = microtime(true);
                for($i = 1; $i <= $data_count; $i++) {
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
                for($i = 1; $i <= $data_count; $i++) {
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
                // Insert test data
                for($i = 1; $i <= $data_count; $i++) {
                    $conn->query("INSERT INTO user1 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '-')");
                    $conn->query("INSERT INTO user2 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '$long_description')");
                }
                
                // Benchmark READ
                $start_time = microtime(true);
                $result = $conn->query("SELECT * FROM user1");
                while($row = $result->fetch_assoc()) {}
                $end_time = microtime(true);
                $results['user1'] = $end_time - $start_time;
                
                $start_time = microtime(true);
                $result = $conn->query("SELECT * FROM user2");
                while($row = $result->fetch_assoc()) {}
                $end_time = microtime(true);
                $results['user2'] = $end_time - $start_time;
                break;
                
            case 'update':
                // Insert test data
                for($i = 1; $i <= $data_count; $i++) {
                    $conn->query("INSERT INTO user1 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '-')");
                    $conn->query("INSERT INTO user2 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '$long_description')");
                }
                
                // Benchmark UPDATE
                $start_time = microtime(true);
                $conn->query("UPDATE user1 SET nama = 'Updated User'");
                $results['user1'] = microtime(true) - $start_time;
                
                $start_time = microtime(true);
                $conn->query("UPDATE user2 SET nama = 'Updated User'");
                $results['user2'] = microtime(true) - $start_time;
                break;
                
            case 'delete':
                // Insert test data
                for($i = 1; $i <= $data_count; $i++) {
                    $conn->query("INSERT INTO user1 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '-')");
                    $conn->query("INSERT INTO user2 (nama, email, deskripsi) VALUES ('User $i', 'user$i@example.com', '$long_description')");
                }
                
                // Benchmark DELETE
                $start_time = microtime(true);
                $conn->query("DELETE FROM user1");
                $results['user1'] = microtime(true) - $start_time;
                
                $start_time = microtime(true);
                $conn->query("DELETE FROM user2");
                $results['user2'] = microtime(true) - $start_time;
                break;
        }
        
        return $results;
    }

    if(isset($_POST['operation']) && isset($_POST['data_count'])) {
        header('Content-Type: application/json');
        echo json_encode(benchmark($_POST['operation'], (int)$_POST['data_count']));
        exit;
    }
    ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-center mb-8">CRUD Benchmark Test</h1>
            
            <div id="results-container"></div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                <button onclick="handleOperation('create')" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Create
                </button>
                <button onclick="handleOperation('read')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                    Read
                </button>
                <button onclick="handleOperation('update')" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                    Update
                </button>
                <button onclick="handleOperation('delete')" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
    function handleOperation(operation) {
        const dataCount = operation === 'create' ? 
            promptDataCount() : 
            Promise.resolve(1000);

        dataCount.then(count => {
            if (count === null) return;

            Swal.fire({
                title: 'Konfirmasi',
                text: `Anda yakin ingin melakukan operasi ${operation}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    runBenchmark(operation, count);
                }
            });
        });
    }

    function promptDataCount() {
        return new Promise((resolve) => {
            Swal.fire({
                title: 'Jumlah Data',
                input: 'number',
                inputLabel: 'Masukkan jumlah data:',
                inputValue: 1000,
                inputAttributes: { min: 1 },
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
                preConfirm: (value) => {
                    if (!value || value < 1) {
                        Swal.showValidationMessage('Masukkan angka yang valid');
                        return false;
                    }
                    return value;
                }
            }).then((result) => {
                resolve(result.isConfirmed ? result.value : null);
            });
        });
    }

    function runBenchmark(operation, dataCount) {
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress = Math.min(progress + Math.random() * 10, 95);
            updateProgress(progress);
        }, 200);

        Swal.fire({
            title: 'Memproses...',
            html: `
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: ${progress}%"></div>
                </div>
                <div class="mt-2 text-sm">${progress.toFixed(1)}% selesai</div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false
        });

        const formData = new FormData();
        formData.append('operation', operation);
        formData.append('data_count', dataCount);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            updateProgress(100);
            setTimeout(() => {
                Swal.close();
                showResults(data, operation);
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            Swal.fire('Error!', 'Terjadi kesalahan saat memproses.', 'error');
        });
    }

    function updateProgress(percent) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = Swal.getHtmlContainer().querySelector('div.text-sm');
        if (progressBar && progressText) {
            progressBar.style.width = `${percent}%`;
            progressText.textContent = `${percent.toFixed(1)}% selesai`;
        }
    }

    function showResults(data, operation) {
        const container = document.getElementById('results-container');
        container.innerHTML = '';

        if (data.user1) {
            const canvas = document.createElement('canvas');
            container.innerHTML = `<h2 class="text-xl font-bold mb-4">Hasil ${operation}:</h2>`;
            container.appendChild(canvas);
            renderChart(canvas, data);
        } else {
            container.innerHTML = `<div class="text-red-500">Tidak ada data hasil</div>`;
        }
    }

    function renderChart(canvas, data) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Table 1 (Short Text)', 'Table 2 (Long Text)'],
                datasets: [{
                    label: 'Waktu Eksekusi (detik)',
                    data: [data.user1, data.user2],
                    backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(255, 99, 132, 0.5)'],
                    borderColor: ['rgb(54, 162, 235)', 'rgb(255, 99, 132)'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    </script>
</body>
</html>