<?php

namespace Tests\Feature\Soap;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibrarySoapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji WSDL kontrak dapat diakses dan strukturnya valid.
     */
    public function test_wsdl_endpoint_returns_valid_xml_contract(): void
    {
        $response = $this->get('/soap/sipus.wsdl');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/xml', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertStringContainsString('CheckClearanceStatus', $content);
        $this->assertStringContainsString('CheckClearanceStatusRequest', $content);
        $this->assertStringContainsString('CheckClearanceStatusResponse', $content);
        $this->assertStringContainsString('LibraryPortType', $content);
        $this->assertStringContainsString('LibraryBinding', $content);
        $this->assertStringContainsString('LibraryService', $content);
    }

    /**
     * Uji SOAP clearance check untuk mahasiswa tanpa pinjaman aktif (is_cleared = true).
     */
    public function test_soap_clearance_check_returns_true_when_no_active_loans(): void
    {
        $soapRequest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soapenv:Header/>
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:nim_peminjam>12S24012</sip:nim_peminjam>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('<tns:active_loans>0</tns:active_loans>', $content);
        $this->assertStringContainsString('<tns:is_cleared>true</tns:is_cleared>', $content);
    }

    /**
     * Uji SOAP clearance check untuk mahasiswa yang masih memiliki pinjaman aktif (is_cleared = false).
     */
    public function test_soap_clearance_check_returns_false_when_has_active_loans(): void
    {
        $kategori = Kategori::create([
            'kode_kategori' => 'TIK',
            'nama_kategori' => 'Teknologi Informasi',
        ]);

        $buku = Buku::create([
            'isbn' => '978-602-0001-01-1',
            'judul' => 'Pemrograman Web Modern',
            'penulis' => 'Penulis Del',
            'penerbit' => 'Del Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $kategori->id,
            'stok' => 5,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PJ-20261109-TEST1',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24012',
            'nama_peminjam' => 'Mahasiswa Del',
            'tanggal_pinjam' => '2026-11-01',
            'tanggal_jatuh_tempo' => '2026-11-08',
            'status' => 'dipinjam',
        ]);

        $soapRequest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soapenv:Header/>
    <soapenv:Body>
        <sip:CheckClearanceStatus>
            <sip:nim_peminjam>12S24012</sip:nim_peminjam>
        </sip:CheckClearanceStatus>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('<tns:active_loans>1</tns:active_loans>', $content);
        $this->assertStringContainsString('<tns:is_cleared>false</tns:is_cleared>', $content);
    }

    /**
     * Uji SOAP fault saat request XML malformed / tidak valid.
     */
    public function test_soap_fault_on_malformed_xml(): void
    {
        $malformedXml = '<soapenv:Envelope><soapenv:Body><unclosedTag></soapenv:Body>';

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $malformedXml
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('soapenv:Fault', $response->getContent());
        $this->assertStringContainsString('XML tidak valid atau malformed', $response->getContent());
    }

    /**
     * Uji SOAP fault saat elemen nim_peminjam tidak ditemukan.
     */
    public function test_soap_fault_on_missing_nim_element(): void
    {
        $soapRequest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:other_element>Value</sip:other_element>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('soapenv:Fault', $response->getContent());
        $this->assertStringContainsString('Elemen nim_peminjam tidak ditemukan', $response->getContent());
    }

    /**
     * Uji SOAP fault saat elemen nim_peminjam kosong.
     */
    public function test_soap_fault_on_empty_nim_element(): void
    {
        $soapRequest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soapenv:Body>
        <sip:CheckClearanceStatusRequest>
            <sip:nim_peminjam>   </sip:nim_peminjam>
        </sip:CheckClearanceStatusRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('soapenv:Fault', $response->getContent());
        $this->assertStringContainsString('NIM peminjam tidak boleh kosong', $response->getContent());
    }

    /**
     * Uji SOAP fault saat operasi tidak dikenali.
     */
    public function test_soap_fault_on_unknown_operation(): void
    {
        $soapRequest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sip="http://sipus.del.ac.id/soap">
    <soapenv:Body>
        <sip:UnknownOperation>
            <sip:nim_peminjam>12S24012</sip:nim_peminjam>
        </sip:UnknownOperation>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = $this->call(
            'POST',
            '/soap/sipus',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('soapenv:Fault', $response->getContent());
        $this->assertStringContainsString("Operasi 'UnknownOperation' tidak dikenali", $response->getContent());
    }
}
