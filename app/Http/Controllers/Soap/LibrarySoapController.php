<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibrarySoapController extends Controller
{
    /**
     * Menyediakan dokumen kontrak WSDL untuk layanan SOAP SIPUS-Del.
     */
    public function wsdl(Request $request): Response
    {
        $endpointUrl = url('/soap/sipus');

        $wsdl = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="LibraryService"
    targetNamespace="http://sipus.del.ac.id/soap"
    xmlns:tns="http://sipus.del.ac.id/soap"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
    xmlns="http://schemas.xmlsoap.org/wsdl/">

    <types>
        <xsd:schema targetNamespace="http://sipus.del.ac.id/soap" elementFormDefault="qualified">
            <xsd:element name="CheckClearanceStatusRequest">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="nim_peminjam" type="xsd:string" />
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="CheckClearanceStatusResponse">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="active_loans" type="xsd:int" />
                        <xsd:element name="is_cleared" type="xsd:boolean" />
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
        </xsd:schema>
    </types>

    <message name="CheckClearanceStatusInput">
        <part name="parameters" element="tns:CheckClearanceStatusRequest" />
    </message>

    <message name="CheckClearanceStatusOutput">
        <part name="parameters" element="tns:CheckClearanceStatusResponse" />
    </message>

    <portType name="LibraryPortType">
        <operation name="CheckClearanceStatus">
            <documentation>Memeriksa status bebas pustaka mahasiswa berdasarkan NIM</documentation>
            <input message="tns:CheckClearanceStatusInput" />
            <output message="tns:CheckClearanceStatusOutput" />
        </operation>
    </portType>

    <binding name="LibraryBinding" type="tns:LibraryPortType">
        <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http" />
        <operation name="CheckClearanceStatus">
            <soap:operation soapAction="http://sipus.del.ac.id/soap/CheckClearanceStatus" />
            <input>
                <soap:body use="literal" />
            </input>
            <output>
                <soap:body use="literal" />
            </output>
        </operation>
    </binding>

    <service name="LibraryService">
        <documentation>Layanan SOAP Perpustakaan SIPUS-Del</documentation>
        <port name="LibraryPort" binding="tns:LibraryBinding">
            <soap:address location="{$endpointUrl}" />
        </port>
    </service>
</definitions>
XML;

        return response($wsdl, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Memproses request SOAP untuk memeriksa status bebas pustaka.
     */
    public function handle(Request $request): Response
    {
        $rawXml = $request->getContent();

        if (empty(trim($rawXml))) {
            return $this->soapFault('Client', 'Request XML kosong.', 400);
        }

        // XXE-safe parsing using LIBXML_NONET
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;

        if (! @$dom->loadXML($rawXml, LIBXML_NONET)) {
            libxml_clear_errors();

            return $this->soapFault('Client', 'XML tidak valid atau malformed.', 400);
        }

        libxml_clear_errors();

        // Validasi Envelope dan Body
        $xpath = new DOMXPath($dom);
        $envelope = $xpath->query('//*[local-name()="Envelope"]');
        $body = $xpath->query('//*[local-name()="Envelope"]/*[local-name()="Body"]');

        if ($envelope->length === 0 || $body->length === 0) {
            return $this->soapFault('Client', 'Struktur SOAP Envelope tidak valid.', 400);
        }

        // Cari operasi di dalam Body
        $operationNodes = $xpath->query('//*[local-name()="Envelope"]/*[local-name()="Body"]/*');
        if ($operationNodes->length === 0) {
            return $this->soapFault('Client', 'Elemen Body SOAP kosong.', 400);
        }

        $operationElement = $operationNodes->item(0);
        $operationName = $operationElement->localName;

        if (! in_array($operationName, ['CheckClearanceStatus', 'CheckClearanceStatusRequest'])) {
            return $this->soapFault('Client', "Operasi '{$operationName}' tidak dikenali.", 400);
        }

        // Cari elemen nim_peminjam
        $nimNodes = $xpath->query('.//*[local-name()="nim_peminjam"]', $operationElement);
        if ($nimNodes->length === 0) {
            return $this->soapFault('Client', 'Elemen nim_peminjam tidak ditemukan.', 400);
        }

        $nim = trim($nimNodes->item(0)->textContent);
        if ($nim === '') {
            return $this->soapFault('Client', 'NIM peminjam tidak boleh kosong.', 400);
        }

        // Hitung peminjaman aktif dengan status 'dipinjam'
        $activeLoans = Peminjaman::where('nim_peminjam', $nim)
            ->where('status', 'dipinjam')
            ->count();

        $isCleared = ($activeLoans === 0);
        $isClearedStr = $isCleared ? 'true' : 'false';

        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://sipus.del.ac.id/soap">
    <soapenv:Body>
        <tns:CheckClearanceStatusResponse>
            <tns:active_loans>{$activeLoans}</tns:active_loans>
            <tns:is_cleared>{$isClearedStr}</tns:is_cleared>
        </tns:CheckClearanceStatusResponse>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return response($responseXml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Membentuk XML respons SOAP Fault.
     */
    protected function soapFault(string $faultCode, string $faultString, int $status = 400): Response
    {
        $safeFaultString = htmlspecialchars($faultString, ENT_XML1, 'UTF-8');
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <soapenv:Fault>
            <faultcode>soapenv:{$faultCode}</faultcode>
            <faultstring>{$safeFaultString}</faultstring>
        </soapenv:Fault>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return response($xml, $status, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
