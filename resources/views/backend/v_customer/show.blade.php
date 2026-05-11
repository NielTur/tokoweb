@extends('backend.v_layouts.app')
@section('content')

    <!-- contentAwal -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $judul }}</h5>

                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Nama</th>
                            <td>{{ $show->user->nama }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $show->user->email }}</td>
                        </tr>
                        <tr>
                            <th>No. HP</th>
                            <td>{{ $show->user->hp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td>{{ $show->alamat ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if ($show->user->status == 1)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Foto</th>
                            <td>
                                @if ($show->user->foto)
                                    <img src="{{ asset('storage/img-customer/' . $show->user->foto) }}"
                                        width="100" alt="foto customer">
                                @else
                                    <span class="text-muted">Tidak ada foto</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Google ID</th>
                            <td>{{ $show->google_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Dibuat</th>
                            <td>{{ $show->created_at }}</td>
                        </tr>
                    </table>

                    <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('backend.customer.edit', $show->id) }}" class="btn btn-cyan">
                        <i class="far fa-edit"></i> Ubah
                    </a>

                </div>
            </div>
        </div>
    </div>
    <!-- contentAkhir -->

@endsection