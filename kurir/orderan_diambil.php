
        <!-- Konten -->
        <div class="container mx-auto py-8 px-4">
            <h1 class="text-3xl font-semibold mb-4">Daftar Orderan yang Telah Diambil</h1>
            <?php if (empty($orderan_diambil)): ?>
                <p class="text-gray-600">Anda belum mengambil orderan apapun saat ini.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($orderan_diambil as $order): ?>
                        <div class="bg-white shadow rounded-lg p-4">
                            <h2 class="text-xl font-semibold mb-2">Order ID: <?php echo $order['id_order']; ?></h2>
                            <h2 class="text-gray-600 mb-2">Username: <?php echo $order['username']; ?></h2>
                            <h2 class="text-gray-600 mb-2">Nomor Telepon: <?php echo $order['phone']; ?></h2>
                            <p class="text-gray-600 mb-2">Total Harga: Rp<?php echo number_format($order['total_price'], 0, ',', '.'); ?></p>
                            <p class="text-gray-600 mb-2">Tanggal Pesanan: <?php echo $order['order_date']; ?></p>
                            <p class="text-gray-600 mb-2">Status Pesanan: <?php echo $order['status_pesanan']; ?></p>
                            <p class="text-gray-600 mb-2">Alamat Pembeli: <?php echo $order['address']; ?></p>
                            <p class="text-gray-600 mb-2">Alamat Toko: <?php echo $order['store_address']; ?></p>
                            <!-- Form untuk mengubah status pesanan -->
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                <select name="status" class="border border-gray-300 rounded-md px-3 py-1">
                                    <option value="menunggu kurir" <?php if ($order['status_pesanan'] === 'menunggu kurir') echo 'selected'; ?>>menunggu kurir</option>
                                    <option value="sedang dikirim" <?php if ($order['status_pesanan'] === 'sedang dikirim') echo 'selected'; ?>>sedang dikirim</option>
                                    <option value="sampai ditujuan" <?php if ($order['status_pesanan'] === 'sampai ditujuan') echo 'selected'; ?>>sampai ditujuan</option>
                                    <option value="dikirim balik" <?php if ($order['status_pesanan'] === 'dikirim balik') echo 'selected'; ?>>dikirim balik</option>
                                </select>

                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Ubah Status
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

