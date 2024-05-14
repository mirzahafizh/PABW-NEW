<?php
include '../user/config.php';

// Tentukan jumlah pesanan yang ingin ditampilkan per halaman
$per_page = 15;

// Ambil halaman saat ini dari URL, defaultnya adalah halaman 1
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;

// Hitung offset untuk query SQL berdasarkan halaman saat ini
$offset = ($current_page - 1) * $per_page;

// Ambil data status pesanan dari tabel orders untuk halaman saat ini
$status_orders = getStatusOrders($pdo, $offset, $per_page);

function getStatusOrders($pdo, $offset, $per_page) {
    $stmt = $pdo->prepare("SELECT * FROM orders LIMIT :offset, :per_page");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk menghitung jumlah total pesanan
function countOrders($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Hitung jumlah total pesanan dan halaman
$total_orders = countOrders($pdo);
$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Status Pesanan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100">

    <div class="container mx-auto p-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Status Pesanan</h2>

        <!-- Input pencarian -->
        <div class="mb-4">
            <input type="text" id="searchInput" onkeyup="searchOrder()" placeholder="Cari Pesanan..."
                class="border-2 w-full rounded-lg p-2">
        </div>

        <!-- Tabel status pesanan -->
        <table class="w-full border-collapse border border-gray-300">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        Pesanan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal
                        Pesan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Pembeli</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                        Toko</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total
                        Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($status_orders as $order) : ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['id_order']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['order_date']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['username']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['store_name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['status_pesanan']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $order['total_price']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Tombol navigasi halaman -->
        <div class="mt-4 flex justify-center">
            <?php if ($total_pages > 1) : ?>
                <ul class="flex space-x-2 border rounded p-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <li>
                            <a href="?action=daftar_status&page=<?= $i ?>"
                                class="px-3 py-2 <?= $i === $current_page ? 'text-blue-500' : 'hover:bg-gray-600 text-blue-500' ?> rounded"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script untuk fungsi pencarian -->
    <script>
        function searchOrder() {
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
