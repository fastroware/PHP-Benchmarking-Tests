<?php
// Konfigurasi koneksi database – sesuaikan dengan database Anda
$dbHost     = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName     = "2025_benchmarking_test2";

// Membuat koneksi menggunakan mysqli
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Membuat tabel MyISAM dan InnoDB (jika belum ada)
// Struktur tabel: id, text1, text2, calc1, calc2, username, userpass
$query_myisam = "CREATE TABLE IF NOT EXISTS benchmark_myisam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text1 VARCHAR(255),
    text2 VARCHAR(255),
    calc1 INT,
    calc2 INT,
    username VARCHAR(50),
    userpass VARCHAR(50)
) ENGINE=MyISAM;";

$query_innodb = "CREATE TABLE IF NOT EXISTS benchmark_innodb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text1 VARCHAR(255),
    text2 VARCHAR(255),
    calc1 INT,
    calc2 INT,
    username VARCHAR(50),
    userpass VARCHAR(50)
) ENGINE=InnoDB;";

$mysqli->query($query_myisam);
$mysqli->query($query_innodb);

// Jika ada request POST (operasi CRUD) – respon dikirim dalam format JSON
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    switch($action) {
        case 'create':
            // Ambil data input
            $text1    = $_POST['text1'] ?? '';
            $text2    = $_POST['text2'] ?? '';
            $calc1    = intval($_POST['calc1'] ?? 0);
            $calc2    = intval($_POST['calc2'] ?? 0);
            $username = $_POST['username'] ?? '';
            $userpass = $_POST['userpass'] ?? '';

            // Lakukan INSERT pada tabel MyISAM dan ukur waktu eksekusi
            $start = microtime(true);
            $stmt = $mysqli->prepare("INSERT INTO benchmark_myisam (text1, text2, calc1, calc2, username, userpass) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiis", $text1, $text2, $calc1, $calc2, $username, $userpass);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_myisam = ($end - $start) * 1000; // milidetik

            // INSERT pada tabel InnoDB
            $start = microtime(true);
            $stmt = $mysqli->prepare("INSERT INTO benchmark_innodb (text1, text2, calc1, calc2, username, userpass) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiis", $text1, $text2, $calc1, $calc2, $username, $userpass);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_innodb = ($end - $start) * 1000;

            echo json_encode([
                'operation'    => 'create',
                'myisam_time'  => $time_myisam,
                'innodb_time'  => $time_innodb,
                'message'      => 'Record berhasil dibuat'
            ]);
            exit;
            break;

        case 'read':
            // Melakukan SELECT untuk membaca data
            $start = microtime(true);
            $res_myisam = $mysqli->query("SELECT * FROM benchmark_myisam");
            $data_myisam = $res_myisam->fetch_all(MYSQLI_ASSOC);
            $res_myisam->free();
            $end = microtime(true);
            $time_myisam = ($end - $start) * 1000;

            $start = microtime(true);
            $res_innodb = $mysqli->query("SELECT * FROM benchmark_innodb");
            $data_innodb = $res_innodb->fetch_all(MYSQLI_ASSOC);
            $res_innodb->free();
            $end = microtime(true);
            $time_innodb = ($end - $start) * 1000;

            echo json_encode([
                'operation'    => 'read',
                'myisam_time'  => $time_myisam,
                'innodb_time'  => $time_innodb,
                'data_myisam'  => $data_myisam,
                'data_innodb'  => $data_innodb
            ]);
            exit;
            break;

        case 'update':
            // Update record berdasarkan ID
            $id       = intval($_POST['id'] ?? 0);
            $text1    = $_POST['text1'] ?? '';
            $text2    = $_POST['text2'] ?? '';
            $calc1    = intval($_POST['calc1'] ?? 0);
            $calc2    = intval($_POST['calc2'] ?? 0);
            $username = $_POST['username'] ?? '';
            $userpass = $_POST['userpass'] ?? '';

            // Update di tabel MyISAM
            $start = microtime(true);
            $stmt = $mysqli->prepare("UPDATE benchmark_myisam SET text1=?, text2=?, calc1=?, calc2=?, username=?, userpass=? WHERE id=?");
            $stmt->bind_param("ssiissi", $text1, $text2, $calc1, $calc2, $username, $userpass, $id);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_myisam = ($end - $start) * 1000;

            // Update di tabel InnoDB
            $start = microtime(true);
            $stmt = $mysqli->prepare("UPDATE benchmark_innodb SET text1=?, text2=?, calc1=?, calc2=?, username=?, userpass=? WHERE id=?");
            $stmt->bind_param("ssiissi", $text1, $text2, $calc1, $calc2, $username, $userpass, $id);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_innodb = ($end - $start) * 1000;

            echo json_encode([
                'operation'    => 'update',
                'myisam_time'  => $time_myisam,
                'innodb_time'  => $time_innodb,
                'message'      => 'Record berhasil diupdate'
            ]);
            exit;
            break;

        case 'delete':
            // Hapus record berdasarkan ID
            $id = intval($_POST['id'] ?? 0);

            // Hapus di tabel MyISAM
            $start = microtime(true);
            $stmt = $mysqli->prepare("DELETE FROM benchmark_myisam WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_myisam = ($end - $start) * 1000;

            // Hapus di tabel InnoDB
            $start = microtime(true);
            $stmt = $mysqli->prepare("DELETE FROM benchmark_innodb WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $end = microtime(true);
            $time_innodb = ($end - $start) * 1000;

            echo json_encode([
                'operation'    => 'delete',
                'myisam_time'  => $time_myisam,
                'innodb_time'  => $time_innodb,
                'message'      => 'Record berhasil dihapus'
            ]);
            exit;
            break;

        case 'reset':
            // Reset (truncate) kedua tabel
            $start = microtime(true);
            $mysqli->query("TRUNCATE TABLE benchmark_myisam");
            $end = microtime(true);
            $time_myisam = ($end - $start) * 1000;

            $start = microtime(true);
            $mysqli->query("TRUNCATE TABLE benchmark_innodb");
            $end = microtime(true);
            $time_innodb = ($end - $start) * 1000;

            echo json_encode([
                'operation'    => 'reset',
                'myisam_time'  => $time_myisam,
                'innodb_time'  => $time_innodb,
                'message'      => 'Tabel berhasil di-reset'
            ]);
            exit;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>PHP Database Benchmarking: MyISAM vs InnoDB</title>
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- jQuery CDN -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
  <div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-4 text-center">PHP Database Benchmarking<br><span class="text-lg font-normal">(MyISAM vs InnoDB)</span></h1>

    <!-- Form CRUD dalam grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- FORM CREATE -->
      <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Create Record</h2>
        <form id="createForm" class="space-y-2">
          <div>
            <label class="block">Text 1:</label>
            <input type="text" name="text1" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Text 2:</label>
            <input type="text" name="text2" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Calc 1:</label>
            <input type="number" name="calc1" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Calc 2:</label>
            <input type="number" name="calc2" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Username:</label>
            <input type="text" name="username" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">User Password:</label>
            <input type="password" name="userpass" class="w-full border p-2" required>
          </div>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create</button>
        </form>
      </div>

      <!-- FORM UPDATE -->
      <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Update Record</h2>
        <form id="updateForm" class="space-y-2">
          <div>
            <label class="block">Record ID:</label>
            <input type="number" name="id" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Text 1:</label>
            <input type="text" name="text1" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Text 2:</label>
            <input type="text" name="text2" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Calc 1:</label>
            <input type="number" name="calc1" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Calc 2:</label>
            <input type="number" name="calc2" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">Username:</label>
            <input type="text" name="username" class="w-full border p-2" required>
          </div>
          <div>
            <label class="block">User Password:</label>
            <input type="password" name="userpass" class="w-full border p-2" required>
          </div>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Update</button>
        </form>
      </div>

      <!-- FORM DELETE -->
      <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Delete Record</h2>
        <form id="deleteForm" class="space-y-2">
          <div>
            <label class="block">Record ID:</label>
            <input type="number" name="id" class="w-full border p-2" required>
          </div>
          <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
        </form>
      </div>

      <!-- TOMBOL READ -->
      <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Read Records</h2>
        <button id="readBtn" class="bg-purple-500 text-white px-4 py-2 rounded mb-2">Load Records</button>
        <div id="readResults" class="overflow-auto max-h-64"></div>
      </div>
    </div>

    <!-- Tombol reset untuk mengosongkan data di kedua tabel -->
    <div class="mt-4">
      <button id="resetBtn" class="bg-yellow-500 text-white px-4 py-2 rounded">Reset Tables</button>
    </div>

    <!-- Tabel untuk menampilkan hasil benchmark -->
    <div class="mt-8 bg-white p-4 rounded shadow">
      <h2 class="text-xl font-semibold mb-2">Benchmark Results</h2>
      <table class="min-w-full divide-y divide-gray-200" id="benchmarkTable">
        <thead>
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operation</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MyISAM (ms)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">InnoDB (ms)</th>
          </tr>
        </thead>
        <tbody id="benchmarkBody" class="divide-y divide-gray-200">
          <!-- Hasil benchmark akan ditambahkan di sini -->
        </tbody>
      </table>
    </div>

    <!-- Bagian Chart -->
    <div class="mt-8 bg-white p-4 rounded shadow">
      <h2 class="text-xl font-semibold mb-2">Performance Chart</h2>
      <canvas id="performanceChart" height="100"></canvas>
    </div>
  </div>

  <script>
    $(document).ready(function(){
      // Objek untuk menyimpan data benchmark terakhir per operasi
      var benchmarkData = {
        create: {myisam: 0, innodb: 0},
        read:   {myisam: 0, innodb: 0},
        update: {myisam: 0, innodb: 0},
        delete: {myisam: 0, innodb: 0},
        reset:  {myisam: 0, innodb: 0}
      };

      // Inisialisasi Chart.js
      var ctx = document.getElementById('performanceChart').getContext('2d');
      var performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Create', 'Read', 'Update', 'Delete', 'Reset'],
          datasets: [{
            label: 'MyISAM (ms)',
            data: [0, 0, 0, 0, 0],
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
          },
          {
            label: 'InnoDB (ms)',
            data: [0, 0, 0, 0, 0],
            backgroundColor: 'rgba(255, 99, 132, 0.7)'
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true }
          }
        }
      });

      // Fungsi untuk memperbarui Chart
      function updateChart() {
        performanceChart.data.datasets[0].data = [
          benchmarkData.create.myisam,
          benchmarkData.read.myisam,
          benchmarkData.update.myisam,
          benchmarkData.delete.myisam,
          benchmarkData.reset.myisam
        ];
        performanceChart.data.datasets[1].data = [
          benchmarkData.create.innodb,
          benchmarkData.read.innodb,
          benchmarkData.update.innodb,
          benchmarkData.delete.innodb,
          benchmarkData.reset.innodb
        ];
        performanceChart.update();
      }

      // Fungsi untuk menambahkan baris hasil benchmark ke tabel HTML
      function appendBenchmarkResult(operation, myisam, innodb) {
        var row = `<tr>
          <td class="px-6 py-4 whitespace-nowrap">${operation}</td>
          <td class="px-6 py-4 whitespace-nowrap">${myisam.toFixed(3)}</td>
          <td class="px-6 py-4 whitespace-nowrap">${innodb.toFixed(3)}</td>
        </tr>`;
        $("#benchmarkBody").append(row);
      }

      // AJAX untuk operasi CREATE
      $("#createForm").submit(function(e){
        e.preventDefault();
        $.ajax({
          url: "",
          type: "POST",
          data: $(this).serialize() + "&action=create",
          dataType: "json",
          success: function(response){
            benchmarkData.create.myisam = response.myisam_time;
            benchmarkData.create.innodb = response.innodb_time;
            appendBenchmarkResult("Create", response.myisam_time, response.innodb_time);
            updateChart();
            alert(response.message);
          }
        });
      });

      // AJAX untuk operasi UPDATE
      $("#updateForm").submit(function(e){
        e.preventDefault();
        $.ajax({
          url: "",
          type: "POST",
          data: $(this).serialize() + "&action=update",
          dataType: "json",
          success: function(response){
            benchmarkData.update.myisam = response.myisam_time;
            benchmarkData.update.innodb = response.innodb_time;
            appendBenchmarkResult("Update", response.myisam_time, response.innodb_time);
            updateChart();
            alert(response.message);
          }
        });
      });

      // AJAX untuk operasi DELETE
      $("#deleteForm").submit(function(e){
        e.preventDefault();
        $.ajax({
          url: "",
          type: "POST",
          data: $(this).serialize() + "&action=delete",
          dataType: "json",
          success: function(response){
            benchmarkData.delete.myisam = response.myisam_time;
            benchmarkData.delete.innodb = response.innodb_time;
            appendBenchmarkResult("Delete", response.myisam_time, response.innodb_time);
            updateChart();
            alert(response.message);
          }
        });
      });

      // AJAX untuk operasi READ
      $("#readBtn").click(function(){
        $.ajax({
          url: "",
          type: "POST",
          data: {action:"read"},
          dataType: "json",
          success: function(response){
            benchmarkData.read.myisam = response.myisam_time;
            benchmarkData.read.innodb = response.innodb_time;
            appendBenchmarkResult("Read", response.myisam_time, response.innodb_time);
            updateChart();

            // Menampilkan data record di div #readResults
            var html = "<h3 class='text-lg font-semibold mb-2'>MyISAM Records</h3><ul class='list-disc pl-5'>";
            response.data_myisam.forEach(function(item){
              html += `<li>ID: ${item.id}, ${item.text1}, ${item.text2}, ${item.calc1}, ${item.calc2}, ${item.username}</li>`;
            });
            html += "</ul><h3 class='text-lg font-semibold mb-2 mt-4'>InnoDB Records</h3><ul class='list-disc pl-5'>";
            response.data_innodb.forEach(function(item){
              html += `<li>ID: ${item.id}, ${item.text1}, ${item.text2}, ${item.calc1}, ${item.calc2}, ${item.username}</li>`;
            });
            html += "</ul>";
            $("#readResults").html(html);
          }
        });
      });

      // AJAX untuk operasi RESET
      $("#resetBtn").click(function(){
        if(confirm("Apakah Anda yakin ingin mereset tabel? Semua data akan hilang.")) {
          $.ajax({
            url: "",
            type: "POST",
            data: {action:"reset"},
            dataType: "json",
            success: function(response){
              benchmarkData.reset.myisam = response.myisam_time;
              benchmarkData.reset.innodb = response.innodb_time;
              appendBenchmarkResult("Reset", response.myisam_time, response.innodb_time);
              updateChart();
              alert(response.message);
            }
          });
        }
      });
    });
  </script>
</body>
</html>
