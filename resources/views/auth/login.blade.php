<h1>Login SILARAS</h1>
<form method="POST" action="/login">
    @csrf
    <select name="role">
        <option value="anggota">Anggota</option>
        <option value="petugas">Petugas</option>
    </select>
    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
    <input type="password" name="password" placeholder="Password">
    <button type="submit">Masuk</button>
    @error('email') <p>{{ $message }}</p> @enderror
</form>
<a href="/register">Daftar sebagai anggota</a>