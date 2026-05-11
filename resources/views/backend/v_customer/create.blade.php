@extends('backend.v_layouts.app')
@section('content')

    <!-- contentAwal -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $judul }}</h5>

                    <form action="{{ route('backend.customer.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama') }}" placeholder="Masukkan Nama">
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="Masukkan Email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan Password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>No. HP</label>
                            <input type="text" name="hp" class="form-control @error('hp') is-invalid @enderror"
                                value="{{ old('hp') }}" placeholder="Masukkan No. HP">
                            @error('hp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror"
                                rows="3" placeholder="Masukkan Alamat">{{ old('alamat') }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Foto</label>
                            <img class="foto-preview" style="max-width: 200px; display: block; margin-bottom: 10px;">
                            <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror"
                                onchange="previewFoto()">
                            @error('foto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- contentAkhir -->

@endsection