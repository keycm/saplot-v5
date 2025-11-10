<?php
include 'session_check.php';

// --- DATABASE CONNECTION ---
$conn = new mysqli("localhost", "root", "", "addproduct");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- FETCH SALES DATA PER DAY ---
$query = "SELECT DATE(date_created) AS sale_date, SUM(amount) AS total_sales 
          FROM revenue 
          GROUP BY DATE(date_created)
          ORDER BY sale_date ASC";
$result = $conn->query($query);

$dates = [];
$totals = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['sale_date'];
    $totals[] = $row['total_sales'];
}

// --- FETCH ALL SOLD PRODUCTS (TABLE) ---
$products = $conn->query("SELECT order_id, amount, date_created FROM revenue ORDER BY date_created DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sales Dashboard</title>
  <link rel="stylesheet" href="CSS/admin.css"/>
  <link rel="stylesheet" href="CSS/sales.css"/>
  <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f9f9f9;
    }

    /* ===== TABLE SCROLL WRAPPER ===== */
    .sales-table-wrapper {
      max-height: 400px;
      overflow-y: scroll;
      overflow-x: hidden;
      padding-right: 10px;
      scrollbar-gutter: stable;
      margin-top: 20px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: #fff;
    }

    /* Always show scrollbar */
    .sales-table-wrapper::-webkit-scrollbar {
      width: 10px;
    }
    .sales-table-wrapper::-webkit-scrollbar-track {
      background: #ffe5e5;
    }
    .sales-table-wrapper::-webkit-scrollbar-thumb {
      background-color: #c0392b;
      border-radius: 10px;
    }

    /* ===== TABLE ===== */
    .sales-table {
      width: 100%;
      border-collapse: collapse;
    }
    .sales-table th, .sales-table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    .sales-table th {
      background-color: #c0392b;
      color: white;
      position: sticky;
      top: 0;
      z-index: 2;
    }
    .sales-table tr:nth-child(even) {
      background-color: #fceaea;
    }
    .sales-table tr:hover {
      background-color: #f8d7da;
    }

    /* ===== BUTTONS ===== */
    .report-btn {
      background: #c0392b;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
      margin-right: 10px;
    }
    .report-btn:hover {
      background: #a93226;
    }

    section {
      margin-top: 30px;
    }
    section h2 {
      color: #c0392b;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Sales Dashboard</h1>
            <a href="logout.php" class="logout-button">Log Out</a>
        </header>
        
        <div class="sales-main-wrapper">
          <div class="sales-container">
            <div class="sales-header">
              <div>
                <h1>₱ 
                  <?php 
                    $total = $conn->query("SELECT SUM(amount) AS total_sales FROM revenue")->fetch_assoc();
                    echo number_format($total['total_sales'], 2);
                  ?>
                </h1>
                <h4>Total Sales</h4>
              </div>
              <div class="sales-legend">
                <span><span class="sales-dot sales-earned"></span> Earned</span>
                <select class="sales-dropdown">
                  <option>Daily</option>
                </select>
              </div>
            </div>
            <canvas id="sales-chart"></canvas>
          </div>
      
          <div class="right-column">
              <div class="sales-order-container">
                <div class="sales-order-header">
                  <h3>Order Time</h3>
                  <button class="sales-report-btn" onclick="printReport()">View Report</button>
                </div>
                <p class="sales-date-range">Sales Overview</p>
                <canvas id="sales-order-chart"></canvas>
          
                <div class="sales-order-legend">
                  <div class="sales-legend-item">
                    <div class="sales-legend-dot dot-afternoon" style="background-color:#e74c3c;"></div>
                    <span>Afternoon</span><small>40%</small>
                  </div>
                  <div class="sales-legend-item">
                    <div class="sales-legend-dot dot-evening" style="background-color:#f1948a;"></div>
                    <span>Evening</span><small>32%</small>
                  </div>
                  <div class="sales-legend-item">
                    <div class="sales-legend-dot dot-morning" style="background-color:#fadbd8;"></div>
                    <span>Morning</span><small>28%</small>
                  </div>
                </div>
              </div>
          </div>
        </div>

        <!-- ===== TABLE NG SOLD PRODUCTS ===== -->
        <section>
          <h2>Sold Products</h2>
          <div class="sales-table-wrapper">
            <table class="sales-table" id="salesTable">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Amount (₱)</th>
                  <th>Date Created</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $products->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['order_id']; ?></td>
                  <td><?php echo number_format($row['amount'], 2); ?></td>
                  <td><?php echo $row['date_created']; ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <button class="report-btn" onclick="printReport()">Print Report</button>
          <button class="report-btn" onclick="exportToWord()">Export to Word</button>
        </section>
    </main>
  </div>

<script>
  // --- CHART DATA FROM PHP ---
  const chartLabels = <?php echo json_encode($dates); ?>;
  const chartData = <?php echo json_encode($totals); ?>;

  // --- LINE CHART ---
  const ctx1 = document.getElementById("sales-chart").getContext("2d");
  new Chart(ctx1, {
    type: "line",
    data: {
      labels: chartLabels,
      datasets: [{
        label: "Total Sales",
        data: chartData,
        borderColor: "#c0392b",
        backgroundColor: "rgba(192,57,43,0.1)",
        tension: 0.3,
        borderWidth: 3,
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true } }
    }
  });

  // --- PIE CHART ---
  const ctx2 = document.getElementById("sales-order-chart").getContext("2d");
  new Chart(ctx2, {
    type: "doughnut",
    data: {
      labels: ["Afternoon", "Evening", "Morning"],
      datasets: [{
        data: [1890, 1512, 1320],
        backgroundColor: ["#c0392b", "#f1948a", "#fadbd8"],
        borderWidth: 0,
        cutout: "70%"
      }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });

  // --- PRINT REPORT ---
  function printReport() {
    window.print();
  }

  // --- EXPORT TO WORD ---
  function exportToWord() {
    const table = document.getElementById("salesTable").outerHTML;
    const header = "<h2>Sold Products Report</h2>";
    const htmlContent = `
      <html><head><meta charset='utf-8'></head><body>${header}${table}</body></html>`;
    const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
    saveAs(blob, "Sold_Products_Report.doc");
  }
</script>
</body>
</html>
