<div class="w-full border bg-white border-black mx-auto flex justify-center">
    <div class="w-full  p-4">
        <h3 class="text-xl font-semibold mb-2">Tambah User</h3>

        <form method="post" action="admin.php" class="flex flex-col">
            <label for="new_fullname" class="mb-2">Fullname</label>
            <input type="text" name="new_fullname" required class="border-2 rounded-lg p-2 mb-4">

            <label for="new_phone" class="mb-2">Phone</label>
            <input type="text" name="new_phone" required class="border-2 rounded-lg p-2 mb-4">

            <label for="new_email" class="mb-2">Email</label>
            <input type="text" name="new_email" required class="border-2 rounded-lg p-2 mb-4">

            <label for="new_username" class="mb-2">Username</label>
            <input type="text" name="new_username" required class="border-2 rounded-lg p-2 mb-4">

            <label for="new_password" class="mb-2">Password</label>
            <input type="password" name="new_password" required class="border-2 rounded-lg p-2 mb-4">

            <label for="new_role" class="mb-2">Role</label>
            <select name="new_role" required class="border-2 rounded-lg p-2 mb-4">
                <option value="">Pilih Role</option>
                <option value="admin">Admin</option>
                <option value="pengguna">Pengguna</option>
                <option value="kurir">Kurir</option>
                <!-- Tambahkan opsi role lain sesuai kebutuhan -->
            </select>

            <button type="submit" name="add_user" class="bg-[#FF8787] text-white py-2 rounded-lg px-4 self-start">Tambah User</button>
        </form>
    </div>
</div>
