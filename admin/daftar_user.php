<!-- daftar_user.php -->
<div>
    <h3 class="text-xl font-semibold mb-2">Daftar User</h3>
    
    <div class="w-full  mx-auto flex justify-center">
    <!-- Input pencarian -->
    <input type="text" id="searchInput" onkeyup="searchUsers()" placeholder="Cari nama pengguna..." class="border-2 w-10/12 bg-transparent rounded-lg p-2 mb-4">
</div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ">
        <?php foreach ($users as $user): ?>
        <div class="border-2 p-4 user-item bg-white rounded-lg">
            <p><strong>Full Name:</strong> <?php echo $user['fullname']; ?></p>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
            <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
            <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
            <p><strong>Address:</strong> <?php echo $user['address']; ?></p>
            <!-- Format saldo menjadi rupiah -->
            <p><strong>Saldo:</strong> <?php echo 'Rp ' . number_format($user['saldo'], 0, ',', '.'); ?></p>
            <!-- Add more user details as needed -->
            
            <form method="post" action="admin.php" class="inline-block">
                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                <button type="submit" name="delete_user" class="bg-red-500 text-white rounded-lg py-2 px-4 mt-2 mr-2">Delete User</button>
            </form>
    
            <a href="admin.php?action=edit_user&username=<?php echo $user['username']; ?>" class="bg-blue-500 rounded-lg text-white py-2 px-4 mt-2 inline-block">Edit User</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function searchUsers() {
        // Mendapatkan nilai input pencarian
        var input, filter, users, user, username;
        input = document.getElementById('searchInput');
        filter = input.value.toUpperCase();
        users = document.getElementsByClassName('user-item');
        
        // Melakukan iterasi untuk setiap pengguna dan menyembunyikan yang tidak cocok dengan kriteria pencarian
        for (var i = 0; i < users.length; i++) {
            user = users[i];
            username = user.getElementsByTagName('p')[3]; // Mengambil elemen yang berisi username
            if (username.innerHTML.toUpperCase().indexOf(filter) > -1) {
                user.style.display = "";
            } else {
                user.style.display = "none";
            }
        }
    }
</script>
