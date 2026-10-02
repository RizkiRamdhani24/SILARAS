<h1>Daftar Anggota</h1>
<form method="POST" action="/register">
    @csrf
    <input name="nama_anggota" value="{{ old('nama_anggota') }}" placeholder="Nama">
    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
    <input name="no_telp" value="{{ old('no_telp') }}" placeholder="No. telp">
    <input type="password" name="password" placeholder="Password">
    <input type="password" name="password_confirmation" placeholder="Ulangi password">
    <button type="submit">Daftar</button>
    @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
</form>