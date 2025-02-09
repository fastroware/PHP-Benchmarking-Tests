<?php
// ---------- HANDLE AJAX REQUEST (CREATE/TURNCATE) ----------
if (isset($_GET['action'])) {
    $conn = new mysqli("localhost", "root", "", "2025_benchmarking_test1");
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection error']));
    }
    // Buat tabel jika belum ada
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

    // Jika action=truncate, reset kedua tabel
    if ($_GET['action'] == 'truncate') {
        $conn->query("TRUNCATE TABLE user1");
        $conn->query("TRUNCATE TABLE user2");
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Jika action=create, proses 1 record (sesuai parameter record) untuk kedua tabel
    if ($_GET['action'] == 'create') {
        $record = isset($_GET['record']) ? intval($_GET['record']) : 0;
        $total  = isset($_GET['total'])  ? intval($_GET['total'])  : 0;
        if ($record <= 0 || $total <= 0 || $record > $total) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }
        // Ambil isi file deskripsi.txt (atau gunakan default)
        $long_description = file_exists('deskripsi.txt') ? file_get_contents('deskripsi.txt') : 'ini adalah deskripsi yang terisi';
        // Jika terlalu panjang, trim (misal: ambil 60.000 karakter)
        if (strlen($long_description) > 60000) {
            $long_description = substr($long_description, 0, 60000);
        }
        // --- INSERT KE TABEL user1 ---
        $start1 = microtime(true);
        $stmt = $conn->prepare("INSERT INTO user1 (nama, email, deskripsi) VALUES (?, ?, '-')");
        $name = "User $record";
        $email = "user{$record}@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $stmt->close();
        $end1 = microtime(true);
        $time_user1 = $end1 - $start1;

        // --- INSERT KE TABEL user2 ---
        $start2 = microtime(true);
        $stmt = $conn->prepare("INSERT INTO user2 (nama, email, deskripsi) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $long_description);
        $stmt->execute();
        $stmt->close();
        $end2 = microtime(true);
        $time_user2 = $end2 - $start2;

        echo json_encode([
            'status'     => 'success',
            'record'     => $record,
            'time_user1' => $time_user1,
            'time_user2' => $time_user2
        ]);
        exit;
    }
    exit;
}

// ---------- HALAMAN UTAMA ----------
$conn = new mysqli("localhost", "root", "", "2025_benchmarking_test1");

// Ambil deskripsi dari file (atau default)
$long_description = file_exists('deskripsi.txt') ? file_get_contents('deskripsi.txt') : 'ini adalah deskripsi yang terisi';
// Jika terlalu panjang, trim agar tidak error
if (strlen($long_description) > 60000) {
    $long_description = substr($long_description, 0, 60000);
}

// Buat tabel jika belum ada
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

// Benchmark function untuk operasi selain create (karena create dengan AJAX diproses secara incremental)
function benchmark($operation) {
    global $conn, $long_description;
    $results = [];

    // Reset tabel
    $conn->query("TRUNCATE TABLE user1");
    $conn->query("TRUNCATE TABLE user2");

    switch ($operation) {
        case 'create':
            // Jika dijalankan secara synchronous (misal fallback non-JS)
            $dataCount = isset($_POST['dataCount']) ? intval($_POST['dataCount']) : 1000;
            $start_time = microtime(true);
            for ($i = 1; $i <= $dataCount; $i++) {
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
            for ($i = 1; $i <= $dataCount; $i++) {
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
            // Insert data terlebih dahulu
            for ($i = 1; $i <= 1000; $i++) {
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
            while ($row = $result->fetch_assoc()) { /* proses simulasi */ }
            $end_time = microtime(true);
            $results['user1'] = $end_time - $start_time;

            $start_time = microtime(true);
            $result = $conn->query("SELECT * FROM user2");
            while ($row = $result->fetch_assoc()) { /* proses simulasi */ }
            $end_time = microtime(true);
            $results['user2'] = $end_time - $start_time;
            break;

        case 'update':
            // Insert data terlebih dahulu
            for ($i = 1; $i <= 1000; $i++) {
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
            // Insert data terlebih dahulu
            for ($i = 1; $i <= 1000; $i++) {
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
            $results['create'] = benchmark('create');
            $results['read']   = benchmark('read');
            $results['update'] = benchmark('update');
            $results['delete'] = benchmark('delete');
            break;
    }

    return $results;
}

$results = null;
// Jika form disubmit via POST (kecuali operasi create yang sudah di‐handle via AJAX)
if (isset($_POST['operation']) && $_POST['operation'] != 'create') {
    $results = benchmark($_POST['operation']);
}
?>
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
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
      <h1 class="text-3xl font-bold text-center mb-8">CRUD Benchmark Test</h1>

      <!-- Form Tombol Operasi CRUD -->
      <form method="POST" id="benchmarkForm" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <button type="submit" name="operation" value="create" 
          class="action-button bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
          Create
        </button>
        <button type="submit" name="operation" value="read"
          class="action-button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
          Read
        </button>
        <button type="submit" name="operation" value="update"
          class="action-button bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
          Update
        </button>
        <button type="submit" name="operation" value="delete"
          class="action-button bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
          Delete
        </button>
        <button type="submit" name="operation" value="all"
          class="action-button bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
          All CRUD
        </button>
        <!-- Input jumlah data untuk create (hanya digunakan bila operasi create) -->
        <div class="md:col-span-5 mt-4">
          <label for="dataCount" class="block mb-2 font-bold">Jumlah Data untuk Create (hanya untuk operasi create):</label>
          <input type="number" id="dataCount" name="dataCount" value="1000" min="1" class="w-full p-2 border rounded" />
        </div>
      </form>

      <?php if ($results): ?>
      <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">Results:</h2>
        <?php if (isset($results['user1'])): ?>
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
                y: { beginAtZero: true }
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
                  scales: { y: { beginAtZero: true } }
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

  <!-- JAVASCRIPT UNTUK SWEETALERT & PROGRESS BAR -->
  <script>
    // Event listener untuk tiap tombol (yang punya class "action-button")
    document.querySelectorAll('button.action-button').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        let operation = this.value;
        if (operation === 'create') {
          let dataCount = document.getElementById('dataCount').value;
          Swal.fire({
            title: 'Konfirmasi',
            text: 'Yakin untuk membuat ' + dataCount + ' data?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, proses',
            preConfirm: () => {
              return new Promise((resolve) => {
                // Pertama, reset tabel via AJAX
                fetch('index.php?action=truncate')
                  .then(response => response.json())
                  .then(data => {
                    if (data.status === 'success') {
                      // Mulai proses create data secara incremental
                      processCreateRecords(dataCount, resolve);
                    } else {
                      Swal.fire('Error', 'Gagal mereset tabel', 'error');
                      resolve();
                    }
                  })
                  .catch(err => {
                    Swal.fire('Error', 'AJAX error: ' + err, 'error');
                    resolve();
                  });
              });
            }
          }).then((result) => {
            if (result.isConfirmed) {
              Swal.fire('Selesai', 'Data berhasil dibuat', 'success');
            }
          });
        } else {
          // Untuk operasi non-create, tampilkan konfirmasi dan submit form secara normal
          Swal.fire({
            title: 'Konfirmasi',
            text: 'Yakin untuk menjalankan operasi ' + operation + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, proses'
          }).then((result) => {
            if (result.isConfirmed) {
              let form = document.getElementById('benchmarkForm');
              let opInput = document.createElement('input');
              opInput.type = 'hidden';
              opInput.name = 'operation';
              opInput.value = operation;
              form.appendChild(opInput);
              form.submit();
            }
          });
        }
      });
    });

    // Fungsi untuk memproses data create secara incremental via AJAX
    function processCreateRecords(total, doneCallback) {
      let current = 0;
      let totalTimeUser1 = 0;
      let totalTimeUser2 = 0;

      Swal.fire({
        title: 'Proses Create Data',
        html: '<div id="progress-container" style="width: 100%; background: #eee;"><div id="progress-bar" style="width: 0%; height: 20px; background: green;"></div></div><p id="progress-text">0 / ' + total + '</p>',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => { processNext(); }
      });

      function processNext() {
        if (current < total) {
          fetch('index.php?action=create&record=' + (current + 1) + '&total=' + total)
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                current++;
                totalTimeUser1 += parseFloat(data.time_user1);
                totalTimeUser2 += parseFloat(data.time_user2);
                let percent = Math.round((current / total) * 100);
                document.getElementById('progress-bar').style.width = percent + '%';
                document.getElementById('progress-text').innerText = current + ' / ' + total;
                processNext();
              } else {
                Swal.fire('Error', 'Terjadi error pada record ' + (current + 1), 'error');
              }
            })
            .catch(err => {
              Swal.fire('Error', 'AJAX error: ' + err, 'error');
            });
        } else {
          Swal.close();
          displayChart(totalTimeUser1, totalTimeUser2);
          doneCallback();
        }
      }
    }

    // Fungsi untuk menampilkan chart benchmark (hasil create)
    function displayChart(time1, time2) {
      let container = document.getElementById('chartContainer');
      if (!container) {
        container = document.createElement('div');
        container.id = 'chartContainer';
        container.innerHTML = '<canvas id="benchmarkChart"></canvas>';
        document.body.appendChild(container);
      }
      new Chart(document.getElementById('benchmarkChart'), {
        type: 'bar',
        data: {
          labels: ['User1 Table (Empty Desc)', 'User2 Table (Long Desc)'],
          datasets: [{
            label: 'Total Execution Time (seconds)',
            data: [time1.toFixed(4), time2.toFixed(4)],
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
