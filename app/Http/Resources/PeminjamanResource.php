<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    /**
     * Transform the resource into an array with dynamic HATEOAS links.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $links = [
            'self' => [
                'href' => url("/api/v1/peminjamans/{$this->id}"),
                'method' => 'GET',
            ],
            'buku' => [
                'href' => url("/api/v1/bukus/{$this->buku_id}"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS dinamis: hanya tampilkan aksi kembalikan jika status masih 'dipinjam'
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = [
                'href' => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH',
                'description' => 'Mengembalikan buku dan memulihkan kuota stok secara atomik',
            ];
        }

        return [
            'id' => $this->id,
            'kode_pinjam' => $this->kode_pinjam,
            'buku_id' => $this->buku_id,
            'buku' => $this->whenLoaded('buku', function () {
                return [
                    'id' => $this->buku->id,
                    'isbn' => $this->buku->isbn,
                    'judul' => $this->buku->judul,
                    'penulis' => $this->buku->penulis,
                    'stok' => (int) $this->buku->stok,
                    'kategori' => $this->buku->kategori ? [
                        'id' => $this->buku->kategori->id,
                        'nama_kategori' => $this->buku->kategori->nama_kategori,
                    ] : null,
                ];
            }),
            'nim_peminjam' => $this->nim_peminjam,
            'nama_peminjam' => $this->nama_peminjam,
            'tanggal_pinjam' => optional($this->tanggal_pinjam)->toDateString(),
            'tanggal_jatuh_tempo' => optional($this->tanggal_jatuh_tempo)->toDateString(),
            'tanggal_kembali' => optional($this->tanggal_kembali)->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            '_links' => $links,
        ];
    }
}
