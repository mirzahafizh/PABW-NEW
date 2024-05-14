<!-- Konten -->
<div class="container mx-auto py-8 px-4 ">
    <h1 class="text-3xl font-semibold mb-4 ">Semua Orderan</h1>
    <?php if (empty($orders)): ?>
        <p class="text-gray-600">Tidak ada orderan saat ini.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-4">
            <?php foreach ($orders as $order): ?>
                <?php if ($order['status_pesanan'] === 'menunggu kurir'): ?>
                    <div class="bg-white shadow rounded-lg p-4">
                        <h2 class="text-xl font-semibold mb-2">Order ID: <?php echo $order['id_order']; ?></h2>
                        <h2 class="text-gray-600 mb-2">Username: <?php echo $order['username']; ?></h2>
                        <h2 class="text-gray-600 mb-2">Nomor Telepon: <?php echo $order['phone']; ?></h2>
                        <p class="text-gray-600 mb-2">Total Harga: Rp<?php echo number_format($order['total_price'], 0, ',', '.'); ?></p>
                        <p class="text-gray-600 mb-2">Tanggal Pesanan: <?php echo $order['order_date']; ?></p>
                        <p class="text-gray-600 mb-2">Status Pesanan: <?php echo $order['status_pesanan']; ?></p>
                        <p class="text-gray-600 mb-2">Alamat Pembeli: <?php echo $order['address']; ?></p>
                        <p class="text-gray-600 mb-2">Alamat Toko: <?php echo $order['store_address']; ?></p>
                        <!-- Tampilkan tombol Ambil Orderan hanya jika belum diambil oleh kurir -->
                        <?php if (!orderHasBeenTaken($order['id_order'])): ?>
                            <form action="kurir.php" method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                <button type="submit" name="ambil_orderan" class="bg-blue-500 text-white py-2 px-4 rounded">Ambil Orderan</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
