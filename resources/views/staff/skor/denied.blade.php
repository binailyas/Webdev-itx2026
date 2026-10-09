@extends('layouts.staff')
@section('title', 'Di luar kelas asuhan')
@section('heading', 'Skor siswa')

@section('content')
<div class="card"><x-empty icon="lock" title="Siswa ini bukan bagian dari kelas asuhanmu" text="Hubungi guru BK."><x-btn variant="outline" :href="sroute('skor.index')">Kembali</x-btn></x-empty></div>
@endsection
