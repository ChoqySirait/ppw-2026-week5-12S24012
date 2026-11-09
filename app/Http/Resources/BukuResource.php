<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
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
                'href' => url("/api/v1/bukus/{$this->id}"),
                'method' => 'GET',
            ],
            'referensi_eksternal' => [
                'href' => url("/api/v1/bukus/{$this->id}/referensi-eksternal"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS dinamis: hanya munculkan tautan pinjam jika stok > 0
        if ($this->stok > 0) {
            $links['pinjam_buku'] = [
                'href' => url('/api/v1/peminjamans'),
                'method' => 'POST',
                'description' => 'Meminjam buku ini secara atomik',
            ];
        }

        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'penerbit' => $this->penerbit,
            'tahun_terbit' => (int) $this->tahun_terbit,
            'stok' => (int) $this->stok,
            'sinopsis' => $this->sinopsis,
            'kategori_id' => $this->kategori_id,
            'kategori' => $this->whenLoaded('kategori', function () {
                return [
                    'id' => $this->kategori->id,
                    'kode_kategori' => $this->kategori->kode_kategori,
                    'nama_kategori' => $this->kategori->nama_kategori,
                ];
            }),
            'peminjamans_count' => $this->peminjamans_count ?? $this->whenCounted('peminjamans'),
            'riwayat_peminjaman' => $this->whenLoaded('peminjamans', function () {
                return $this->peminjamans->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'kode_pinjam' => $p->kode_pinjam,
                        'tanggal_pinjam' => optional($p->tanggal_pinjam)->toDateString(),
                        'tanggal_jatuh_tempo' => optional($p->tanggal_jatuh_tempo)->toDateString(),
                        'tanggal_kembali' => optional($p->tanggal_kembali)->toDateString(),
                        'status' => $p->status,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            '_links' => $links,
        ];
    }
}
