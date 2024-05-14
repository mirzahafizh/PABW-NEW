<?php
include '../user/config.php';

// Fungsi untuk mengambil log aktivitas dari database dengan paginasi
function getLogs($pdo, $offset, $limit) {
    $stmt = $pdo->prepare("SELECT * FROM activity_log ORDER BY timestamp DESC LIMIT :offset, :limit");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tentukan jumlah data per halaman
$limit = 15;

// Hitung jumlah total data
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM activity_log");
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Hitung jumlah halaman
$totalPages = ceil($total / $limit);

// Tentukan halaman saat ini
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$page = max(1, min($totalPages, intval($page)));

// Hitung offset
$offset = ($page - 1) * $limit;

// Ambil data log aktivitas dengan paginasi
$logs = getLogs($pdo, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="font-sans bg-gray-100">

<div class="container mx-auto py-8">
    <h1 class="text-3xl font-bold mb-4">Activity Logs</h1>

    <!-- Form pencarian -->
    <div class="mb-4">
        <input type="text" id="searchInput" onkeyup="searchLogs()" placeholder="Search by activity or username..."
               class="border-2 w-full rounded-lg p-2">
    </div>

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="min-w-max w-full table-auto">
            <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Timestamp</th>
                <th class="py-3 px-6 text-left">Activity</th>
                <th class="py-3 px-6 text-left">Username</th>
            </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light" id="logTableBody">
            <?php foreach ($logs as $log): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-3 px-6 text-left whitespace-nowrap"><?= $log['timestamp'] ?></td>
                    <td class="py-3 px-6 text-left"><?= $log['activity'] ?></td>
                    <td class="py-3 px-6 text-left"><?= $log['username'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-end  p-4">
                <ul class="flex space-x-2 border rounded p-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?action=logs&page=<?= $i ?>"
                               class="px-3 py-2 hover:bg-gray-600 <?= $i === $page ? 'text-blue-500' : 'text-blue-500' ?> rounded"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script untuk fungsi pencarian -->
<script>
    function searchLogs() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById('searchInput');
        filter = input.value.toUpperCase();
        table = document.querySelector('table');
        tr = table.getElementsByTagName('tr');

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName('td');
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                        break;
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    }
</script>

</body>
</html>
